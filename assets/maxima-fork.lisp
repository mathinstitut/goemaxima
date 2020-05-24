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
  (finish-output)
  (let ((tmp-dir (fork-new-process)))
    (when (not tmp-dir)
      (sb-ext:exit :code 1))
    (maxima::set-tmp-dir-vars tmp-dir))
  t)

