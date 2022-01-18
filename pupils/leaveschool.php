<?PHP // $Id: leaveschool.php,v 1.2 2009/06/11 09:40:37 Shtifanov Exp $

    require_once("../../../config.php");
	require_once($CFG->libdir.'/adminlib.php');
    require_once('../../monitoring/lib.php');
    require_once('../lib_ege.php');

    $rid     = required_param('rid', PARAM_INT);                 // Rayon id
    $sid     = required_param('sid', PARAM_INT);                 // School id
    $yid = required_param('yid', PARAM_INT);          // Group id
    $gid = required_param('gid', PARAM_INT);          // Group id
    $delete  = required_param('uid', PARAM_INT);
	$confirm = optional_param('confirm');

    if (! $site = get_site()) {
        redirect("$CFG->wwwroot/$CFG->admin/index.php");
    }

	require_login();

	$admin_is = isadmin();
	$region_operator_is = ismonitoperator('region');
	$rayon_operator_is  = ismonitoperator('rayon', 0, 0, 0, true);
	if (!$admin_is && !$region_operator_is && !$rayon_operator_is) {
        error(get_string('adminaccess', 'block_monitoring'), "$CFG->wwwroot/login/index.php");
	}

	if (isregionviewoperator() || israyonviewoperator())  {
        error(get_string('adminaccess', 'block_monitoring'));
	}


    $pupil = get_record('monit_school_pupil_card', 'userid', $delete);
    $user = get_record_sql("SELECT id, lastname, firstname FROM {$CFG->prefix}user WHERE id=$delete");
    $fullname = fullname($user);

	$strclasses = get_string('classes','block_mou_ege');
	$strclass = get_string('class','block_mou_ege');
	$strpupils = get_string('pupils', 'block_mou_ege');
	$strpupil = get_string('pupil', 'block_mou_ege');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= "-> <a href=\"{$CFG->wwwroot}/blocks/mou_ege/class/classlist.php?rid=$rid&amp;sid=$sid&amp;yid=$yid\">$strclasses</a>";
	$breadcrumbs .= "-> <a href=\"{$CFG->wwwroot}/blocks/mou_ege/class/classpupils.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid\">$strpupils</a>";
	$breadcrumbs .= "-> $strpupil";
    print_header("$site->shortname: $strpupils", $site->fullname, $breadcrumbs);


	if (!$admin_is && !$region_operator_is) {
        error(get_string('accesstemporarylock', 'block_mou_ege'));
	}


 	if ($delete and confirm_sesskey()) {              // Delete a selected user, after confirmation

        // if (!has_capability('moodle/user:delete', $sitecontext)) {
            // error('You do not have the required permission to delete a user.');
        // }

        if (!$user = get_record('user', 'id', $delete)) {
            error("No such user!", '', true);
        }

        $primaryadmin = get_admin();
        if ($user->id == $primaryadmin->id) {
            error("You are not allowed to delete the primary admin user!", '', true);
        }

        if ($confirm != md5($delete)) {
            $fullname = fullname($user, true);
            print_heading(get_string('deleteprofilepupil', 'block_mou_ege'));
            $optionsyes = array('rid'=>$rid, 'sid'=>$sid, 'yid'=>$yid, 'gid'=>$gid, 'uid'=>$delete,
            					'confirm'=>md5($delete), 'sesskey'=>sesskey());
	        notice_yesno(get_string('leavecheckfull', 'block_mou_ege', "'$fullname'"), 'leaveschool.php', $CFG->wwwroot.'/blocks/mou_ege/class/classpupils.php', $optionsyes, $optionsyes, 'post', 'get');

        } else if (data_submitted() and !$user->deleted) {
            //following code is also used in auth sync scripts
            $updateuser = new object();
            $updateuser->id           = $user->id;
            $updateuser->deleted      = 1;
            $updateuser->timemodified = time();
            if (update_record('user', $updateuser)) {            // if (set_field('user', 'deleted', 1, 'id', $user->id))   {
           		set_field('monit_school_pupil_card', 'deleted', 1, 'userid', $user->id);
		   		redirect("{$CFG->wwwroot}/blocks/mou_ege/class/classpupils.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid", get_string('leavededactivity', 'block_mou_ege', fullname($user, true)), 3);
            } else {
           		redirect("{$CFG->wwwroot}/blocks/mou_ege/class/classpupils.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid", get_string('deletednot', '', fullname($user, true)), 5);
               // notify(get_string('deletednot', '', fullname($user, true)));
            }


        }
    }

	print_footer();
?>
