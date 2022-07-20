(cl:in-package "MAXIMA")

(defun set-tmp-dir-vars (tmp-dir)
  (defparameter |$image_dir| (concatenate 'string tmp-dir "/output/"))
  (defparameter |$MAXIMA_TEMPDIR| (concatenate 'string tmp-dir "/work/"))
  nil)
(defparameter |$url_base| "!ploturl!")

(cl:defpackage "MAXIMA-FORK"
	       (:use "CL" "SB-ALIEN")
	       (:export "FORKING-LOOP"))
(cl:in-package "MAXIMA-FORK")

;;; load shared library
(load-shared-object "libmaximafork.so")

;;; define c function
(declaim (inline fork-new-process))
(define-alien-routine fork-new-process c-string)

;;; forking loop
(defun forking-loop ()
  #+sb-thread (sb-thread::with-system-mutex (sb-thread::*make-thread-lock*)
		(sb-impl::finalizer-thread-stop))
  (finish-output)
  ;;; in order to prevent TIOCSTI related shenanigans, close the tty
  ;;; (the native C function calls closefrom(3) so the tty fd will be closed as well)
  (close *terminal-io*)
  (setf *terminal-io* (make-two-way-stream sb-sys:*stdin* sb-sys:*stdout*))
  (let ((tmp-dir (fork-new-process)))
    #+sb-thread (sb-impl::finalizer-thread-start)
    (when (not tmp-dir)
      (sb-ext:exit :code 1))
    (maxima::set-tmp-dir-vars tmp-dir))
  t)

