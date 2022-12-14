#include <stdio.h>
#include <string.h>
#include <errno.h>
#include <stdlib.h>
#include <pwd.h>
#include <signal.h>

#include <sys/resource.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <sys/wait.h>

#include <fcntl.h>
#include <bsd/unistd.h>
#include <limits.h>
#include <grp.h>
#ifndef N_SLOT
#define N_SLOT 32
#endif
#define RNOFILE 256
#define FILEPATH_LEN (PATH_MAX + 1)
char filepath[FILEPATH_LEN];


// inits a maxima process for web service:
// changes gid/uid to maxima-{slot}
// redirects input/output, creates temporary subdirectories
char *fork_new_process() {
	fflush(stdout);
	uid_t user_id[N_SLOT];
	gid_t group_id[N_SLOT];

	// cache and verify uids/gids on startup
	for (int i = 1; i <= N_SLOT; i++) {
		char username[16];
		int len = snprintf(username, 15, "maxima-%d", i);
		if (len < 0 || len > 15) {
			dprintf(STDERR_FILENO, "Internal error getting user name\n");
			return NULL;
		}
		struct passwd *userinfo = getpwnam(username);
		if (!userinfo) {
			dprintf(STDERR_FILENO, "Could not read user information for %s: %s\n",
					username, strerror(errno));
			return NULL;
		}

		uid_t uid = userinfo->pw_uid;
		gid_t gid = userinfo->pw_gid;
		if (uid == 0 || gid == 0) {
			dprintf(STDERR_FILENO, "maxima-%d is root, quitting\n", i);
			return NULL;
		}
		user_id[i - 1] = uid;
		group_id[i - 1] = gid;
	}

	// send an S for Synchronization, so that
	// the server process doesn't accidentally write into
	// sbcl's buffer
	// the server should not write anything before it has read this
	write(STDOUT_FILENO, "\x02", 1);

	// while the loop is running, the SIGCHLD handler
	// is deactivated so that children are automatically reaped
	// after that, it is again restored
	struct sigaction old, new;
	new.sa_handler = SIG_IGN;
	sigemptyset(&new.sa_mask);
	new.sa_flags = SA_NOCLDWAIT;
	char *ret = NULL;
	if (sigaction(SIGCHLD, &new, &old) == -1) {
		perror("Could not set signal error for children");
		return NULL;
	}

	// when sbcl spawns a child process through lisp, sbcl tries to close all
	// filedescriptors until RLIMIT_NOFILE
	// in docker containers, this is by standard quite high, so it takes long
	// which is remediated here by setting it lower manually
	struct rlimit nofile = { .rlim_cur = RNOFILE, .rlim_max = RNOFILE };
	if (setrlimit(RLIMIT_NOFILE, &nofile) == -1) {
		perror("Error setting rlimit_nofile");
		sigaction(SIGCHLD, &old, NULL);
		return NULL;
	}

	for (;;) {
		// can't flush enough
		fflush(stdout);
		int slot;
		
		// the slot number and temp directory is sent to the process
		// over stdin in the format "%d%s", where %s can contain anything
		// but newlines and musn't start with a number, which isn't a
		// problem for absolute paths
		if (scanf("%d", &slot) == EOF) {
			if (errno != 0) {
				perror("Error getting slot number from stdin");
				ret = NULL;
			}
			break;
		}
		char *tempdir = fgets(filepath, FILEPATH_LEN, stdin);
		if (!tempdir) {
			perror("Error getting temp path name");
			ret = NULL;
			break;
		}
		// remove the last newline, if it exists
		size_t last_char = strlen(tempdir) - 1;
		if (tempdir[last_char] == '\n') {
			tempdir[strlen(tempdir) - 1] = '\0';
		}

		// we fork the main process and use the child without execve
		// this way, startup time is improved
		pid_t pid = fork();
		if (pid == -1) {
			perror("Could not fork");
			ret = NULL;
			break;
		}
		if (pid != 0) {
			continue;
		}

		uid_t uid = user_id[slot - 1];
		gid_t gid = group_id[slot - 1];
		// note: setgid should be executed before setuid when dropping from root
		if (setgid(gid) == -1) {
			perror("Could not set gid");
			ret = NULL;
			break;
		}
	
		// remove all aux groups
		if (setgroups(0, NULL)) {
			perror("Could not remove aux groups");
			ret = NULL;
			break;
		}
	
		// after this, we should be non-root
		if (setuid(uid) == -1) {
			perror("Could not set uid");
			ret = NULL;
			break;
		}

		if (chdir(tempdir) == -1) {
			perror("Could not chdir to temporary directory");
			ret = NULL;
			break;
		}

		// redirect stdout to pipe
		// note: open outpipe before inpipe to avoid deadlock
		if (!freopen("outpipe", "a", stdout)) {
			perror("Could not connect output pipe");
			ret = NULL;
			break;
		}

		// redirect stdin from pipe
		if (!freopen("inpipe", "r", stdin)) {
			perror("Could not create input pipe");
			ret = NULL;
			break;
		}

		// everything execpt std{in,out,err} is closed
		// note: this is a function from libbsd
		closefrom(3);

		// verify valid slot number
		if (slot <= 0 || slot > N_SLOT) {
			dprintf(STDERR_FILENO, "Invalid slot number: %d\n", slot);
			ret = NULL;
			break;
		}

		// create temporary folders and files
		if (mkdir("output", 0755) == -1) {
			perror("Could not create output directory");
			ret = NULL;
			break;
		}
		if (mkdir("work", 0755) == -1) {
			perror("Could not create work directory");
			ret = NULL;
			break;
		}
		ret = tempdir;
		break;
	}
	// restore normal SIGCHLD handler
	if (sigaction(SIGCHLD, &old, NULL) == -1) {
		return NULL;
	}
	return ret;
}
