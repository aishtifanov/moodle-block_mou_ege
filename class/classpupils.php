<?php // $Id: classpupils.php,v 1.43 2011/02/11 07:31:29 shtifanov Exp $

    require_once("../../../config.php");
    require_once($CFG->libdir.'/tablelib.php');
    require_once('../../monitoring/lib.php');
    require_once('../../mou_att/lib_att.php');
    require_once('../lib_ege.php');

    
    $rid = required_param('rid', PARAM_INT);          // Rayon id
    $sid = required_param('sid', PARAM_INT);       // School id
	// $cid = required_param('cid', PARAM_INT);		  // Curriculum id
    $gid = required_param('gid', PARAM_INT);          // Class id
    $newuser = optional_param('newuser', false);  // Add new user

    $yid = optional_param('yid', '0', PARAM_INT);       // Year id

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
		listbox_rayons("classpupils.php?sid=0&amp;yid=$yid&amp;gid=0&amp;rid=", $rid);
		listbox_schools("classpupils.php?rid=$rid&amp;yid=$yid&amp;gid=0&amp;sid=", $rid, $sid, $yid);
	    listbox_class("classpupils.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=", $rid, $sid, $yid, $gid);
		echo '</table>';

	} else  if ($rayon_operator_is)  {

		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
		listbox_schools("classpupils.php?rid=$rid&amp;yid=$yid&amp;gid=0&amp;sid=", $rid, $sid, $yid);
	    listbox_class("classpupils.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=", $rid, $sid, $yid, $gid);
		echo '</table>';

	} else  if ($school_operator_is)  {
		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
	    listbox_class("classpupils.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=", $rid, $sid, $yid, $gid);
		echo '</table>';
	}

	if ($rid != 0 && $sid != 0 && $yid != 0 && $gid != 0)  {

	    $currenttab = 'listclass';
	    include('tabsclass.php');


			$disciplines =  get_records_sql ("SELECT id, yearid, name  FROM  {$CFG->prefix}monit_school_discipline_ege
											  WHERE yearid=$yid ORDER BY name");
			if ($disciplines)	{
				$listegeids = array();
				foreach ($disciplines as $discipline) 	{
					$listegeids [$discipline->id] = $discipline->name;
				}
			}


	    $strnever = get_string('never');

        $strdisciplines = get_string('disciplines_ege', 'block_mou_ege');
			if ($is9class)	{
		        $tablecolumns = array('picture', 'fullname', 'username', 'pswtxt', 'disciplines',  'timeappeal', '');
		        $tableheaders = array('', get_string('fullname'), get_string('username'),
		        						get_string('startpassword', 'block_mou_att'),
		        						$strdisciplines, get_string('datetimeappelant', 'block_mou_ege'),
		        						get_string('action'));
		    } else {
		        $tablecolumns = array('picture', 'fullname', 'username', 'pswtxt', 'email',  '');
		        $tableheaders = array('', get_string('fullname'), get_string('username'),
		        						get_string('startpassword', 'block_mou_att'),
		        						get_string('email'), get_string('action'));
		    }
		    $table->class = 'moutable';

	    // Should use this variable so that we don't break stuff every time a variable is added or changed.
	    $baseurl = $CFG->wwwroot."/blocks/mou_ege/class/classpupils.php?sid=$sid&amp;rid=$rid&amp;yid=$yid&amp;gid=$gid";

        $table = new flexible_table("user-index-$gid");

	    $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
		// $table->column_style_all('align', 'left');

        $table->define_baseurl($baseurl);

        $table->sortable(true, 'lastname');
		// $table->sortable(true, 'lastaccess', SORT_DESC);

        $table->set_attribute('cellspacing', '0');
		// $table->set_attribute('align', 'left');
        $table->set_attribute('id', 'students');
        $table->set_attribute('class', 'generaltable generalbox');

        $table->setup();

	    if($whereclause = $table->get_sql_where()) {
            $whereclause .= ' AND ';
        }
        $studentsql = "SELECT u.id, u.username, u.firstname, u.lastname, u.email, u.maildisplay,
							  u.city, u.country, u.lastlogin as disciplines , u.picture, u.lang,
							  u.timezone as pswtxt, u.timemodified as timeappeal, u.lastaccess, m.classid, m.yearid, m.deleted
                            FROM {$CFG->prefix}user u
                       LEFT JOIN {$CFG->prefix}monit_school_pupil_card m ON m.userid = u.id ";

        $whereclause .= 'classid = '.$gid.' AND ';

	    $studentsql .= 'WHERE '.$whereclause.' u.deleted = 0 AND u.confirmed = 1';


        if($sortclause = $table->get_sql_sort()) {
            $studentsql .= ' ORDER BY '.$sortclause;
        }

		// print_r($studentsql); echo '<hr>';
        $students = get_records_sql($studentsql);

        if(!empty($students)) {

            if ($mode == 6) {
                foreach ($students as $key => $student) {
                    print_user($student, $course);
                }
            }
			else {
                foreach ($students as $student) {

                    if ($student->lastaccess) {
                        $lastaccess = format_time(time() - $student->lastaccess);
                    } else {
                        $lastaccess = $strnever;
                    }


				    $pupil = get_record('monit_school_pupil_card', 'userid', $student->id, 'yearid', $yid);

					$list_disc = get_list_discipline($listegeids, $pupil->listegeids, $pupil->listdatesids);


					$title = get_string('editprofilepupil','block_mou_ege');
					$strlinkupdate = "<a href=\"{$CFG->wwwroot}/blocks/mou_ege/pupils/pupilcard.php?mode=4&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid&amp;uid={$student->id}\">";
					$strlinkupdate .= "<img src=\"{$CFG->pixpath}/i/edit.gif\" alt=\"$title\" /></a>&nbsp;";
/*
					$title = get_string('pupilleaveschool','block_mou_ege');
				    $strlinkupdate .= "<a title=\"$title\" href=\"{$CFG->wwwroot}/blocks/mou_ege/pupils/leaveschool.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid&amp;uid={$student->id}&amp;sesskey=$USER->sesskey\">";
					$strlinkupdate .= "<img src=\"{$CFG->wwwroot}/blocks/mou_ege/i/leave.png\" alt=\"$title\" /></a>&nbsp;";

					$title = get_string('pupilmoveschool','block_mou_ege');
					$strlinkupdate .= "<a title=\"$title\" href=\"{$CFG->wwwroot}/blocks/mou_ege/class/question_to_move.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid&amp;uid={$student->id}&amp;sesskey=$USER->sesskey\">";
				    //$strlinkupdate .= "<a title=\"$title\" href=\"{$CFG->wwwroot}/blocks/mou_ege/pupils/movepupil.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid&amp;uid={$student->id}&amp;sesskey=$USER->sesskey\">";
					$strlinkupdate .= "<img src=\"{$CFG->wwwroot}/blocks/mou_ege/i/btn_move.png\" alt=\"$title\" /></a>&nbsp;";
*/      
                    $strfullname = fullname($student);
                    if ($student->deleted == 1) {
                        $strfullname .= ' (УДАЛЁН ИЗ БД!!!)';
                    }
                    
					if ($is9class)	{
						$title = get_string('markspupil', 'block_mou_ege');
						$strlinkupdate .= "<a title=\"$title\" href=\"pupilmarks.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid&amp;uid={$student->id}\">";
						$strlinkupdate .= "<img src=\"{$CFG->wwwroot}/blocks/mou_ege/i/mark.png\" alt=\"$title\" /></a>&nbsp;";

						$title = get_string('putappeal', 'block_mou_ege');
						$strlinkupdate .= "<a title=\"$title\" href=\"appeal_oper.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid&amp;uid={$student->id}\">";
						$strlinkupdate .= "<img src=\"{$CFG->wwwroot}/blocks/mou_ege/i/a.png\" alt=\"$title\" /></a>&nbsp;";

						$strcolumn5 = "<div align=left>$list_disc</div>";
					}  else {
						$strcolumn5 = $student->email;
					}
/*
					$title = get_string('deleteprofilepupil','block_mou_ege');
				    $strlinkupdate .= "<a title=\"$title\" href=\"{$CFG->wwwroot}/blocks/mou_ege/pupils/delpupil.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid&amp;uid={$student->id}&amp;sesskey=$USER->sesskey\">";
					$strlinkupdate .= "<img src=\"{$CFG->pixpath}/i/cross_red_big.gif\" alt=\"$title\" /></a>&nbsp;";
*/
					if ($is9class)	{
			            $timeappeal = '-';
				    	if ($appeals = get_records('monit_appeal', 'userid', $student->id, 'codepredmet'))	{
				    		$timeappeal = '';
				    		foreach ($appeals as $appeal)	{
					    	    if ($appeal->timeappeal > 0)	{
						    		$timeappeal .= userdate($appeal->timeappeal, get_string('strftimedaydatetime1', 'block_mou_ege')) . '<br>';
						    	}
						    }
				    	}
	                    $table->add_data(array (print_user_picture($student->id, 1, $student->picture, false, true),
									    "<div align=left><strong><a href=\"{$CFG->wwwroot}/blocks/mou_ege/pupils/pupilcard.php?mode=4&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid&amp;uid={$student->id}\">".$strfullname."</a></strong></div>",
									    $student->username,
									    $pupil->pswtxt,
	                                    $strcolumn5,
	                                    $timeappeal,
	                                    $strlinkupdate));
	                } else {
	                    $table->add_data(array (print_user_picture($student->id, 1, $student->picture, false, true),
									    "<div align=left><strong><a href=\"{$CFG->wwwroot}/blocks/mou_ege/pupils/pupilcard.php?mode=4&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid&amp;uid={$student->id}\">".$strfullname."</a></strong></div>",
									    $student->username,
									    $pupil->pswtxt,
	                                    $strcolumn5,
	                                    $strlinkupdate));
	                }
                }
				$class = get_record('monit_school_class', 'id', $gid);
				print_heading(get_string('class', 'block_mou_ege') . ': '. $class->name, "center", 3);
		    	echo '<div align=center>';
				$table->print_html();
	        	echo '</div>';
			}

		}

		?>	<table align="center">
			<tr>

<?php
	 // if ($admin_is || $region_operator_is) {
/*
	?>
		 <td>
		  <form name="adduser" method="post" action="choice.php?mode=4&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid">
			    <div align="center">
				<input type="hidden" name="rid" value="<?php echo $rid ?>" />
				<input type="hidden" name="sid" value="<?php echo $sid ?>" />
				<input type="hidden" name="yid" value="<?php echo $yid ?>" />
				<input type="hidden" name="gid" value="<?php echo $gid ?>" />
			    <input type="hidden" name="newuser" value="true" />
				<input type="hidden" name="sesskey" value="<?php echo $USER->sesskey ?>">
				<input type="submit" name="addteacher" value="<?php print_string('addpupil','block_mou_ege')?>">
			    </div>
		  </form>
		  </td>
<?php
	// }
*/	
	?>

			<td>
			<form name="download" method="post" action="classpupils.php">
			    <div align="center">
				<input type="hidden" name="rid" value="<?php echo $rid ?>" />
				<input type="hidden" name="sid" value="<?php echo $sid ?>" />
				<input type="hidden" name="yid" value="<?php echo $yid ?>" />
				<input type="hidden" name="gid" value="<?php echo $gid ?>" />
				<input type="hidden" name="sesskey" value="<?php echo $USER->sesskey ?>">
				<input type="hidden" name="action" value="excel" />
				<input type="submit" name="downloadexcel" value="<?php print_string('downloadexcel_class', 'block_mou_ege')?>">
			    </div>
		  </form>
			</td>
 		  </tr>
		  </table>
			<?php
	}

	$remark = get_string('remarkmuppupil', 'block_mou_ege');
	
	// http://mou.bsu.edu.ru/blocks/mou_school/class/classpupils.php?rid=1&sid=1436&yid=3&gid=4082
	echo "<a href=\"{$CFG->wwwroot}/blocks/mou_school/class/classpupils.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid\"> $remark </a>";  
	
    print_footer();


function classpupils_download($rid, $sid, $gid)
{
    global $CFG, $yid;

        require_once("$CFG->libdir/excel/Worksheet.php");
        require_once("$CFG->libdir/excel/Workbook.php");

	    $rayon = get_record('monit_rayon', 'id', $rid);

	    $school = get_record('monit_school', 'id', $sid);

		$class = get_record('monit_school_class', 'id', $gid);

		$is9class = is9class($class->name);

	    $txtl = new textlib();
   		$strwin1251 =  $txtl->convert($class->name, 'utf-8', 'windows-1251');

		// HTTP headers
        header("Content-type: application/vnd.ms-excel");
        $downloadfilename = clean_filename("class_".$rid. '_' . $sid . '_' . $strwin1251);
        header("Content-Disposition: attachment; filename=\"{$downloadfilename}.xls\"");
        header("Expires: 0");
        header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
        header("Pragma: public");

		/// Creating a workbook
        $workbook = new Workbook("-");
        $myxls =& $workbook->add_worksheet($strwin1251);

		/// Print names of all the fields
		$formath1 =& $workbook->add_format();
		$formath2 =& $workbook->add_format();
		$formatp =& $workbook->add_format();

		$formath1->set_size(12);
	    $formath1->set_align('center');
	    $formath1->set_align('vcenter');
		$formath1->set_color('black');
		$formath1->set_bold(1);
		$formath1->set_italic();
		$formath1->set_text_wrap();
		// $formath1->set_border(2);

		$formath2->set_size(11);
	    $formath2->set_align('center');
	    $formath2->set_align('vcenter');
		$formath2->set_color('black');
		$formath2->set_bold(1);
		//$formath2->set_italic();
		$formath2->set_border(2);
		$formath2->set_text_wrap();

		$formatp->set_size(11);
	    $formatp->set_align('left');
	    $formatp->set_align('vcenter');
		$formatp->set_color('black');
		$formatp->set_bold(0);
		$formatp->set_border(1);
		$formatp->set_text_wrap();

		$myxls->set_column(0,0,4);
		$myxls->set_column(1,1,29);
		$myxls->set_column(2,2,12);
		$myxls->set_column(3,3,10);
		$myxls->set_row(0, 70);
		if 	($is9class)	{
			$myxls->set_column(4,4,42);
			$myxls->set_column(5,5,10);
			$myxls->set_row(0, 30);
		}


	    $strtitle =  $rayon->name .', ';
		$strwin1251 =  $txtl->convert($strtitle, 'utf-8', 'windows-1251');
	    $myxls->write_string(0, 0, $strwin1251, $formath1);
		if 	($is9class)	{
			$myxls->merge_cells(0, 0, 0, 5);
		} else {
			$myxls->merge_cells(0, 0, 0, 3);
		}

		$myxls->set_row(1, 50);
	    $strtitle =  $school->name . ', '. get_string('class','block_mou_ege').' '.$class->name;
		$strwin1251 =  $txtl->convert($strtitle, 'utf-8', 'windows-1251');
	    $myxls->write_string(1, 0, $strwin1251, $formath1);
		if 	($is9class)	{
			$myxls->merge_cells(1, 0, 1, 5);
		} else {
			$myxls->merge_cells(1, 0, 1, 3);
		}

   		$strwin1251 =  $txtl->convert('№', 'utf-8', 'windows-1251');
        $myxls->write_string(2, 0,  $strwin1251, $formath2);

   		$strwin1251 =  $txtl->convert(get_string('pupil_fio', 'block_mou_ege'), 'utf-8', 'windows-1251');
        $myxls->write_string(2, 1, $strwin1251, $formath2);

   		$strwin1251 =  $txtl->convert(get_string('username'), 'utf-8', 'windows-1251');
        $myxls->write_string(2, 2, $strwin1251, $formath2);

   		$strwin1251 =  $txtl->convert(get_string('startpassword', 'block_mou_att'), 'utf-8', 'windows-1251');
        $myxls->write_string(2, 3, $strwin1251, $formath2);

		if 	($is9class)	{
	   		$strwin1251 =  $txtl->convert(get_string('disciplines_ege', 'block_mou_ege'), 'utf-8', 'windows-1251');
	        $myxls->write_string(2, 4, $strwin1251, $formath2);

	   		$strwin1251 =  $txtl->convert(get_string('pupil_sign', 'block_mou_ege'), 'utf-8', 'windows-1251');
	        $myxls->write_string(2, 5, $strwin1251, $formath2);
	    }

		  // get_string('city'), get_string('country'), get_string('lastaccess'));

        $studentsql = "SELECT u.id, u.username, u.firstname, u.lastname, u.email, u.maildisplay,
							  u.city, u.country, u.lastlogin, u.picture, u.lang, u.timezone,
                              u.lastaccess, m.classid
                            FROM {$CFG->prefix}user u
                       LEFT JOIN {$CFG->prefix}monit_school_pupil_card m ON m.userid = u.id
                       WHERE classid = $gid AND u.deleted = 0 AND u.confirmed = 1
                       ORDER BY u.lastname";

 	 // print_r($studentsql);
        $students = get_records_sql($studentsql);


			$disciplines =  get_records_sql ("SELECT id, yearid, name  FROM  {$CFG->prefix}monit_school_discipline_ege
											  WHERE yearid=$yid ORDER BY name");
			if ($disciplines)	{
				$listegeids = array();
				foreach ($disciplines as $discipline) 	{
					$listegeids [$discipline->id] = $discipline->name;
				}
			}

        if(!empty($students)) {
             $i = 2;
             foreach ($students as $student) {
		    	$pupil = get_record('monit_school_pupil_card', 'userid', $student->id, 'yearid', $yid);

				if 	($is9class)	{
			    	$list_disc = '';
				    if (!empty($pupil->listegeids))	{
				    	$pli = explode(',', $pupil->listegeids);
				    	foreach ($pli as $pli1)	{
				    	    if ($pli1 != 0) {
					    		$list_disc .= $listegeids[$pli1] . ', ';
					    	}
				    	}
				    	if ($list_disc != '')  {
				    		$list_disc = substr($list_disc, 0, strlen($list_disc)- 2);
				    	}

				    }
				    if ($list_disc == '')  $list_disc = '-';
				}


			    $i++;
    	       	$myxls->write_string($i,0,($i-2).'.',$formatp);

		   		$strwin1251 =  $txtl->convert(fullname($student), 'utf-8', 'windows-1251');
        	    $myxls->write_string($i, 1, $strwin1251,$formatp);

		   		$strwin1251 =  $txtl->convert($student->username, 'utf-8', 'windows-1251');
        	    $myxls->write_string($i, 2, $strwin1251,$formatp);

		   		$strwin1251 =  $txtl->convert($pupil->pswtxt, 'utf-8', 'windows-1251');
        	    $myxls->write_string($i, 3, $strwin1251,$formatp);

				if 	($is9class)	{
			   		$strwin1251 =  $txtl->convert($list_disc, 'utf-8', 'windows-1251');
	    	       	$myxls->write_string($i, 4, $strwin1251, $formatp);

			   		$strwin1251 =  $txtl->convert(' ', 'utf-8', 'windows-1251');
	           	    $myxls->write_string($i, 5, $strwin1251, $formatp);
	           	}
	 		 }
	  	     $i++;

	   		 $strwin1251 =  $txtl->convert(get_string('vsego','block_mou_ege'), 'utf-8', 'windows-1251');
  	   		 $myxls->write_string($i, 2, $strwin1251, $formath1);

       		 $myxls->write_formula($i, 3, "=COUNTA(D4:D$i)", $formath1);
		}

       $workbook->close();
       exit;
}

?>


