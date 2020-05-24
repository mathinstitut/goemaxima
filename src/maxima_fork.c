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
#define N_SLOT 16
#define RNOFILE 256
#define FILEPATH_LEN (PATH_MAX + 1)
char filepath[FILEPATH_LEN];

// inits a maxima process for web service:
// changes gid/uid to maxima-{slot}
// redirects input/output, creates temporary subdirectories
char *fork_new_process() {
	fflush(stdout);

	// send an S for Synchronization, so that
	// the server process doesn't accidentally write into
	// sbcl's buffer
	// the server should not write anything before it has read this
	write(STDOUT_FILENO, "S", 1);

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

		if (chdir(tempdir) == -1) {
			perror("Could not chdir to temporary directory");
			ret = NULL;
			break;
		}

		// redirect stdout to pipe
		// note: open outpipe before inpipe to avoid deadlock
		int outfd = open("outpipe", O_WRONLY);
		if (outfd == -1) {
			perror("Could not connect output pipe");
			ret = NULL;
			break;
		}
		if (dup2(outfd, STDOUT_FILENO) == -1) {
			perror("Could not copy output file descriptor");
			ret = NULL;
			break;
		}

		// redirect stdin from pipe
		int infd = open("inpipe", O_RDONLY);
		if (infd == -1) {
			perror("Could not create input pipe");
			ret = NULL;
			break;
		}
		if (dup2(infd, STDIN_FILENO) == -1) {
			perror("Could not copy input file descriptor");
			ret = NULL;
			break;
		}

		// replace stdin with a new stream, for good measure
		FILE *new_stdin = fdopen(STDIN_FILENO, "r");
		if (!new_stdin) {
			perror("Could not create stream from stdin");
			ret = NULL;
			break;
		}
		stdin = new_stdin;

		// everything execpt std{in,out,err} is closed
		// note: this is a function from libbsd
		closefrom(3);

		// get uid/gid from username
		if (slot <= 0 || slot > N_SLOT) {
			dprintf(STDERR_FILENO, "Invalid slot number: %d\n", slot);
			ret = NULL;
			break;
		}
		char username[16];
		int len = snprintf(username, 15, "maxima-%d", slot);
		if (len < 0 || len > 15) {
			dprintf(STDERR_FILENO, "Internal error getting user name\n");
			ret = NULL;
			break;
		}
		struct passwd *userinfo = getpwnam(username);
		if (!userinfo) {
			dprintf(STDERR_FILENO, "Could not read user information for %s: %s\n",
					username, strerror(errno));
			ret = NULL;
			break;
		}
		uid_t uid = userinfo->pw_uid;
		gid_t gid = userinfo->pw_gid;
		if (uid == 0 || gid == 0) {
			dprintf(STDERR_FILENO, "Refusing to setuid/gid to root\n");
			ret = NULL;
			break;
		}

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

		// create temporary folders and files
		if (mkdir("output", 0770) == -1) {
			perror("Could not create output directory");
			ret = NULL;
			break;
		}
		if (mkdir("work", 0770) == -1) {
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
