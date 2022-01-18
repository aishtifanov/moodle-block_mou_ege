<?php // $Id: gia_teacher_edit.php,v 1.5 2011/08/01 07:34:15 shtifanov Exp $

    require_once("../../../config.php");
    require_once('../../monitoring/lib.php');
    require_once('../lib_ege.php');

    $rid = required_param('rid', PARAM_INT);          // Rayon id
    $sid = required_param('sid', PARAM_INT);          // School id
    $yid = required_param('yid', PARAM_INT);       // Year id
    $uid = required_param('uid', PARAM_INT);       // User id

    if (!$site = get_site()) {
        redirect("$CFG->wwwroot/$CFG->admin/index.php");
    }

	require_login();

	$admin_is = isadmin();
	$region_operator_is = ismonitoperator('staff');
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


    if ($sid != 0)	{
    	$school = get_record('monit_school', 'id', $sid, 'yearid', $yid);
   	    $strschool = $school->name;
    }	else  {
   	    $strschool = get_string('school', 'block_monitoring');
    }

	if ($rid == 0 &&  $sid != 0) {
		$rid = $school->rayonid;
	}

    // $strstaffs = get_string('staffs', 'block_mou_att');
    $straction = get_string('action', 'block_monitoring');
    $strtitle =  get_string('title','block_mou_att');
	$strclasses = get_string('school','block_monitoring');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= "-> $strclasses";
    print_header("$site->shortname: $strclasses", $site->fullname, $breadcrumbs);

    // add_to_log(SITEID, 'monit', 'school view', 'school.php?id='.SITEID, $strschool);
	if ($rid == 0)  {
	   $rayon = get_record('monit_rayon', 'id', 1);
	}
	else if (!$rayon = get_record('monit_rayon', 'id', $rid)) {
        error(get_string('errorrayon', 'block_monitoring'), '..\rayon\rayons.php');
    }

    if ($admin_is  || $region_operator_is) {  // || $rayon_operator_is)  {
		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
		listbox_rayons("gia_teacher_edit.php?sid=0&amp;yid=$yid&amp;uid=$uid&amp;rid=", $rid);
		listbox_schools("gia_teacher_edit.php?rid=$rid&amp;yid=$yid&amp;uid=0&amp;sid=", $rid, $sid, $yid);
		listbox_teachers("gia_teacher_edit.php?rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;uid=", $rid, $sid, $yid, $uid);
		echo '</table>';
	} else  if ($rayon_operator_is)  {
		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
		listbox_schools("gia_teacher_edit.php?rid=$rid&amp;yid=$yid&amp;uid=0&amp;sid=", $rid, $sid, $yid);
		listbox_teachers("gia_teacher_edit.php?rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;uid=", $rid, $sid, $yid, $uid);
		echo '</table>';
	}  else if ($school_operator_is) {
		print_heading($strclasses.': '.$school->name, "center", 3);
		listbox_teachers("gia_teacher_edit.php?rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;uid=", $rid, $sid, $yid, $uid);
	}

	if ($rid == 0 ||  $sid == 0) {
	    print_footer();
	 	exit();
	}

	if ($rayon_operator_is && $rayon_operator_is != $rid)  {
		notify(get_string('selectownrayon', 'block_monitoring'));
	    print_footer();
		exit();
	}

	print_tabs_years($yid, "gia_teachers.php?rid=$rid&amp;sid=$sid&amp;yid=");

    $currenttab = 'gia';
    include('tabsclasses.php');

    $currenttab = 'gia_teachers';
    include('tabsgia.php');


