<?PHP // $Id: checkappeal.php,v 1.21 2010/06/17 10:10:45 Shtifanov Exp $

    require_once('../../../config.php');
    require_once('../../monitoring/lib.php');
    require_once('../lib_ege.php');

    $yid = optional_param('yid', 0, PARAM_INT);       // Year id
	$rid = optional_param('rid', 0, PARAM_INT);
	$sid = optional_param('sid', 0, PARAM_INT);
	$gid = optional_param('gid', 0, PARAM_INT);
    $did = optional_param('did', 0, PARAM_INT);       // Discipline id
	$uid = optional_param('uid', 0, PARAM_INT);
   	$level = optional_param('level', 'school');
    $page  = optional_param('page', 0, PARAM_INT);
    $perpage = optional_param('perpage', 30, PARAM_INT);        // how many per page
	$action = optional_param('action', '');
    $numday = optional_param('nd', 1, PARAM_INT);       // numday

    require_login();

    if ($yid == 0)	{
    	$yid = get_current_edu_year_id();
    }


    switch ($level)	{
		case 'region':
		break;
		case 'rayon':
					    $rid = required_param('rid', PARAM_INT);       // Rayon id
		break;
		case 'school':
					    $rid = required_param('rid', PARAM_INT);       // Rayon id
					    $sid = required_param('sid', PARAM_INT);       // School id
		break;
		case 'class':
					    $rid = required_param('rid', PARAM_INT);       // Rayon id
					    $sid = required_param('sid', PARAM_INT);       // School id
					    $gid = required_param('gid', PARAM_INT);       // Class id
		break;
    }

	$redirlink = "checkappeal.php?level=$level&rid=$rid&yid=$yid&sid=$sid&did=$did";
    if ($action == 'ok')	{
	   	  $tappeal = 0;
	      if (!$discipline_ege = get_record('monit_school_discipline_ege', 'id', $did))	{
		    	error('Discipline not found! (maxtime)', $redirlink);
 	      }
 	      $timeload = 0;
 	      if ($gia_result = get_record('monit_gia_results', 'yearid', $yid, 'codepredmet', $discipline_ege->code, 'userid', $uid))	{
				$giadate = get_record_select('monit_school_gia_dates', "yearid=$yid AND discegeid={$discipline_ege->id} AND timeload = {$gia_result->timeload}");
				$timeload = $giadate ->timeload;   	      	
 	      }

 	     // print_r ($discipline_ege); echo '<hr>';
	   	  if ($timeload != 0)	{
	    	  $maxtime = get_record_sql("SELECT Max(timeappeal) as max FROM {$CFG->prefix}monit_appeal
								    	 WHERE yearid = $yid AND codepredmet = {$discipline_ege->code} AND timemodified = {$gia_result->timeload}");
	    	  // print_r ($maxtime); echo '<hr>';
	    	  if ($maxtime->max == 0)	{
			        $tappeal = $giadate->timestartappealhearing;
	    	  } else {

		          $arrdate = usergetdate($maxtime->max);
		          $hour = $arrdate['hours'];
		          $minut = $arrdate['minutes'];
                  if ($hour == 13 && $minut >= 00)	{
					 $tappeal = $maxtime->max + HOURSECS; // HOURSECS + HOURSECS/2
				  }
				  else if ($hour == 18 && $minut >= 00)	{
   					 $tappeal = $maxtime->max + 15*HOURSECS; // 16*HOURSECS +  HOURSECS/2
				  } else {
		    	   	 $tappeal = $maxtime->max + $giadate->deltatime*MINSECS; // 3 minute
		    	  }
		      }

		      // Check Sunday
		      $numdayinweek = date('w', $tappeal);
			  if ($numdayinweek == 0) 	{
					$tappeal += DAYSECS;
		  	  }
		  	  /*
		  	  } else if ($numdayinweek == 6) 	{
					$tappeal += 2*DAYSECS;
		  	  }
              */

	       	  set_field('monit_appeal', 'timeappeal', $tappeal, 'yearid', $yid, 'userid', $uid, 'codepredmet', $discipline_ege->code);
 	      	  set_field('monit_appeal', 'status', 6, 'yearid', $yid, 'userid', $uid, 'codepredmet', $discipline_ege->code);
 	      }  else {
 	      	error (get_string('errornotsetstartappeal', 'block_mou_ege'), $redirlink);
 	      }
    } else if ($action == 'break')	{
	      if (!$discipline_ege = get_record('monit_school_discipline_ege', 'id', $did))	{
		    	error('Discipline not found! (maxtime)', $redirlink);
 	      }

       	  set_field('monit_appeal', 'timeappeal', 0, 'yearid', $yid, 'userid', $uid, 'codepredmet', $discipline_ege->code);
       	  set_field('monit_appeal', 'status', 8, 'yearid', $yid, 'userid', $uid, 'codepredmet', $discipline_ege->code);
    }


	$action = optional_param('action', '');
	$tm = optional_param('tm', 0, PARAM_INT);

    if ($action == 'excel') {
	    $table = table_checkappeal ($level, $yid, $did, $rid, $sid, 0, '', '', $tm);
    	// print_r($table);
        print_table_to_excel($table, 1);
        exit();
	} else  if ($action == 'csv') {
	    print_appeal_to_csv($level, $yid, $did, 0, 0, 0, $tm);
        exit();
    }

	require_login();

	$admin_is = isadmin();
	$region_operator_is = ismonitoperator('region');
	$rayon_operator_is  = ismonitoperator('rayon', 0, 0, 0, true);
	if (!$admin_is && !$region_operator_is && !$rayon_operator_is) {
        error(get_string('adminaccess', 'block_monitoring'), "$CFG->wwwroot/login/index.php");
	}

	if (isregionviewoperator())  {
        error(get_string('adminaccess', 'block_monitoring'));
	}


/*
	$admin_is = isadmin();
	$region_operator_is = ismonitoperator('region');
	$rayon_operator_is  = ismonitoperator('rayon', 0, 0, 0, true);
	$school_operator_is = ismonitoperator('school', 0, $rid, $sid);
	if (!$admin_is && !$region_operator_is && !$rayon_operator_is && !$school_operator_is) {
        error(get_string('adminaccess', 'block_monitoring'));
	}

	if (!$admin_is && !$region_operator_is && !$rayon_operator_is && $school_operator_is)  {
	      if ($school = get_record_sql ("SELECT id, uniqueconstcode, name FROM {$CFG->prefix}monit_school
	                                     WHERE rayonid=$rid AND uniqueconstcode=$sid AND yearid=$yid"))	{
	     		$sid = $school->id;
	      } else {
				$school = get_record('monit_school', 'id', $sid);
	      }
	}
*/
	// $strclasses = get_string('classes','block_mou_ege');
	$strclasses = get_string('school','block_monitoring');
	$strmarks = get_string('checkappeal','block_mou_ege');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= " -> $strmarks";
    print_header_mou("$SITE->shortname: $strmarks", $SITE->fullname, $breadcrumbs);

	print_tabs_years($yid, "checkappeal.php?rid=$rid&amp;sid=$sid&amp;yid=");

    $currenttab = 'appeal'.$level;
    include('tabs2.php');

    switch ($level)	{
		case 'region':  if ($admin_is || $region_operator_is) 	{
      						echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
							listbox_discipline_ege("checkappeal.php?level=region&amp;yid=$yid&amp;did=", $rid, $sid, $yid, $did);
							echo '</table>';

						 	if ($did != 0)  {
						 		
								listbox_numday("checkappeal.php?level=region&amp;yid=$yid&amp;did=$did", $numday, $yid);

							    if (!$discipline_ege = get_record('monit_school_discipline_ege', 'id', $did))	{
							    	error('Discipline not found!', $redirlink);
							    }
							    
							    
							    $timeload_mark = get_timeload_for_discipline ($yid, $did, $numday);
						    
								// print_r($dates_gia); echo '<hr>';
								// echo $timeload_mark; echo '<hr>';
								
								$usercount = count_records_sql("SELECT COUNT(*) FROM {$CFG->prefix}monit_appeal
																WHERE yearid=$yid AND codepredmet={$discipline_ege->code} AND timemodified = $timeload_mark");
								if ($usercount/$perpage > 30) 	{
									$perpage = round($usercount/30);
								}
								$table = table_checkappeal($level, $yid, $did, 0, 0, 0, $page, $perpage, $timeload_mark);

							    print_paging_bar($usercount, $page, $perpage, "checkappeal.php?level=region&amp;yid=$yid&amp;perpage=$perpage&amp;did=$did&amp;nd=$numday&amp;");
								print_color_table($table);
							    print_paging_bar($usercount, $page, $perpage, "checkappeal.php?level=region&amp;yid=$yid&amp;perpage=$perpage&amp;did=$did&amp;nd=$numday&amp;");

						   		$options = array('rid' => $rid, 'sid' => $sid, 'did' => $did, 'yid' => $yid, 
								   				 'level' => 'region', 'action' => 'excel', 'nd' => $numday, 'tm' => $timeload_mark);     
								echo '<table align="center" border=0><tr><td>';
							    print_single_button("checkappeal.php", $options, get_string('downloadexcel'));
	   							echo '</td><td>';
	   							$options ['action'] = 'csv';
							    print_single_button("checkappeal.php", $options, get_string('downloadcsv', 'block_mou_ege'));
	   							echo '</td></tr></table>';
						    }
						}
		break;

		case 'rayon':   if ($admin_is || $region_operator_is) 	{
							echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
							listbox_rayons("checkappeal.php?level=rayon&amp;yid=$yid&amp;rid=", $rid);
							listbox_discipline_ege("checkappeal.php?level=rayon&amp;rid=$rid&amp;yid=$yid&amp;did=", $rid, $sid, $yid, $did);
							echo '</table>';
						}  else if ($rayon_operator_is)	{
							echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
							listbox_discipline_ege("checkappeal.php?level=rayon&amp;rid=$rid&amp;yid=$yid&amp;did=", $rid, $sid, $yid, $did);
							echo '</table>';
						}

					 	if ($rid != 0 && $did != 0)  {
					 		
					 		listbox_numday("checkappeal.php?level=rayon&amp;rid=$rid&amp;yid=$yid&amp;did=$did", $numday, $yid);

						    if (!$discipline_ege = get_record('monit_school_discipline_ege', 'id', $did))	{
						    	error('Discipline not found!', $redirlink);
						    }
						    
						    $timeload_mark = get_timeload_for_discipline ($yid, $did, $numday);
						    
							$usercount = count_records_sql("SELECT COUNT(*) FROM {$CFG->prefix}monit_appeal
															WHERE rayonid=$rid AND yearid=$yid AND codepredmet={$discipline_ege->code} AND timemodified = $timeload_mark");
							if ($usercount/$perpage > 30) 	{
								$perpage = round($usercount/30);
							}

							$table = table_checkappeal ($level, $yid, $did, $rid, 0, 0, $page, $perpage, $timeload_mark);

						    print_paging_bar($usercount, $page, $perpage, "checkappeal.php?level=rayon&amp;rid=$rid&amp;yid=$yid&amp;perpage=$perpage&amp;did=$did&amp;");
							print_color_table($table);
						    print_paging_bar($usercount, $page, $perpage, "checkappeal.php?level=rayon&amp;rid=$rid&amp;yid=$yid&amp;perpage=$perpage&amp;did=$did&amp;");

					   		$options = array('rid' => $rid, 'sid' => $sid, 'did' => $did, 'yid' => $yid, 
							   				 'level' => 'rayon', 'action' => 'excel', 'nd' => $numday, 'tm' => $timeload_mark);     
			   		
							echo '<table align="center" border=0><tr><td>';
						    print_single_button("checkappeal.php", $options, get_string("downloadexcel"));
   							echo '</td></tr></table>';
					    }
		break;

		case 'school':  if ($admin_is || $region_operator_is) 	{
							echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
							listbox_rayons("checkappeal.php?level=school&amp;sid=0&amp;yid=$yid&amp;rid=", $rid);
							listbox_schools("checkappeal.php?level=school&amp;rid=$rid&amp;yid=$yid&amp;sid=", $rid, $sid, $yid);
							listbox_discipline_ege("checkappeal.php?level=school&amp;rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;did=", $rid, $sid, $yid, $did);
							echo '</table>';
						} else  if ($rayon_operator_is)  {
							echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
							listbox_schools("checkappeal.php?level=school&amp;rid=$rid&amp;yid=$yid&amp;sid=", $rid, $sid, $yid);
							listbox_discipline_ege("checkappeal.php?level=school&amp;rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;did=", $rid, $sid, $yid, $did);
							echo '</table>';
						}  else if ($school_operator_is) {
							print_heading($strclasses.': '.$school->name, "center", 3);
							echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
							listbox_discipline_ege("checkappeal.php?level=school&amp;rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;did=", $rid, $sid, $yid, $did);
							echo '</table>';
						}

					 	if ($rid != 0 && $sid != 0 && $did != 0)  {
					 		
					 		listbox_numday("checkappeal.php?level=school&amp;rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;did=$did", $numday, $yid);
					 		
					 		$timeload_mark = get_timeload_for_discipline ($yid, $did, $numday);
					 		
							$table = table_checkappeal ($level, $yid, $did, $rid, $sid, 0, '', '', $timeload_mark);
					
							print_color_table($table);
					   		$options = array('rid' => $rid, 'sid' => $sid, 'did' => $did, 'yid' => $yid, 
							   				 'level' => 'school', 'action' => 'excel', 'nd' => $numday, 'tm' => $timeload_mark);     
							echo '<table align="center" border=0><tr><td>';
						    print_single_button("checkappeal.php", $options, get_string("downloadexcel"));
   							echo '</td></tr></table>';
					    }
		break;
    }

	print_footer();


function table_checkappeal ($level, $yid, $did, $rid = 0, $sid = 0, $gid = 0, $page = '', $perpage = '', $timeload_mark = 0)
{
	global $CFG, $admin_is, $region_operator_is, $page, $numday;

    if (!$discipline_ege = get_record('monit_school_discipline_ege', 'id', $did))	{
    	error('Discipline not found!');
    }

	$strappeal = get_string('appeal', 'block_mou_ege');
    $strstatus = get_string('status', 'block_monitoring');

    $table->head  = array ($strstatus, get_string('number','block_monitoring'), get_string('school', 'block_monitoring'),
    					   get_string('class', 'block_mou_ege'), get_string('fullname'), // get_string('firstandsecondname', 'block_monitoring'),
    					   $strappeal,  get_string('datetimeappelant', 'block_mou_ege'), get_string("action","block_mou_ege"));

	$table->align = array ('center', 'center', 'left', 'center',  "left",  'left', 'center', 'center');
	$table->columnwidth = array (10, 7, 14, 7, 25, 14, 10, 7);
    // $table->datatype = array ('char', 'char');
    $table->class = 'moutable';
   	$table->width = '95%';
    // $table->size = array ('10%', '10%');
    $table->titles = array();
    $table->titles[] = get_string('checkappeal', 'block_mou_ege');
    $table->worksheetname = $level;

	$strsqlresults = "SELECT *  FROM {$CFG->prefix}monit_appeal ";
	$strsqlschools = "SELECT id, name FROM {$CFG->prefix}monit_school ";
	$strsqlclasses = "SELECT id, name FROM {$CFG->prefix}monit_school_class	";

    switch ($level)	{
		case 'region':
						$strsqlresults .= " WHERE yearid=$yid AND codepredmet={$discipline_ege->code} AND timemodified = $timeload_mark
										 	ORDER BY rayonid, schoolid"; // ORDER BY timeappeal";
						$strsqlschools .= " WHERE isclosing=0 AND yearid=$yid ";
						$strsqlclasses .= " WHERE yearid=$yid ";

						$table->titles[] = get_string('nameregion', 'block_mou_ege');
						$table->downloadfilename = 'appeal_region';
		break;
		case 'rayon':
						$strsqlresults .= " WHERE rayonid=$rid AND yearid=$yid AND codepredmet={$discipline_ege->code}  AND timemodified = $timeload_mark
											ORDER BY schoolid, classid";
						$strsqlschools .= " WHERE rayonid = $rid AND isclosing=0 AND yearid=$yid ";
						$strsqlclasses .= " WHERE yearid=$yid ";

					    $rayon = get_record('monit_rayon', 'id', $rid);
						$table->titles[] = $rayon->name;
						$table->downloadfilename = 'appeal_rayon_'.$rid;
		break;
		case 'school':
						$strsqlresults .= " WHERE rayonid=$rid AND yearid=$yid AND schoolid=$sid AND codepredmet={$discipline_ege->code}  AND timemodified = $timeload_mark
									 		ORDER BY classid, userid";
						$strsqlschools .= " WHERE id=$sid ";
						$strsqlclasses .= " WHERE schoolid=$sid AND yearid=$yid ";

					    $rayon = get_record('monit_rayon', 'id', $rid);
					    $school = get_record('monit_school', 'id', $sid);
	                	$table->titles[] = $school->name . " ({$rayon->name})";
						$table->downloadfilename = 'appeal_school_'.$sid;
		break;
    }

	$table->titles[] = $discipline_ege->name;
    $table->titlesrows = array(30, 30, 30, 30);

	$schoolsarray = array();
 	if ($schools = get_records_sql($strsqlschools))	{
	    foreach ($schools as $sa)  {
	        $schoolsarray[$sa->id] = $sa->name;
	    }
	}

    $classesarray = array();
 	if ($classes = get_records_sql($strsqlclasses))	{
	    foreach ($classes as $class)  {
	        $classesarray[$class->id] = $class->name;
	    }
	}

    // print_r($schoolsarray); echo '<hr>';

    // echo $strsqlresults;

 	if ($gia_appeals = get_records_sql($strsqlresults, $page*$perpage, $perpage))	{
        require_once($CFG->libdir.'/filelib.php');
 		$i = $page*$perpage + 1;
        foreach ($gia_appeals as $gia)	{
            $user = get_record_sql("SELECT id, lastname, firstname FROM  {$CFG->prefix}user WHERE id = {$gia->userid}");

            $file = basename($gia->fullpath);
            $icon = mimeinfo('icon', $file);
			$filearea = "1/appeal/{$gia->schoolid}/{$gia->userid}/$did";
            if ($CFG->slasharguments) {
                $ffurl = "$CFG->wwwroot/file.php/$filearea/$file";
            } else {
                $ffurl = "$CFG->wwwroot/file.php?file=/$filearea/$file";
            }

            $output = '<a href="'.$ffurl.'" target=_blank>'.
            		  '<img align="middle" src="'.$CFG->pixpath.'/f/'.$icon.'" class="icon" alt="'.$strappeal.'" />'.
            		  $file.'</a><br />';
            if ($gia->status == 6 && $gia->ballold != 0)	{
            	 // $strformrkpu_status .= "({$gia->ballold} --  {$gia->ocenkaold})";
	        	$output .= '<br>';
	        	$output .= get_string('oldresults', 'block_mou_ege', "{$gia->ocenkaold}({$gia->ballold})");

				$newgia = get_record_sql("SELECT id, ocenka, ball FROM {$CFG->prefix}monit_gia_results
										 WHERE yearid=$yid AND codepredmet={$discipline_ege->code} AND userid={$gia->userid}");

	        	$output .= '<br>';
	        	$output .= get_string('newresults', 'block_mou_ege', "{$newgia->ocenka}({$newgia->ball})");
            }


			if 	($admin_is || $region_operator_is)	 {
				$title = get_string('setokappeal', 'block_mou_ege');
				$strlinkupdate = "<a title=\"$title\" href=\"checkappeal.php?action=ok&amp;level=$level&amp;rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;did={$discipline_ege->id}&amp;uid={$gia->userid}&amp;page=$page&amp;nd=$numday\">";
				$strlinkupdate .=  "<img src=\"{$CFG->pixpath}/i/tick_green_big.gif\" alt=\"$title\" /></a>&nbsp;";

				$title = get_string('breakappeal','block_mou_ege');
		  	 	$strlinkupdate .= "<a title=\"$title\" href=\"checkappeal.php?action=break&amp;level=$level&amp;rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;did={$discipline_ege->id}&amp;uid={$gia->userid}&amp;page=$page&amp;nd=$numday\">";
				$strlinkupdate .= "<img src=\"{$CFG->pixpath}/i/minus.gif\" alt=\"$title\" /></a>&nbsp;";

				$title = get_string('deleteappeal','block_mou_ege');
		  	 	$strlinkupdate .= "<a title=\"$title\" href=\"checkappeal.php?action=break&amp;level=$level&amp;rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;did={$discipline_ege->id}&amp;uid={$gia->userid}&amp;page=$page&amp;nd=$numday\">";
				$strlinkupdate .= "<img src=\"{$CFG->pixpath}/i/cross_red_big.gif\" alt=\"$title\" /></a>&nbsp;";
			}
			else	{
				$strlinkupdate = '-';
			}


            $recstatus = get_record('monit_status', 'id', $gia->status);
			$strformrkpu_status = $recstatus->name; // get_string('status'.$gia->status, "block_monitoring");
			$strcolor = $recstatus->color; // get_string('status'.$gia->status.'color',"block_monitoring");

            if ($gia->timeappeal != 0) {
	    		$timeappeal = userdate($gia->timeappeal, get_string('strftimedaydatetime1', 'block_mou_ege'));
	    	} else {
	    		$timeappeal = '-';
	    	}

            $table->data[] = array ($strformrkpu_status, $i++ . '.', $schoolsarray[$gia->schoolid], $classesarray[$gia->classid],
            					fullname($user), $output, $timeappeal, $strlinkupdate);
			$table->bgcolor[] = array ($strcolor);
        }

    }  else 	{
    	$table->data[] = array ();
    }
   // print_r($gia_results);

    return $table;
}


function print_appeal_to_csv($level, $yid, $did, $rid = 0, $sid = 0, $gid = 0, $tm = 0)
{
  global $CFG;

  if (!$discipline_ege = get_record('monit_school_discipline_ege', 'id', $did))	{
    	error('Discipline not found!');
  }

  if ($level == 'region')	{
	$strsqlresults = "SELECT *  FROM {$CFG->prefix}monit_appeal
					  WHERE yearid=$yid AND codepredmet={$discipline_ege->code} AND status=6 AND timemodified = $tm";
 	if ($gia_appeals = get_records_sql($strsqlresults))	 {

	    $filename = 'mou_data_'.userdate(time(),"%d-%m-%Y_%H-%M",99,false);
	    $filename .= '.csv';
	    header("Content-Type: application/download\n");
	    header("Content-Disposition: attachment; filename=$filename");
	    header("Expires: 0");
	    header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
	    header("Pragma: public");

	    echo "yearid;rayonid;schoolid;userid;codepredmet\n";
        foreach ($gia_appeals as $gia)	{
            $text  = $gia->yearid . ';';
            $text .= $gia->rayonid . ';';
            $text .= $gia->schoolid . ';';
            $text .= $gia->userid . ';';
            $text .= $gia->codepredmet . "\n";
		    echo $text;
        }

 	}
    return true;
  } else {
    return false;
  }

}


function listbox_numday($scriptname, $numday, $yid)
{
    global $CFG;

    $strsql = "SELECT COUNT(discegeid) as cnt FROM {$CFG->prefix}monit_school_gia_dates
               where yearid=$yid and  discmiid=0 
               GROUP BY discegeid 
               HAVING COUNT(discegeid)>1";
    if ($cntdates = get_records_sql($strsql))	{
    	$maxcnt = 0;
    	foreach ($cntdates as $cntdate)	 {
    		if ($cntdate->cnt > $maxcnt)	$maxcnt = $cntdate->cnt;
    	}
    }

    $toprow = array();
    for ($i=1; $i<=$maxcnt; $i++)	{
	    $toprow[] = new tabobject($i, $scriptname. "&amp;nd=$i",
    	            get_string('numday_i', 'block_mou_ege', $i));
    }

    $tabs = array($toprow);
    print_tabs($tabs, $numday, NULL, NULL);
}



function get_timeload_for_discipline ($yid, $did, $numday)	
{
  global $CFG;	
	
  $timeload_mark = 0;
  $dates_gia = get_records_sql ("SELECT id, yearid, discegeid, timeload
  								FROM  {$CFG->prefix}monit_school_gia_dates
						  		WHERE yearid=$yid AND discegeid = $did
						  		ORDER BY date_gia");
  if ($dates_gia)	{
    $i = 0;
  	foreach ($dates_gia as $d_gia)	{
  		$i++;
  		if ($i == $numday)	{
	  		$timeload_mark = $d_gia->timeload;
			break;  
  		}
  	}
  }
  
  return $timeload_mark;
 } 

?>
