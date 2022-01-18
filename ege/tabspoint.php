<?php  // $Id: tabspoint.php,v 1.6 2010/04/06 10:18:13 Shtifanov Exp $
/// This file to be included so we can assume config.php has already been included.
/// We also assume that $user, $course, $currenttab have been set


    if (empty($currenttab)) {
        error('You cannot call this script in that way');
    }

    $toprow = array();
	if ($admin_is || $region_operator_is || $rayon_operator_is) 	{
	    $toprow[] = new tabobject('formpoints', $CFG->wwwroot."/blocks/mou_ege/ege/formpoints.php?rid=$rid&amp;yid=$yid",
	                get_string('formpoints', 'block_mou_ege'));
	    $toprow[] = new tabobject('points', $CFG->wwwroot."/blocks/mou_ege/ege/points.php?rid=$rid&amp;yid=$yid",
					get_string('numberpoints','block_mou_ege'));
	    $toprow[] = new tabobject('setpoints', $CFG->wwwroot."/blocks/mou_ege/ege/setpoints.php?did=$did&amp;rid=$rid&amp;yid=$yid",
 	               get_string('setpoints', 'block_mou_ege'));
	    $toprow[] = new tabobject('reportpoints', $CFG->wwwroot."/blocks/mou_ege/ege/reportpoints.php?rid=$rid&amp;yid=$yid&amp;did=$did",
	                get_string('reportpoints', 'block_mou_ege'));
	    $toprow[] = new tabobject('pupilppe', $CFG->wwwroot."/blocks/mou_ege/ege/pupilppe.php?rid=$rid&amp;yid=$yid&amp;did=$did",
	                get_string('pupilppe', 'block_mou_ege'));

 	}
	if ($admin_is || $region_operator_is) 	{
	    $toprow[] = new tabobject('listcodeppe', $CFG->wwwroot."/blocks/mou_ege/ege/listcodeppe.php?yid=$yid",
	                get_string('listcodeppe', 'block_mou_ege'));
	}

    $tabs = array($toprow);
    print_tabs($tabs, $currenttab, NULL, NULL);

?>