/// If data submitted, then process and store.

    if ($usernew = data_submitted()) 	{

			$disciplines =  get_records_sql ("SELECT id, yearid, name  FROM  {$CFG->prefix}monit_school_discipline_ege
											  WHERE yearid=$yid ORDER BY name");
			if ($disciplines)	{
				$listegeids = '';
				foreach ($disciplines as $discipline) 	{
					$pf = 'disc_ege_'.$discipline->id;
					if (isset($usernew->{$pf}) && $usernew->{$pf} == 1)  {
						$listegeids .= $discipline->id . ',';
					}
				}
				$listegeids .= '0';
				// $staff->listegeids = $listegeids;
                if (set_field('monit_att_staff', 'listegeids', $listegeids, 'userid', $uid))	{
                   	redirect("gia_teachers.php?rid=$rid&amp;yid=$yid&amp;sid=$sid", get_string("changessaved"), 0);
                }	else {
	                error("Could not update the staff record ($uid).");
                }
			}
    }


    if ($rid != 0 && $sid != 0 && $uid != 0)  {

        $teachersql = "SELECT u.id, u.firstname, u.lastname, t.listegeids
                      FROM {$CFG->prefix}user u, {$CFG->prefix}monit_att_staff t
     	              WHERE  t.userid = u.id AND u.id = $uid";
        $teacher = get_record_sql($teachersql);
/*
        print_r($teachersql);
        echo '<hr>';
        print_r($teachers);
*/
       	$fullname = fullname($teacher);
       	print_heading($fullname, 'center', 2);

   		if (isset($teacher->is_header) && $teacher->is_header == 1)		{
     		$strappointment = '<i>1)</i> ' . $teacher->appointment_head . '<br><i>2)</i> ' . $teacher->appointment_ped;
		} else if (!empty($teacher->appointment_ped))	{
     		$strappointment = $teacher->appointment_ped;
     	}  else {
			$strappointment = '';
     	}
       	print_heading($strappointment, 'center', 3);


	    print_simple_box_start("center", '50%', 'white');
?>

<table class="formtable">
<form method="post" name="form" enctype="multipart/form-data" action="gia_teacher_edit.php">
<input type="hidden" name="rid" value="<?php echo $rid ?>" />
<input type="hidden" name="sid" value="<?php echo $sid ?>" />
<input type="hidden" name="yid" value="<?php echo $yid ?>" />
<input type="hidden" name="uid" value="<?php echo $uid ?>" />


<?php

		$disciplines =  get_records_sql ("SELECT id, yearid, name
										  FROM  {$CFG->prefix}monit_school_discipline_ege
										  WHERE yearid=$yid
										  ORDER BY name");

		if ($disciplines)	{
		    $arr_egeids = explode(',', $teacher->listegeids);
		    $strdisciplines = get_string('disciplines_ege', 'block_mou_ege');
			echo "<tr><th>$strdisciplines:</th>";
			echo "<td>";
			foreach ($disciplines as $discipline) 	{
				$name = 'disc_ege_'.$discipline->id;
				if (in_array($discipline->id, $arr_egeids))	{
					echo "<input name=$name type=checkbox checked=checked value=1>";
				} else {
					echo "<input name=$name type=checkbox value=1>";
				}
				echo $discipline->name.'<br>';
			}
			echo "</td>";
		}
		if (!isregionviewoperator() && !israyonviewoperator())  {
			echo '<tr><td colspan="2"><hr /></td></tr>';
			echo '<tr align=center><td align=right><input type="submit" value="' . get_string("savechanges") . '" /></td>';
	  		echo '</form><td align=left>';
/*
	   		$options = array();
		    $options['rid'] = $rid;
		    $options['sid'] = $sid;
		    $options['yid'] = $yid;
		    print_single_button("gia_teachers.php", $options, get_string("revert"));
*/
?>

<form method="post" name="form2" enctype="multipart/form-data" action="gia_teachers.php">
<input type="hidden" name="rid" value="<?php echo $rid ?>" />
<input type="hidden" name="sid" value="<?php echo $sid ?>" />
<input type="hidden" name="yid" value="<?php echo $yid ?>" />
<input type="hidden" name="uid" value="<?php echo $uid ?>" />
<input type="submit" value="<?php print_string("revert")?>" />
</form>

<?php
	  		echo '</td></tr></table>';
  		}

	   	print_simple_box_end();
    }
    print_footer();


?>


