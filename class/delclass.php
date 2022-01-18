<?PHP // $Id: delclass.php,v 1.8 2009/06/11 09:40:34 Shtifanov Exp $

    require_once("../../../config.php");
    require_once('../../monitoring/lib.php');
    require_once('../lib_ege.php');


    $rid = required_param('rid', PARAM_INT);          // Rayon id
    $sid = required_param('sid', PARAM_INT);       // School id
	// $cid = required_param('cid', PARAM_INT);		  // Curriculum id
    $yid = required_param('yid', PARAM_INT);       // Year id
    $gid = required_param('gid', PARAM_INT);          // Class id
	$confirm = optional_param('confirm');
	$action   = optional_param('action', 'action');

    if (! $site = get_site()) {
        redirect("$CFG->wwwroot/$CFG->admin/index.php");
    }

	require_login();

	$admin_is = isadmin();
	$region_operator_is = ismonitoperator('region');
	$rayon_operator_is  = ismonitoperator('rayon', 0, 0, 0, true);
	$school_operator_is = ismonitoperator('school', 0, $rid, $sid);
	if (!$admin_is && !$region_operator_is && !$rayon_operator_is && !$school_operator_is) {
        error(get_string('adminaccess', 'block_monitoring'), "$CFG->wwwroot/login/index.php");
	}

	if (isregionviewoperator() || israyonviewoperator())  {
        error(get_string('adminaccess', 'block_monitoring'));
	}

	$strclasses = get_string('classes','block_mou_ege');
	$strclass = get_string('class','block_mou_ege');
	$strpupils = get_string('pupils', 'block_mou_ege');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= "-> <a href=\"{$CFG->wwwroot}/blocks/mou_ege/class/classlist.php?rid=$rid&amp;sid=$sid&amp;yid=$yid\">$strclasses</a>";
	$breadcrumbs .= " -> $strpupils";
    print_header("$site->shortname: $strpupils", $site->fullname, $breadcrumbs);

	if (!$class = get_record('monit_school_class', 'id', $gid)) {
        error("Class not found!");
	}

	if (isset($confirm)) {
		$countpupils = count_records('monit_school_pupil_card', 'classid',  $class->id);
		if ($countpupils == 0)		{
			delete_records('monit_school_class', 'id', $gid);
			add_to_log(1, 'mou_ege', 'Class deleted', 'delclass.php', $USER->lastname.' '.$USER->firstname);
		}
		else	{
		    if ($action == 'clear') {				if ($admin_is || $region_operator_is || ($rayon_operator_is == $rayon->id)) 	{
				    $pupils = get_records('monit_school_pupil_card', 'classid',  $class->id);
				    foreach ($pupils as $pupil) {		                role_unassign(0, $pupil->userid);
		           		delete_records('monit_school_pupil_card', 'userid', $pupil->userid);
		           		delete_records('user', 'id', $pupil->userid);
				    }
	    			delete_records('monit_school_class', 'id', $gid);
		 			redirect($CFG->wwwroot."/blocks/mou_ege/class/classlist.php?mode=3&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid", get_string('classdeleted', 'block_mou_ege', $class->name), 3);
				}		    }
			error(get_string('errorindelclass', 'block_mou_ege'), $CFG->wwwroot."/blocks/mou_ege/class/classlist.php?mode=3&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid");
		}
		redirect($CFG->wwwroot."/blocks/mou_ege/class/classlist.php?mode=3&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid", get_string('classdeleted', 'block_mou_ege', $class->name), 3);
	}


	print_heading(get_string('deletingclass','block_mou_ege') .' :: ' .$class->name);

	notice_yesno(get_string('deletecheckfull', '', $class->name . ' ' . $strclass ),
               "delclass.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid&amp;confirm=1&amp;action=$action", "classlist.php?mode=3&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid");

	print_footer();
?>
