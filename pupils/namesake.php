<?PHP // $Id: namesake.php,v 1.3 2009/06/11 09:40:37 Shtifanov Exp $

    require_once('../../../config.php');
    require_once('../../monitoring/lib.php');
    require_once('../lib_ege.php');

    $rid = optional_param('rid', '0', PARAM_INT);       // Rayon id
    $sid = optional_param('sid', '0', PARAM_INT);       // School id
    $namestudent = optional_param('namestudent', '');		// pupil lastname
    $loginstudent = optional_param('loginstudent', '');		// pupil login
   	$action = optional_param('action', '');
    $yid = optional_param('yid', '0', PARAM_INT);       // Year id

    if ($yid == 0)	{
    	$yid = get_current_edu_year_id();
    }

	if (! $site = get_site()) {
        redirect("$CFG->wwwroot/$CFG->admin/index.php");
    }

	require_login();

	$admin_is = isadmin();
	$region_operator_is = ismonitoperator('region');
	$rayon_operator_is  = ismonitoperator('rayon', 0, 0, 0, true);
	// $school_operator_is = ismonitoperator('school', 0, $rid, $sid);
	if (!$admin_is && !$region_operator_is && !$rayon_operator_is) {
        error(get_string('adminaccess', 'block_monitoring'), "$CFG->wwwroot/login/index.php");
	}

	$strstudent  = get_string('pupil', 'block_mou_ege');
	$strstudents = get_string('pupils', 'block_mou_ege');
    $strsearchstudent = get_string('searchpupil', 'block_mou_ege');
    $strsearch = get_string("search");
    $strsearchresults  = get_string("searchresults");
    $searchtext1 = '';
    $searchtext2 = '';

    $breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= " -> $strsearchstudent";
    print_header_mou("$site->shortname: $strsearchstudent", $site->fullname, $breadcrumbs);

    $studentsql = "SELECT v.id, rayonid, schoolid, classid, id2, lastname, firstname
    			  FROM mdl_monit_school_pupil_card m, view_full_namesake v
				  WHERE (lastname <> 'Фамилия') and  (m.userid = v.id)";
	if (!$admin_is && !$region_operator_is && $rayon_operator_is) {
		 $studentsql .= " AND (rayonid = $rayon_operator_is) ";
	}
    $studentsql .= "ORDER BY lastname";

    $students = get_records_sql($studentsql);

    if(!empty($students)) {

		$strsql =  "SELECT userid, schoolid  FROM {$CFG->prefix}monit_school_pupil_card";
	 	if ($userschools = get_records_sql($strsql))	{
	        $userid_sid = array();
		    foreach ($userschools as $sa)  {
		        $userid_sid[$sa->userid] = $sa->schoolid;
		    }
		}

	    $straction = get_string('action', 'block_monitoring');
	    $strschool = get_string('school', 'block_monitoring');

      	$table->head  = array ($strschool,  get_string('fullname'), get_string('fullname'), $straction);
	    $table->align = array ('left', 'left', 'left', 'center');
 		$table->class = 'moutable';

        foreach ($students as $student) 	{        	if (isset($userid_sid[$student->id]))	{	        	$schoolid1 = $userid_sid[$student->id];
	        	if (isset($userid_sid[$student->id2]))	{		        	$schoolid2 = $userid_sid[$student->id2];
		        	if ($schoolid1 == $schoolid2)	{		        		$strlinkupdate = '';

                        $rid 	= $student->rayonid;
               			$sid 	= $schoolid1; // $student->schoolid;

        				if ($school = get_record_sql("SELECT id, name FROM {$CFG->prefix}monit_school WHERE id=$sid")) 	{
			                   $mesto = "<a href=\"{$CFG->wwwroot}/blocks/mou_ege/class/classlist.php?rid=$rid&amp;yid=$yid&amp;sid=$sid\">". $school->name . '</a>';
						}

						$strlink1 = "<div align=left><strong><a href=\"pupil.php?mode=4&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid={$student->classid}&amp;uid={$student->id}\">".fullname($student)."</a></strong></div>";
						if ($pupil2 = get_record('monit_school_pupil_card', 'userid', $student->id2))	{							$strlink2 = "<div align=left><strong><a href=\"pupil.php?mode=4&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid={$pupil2->classid}&amp;uid={$student->id2}\">".fullname($student)."</a></strong></div>";						} else {							$strlink2 = fullname($student);						}

		               $table->data[] = array ($mesto, $strlink1,  $strlink2,  $strlinkupdate);
		        	}        	    }
        	}
        }

        print_heading(get_string('resultsearchpupil', 'block_mou_ege'), 'center', 3);
		print_color_table($table);
	}
	else {
		notify(get_string('pupilnotfound','block_mou_ege'));
		echo '<hr>';
	}
    print_footer();

?>
