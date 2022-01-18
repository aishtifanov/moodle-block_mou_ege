<?php // $Id: enrolclass.php,v 1.5 2011/10/05 06:04:06 shtifanov Exp $

    require_once("../../../config.php");
    require_once($CFG->libdir.'/tablelib.php');
    require_once('../../monitoring/lib.php');
    require_once('../lib_ege.php');

    $rid = required_param('rid', PARAM_INT);          // Rayon id
    $sid = required_param('sid', PARAM_INT);       // School id
	// $cid = required_param('cid', PARAM_INT);		  // Curriculum id
    $gid = required_param('gid', PARAM_INT);          // Class id
    $yid = optional_param('yid', '0', PARAM_INT);       // Year id
	$ishowall = optional_param('iall', 0, PARAM_INT);		// Show all course
	$modecheck = optional_param('check', 0, PARAM_INT);		// Synchronise enrol/unenrol

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

   /*
	if ($old_user = get_record("user", "username", "pupil".$code))	{
   			delete_records('user', 'id', $old_user->id);
	   		delete_records('monit_school_pupil_card', 'userid', $old_user->id);
	}
	*/
	$class = get_record('monit_school_class', 'id', $gid);

	$is9class = is9class($class->name);

	$strclasses = get_string('classes','block_mou_ege');
	$strclass = get_string('class','block_mou_ege');
	$strpupils = get_string('pupils', 'block_mou_ege');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= "-> <a href=\"{$CFG->wwwroot}/blocks/mou_ege/class/classlist.php?rid=$rid&amp;sid=$sid&amp;yid=$yid\">$strclasses</a>";
	$breadcrumbs .= "-> $strpupils";
    print_header("$SITE->shortname: $strpupils", $SITE->fullname, $breadcrumbs);

    if ($admin_is  || $region_operator_is) {  // || $rayon_operator_is)  {

		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
		listbox_rayons("enrolclass.php?sid=0&amp;yid=$yid&amp;gid=0&amp;rid=", $rid);
		listbox_schools("enrolclass.php?rid=$rid&amp;yid=$yid&amp;gid=0&amp;sid=", $rid, $sid, $yid);
	    listbox_class("enrolclass.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=", $rid, $sid, $yid, $gid);
		echo '</table>';

	} else  if ($rayon_operator_is)  {

		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
		listbox_schools("enrolclass.php?rid=$rid&amp;yid=$yid&amp;gid=0&amp;sid=", $rid, $sid, $yid);
	    listbox_class("enrolclass.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=", $rid, $sid, $yid, $gid);
		echo '</table>';

	} else  if ($school_operator_is)  {
		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
	    listbox_class("enrolclass.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=", $rid, $sid, $yid, $gid);
		echo '</table>';
	}


	if ($rid != 0 && $sid != 0 && $yid != 0 && $gid != 0)  {

	    $currenttab = 'enrolclass';
	    include('tabsclass.php');

	    // $allcourses = get_records("course", '', '', "fullname");
	    $allcourses = get_courses('all', 'fullname');

	    $school = get_record('monit_school', 'id', $class->schoolid);
        if ($school->code == '999999')	{
        	error(get_string('nosetcodeschool', 'block_mou_ege'), "classlist.php?rid=$rid&amp;sid=$sid&amp;yid=$yid");
        }

		$uniqname = get_unique_classname ($class);			/// Get unique class name

	/// A form was submitted so process the input
    if ($frm = data_submitted())   {

        if (!empty($frm->add) and !empty($frm->addselect) and confirm_sesskey()) {

		    $strsql = "SELECT id, userid, classid, schoolid, listegeids
		    		   FROM {$CFG->prefix}monit_school_pupil_card
					   WHERE classid = $gid AND deleted=0";
			// echo $strsql;
			$pupils = get_records_sql ($strsql);

		    // $pupils= get_records('monit_school_pupil_card', 'classid', $gid);
            // print_r($academystudents); echo '<hr>';

            foreach ($frm->addselect as $addcourse) {

				/// Create a new group
				// debug
				// $crs = get_record('course', 'id', $addcourse);
				// print '<br>='.$crs->fullname.':'.$crs->fullname. '<br>';
				//

                if (record_exists('groups', 'courseid', $addcourse, 'name', $uniqname))	{
	 			    notify ('Группа $uniqname уже подписана на курс с идентификатором $addcourse.', 'black');
	 			    continue;
	 			} else {
	 				unset($newgroup);
                    $newgroup->name = $uniqname;
			        $newgroup->courseid = $addcourse;
                    $newgroup->picture = 0;
                    $newgroup->hidepicture = 0;
                    $newgroup->timemodified = 0;
				    $newgroup->description = '';
                    $newgroup->timecreated = time();
  	                if (!$newgrpid = insert_record("groups", $newgroup)) {
          	            error("Could not insert the new group '$newgroup->name'");
              	    } else {
	             	    // $mgroup = get_record('groups', 'name', $uniqname);
          	    	    // notify("the new group '$newgroup->name'");
  	            	    // add_to_log(1, 'dean', 'new moodle group registered', "blocks/dean/groups/registergroup.php?mode=new&amp;fid=$fid&amp;sid=$sid&amp;cid=$cid", $USER->lastname.' '.$USER->firstname);
            	    }
	 			}

			    if ($pupils)  {
			   	   foreach ($pupils as $astud)	  {
	                /// Enrol student
				     if ($usr = get_record_select('user', "id = $astud->userid", 'id, deleted'))	{
					    // print '-'.$usr->id.':'.$usr->lastname.' '. $usr->firstname. '<br>';
					    if ($usr->deleted != 1)	 {
							if (enrol_student($astud->userid, $addcourse))  {

                                // if (add_user_to_group($groupid[$i], $user->id)) {
                                if (groups_add_member($newgrpid, $astud->userid))	{
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
        } else if (!empty($frm->remove) and !empty($frm->removeselect) and confirm_sesskey()) {

		    $strsql = "SELECT id, userid, classid, schoolid, listegeids
		    		   FROM {$CFG->prefix}monit_school_pupil_card
					   WHERE classid=$gid AND deleted=0";
			// echo $strsql;
			$pupils = get_records_sql ($strsql);

   		    // $pupils= get_records('monit_school_pupil_card', 'classid', $gid);

            foreach ($frm->removeselect as $removecourse) {
	 		    if ($pupils) 	{
	 		   		foreach ($pupils as $astud)	  {
		                /// UnEnrol student
						unenrol_student ($astud->userid, $removecourse);
					}
				}
				if ($mgroup = get_record('groups', 'courseid', $removecourse, 'name', $uniqname))	{
	                delete_records('groups', 'courseid', $removecourse, 'id', $mgroup->id);
 	            }
			}
        } else if (!empty($frm->showall)) {
            unset($frm->searchtext);
            $frm->previoussearch = 0;
        }

    }

    $enrolcourses = array();
	$idcourses    = array();
    if ($mgroups = get_records('groups', 'name', $uniqname))  {
    	$i=0;
		foreach($mgroups as $mgroup)  {
			$enrolcourses[$i] =  get_record_select("course", "id = $mgroup->courseid", 'id, fullname');
			$idcourses[$i] = $mgroup->courseid;
			$i++;
		}
	}


   print_simple_box_start("center");

   // echo '<hr>'. $ishowall . '<hr>';

   $sesskey = !empty($USER->id) ? $USER->sesskey : '';
?>


<form name="studentform" id="studentform" method="post" action="enrolclass.php">
<input type="hidden" name="previoussearch" value="<?php echo $previoussearch ?>" />
<input type="hidden" name="mode" value="<?php echo $mode ?>" />
<input type="hidden" name="rid" value="<?php echo $rid ?>" />
<input type="hidden" name="sid" value="<?php echo $sid ?>" />
<input type="hidden" name="yid" value="<?php echo $yid ?>" />
<input type="hidden" name="gid" value="<?php echo $gid ?>" />
<input type="hidden" name="iall" value="<?php echo $ishowall ?>" />
<input type="hidden" name="sesskey" value="<?php echo $sesskey ?>" />
  <table align="center" border="0" cellpadding="5" cellspacing="0">
    <tr>
      <td valign="top" align="center">
          <?php
              echo get_string('listofenrollcourse', 'block_mou_ege') . ': ' . count($enrolcourses) . '<br>';
          ?>
          <select name="removeselect[]" size="10" id="removeselect" multiple
                  onFocus="document.studentform.add.disabled=true;
                           document.studentform.remove.disabled=false;
                           document.studentform.addselect.selectedIndex=-1;" />
          <?php
              foreach ($enrolcourses as $ec) {
					  $strfn = substr($ec->fullname,0, 150) ;
                  echo "<option value=\"$ec->id\">$strfn</option>\n";
              }
          ?>

          </select>
      </td>
	</tr>
	<tr>
      <td valign="top" align="center">
        <input name="add" type="submit" id="add" value="&uarr;" />
        <input name="remove" type="submit" id="remove" value="&darr;" />
      </td>
	</tr>
    <tr>
      <td valign="top" align="center">
          <?php
              if ($ishowall != 0) echo get_string('listofallcourse', 'block_mou_ege') . ': ' . count($allcourses) .'<br>';
          ?>
          <select name="addselect[]" size="20" id="addselect" multiple
                  onFocus="document.studentform.add.disabled=false;
                           document.studentform.remove.disabled=true;
                           document.studentform.removeselect.selectedIndex=-1;">
          <?php
			if ($ishowall == 0)  {
		        	foreach ($allcourses as $ec) {
						if (!in_array($ec->id, $idcourses))  {
		                  $strfn = substr($ec->fullname,0, 150);
	                	  echo "<option value=\"$ec->id\">$strfn</option>\n";

	                	}
		            }
			}
			else {
		        	foreach ($allcourses as $ec) {
						if (!in_array($ec->id, $idcourses))  {
		                  $strfn = substr($ec->fullname,0, 150);
	                	  echo "<option value=\"$ec->id\">$strfn</option>\n";

	                	}
		            }
			}

          ?>
         </select>
       </td>
    </tr>
  </table>
</form>

<table align="center" border="0" cellpadding="5" cellspacing="0">
	<tr>
      <td valign="top" align="center">
          <?php
        	 $options = array();
			 $options['rid'] = $rid;
			 $options['sid'] = $sid;
		     $options['yid'] = $yid;
             $options['gid'] = $gid;
		     $options['mode'] = $mode;
			 if ($ishowall == 0) {
 			     $options['iall'] = 1;
   		     	print_single_button("enrolclass.php", $options, get_string('showallcourse','block_mou_ege'));
			 } else  {
				// if (empty($frm->add) && empty($frm->remove))  $options['mode'] = 9;
 			     $options['iall'] = 0;
   		     	print_single_button("enrolclass.php", $options, get_string('showcoursecurr','block_mou_ege'));
			 }
          ?>
      </td>
	</tr>
   </table>
</form>

<?php
   print_simple_box_end();
 }
   print_footer();

?>