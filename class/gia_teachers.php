<?php // $Id: gia_teachers.php,v 1.5 2011/08/01 07:34:16 shtifanov Exp $

    require_once("../../../config.php");
    require_once('../../monitoring/lib.php');
    require_once($CFG->libdir.'/tablelib.php');

    $rid = required_param('rid', PARAM_INT);          // Rayon id
    $sid = required_param('sid', PARAM_INT);          // School id
    $yid = optional_param('yid', '0', PARAM_INT);       // Year id

    if ($yid == 0)	{
	    $yid = get_current_edu_year_id();
	}


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

	$action   = optional_param('action', '');
    if ($action == 'excel') {
        print_excel_staffs($rid, $sid, $yid);
        exit();
	}


    if ($sid != 0)	{
    	$school = get_record('monit_school', 'id', $sid, 'yearid', $yid);
   	    $strschool = $school->name;
  		$type_ou = $school->type_ou;
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
		listbox_rayons("gia_teachers.php?sid=0&amp;yid=$yid&amp;rid=", $rid);
		listbox_schools("gia_teachers.php?rid=$rid&amp;yid=$yid&amp;sid=", $rid, $sid, $yid);
		echo '</table>';
	} else  if ($rayon_operator_is)  {
		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
		listbox_schools("gia_teachers.php?rid=$rid&amp;yid=$yid&amp;sid=", $rid, $sid, $yid);
		echo '</table>';
	}  else if ($school_operator_is) {
		print_heading($strclasses.': '.$school->name, "center", 3);
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

   	print_heading(get_string('gia_teachers', 'block_mou_ege'), "center");

    if ($rid != 0 && $sid != 0)  {

        $teachersql = "SELECT u.id, u.username, u.firstname, u.lastname, u.picture, 
        			  t.schoolid, t.birthday, t.graduate, t.listegeids
                      FROM {$CFG->prefix}user u
    	              LEFT JOIN {$CFG->prefix}monit_att_staff t ON t.userid = u.id
     	              WHERE t.schoolid=$sid AND u.deleted = 0 AND u.confirmed = 1";
		$teachersql .= ' ORDER BY u.lastname';

        $teachers = get_records_sql($teachersql);
/*
        print_r($teachersql);
        echo '<hr>';
        print_r($teachers);
*/

	    $strnever = get_string('never');
   		$strappointment = get_string('appointment_ped', 'block_mou_att');

	    $table->head  = array ( '', get_string('fullname'), get_string('graduate', 'block_mou_att'),
	    						$strappointment, get_string('teacher_disciplines_ege','block_mou_ege'),  $straction);
	    $table->align = array ('center', 'left', 'center', 'center', 'center', 'center');
	    $table->class = 'moutable';

	    $strlinkupdate = '';

        if(!empty($teachers)) {

			$disciplines =  get_records_sql ("SELECT id, yearid, name  FROM  {$CFG->prefix}monit_school_discipline_ege
											  WHERE yearid=$yid ORDER BY name");
			if ($disciplines)	{
				$listegeids = array();
				foreach ($disciplines as $discipline) 	{
					$listegeids [$discipline->id] = $discipline->name;
				}
			}


            foreach ($teachers as $teacher) {

	    		if (isset($teacher->is_header) && $teacher->is_header == 1)		{
		     		$strappointment = '<i>1)</i> ' . $teacher->appointment_head . '<br><i>2)</i> ' . $teacher->appointment_ped;
				} else if (!empty($teacher->appointment_ped))	{
		     		$strappointment = $teacher->appointment_ped;
		     	}  else {
					$strappointment = '';
		     	}

		    	$list_disc = '';
			    if (!empty($teacher->listegeids))	{
			    	$pli = explode(',', $teacher->listegeids);
			    	foreach ($pli as $pli1)	{
			    		if ($pli1 > 0)	{
				    		$list_disc .= $listegeids[$pli1] . ', ';
				    	}
			    	}
			    	if ($list_disc != '')  {
			    		$list_disc = substr($list_disc, 0, strlen($list_disc)- 2);
			    	}

			    }
			    if ($list_disc == '')  $list_disc = '-';

				$title = get_string('change_disciplines_ege','block_mou_ege');
				$strlinkupdate = "<a title=\"$title\" href=\"gia_teacher_edit.php?mode=add&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;uid={$teacher->id}\">";
				$strlinkupdate = $strlinkupdate . "<img src=\"{$CFG->pixpath}/i/edit.gif\" alt=\"$title\" /></a>&nbsp;";


       			$table->data[] = array (print_user_picture($teacher->id, 1, $teacher->picture, false, true),
				    					"<div align=left><strong>".fullname($teacher)."</strong></div>",
		                                $teacher->graduate,
										$strappointment,
										$list_disc,
										$strlinkupdate);
            }
           	print_color_table($table);
    	}
    }

//    echo '<div align=center><b>'. get_string('attentionstaff', 'block_mou_att') . '</b></div>';
    print_footer();


function print_excel_staffs($rid, $sid, $yid)
{
    global $CFG;

	return true;
}


?>


