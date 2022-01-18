<?php  // $Id: tabsmark.php,v 1.6 2009/06/19 12:01:02 Shtifanov Exp $


    $toprow = array();
    $toprow[] = new tabobject('resultclass', $CFG->wwwroot."/blocks/mou_ege/pupils/markspupil.php?level=class&amp;rid=$rid&amp;sid=$sid&amp;gid=$gid&amp;yid=$yid",
                get_string('resultclass', 'block_mou_ege'));

    $toprow[] = new tabobject('resultschool', $CFG->wwwroot."/blocks/mou_ege/pupils/markspupil.php?level=school&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid",
                get_string('resultschool', 'block_mou_ege'));

	if ($admin_is || $region_operator_is) {
	    $toprow[] = new tabobject('resultrayon', $CFG->wwwroot."/blocks/mou_ege/pupils/markspupil.php?level=rayon&amp;rid=$rid&amp;yid=$yid",
 	               get_string('resultrayon', 'block_mou_ege'));

	    $toprow[] = new tabobject('resultregion', $CFG->wwwroot."/blocks/mou_ege/pupils/markspupil.php?level=region&amp;yid=$yid",
 	               get_string('resultregion', 'block_mou_ege'));

	    $toprow[] = new tabobject('importmarks', $CFG->wwwroot."/blocks/mou_ege/pupils/importmarks.php",
	                get_string('importmarks', 'block_mou_ege'));

	    $toprow[] = new tabobject('pupilnomarks', $CFG->wwwroot."/blocks/mou_ege/pupils/pupilnomarks.php",
	                get_string('pupilnomarks', 'block_mou_ege'));

	}

	if ($admin_is || ($region_operator_is && ($USER->id == 573 || $USER->id == 7521))) {
	    $toprow[] = new tabobject('quickeditmarks', $CFG->wwwroot."/blocks/mou_ege/pupils/editmarks.php?rid=$rid&amp;sid=$sid&amp;gid=$gid&amp;did=$did",
	                get_string('quickeditmarks', 'block_mou_ege'));

	}
    $tabs = array($toprow);
    print_tabs($tabs, $currenttab, NULL, NULL);

?>
