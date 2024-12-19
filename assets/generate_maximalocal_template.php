<?php

define('MOODLE_INTERNAL', true);
class moodle_exception extends exception {};

require_once('casstring.units.class.php');

?>
file_search_maxima:append( [sconcat("${LIB}/###.{mac,mc}")] , file_search_maxima)$
file_search_lisp:append( [sconcat("${LIB}/###.{lisp}")] , file_search_lisp)$
file_search_maxima:append( [sconcat("${LOG}/###.{mac,mc}")] , file_search_maxima)$
file_search_lisp:append( [sconcat("${LOG}/###.{lisp}")] , file_search_lisp)$

STACK_SETUP(ex):=block(
    MAXIMA_VERSION_NUM_EXPECTED:"${MAXIMA_VERSION:2:4}",
    MAXIMA_PLATFORM:"server",
    maxima_tempdir:"${TMP}",
    IMAGE_DIR:"${PLOT}",
    PLOT_SIZE:[450,300],
    PLOT_TERMINAL:"svg",
    PLOT_TERM_OPT:"dynamic font \",11\" linewidth 1.2",
    DEL_CMD:"rm",
    GNUPLOT_CMD:"gnuplot",
    MAXIMA_VERSION_EXPECTED:"${MAXIMA_VERSION}",
    URL_BASE:"!ploturl!",
<?php echo stack_cas_casstring_units::maximalocal_units(); ?>
    true)$
