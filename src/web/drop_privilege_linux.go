package main

// This file drops the privileges of a single thread and executes functions in it.
// The effective uid is set to that of the maxima user we want to interact with.
// The real uid is set to goemaxima-nobody, which ensures that kill -1 does not
// kill the maxima mother process. If the real uid was the maxima user, an evil
// maxima process might ptrace our own process and use that to escalate privileges,
// or send signals or interact in another way.

import (
	"fmt"
	"os/user"
	"runtime"
	"strconv"

	"golang.org/x/sys/unix"
)

const goemaxima_nobody string = "goemaxima-nobody"

type PrivilegeDropper struct {
	server_uid       uint16
	server_gid       uint16
	nobody_uid       uint16
	nobody_gid       uint16
	execution_getter <-chan ExecutionInfo
}

type ExecutionInfo struct {
	Uid uint16
	Gid uint16
	F   func(error)
}

func (dropper *PrivilegeDropper) thread_drop_euid(uid uint16) error {
	// These functions use the raw syscall interface since unix/posix usually regards setresuid to set the UIDs of the whole process
	// (usually implemented through some signal handlers) but we absolutely don't want that.
	_, _, errno := unix.Syscall(unix.SYS_SETRESUID, uintptr(dropper.nobody_uid), uintptr(uid), uintptr(dropper.server_uid))
	if errno != 0 {
		return errno
	}
	return nil
}

func (dropper *PrivilegeDropper) thread_restore_euid() {
	// since we still have our saved-set-uid, we can restore our uids
	_, _, errno := unix.Syscall(unix.SYS_SETRESUID, uintptr(dropper.server_uid), uintptr(dropper.server_uid), uintptr(dropper.server_uid))
	if errno != 0 {
		panic(errno)
	}
}

func (dropper *PrivilegeDropper) thread_drop_egid(gid uint16) error {
	_, _, errno := unix.Syscall(unix.SYS_SETRESGID, uintptr(dropper.nobody_gid), uintptr(gid), uintptr(dropper.server_gid))
	if errno != 0 {
		return errno
	}
	return nil
}

func (dropper *PrivilegeDropper) thread_restore_egid() {
	_, _, errno := unix.Syscall(unix.SYS_SETRESGID, uintptr(dropper.server_gid), uintptr(dropper.server_gid), uintptr(dropper.server_gid))
	if errno != 0 {
		panic(errno)
	}
}

func (dropper *PrivilegeDropper) run_as_user(uid, gid uint16, f func(error)) {
	if err := dropper.thread_drop_egid(gid); err != nil {
		f(err)
		return
	}
	defer dropper.thread_restore_egid()

	if err := dropper.thread_drop_euid(uid); err != nil {
		f(err)
		return
	}
	defer dropper.thread_restore_euid()

	f(nil)
}

func (dropper *PrivilegeDropper) run() {
	// since goroutines may switch between machine threads at any time,
	// we lock this routine to always stay on the same machine thread
	runtime.LockOSThread()
	defer runtime.UnlockOSThread()
	for ex := range dropper.execution_getter {
		dropper.run_as_user(ex.Uid, ex.Gid, ex.F)
	}
}

func StartDropper(execution_channel chan ExecutionInfo, process_isolation bool) error {
	server, err := user.Current()
	if err != nil {
		return err
	}

	server_uid, err := strconv.Atoi(server.Uid)
	if err != nil {
		return err
	}

	server_gid, err := strconv.Atoi(server.Gid)
	if err != nil {
		return err
	}

	var nobody_uid, nobody_gid int

	if process_isolation {
		nobody, err := user.Lookup(goemaxima_nobody)
		if err != nil {
			return fmt.Errorf("no %s user found, please make sure it exists and is not the user used by the server; error: %s", goemaxima_nobody, err)
		}

		nobody_uid, err = strconv.Atoi(nobody.Uid)
		if err != nil {
			return err
		}

		nobody_gid, err = strconv.Atoi(nobody.Gid)
		if err != nil {
			return err
		}

		if nobody_uid == server_uid {
			return fmt.Errorf("server uid %d is same as %s uid %d. Please make sure that the server does not run as %s",
				server_uid, goemaxima_nobody, nobody_uid, goemaxima_nobody)
		}
	} else {
		nobody_uid = server_uid
		nobody_gid = server_gid
	}

	dropper := PrivilegeDropper{
		server_uid:       uint16(server_uid),
		server_gid:       uint16(server_gid),
		nobody_uid:       uint16(nobody_uid),
		nobody_gid:       uint16(nobody_gid),
		execution_getter: execution_channel,
	}

	go dropper.run()

	return nil
}
