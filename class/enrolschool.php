<?php // $Id: enrolschool.php,v 1.3 2009/06/11 09:40:34 Shtifanov Exp $

    require_once("../../../config.php");
    require_once($CFG->libdir.'/tablelib.php');
    require_once('../../monitoring/lib.php');
    require_once('../lib_ege.php');

    $rid = required_param('rid', PARAM_INT);          // Rayon id
    $sid = required_param('sid', PARAM_INT);       // School id
	// $cid = required_param('cid', PARAM_INT);		  // Curriculum id
    $yid = optional_param('yid', '0', PARAM_INT);       // Year id
	$ishowall = optional_param('iall', 0, PARAM_INT);		// Show all course
	$modecheck = optional_param('check', 0, PARAM_INT);		// Synchronise enrol/unenrol
    $courseid = optional_param('did', 0, PARAM_INT);  // Course id
   	$level = optional_param('level', '');

    $curryearid = get_current_edu_year_id();
    if ($yid == 0)	{
    	$yid = $curryearid;
    }

	$mode = 4;

	$action   = optional_param('action', 'action');
    if ($action == 'excel') {
        classpupils_download($rid, $sid, $gid);
        exit();
	}

    if (!$site = get_site()) {
        redirect("$CFG->wwwroot/$CFG->admin/index.php");
    }

	require_login();

	$admin_is = isadmin();
	$region_operator_is = ismonitoperator('region');
	$rayon_operator_is  = ismonitoperator('rayon', 0, 0, 0, true);
	$school_operator_is = ismonitoperator('school', 0, $rid, $sid);
	if (!$admin_is && !$region_operator_is && !$rayon_operator_is) { //  && !$school_operator_is) {
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

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= "-> $strclasses";
    print_header("$site->shortname: $strclasses", $site->fullname, $breadcrumbs);

    if ($admin_is  || $region_operator_is) {  // || $rayon_operator_is)  {
		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
		listbox_rayons("enrolschool.php?sid=0&amp;yid=$yid&amp;rid=", $rid);
		listbox_schools("enrolschool.php?rid=$rid&amp;yid=$yid&amp;sid=", $rid, $sid, $yid);
		echo '</table>';
	} else  if ($rayon_operator_is)  {
		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
		listbox_schools("enrolschool.php?rid=$rid&amp;yid=$yid&amp;sid=", $rid, $sid, $yid);
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

    $currenttab = 'enrolschool';
    include('tabsclasses.php');

	echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
	listbox_courses("enrolschool.php?rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;did=", $courseid);
	echo '</table>';



	if ($courseid > 1)  {

        $course = get_record('course', 'id', $courseid);

		if ($level != '') 	{			switch ($level)	{				case 'school': 	$classes = get_records_sql ("SELECT * FROM {$CFG->prefix}monit_school_class
											  WHERE schoolid=$sid AND yearid=$yid
											  ORDER BY name");
				break;
				case 'rayon':  	$strsql =  "SELECT id, rayonid, name, number  FROM {$CFG->prefix}monit_school
							   				WHERE rayonid = $rid AND isclosing=0 AND yearid=$yid
							   				ORDER BY number";

							    $schoolslist = '';
							 	if ($schools = get_records_sql($strsql))	{

							        $schoolsarray = array();
								    foreach ($schools as $sa)  {
								        $schoolsarray[] = $sa->id;
								    }
								    $schoolslist = implode(',', $schoolsarray);
							    }

								$classes = get_records_sql ("SELECT * FROM {$CFG->prefix}monit_school_class
											  WHERE schoolid in ($schoolslist) AND yearid=$yid
											  ORDER BY name");
				break;
			}

			if ($classes)	{

				foreach ($classes as $class) {
					$uniqname = get_unique_classname ($class);			/// Get unique class name

					$gid = $class->id;

				    $strsql = "SELECT id, userid, classid, schoolid, listegeids
				    		   FROM {$CFG->prefix}monit_school_pupil_card
							   WHERE classid = $gid AND deleted=0";
					// echo $strsql;
					$pupils = get_records_sql ($strsql);

				    // $pupils= get_records('monit_school_pupil_card', 'classid', $gid);
		            // print_r($academystudents); echo '<hr>';

				    if (!$mgroup = get_record('groups', 'name', $uniqname))	{
		      	        $newgroup->name = $uniqname;
					//    $newgroup->courseid = $addcourse;
						$newgroup->description = '';
						$newgroup->password = '';
						$newgroup->theme = '';
		              	$newgroup->lang = current_language();
		                $newgroup->timecreated = time();
		  	            if (!$newgrpid=insert_record("groups", $newgroup)) {
		      	            error("Could not insert the new group '$newgroup->name'");
		          	    } else {
			          	    $mgroup = get_record('groups', 'name', $uniqname);
		          	    	// notify("the new group '$newgroup->name'");
		  	            	// add_to_log(1, 'dean', 'new moodle group registered', "blocks/dean/groups/registergroup.php?mode=new&amp;fid=$fid&amp;sid=$sid&amp;cid=$cid", $USER->lastname.' '.$USER->firstname);
		          	    }
		          	}


		            $addcourse = $courseid;


	                if (record_exists('groups_courses_groups', 'courseid', $addcourse, 'groupid', $mgroup->id))	{
		 			    notify ("Группа $uniqname уже подписана на курс \"{$course->fullname}\"", 'black');
		 			    continue;
		 			} else {
		 				unset($rec);
		 				$rec->courseid = $addcourse;
		 				$rec->groupid = $mgroup->id;
		  	            if (!insert_record('groups_courses_groups', $rec)) {
		      	            error("Could not insert the new group '$newgroup->name'");
		          	    }
		 			}

				    if ($pupils)  {
				   	   foreach ($pupils as $astud)	  {
		                /// Enrol student
					     if ($usr = get_record('user', 'id', $astud->userid))	{
						    // print '-'.$usr->id.':'.$usr->lastname.' '. $usr->firstname. '<br>';
						    if ($usr->deleted != 1)	 {
								if (enrol_student($astud->userid, $addcourse))  {

	                                // if (add_user_to_group($groupid[$i], $user->id)) {
	                                if (groups_add_member($mgroup->id, $astud->userid))	{
	                                   // notify('-->' . get_string('addedtogroup','',$uniqname), 'green');
	                                } else {
	                                    notify('-->' . get_string('addedtogroupnot','',$uniqname));
	                                }

	      			            } else {
	    		                      error("Could not add student with id $astud->userid to the course $addcourse!");
	    		                }
	      			        }
	      			     }
		               }
					}
				}

                notice (get_string('successenrol'.$level, 'block_mou_ege', $course->fullname), "enrolschool.php?rid=$rid&amp;yid=$yid&amp;sid=$sid");
			}
		}

   		$options = array('rid' => $rid, 'sid' => $sid, 'did' => $courseid, 'yid' => $yid, 'level' => 'school');
		echo '<table align="center" border=0><tr><td>';
   	    print_single_button("enrolschool.php", $options, get_string('enrolallschoolclass','block_mou_ege'));

	     if ($admin_is  || $region_operator_is || $rayon_operator_is)  {	     	$options['level']= 'rayon';			echo '</td><td>';
 	  	    print_single_button("enrolschool.php", $options, get_string('enrolallrayonclass','block_mou_ege'));
	     }

		echo '</td></tr></table>';
    }

   print_footer();

?>