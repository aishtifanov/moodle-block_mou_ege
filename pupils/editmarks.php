<?php  // $Id: editmarks.php,v 1.8 2010/06/22 10:49:29 Shtifanov Exp $

    require_once("../../../config.php");
    require_once('../../monitoring/lib.php');
    require_once('../lib_ege.php');
    require_once('editmarks_form.php');

    require_login();

    if (isguest()) {
        error("No guests here!");
    }

    $rid = required_param('rid', PARAM_INT);       // Rayon id
    $sid = required_param('sid', PARAM_INT);       // School id
    $gid = required_param('gid', PARAM_INT);       // Class id
    $yid = optional_param('yid', 0, PARAM_INT);       // Year id
    $did = optional_param('did', 0, PARAM_INT);       // Discipline id
    $uid = optional_param('uid', 0, PARAM_INT);       // User id

    if ($yid == 0)	{
    	$yid = get_current_edu_year_id();
    }


	$admin_is = isadmin();
	$region_operator_is = ismonitoperator('region');
	if (!$admin_is && !($region_operator_is && ($USER->id == 573 || $USER->id == 7521))) {
        error(get_string('adminaccess', 'block_monitoring'), "$CFG->wwwroot/login/index.php");
	}

	if (isregionviewoperator() || israyonviewoperator())  {
        error(get_string('adminaccess', 'block_monitoring'));
	}

	// $strclasses = get_string('classes','block_mou_ege');
	$strclasses = get_string('school','block_monitoring');
	$strmarks = get_string('quickeditmarks','block_mou_ege');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= " -> $strmarks";
    print_header_mou("$SITE->shortname: $strmarks", $SITE->fullname, $breadcrumbs);


	print_tabs_years($yid, "editmarks.php?yid=");

    $currenttab = 'quickeditmarks';
    include('tabsmark.php');

	echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
	// full link yid=$yid&amp;rid=$rid&amp;sid=$sid&amp;gid=$gid&amp;did=$did&amp;uid=$uid
	listbox_rayons("editmarks.php?yid=$yid&amp;sid=$sid&amp;gid=$gid&amp;did=$did&amp;uid=$uid&amp;rid=", $rid);
	listbox_schools("editmarks.php?yid=$yid&amp;rid=$rid&amp;gid=$gid&amp;did=$did&amp;uid=$uid&amp;sid=", $rid, $sid, $yid);
    listbox_class("editmarks.php?yid=$yid&amp;rid=$rid&amp;sid=$sid&amp;did=$did&amp;uid=$uid&amp;gid=", $rid, $sid, $yid, $gid);
	listbox_discipline_ege("editmarks.php?yid=$yid&amp;rid=$rid&amp;sid=$sid&amp;gid=$gid&amp;uid=$uid&amp;did=", $rid, $sid, $yid, $did);
	listbox_pupils("editmarks.php?yid=$yid&amp;rid=$rid&amp;sid=$sid&amp;gid=$gid&amp;did=$did&amp;uid=", $rid, $sid, $yid, $gid, $uid);
	echo '</table>';

 	if ($rid != 0 && $sid != 0 && $did != 0 && $gid != 0 && $uid != 0)  {

	    if (!$discipline_ege = get_record('monit_school_discipline_ege', 'id', $did))	{
	    	error('Discipline not found!');
	    }

	    $editform = new editmarks_form('editmarks.php');
	    // now override defaults if course already exists
		$strsqlresults = "SELECT *  FROM {$CFG->prefix}monit_gia_results
						 WHERE yearid=$yid AND schoolid=$sid AND codepredmet={$discipline_ege->code} AND userid = $uid";

	    $gia_res = get_record_sql($strsqlresults);

        // echo  $strsqlresults . '<hr>'; print_r($gia_res);

	    if (!empty($gia_res)) {
	        $editform->set_data($gia_res);
	    }

	    if ($editform->is_cancelled())	{
            redirect("editmarks.php?rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;gid=$gid&amp;did=$did&amp;uid=0", '', 0);
	    } else if ($data = $editform->get_data()) 	{
            // print_r($data); echo  '<hr>';
	    	if (!empty($gia_res))	 {
		    	$data->id =  $gia_res->id;
		    	// print_r($data);
		        if (update_record('monit_gia_results', $data)) {
		            // notice(get_string('giaresultupdated', 'block_mou_ege', $data->id));
		            redirect("editmarks.php?rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;gid=$gid&amp;did=$did&amp;uid=0", get_string('giaresultupdated', 'block_mou_ege', $data->id), 0);
		        } else {
		            error('Error in update pupil mark.');
		        }
		    } else {
		    	$rec->yearid = $data->yid;
		    	$rec->rayonid = $data->rid;
		    	$rec->schoolid = $data->sid;
		    	$rec->classid  = $data->gid;
		    	$rec->userid  = $data->uid;
		    	$rec->pp  = $data->pp;
		    	$rec->audit  = $data->audit;
		    	$rec->codepredmet = $discipline_ege->code;
		    	$rec->variant = $data->variant;
		    	$rec->sidea = $data->sidea;
		    	$rec->sideb = $data->sideb;
		    	$rec->sidec = $data->sidec;
		    	$rec->ball = $data->ball;
		    	$rec->ocenka = $data->ocenka;
		    	$rec->timemodified = time();
		        if ($newid = insert_record('monit_gia_results', $rec)) {
		            // notice(get_string('giaresultupdated', 'block_mou_ege', $data->id));
		            redirect("editmarks.php?rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;gid=$gid&amp;did=$did&amp;uid=0", get_string('giaresultupdated', 'block_mou_ege', $newid), 0);
		        } else {
		            print_r($rec);
		            error('Error in insert pupil mark.');
		        }

		    }
	    } else {
	        $editform->display();
	    }

	}

    print_footer();

?>
