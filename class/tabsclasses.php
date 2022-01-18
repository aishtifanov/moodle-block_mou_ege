<?php  // $Id: tabsclasses.php,v 1.7 2009/04/21 11:23:36 Shtifanov Exp $
/// This file to be included so we can assume config.php has already been included.
/// We also assume that $user, $course, $currenttab have been set


    if (empty($currenttab)) {
        error('You cannot call this script in that way');
    }

    $toprow = array();
    $toprow[] = new tabobject('listclasses', $CFG->wwwroot."/blocks/mou_ege/class/classlist.php?rid=$rid&amp;yid=$yid&amp;sid=$sid",
                get_string('listclasses', 'block_mou_ege'));

    $toprow[] = new tabobject('enrolschool', $CFG->wwwroot."/blocks/mou_ege/class/enrolschool.php?rid=$rid&amp;yid=$yid&amp;sid=$sid",
                get_string('enrolcourses', 'block_mou_ege'));

    $toprow[] = new tabobject('gia', $CFG->wwwroot."/blocks/mou_ege/class/classdisc.php?sid=$sid&amp;rid=$rid&amp;yid=$yid",
    	            get_string('gia', 'block_mou_ege'));

    $toprow[] = new tabobject('importclass', $CFG->wwwroot."/blocks/mou_ege/class/importclasses.php?sid=$sid&amp;rid=$rid&amp;yid=$yid",
    	            get_string('importclass', 'block_mou_ege'));



    $tabs = array($toprow);

    print_tabs($tabs, $currenttab, NULL, NULL);

?>
