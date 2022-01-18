<?php // $Id: profile.php,v 1.6 2010/05/26 11:15:02 Shtifanov Exp $

    require_once('../../../config.php');
    require_once('../../monitoring/lib.php');
    require_once('../lib_ege.php');

    $rid = optional_param('rid', '0', PARAM_INT);   // Rayon id
    $sid = optional_param('sid', '0', PARAM_INT);	// School id
    $yid = optional_param('yid', '0', PARAM_INT);       // Year id
    $uid = optional_param('uid', 0, PARAM_INT);       // User id
    $gid = optional_param('gid', 0, PARAM_INT);       // User id
    $tab = optional_param('tab', 'profile');
  	$action = optional_param('action', '');       // action

    if ($yid == 0)	{
	    $yid = get_current_edu_year_id();
    }

    // print_r ($action);
    if ($action == 'appellant') {
		form_download($rid, $sid, $yid, $action);
        exit();
	}

    if (! $site = get_site()) {
        redirect("$CFG->wwwroot/$CFG->admin/index.php");
    }

	require_login();

	$admin_is = isadmin();
	$pupil_is = ispupil();
	$region_operator_is = ismonitoperator('region');
	if (!$admin_is && !$pupil_is && !$region_operator_is) {
        error(get_string('adminaccess', 'block_monitoring'), "$CFG->wwwroot/login/index.php");
	}

	$uid = $USER->id;
	if ($admin_is || $region_operator_is)	{
		$uid = 57168;
	}



	$strmarks = get_string('profilepupil', 'block_mou_ege');
	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= " -> $strmarks";
    print_header_mou("$site->shortname: $strmarks", $site->fullname, $breadcrumbs);


    $pupil = get_record('monit_school_pupil_card', 'userid', $uid);

    if ($sid == 0)	{
    	$sid = $pupil->schoolid;
    }
	$school = get_record('monit_school', 'id', $sid);

    if ($gid == 0)	{
    	$gid = $pupil->classid;
    }
	$class = get_record('monit_school_class', 'id', $gid);


    if ($rid == 0)	{
    	$rid = $school->rayonid;
    }
	$rayon = get_record('monit_rayon', 'id', $rid);

    if (!$user = get_record('user', 'id', $uid) ) {
        error('No such pupil in this class!', '..\index.php');
	}

   	$fullname = fullname($user);

/*
	$profile->fields = array( 'typedocuments', 'serial', 'number', 'who_hands',  'when_hands');
	$profile->type 	 = array('bool', 'text', 'int', 'text', 'date');
    $profile->numericfield = array('number');
*/
	$profile->fields = array( );
	$profile->type 	 = array();
    $profile->numericfield = array();

	    $personalprofile = get_string("personalprofile");
	    $participants = get_string("participants");

	    if ($user->deleted) {
	        print_heading(get_string("userdeleted"));
	    }

	    $currenttab = 'profile';
	    include('tabs.php');


    	echo "<table width=\"80%\" align=\"center\" border=\"0\" cellspacing=\"0\" class=\"userinfobox\">";
	    echo "<tr>";
	    echo "<td width=\"100\" valign=\"top\" class=\"side\">";
    	print_user_picture($user->id, 1, $user->picture, true, false, false);
	    echo "</td><td width=\"100%\" class=\"content\">";

    	// Print the description

    	if ($user->description) {
        	echo format_text($user->description, FORMAT_MOODLE)."<hr />";
	    }

    	// Print all the little details in a list

	    echo '<table border="0" cellpadding="0" cellspacing="0" class="list">';

    	print_row(get_string('fio', 'block_monitoring').':', $fullname);

    	print_row(get_string('rayon', 'block_monitoring').':', $rayon->name);

    	print_row(get_string('school', 'block_monitoring').':', $school->name);

    	print_row(get_string('class', 'block_mou_ege').':', $class->name);


		$i = 0;
		foreach ($profile->fields as $pf)  {
		    $printstr = get_string($pf, 'block_mou_ege');
			if (!empty($pupil->{$pf}))  {
				switch ($profile->type[$i]) {
					case 'text': case 'int':
					    $printval = $pupil->{$pf};
					break;
					case 'date':
					    $printval = convert_date($pupil->{$pf}, 'en', 'ru');
					break;
					case 'bool':
					    $printval = get_string($pf.$pupil->{$pf}, 'block_mou_ege');
					break;
				}
			} else {
				if (in_array($pf, $profile->numericfield)) {
					$printval = '0';
				} else {
					$printval = '-';
				}
			}
			$i++;
	    	print_row($printstr . ':', $printval);
		}

    	print_row('<hr>', '<hr>');

		$disciplines =  get_records_sql ("SELECT id, yearid, name
										  FROM  {$CFG->prefix}monit_school_discipline_ege
										  WHERE yearid=$yid
										  ORDER BY name");

		if ($disciplines)	{
		    $arr_egeids = explode(',', $pupil->listegeids);
		    $listdisciplines = '';
			foreach ($disciplines as $discipline) 	{
				if (in_array($discipline->id, $arr_egeids))	{
					$listdisciplines .= $discipline->name.'<br>';
				}
			}
		}

        print_row(get_string('disciplines_ege', 'block_mou_ege').":", $listdisciplines);
    	print_row('<hr>', '<hr>');


		$stradress = "";
	    if ($user->city or $user->country) {
	        $countries = get_list_of_countries();
			$stradress .= $countries["$user->country"].", $user->city";
	    }
    	if ($user->address) {
			$stradress .= ", $user->address";
	    }
        print_row(get_string("address").":", $stradress);

    	print_row("E-mail:", obfuscate_mailto($user->email, '', $user->emailstop));

	    if ($user->phone2) {
	    	$phone2 = $user->phone2;
    	} else  {
	    	$phone2 = '-';
    	}
        print_row(get_string('mobilephone', 'block_monitoring').":", $phone2);


/*
    	print_row('<hr>', '<hr>');
       	print_row(get_string('username').':', $user->username);
       	print_row(get_string('startpassword', 'block_mou_att').':', $pupil->pswtxt);

	    if ($mycourses = get_my_courses($user->id)) {
    	   $courselisting = '';
    	   print_row('<hr>', '<hr>');
	       print_row(get_string('courses').':', '('.count($mycourses).')');
	       foreach ($mycourses as $mycourse) {
		       if ($mycourse->visible and $mycourse->category) {
    		       $courselisting = "<a href=\"$CFG->wwwroot/user/view.php?id=$user->id&amp;course=$mycourse->id\">$mycourse->fullname</a>";
       			}
		       print_row('-', $courselisting);
		    }
		}
*/
    echo "</table>";
    echo "</td></tr></table>";

    print_footer();

/// Functions ///////

function print_row($left, $right) {
    echo "\n<tr><td nowrap=\"nowrap\" valign=\"top\" class=\"label c0\" align=\"left\">$left</td><td align=\"left\" valign=\"top\" class=\"info c1\">$right</td></tr>\n";
}


?>


