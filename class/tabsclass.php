<?php  // $Id: tabsclass.php,v 1.1 2009/04/14 07:28:05 Shtifanov Exp $
/// This file to be included so we can assume config.php has already been included.
/// We also assume that $user, $course, $currenttab have been set


    if (empty($currenttab)) {
        error('You cannot call this script in that way');
    }

    $toprow = array();
    $toprow[] = new tabobject('listclass', $CFG->wwwroot."/blocks/mou_ege/class/classlist.php?rid=$rid&amp;yid=$yid&amp;sid=$sid",
                get_string('listclass', 'block_mou_ege'));

    $toprow[] = new tabobject('enrolclass', $CFG->wwwroot."/blocks/mou_ege/class/enrolclass.php?rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;gid=$gid",
                get_string('enrolclass', 'block_mou_ege'));


    $tabs = array($toprow);

    print_tabs($tabs, $currenttab, NULL, NULL);

?>
