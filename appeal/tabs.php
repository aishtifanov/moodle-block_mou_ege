<?php  // $Id: tabs.php,v 1.1 2009/03/13 15:19:15 Shtifanov Exp $
/// This file to be included so we can assume config.php has already been included.
/// We also assume that $user, $course, $currenttab have been set


    if (empty($currenttab) or empty($user)) {
        error('You cannot call this script in that way');
    }

    $toprow = array();
    $toprow[] = new tabobject('profile', "profile.php?mode=4&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid&amp;uid={$user->id}",
                get_string('profilepupil', 'block_mou_ege'));

    $toprow[] = new tabobject('markspupil', "marks.php?mode=4&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid&amp;uid={$user->id}",
                get_string('markspupil', 'block_mou_ege'));

    $toprow[] = new tabobject('appeal', "appeal.php?mode=4&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid&amp;uid={$user->id}",
                get_string('appeal', 'block_mou_ege'));

    $tabs = array($toprow);
    print_tabs($tabs, $currenttab, NULL, NULL);

?>
