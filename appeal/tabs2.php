<?php  // $Id: tabs2.php,v 1.3 2009/06/08 11:53:18 Shtifanov Exp $


    $toprow = array();
    $toprow[] = new tabobject('appealschool', "checkappeal.php?level=school&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid",
                get_string('appealschool', 'block_mou_ege'));

    $toprow[] = new tabobject('appealrayon', "checkappeal.php?level=rayon&amp;rid=$rid&amp;yid=$yid",
	               get_string('appealrayon', 'block_mou_ege'));

	if ($admin_is || $region_operator_is) {
	    $toprow[] = new tabobject('appealregion', "checkappeal.php?level=region&amp;yid=$yid",
 	               get_string('appealregion', 'block_mou_ege'));

	    $toprow[] = new tabobject('setappealtime', "setappealtime.php?yid=$yid",
 	               get_string('intervalappealtime', 'block_mou_ege'));

	    $toprow[] = new tabobject('importappeal', "importappeal.php?yid=$yid&amp;did=$did",
                get_string('importappeal', 'block_mou_ege'));

	}

    $tabs = array($toprow);
    print_tabs($tabs, $currenttab, NULL, NULL);


?>
