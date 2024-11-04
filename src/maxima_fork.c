#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include <signal.h>

#include <sys/resource.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <sys/wait.h>

#include <bsd/unistd.h>
#include <unistd.h>
#include <limits.h>
#include <grp.h>

// maximum number of open file descriptors
#ifndef RNOFILE
#define RNOFILE 256
#endif

// maximum number of threads that the current user may have
// note that this is not limited to the container but the global user
// so make sure to make this reasonably high
#ifndef RNPROC
#define RNPROC 4096
#endif

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
		
		// the uid/gid and temp directory is sent to the process
		// over stdin in the format "%d:%d%s", where %s can contain anything
		// but newlines and musn't start with a number, which isn't a
		// problem for absolute paths
		char *input = fgets(filepath, FILEPATH_LEN, stdin);
		if (!input) {
			perror("Error getting input");
			ret = NULL;
			break;
		}

		char *cur;
		long uid = strtol(input, &cur, 10);
		if (cur == input) {
			fprintf(stderr, "Invalid uid\n");
			ret = NULL;
			break;
		}
		if (*cur++ != ':') {
			fprintf(stderr, "Invalid input\n");
			ret = NULL;
			break;
		}
		input = cur;
		long gid = strtol(input, &cur, 10);
		if (cur == input) {
			fprintf(stderr, "Invalid gid\n");
			ret = NULL;
			break;
		}
		char *tempdir = cur;

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
			dprintf(STDOUT_FILENO, "%d\n", pid);
			continue;
		}

		// valididate uid and gid
		if (uid <= 0 || uid > 0xffff) {
			dprintf(STDERR_FILENO, "Invalid uid: %ld\n", uid);
			ret = NULL;
			break;
		}
		if (gid <= 0 || gid > 0xffff) {
			dprintf(STDERR_FILENO, "Invalid gid: %ld\n", gid);
			ret = NULL;
			break;
		}


		// note: setgid should be executed before setuid when dropping from root
		if (setgid(gid) == -1) {
			perror("Could not set gid");
			ret = NULL;
			break;
		}
	
		// after this, we should be non-root
		if (setuid(uid) == -1) {
			perror("Could not set uid");
			ret = NULL;
			break;
		}

		// start new process group
		if (setpgid(0, 0) == -1) {
			perror("Could not set pgid");
			ret = NULL;
			break;
		}

		if (chdir(tempdir) == -1) {
			perror("Could not chdir to temporary directory");
			ret = NULL;
			break;
		}

		// to prevent fork bombs from slowing down the server to a crawl,
		// limit the number of processes the user may have
		struct rlimit noproc = { .rlim_cur = RNPROC, .rlim_max = RNPROC };
		if (setrlimit(RLIMIT_NPROC, &noproc) == -1) {
			perror("Error setting rlimit_nproc");
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
