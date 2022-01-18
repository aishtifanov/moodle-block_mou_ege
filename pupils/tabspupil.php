<?php  // $Id: tabspupil.php,v 1.9 2010/03/31 06:27:05 Shtifanov Exp $
/// This file to be included so we can assume config.php has already been included.
/// We also assume that $user, $course, $currenttab have been set


    if (empty($currenttab) or empty($user)) {
        error('You cannot call this script in that way');
    }

    $toprow = array();
    $toprow[] = new tabobject('profile', $CFG->wwwroot."/blocks/mou_ege/pupils/pupil.php?mode=4&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid&amp;uid={$user->id}",
                get_string('profilepupil', 'block_mou_ege'));

	if ($admin_is || $region_operator_is || $rayon_operator_is  || $school_operator_is) {
	    $toprow[] = new tabobject('pupilcard', $CFG->wwwroot."/blocks/mou_ege/pupils/pupilcard.php?mode=4&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid&amp;uid={$user->id}",
					get_string('editprofilepupil', 'block_mou_ege'));
	}

    $tabs = array($toprow);
    print_tabs($tabs, $currenttab, NULL, NULL);

?>
