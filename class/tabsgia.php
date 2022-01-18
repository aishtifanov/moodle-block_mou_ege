<?php  // $Id: tabsgia.php,v 1.2 2009/05/14 09:20:28 Shtifanov Exp $
/// This file to be included so we can assume config.php has already been included.
/// We also assume that $user, $course, $currenttab have been set


    if (empty($currenttab)) {
        error('You cannot call this script in that way');
    }

    $toprow = array();
    $toprow[] = new tabobject('disciplines_ege', $CFG->wwwroot."/blocks/mou_ege/class/classdisc.php?sid=$sid&amp;rid=$rid&amp;yid=$yid",
    	            get_string('disciplines_ege', 'block_mou_ege'));

    $toprow[] = new tabobject('giapoints', $CFG->wwwroot."/blocks/mou_ege/class/giapoints.php?sid=$sid&amp;rid=$rid&amp;yid=$yid",
    	            get_string('giapoints', 'block_mou_ege'));

    $toprow[] = new tabobject('gia_teachers', $CFG->wwwroot."/blocks/mou_ege/class/gia_teachers.php?sid=$sid&amp;rid=$rid&amp;yid=$yid",
    	            get_string('gia_teachers', 'block_mou_ege'));

    $toprow[] = new tabobject('gia_textbook', $CFG->wwwroot."/blocks/mou_ege/class/gia_textbooks.php?sid=$sid&amp;rid=$rid&amp;yid=$yid",
    	            get_string('gia_textbook', 'block_mou_ege'));

    $toprow[] = new tabobject('import9class', $CFG->wwwroot."/blocks/mou_ege/class/import9classes.php?sid=$sid&amp;rid=$rid&amp;yid=$yid",
    	            get_string('import9class', 'block_mou_ege'));

    $tabs = array($toprow);

    print_tabs($tabs, $currenttab, NULL, NULL);

?>
