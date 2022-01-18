<?php  // $Id: tabsege.php,v 1.3 2009/02/03 08:41:18 Shtifanov Exp $
/// This file to be included so we can assume config.php has already been included.
/// We also assume that $user, $course, $currenttab have been set


    if (empty($currenttab)) {
        error('You cannot call this script in that way');
    }

    $toprow = array();
    $toprow[] = new tabobject('disciplines_ege', $CFG->wwwroot."/blocks/mou_ege/ege/disciplines_ege.php?rid=$rid&amp;yid=$yid",
    	            get_string('disciplines_ege', 'block_mou_ege'));

    $toprow[] = new tabobject('stats_ege', $CFG->wwwroot."/blocks/mou_ege/ege/stats_ege.php?rid=$rid&amp;yid=$yid",
                get_string('stats_ege_school', 'block_mou_ege'));

    if ($admin_is  || $region_operator_is)	 {	    $toprow[] = new tabobject('stats_ege_rayon', $CFG->wwwroot."/blocks/mou_ege/ege/stats_ege_rayon.php?rid=$rid&amp;yid=$yid",
                get_string('stats_ege_rayons', 'block_mou_ege'));

	    $toprow[] = new tabobject('stats_ege_region', $CFG->wwwroot."/blocks/mou_ege/ege/stats_ege_region.php",
 	               get_string('stats_ege_region', 'block_mou_ege'));
 	}


    $tabs = array($toprow);

    print_tabs($tabs, $currenttab, NULL, NULL);

?>
