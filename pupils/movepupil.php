<?php // $Id: movepupil.php,v 1.6 2010/02/16 08:50:53 Oleg Exp $

    require_once("../../../config.php");
    require_once("$CFG->libdir/gdlib.php");
    require_once('../../monitoring/lib.php');
    require_once('../lib_ege.php');

    $rid = required_param('rid', PARAM_INT);          // Rayon id
    $sid = required_param('sid', PARAM_INT);       // School id
    $yid = required_param('yid', PARAM_INT);       // Year id
    $gid = required_param('gid', PARAM_INT);          // Group id
    $uid = optional_param('uid', 0, PARAM_INT);       // User id
    $mode = optional_param('mode', 0, PARAM_INT);        // Mode: 0, 1, 2, 3, 4, 9, 99 Can(or can't) show groups
    $rid2 = optional_param('rid2', $rid, PARAM_INT);          // Rayon id
    $sid2 = optional_param('sid2', $sid, PARAM_INT);       // School id
    $gid2 = optional_param('gid2', 0, PARAM_INT);          // Group id
	$action   = optional_param('action', '');

    if (!$site = get_site()) {
        redirect("$CFG->wwwroot/$CFG->admin/index.php");
    }

    $curryearid = get_current_edu_year_id();
    if ($yid == 0)	{
    	$yid = $curryearid;
    }

	require_login();

	$admin_is = isadmin();
	$region_operator_is = ismonitoperator('region');
	$rayon_operator_is  = ismonitoperator('rayon', 0, 0, 0, true);
	$school_operator_is = ismonitoperator('school', 0, $rid, $sid);
	if (!$admin_is && !$region_operator_is && !$rayon_operator_is && !$school_operator_is) {
        error(get_string('adminaccess', 'block_monitoring'), "$CFG->wwwroot/login/index.php");
	}

	if (!$admin_is && !$region_operator_is && !$rayon_operator_is && $school_operator_is)  {
	      if ($school = get_record_sql ("SELECT id, uniqueconstcode FROM {$CFG->prefix}monit_school
	                                     WHERE rayonid=$rid AND uniqueconstcode=$sid AND yearid=$yid"))	{
	     		$sid = $school->id;
	      }
	}


	$strclasses = get_string('classes','block_mou_ege');
	$strclass = get_string('class','block_mou_ege');
	$strpupils = get_string('pupils', 'block_mou_ege');
	$strpupil = get_string('pupil', 'block_mou_ege');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= "-> <a href=\"{$CFG->wwwroot}/blocks/mou_ege/class/classlist.php?rid=$rid&amp;sid=$sid&amp;yid=$yid\">$strclasses</a>";
	$breadcrumbs .= "-> <a href=\"{$CFG->wwwroot}/blocks/mou_ege/class/classpupils.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid\">$strpupils</a>";
	$breadcrumbs .= "-> $strpupil";
    print_header("$site->shortname: $strpupils", $site->fullname, $breadcrumbs);


	$rayon = get_record('monit_rayon', 'id', $rid);

	$school = get_record('monit_school', 'id', $sid);

	$class = get_record('monit_school_class', 'id', $gid);

    $pupil = get_record('monit_school_pupil_card', 'userid', $uid);


    if ($action == 'move')	{

       	  $pupil->rayonid = $rid2;
       	  $pupil->schoolid = $sid2;
       	  $pupil->classid = $gid2;
       	  
	      if (!update_monit_record('monit_school_pupil_card', $pupil))	{
					error(get_string('errorinupdateprofilepupil','block_mou_ege'), "{$CFG->wwwroot}/blocks/mou_ege/pupils/pupil.php?mode=4&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid&amp;uid=$uid");
 		    }
 		    
 		  $rec->userid = $uid;
    	  $rec->county  = get_string('nameregion','block_mou_ege');
    	  $rec->rayon  = '';
    	  $rec->naspunkt = '';
    	  $rec->school = '';
    	  $rec->class = '';
    	  $rec->rayoninid = $rid;
    	  $rec->schoolinid = $sid;
    	  $rec->classinid  = $gid;
		  $dateout = date('Y-m-d');	    	
    	  $rec->dateout  = $dateout;	
    	 
		  if (!insert_record('monit_school_movepupil', $rec))   {
    	  	   error(get_string('errorinupdateprofilepupil','block_mou_ege'), "{$CFG->wwwroot}/blocks/mou_ege/class/classpupils.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid&amp;uid=$uid");
    	  }		

          redirect("$CFG->wwwroot/blocks/mou_ege/class/classpupils.php?rid=$rid2&amp;sid=$sid2&amp;yid=$yid&amp;gid=$gid2", get_string("changessaved"), 0);
    }


    if (!$user1 = get_record('user', 'id', $uid) ) {
        error('No such pupil in this class!', '..\index.php');
	}

   	$fullname = fullname($user1);



?>
<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">
<tr valign="top">
    <td align="left"><b><?php  print_string('rayon', 'block_monitoring') ?>:</b></td>
    <td align="left"> <?php p($rayon->name) ?> </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print_string('school', 'block_monitoring') ?>:</b></td>
    <td align="left"> <?php echo $school->name ?> </td>
</tr>
<tr valign="top">
    <td align="left"><b><?php  print $strclass; ?>:</b></td>
    <td align="left"> <?php p($class->name) ?> </td>
</tr>
</table>
<?php


    print_heading(get_string('pupilmovein', 'block_mou_ege', $fullname), "center", 3);

    if ($admin_is  || $region_operator_is) {  // || $rayon_operator_is)  {

		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
		listbox_rayons("movepupil.php?mode=1&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;uid=$uid&amp;gid=$gid&amp;sid2=$sid2&amp;gid2=$gid2&amp;rid2=", $rid2);
		listbox_schools("movepupil.php?mode=2&amp;rid=$rid&amp;yid=$yid&amp;uid=$uid&amp;gid=$gid&amp;sid=$sid&amp;rid2=$rid2&amp;gid2=$gid2&amp;sid2=", $rid2, $sid2, $yid);
	    listbox_class("movepupil.php?mode=3&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;uid=$uid&amp;gid=$gid&amp;rid2=$rid2&amp;sid2=$sid2&amp;gid2=", $rid2, $sid2, $yid, $gid2);
		echo '</table>';

	} else  if ($rayon_operator_is)  {

		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
		listbox_schools("movepupil.php?mode=2&amp;rid=$rid&amp;yid=$yid&amp;uid=$uid&amp;gid=$gid&amp;sid=$sid&amp;rid2=$rid2&amp;gid2=$gid2&amp;sid2=", $rid2, $sid2, $yid);
	    listbox_class("movepupil.php?mode=3&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;uid=$uid&amp;gid=$gid&amp;rid2=$rid2&amp;sid2=$sid2&amp;gid2=", $rid2, $sid2, $yid, $gid2);
		echo '</table>';

	} else  if ($school_operator_is)  {

		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
	    listbox_class("movepupil.php?mode=3&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;uid=$uid&amp;gid=$gid&amp;rid2=$rid2&amp;sid2=$sid2&amp;gid2=", $rid2, $sid2, $yid, $gid2);
		echo '</table>';

	}


	if ($mode == 3)  {

		$options = array('rid' => $rid, 'sid' => $sid, 'gid' => $gid, 'yid' => $yid,  'uid' => $uid,
						 'rid2' => $rid2, 'sid2' => $sid2, 'gid2' => $gid2, 'action' => 'move');
		echo '<table align="center" border=0><tr><td>';
	    print_single_button("movepupil.php", $options, get_string('makepupilmovein', 'block_mou_ege'));
		echo '</td></tr></table>';
	}

    print_footer();
?>


