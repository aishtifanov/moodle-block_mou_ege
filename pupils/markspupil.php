<?PHP // $Id: markspupil.php,v 1.15 2011/06/15 06:54:00 shtifanov Exp $

    require_once('../../../config.php');
    require_once('../../monitoring/lib.php');
    require_once('../lib_ege.php');

    $yid = optional_param('yid', 0, PARAM_INT);       // Year id
    $did = optional_param('did', 0, PARAM_INT);       // Discipline id
   	$level = optional_param('level', 'school');
	$rid = 0;
	$sid = 0;
	$gid = 0;
    $page    = optional_param('page', 0, PARAM_INT);
    $perpage = optional_param('perpage', 30, PARAM_INT);        // how many per page

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


	$action   = optional_param('action', '');
    if ($action == 'excel') {
		$table = table_markspupil ($level, $yid, $did, $rid, $sid, $gid);
    	// print_r($table);
        print_table_to_excel($table);
        exit();
	}


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

	if (!$admin_is && !$region_operator_is && !$rayon_operator_is && $school_operator_is)  {
	      if ($school = get_record_sql ("SELECT id, uniqueconstcode, name FROM {$CFG->prefix}monit_school
	                                     WHERE rayonid=$rid AND uniqueconstcode=$sid AND yearid=$yid"))	{
	     		$sid = $school->id;
	      } else {
				$school = get_record('monit_school', 'id', $sid);
	      }
	}

	// $strclasses = get_string('classes','block_mou_ege');
	$strclasses = get_string('school','block_monitoring');
	$strmarks = get_string('markspupil','block_mou_ege');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= " -> $strmarks";
    print_header_mou("$site->shortname: $strmarks", $site->fullname, $breadcrumbs);


	print_tabs_years_link("markspupil.php?level=$level&amp;gid=$gid", $rid, $sid, $yid);

    $currenttab = 'result'.$level;
    include('tabsmark.php');

    switch ($level)	{
		case 'region':  if ($admin_is || $region_operator_is) 	{
      						echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
							listbox_discipline_ege("markspupil.php?level=region&amp;yid=$yid&amp;did=", $rid, $sid, $yid, $did);
							echo '</table>';

						 	if ($did != 0)  {

							    if (!$discipline_ege = get_record('monit_school_discipline_ege', 'id', $did))	{
							    	error('Discipline not found!');
							    }

								$usercount = count_records_sql("SELECT COUNT(*) FROM {$CFG->prefix}monit_gia_results
																WHERE yearid=$yid AND codepredmet={$discipline_ege->code}");
								if ($usercount/$perpage > 30) 	{
									$perpage = round($usercount/30);
								}
								$table = table_markspupil ($level, $yid, $did, 0, 0, 0, $page, $perpage);

							    print_paging_bar($usercount, $page, $perpage, "markspupil.php?level=region&amp;yid=$yid&amp;perpage=$perpage&amp;did=$did&amp;");
								print_color_table($table);
							    print_paging_bar($usercount, $page, $perpage, "markspupil.php?level=region&amp;yid=$yid&amp;perpage=$perpage&amp;did=$did&amp;");

						   		$options = array('rid' => $rid, 'sid' => $sid, 'did' => $did, 'yid' => $yid, 'level' => 'region', 'action' => 'excel');
								echo '<table align="center" border=0><tr><td>';
							    print_single_button("markspupil.php", $options, get_string("downloadexcel"));
	   							echo '</td></tr></table>';

							    $linkmark = "<a href =\"statsmarkspupil.php?level=region&yid=$yid&tab=ocenka&did=$did\">". get_string('statsmarkspupil', 'block_mou_ege') .'</a';
							    print_heading($linkmark, 'center', 4);

						    }
						}
		break;

		case 'rayon':   if ($admin_is || $region_operator_is) 	{
							echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
							listbox_rayons("markspupil.php?level=rayon&amp;yid=$yid&amp;rid=", $rid);
							listbox_discipline_ege("markspupil.php?level=rayon&amp;rid=$rid&amp;yid=$yid&amp;did=", $rid, $sid, $yid, $did);
							echo '</table>';
						} else  if ($rayon_operator_is)  {
							echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
							listbox_discipline_ege("markspupil.php?level=rayon&amp;rid=$rid&amp;yid=$yid&amp;did=", $rid, $sid, $yid, $did);
							echo '</table>';
						}

					 	if ($rid != 0 && $did != 0)  {

						    if (!$discipline_ege = get_record('monit_school_discipline_ege', 'id', $did))	{
						    	error('Discipline not found!');
						    }
							$usercount = count_records_sql("SELECT COUNT(*) FROM {$CFG->prefix}monit_gia_results
															WHERE rayonid=$rid AND yearid=$yid AND codepredmet={$discipline_ege->code}");
							if ($usercount/$perpage > 30) 	{
								$perpage = round($usercount/30);
							}

							$table = table_markspupil ($level, $yid, $did, $rid, 0, 0, $page, $perpage);

						    print_paging_bar($usercount, $page, $perpage, "markspupil.php?level=rayon&amp;rid=$rid&amp;yid=$yid&amp;perpage=$perpage&amp;did=$did&amp;");
							print_color_table($table);
						    print_paging_bar($usercount, $page, $perpage, "markspupil.php?level=rayon&amp;rid=$rid&amp;yid=$yid&amp;perpage=$perpage&amp;did=$did&amp;");

					   		$options = array('rid' => $rid, 'sid' => $sid, 'did' => $did, 'yid' => $yid, 'level' => 'rayon', 'action' => 'excel');
							echo '<table align="center" border=0><tr><td>';
						    print_single_button("markspupil.php", $options, get_string("downloadexcel"));
   							echo '</td></tr></table>';

						    $linkmark = "<a href =\"statsmarkspupil.php?level=rayon&rid=$rid&yid=$yid&tab=ocenka&did=$did\">". get_string('statsmarkspupil', 'block_mou_ege') .'</a';
						    print_heading($linkmark, 'center', 4);

					    }
		break;

		case 'school':  if ($admin_is || $region_operator_is) 	{
							echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
							listbox_rayons("markspupil.php?level=school&amp;sid=0&amp;yid=$yid&amp;rid=", $rid);
							listbox_schools("markspupil.php?level=school&amp;rid=$rid&amp;yid=$yid&amp;sid=", $rid, $sid, $yid);
							listbox_discipline_ege("markspupil.php?level=school&amp;rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;did=", $rid, $sid, $yid, $did);
							echo '</table>';
						} else  if ($rayon_operator_is)  {
							echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
							listbox_schools("markspupil.php?level=school&amp;rid=$rid&amp;yid=$yid&amp;sid=", $rid, $sid, $yid);
							listbox_discipline_ege("markspupil.php?level=school&amp;rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;did=", $rid, $sid, $yid, $did);
							echo '</table>';
						}  else if ($school_operator_is) {
							print_heading($strclasses.': '.$school->name, "center", 3);
							echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
							listbox_discipline_ege("markspupil.php?level=school&amp;rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;did=", $rid, $sid, $yid, $did);
							echo '</table>';
						}

					 	if ($rid != 0 && $sid != 0 && $did != 0)  {

						    if (!$discipline_ege = get_record('monit_school_discipline_ege', 'id', $did))	{
						    	error('Discipline not found!');
						    }

							$table = table_markspupil ($level, $yid, $did, $rid, $sid);
							print_color_table($table);
					   		$options = array('rid' => $rid, 'sid' => $sid, 'did' => $did, 'yid' => $yid, 'level' => 'school', 'action' => 'excel');
							echo '<table align="center" border=0><tr><td>';
						    print_single_button("markspupil.php", $options, get_string("downloadexcel"));
   							echo '</td></tr></table>';
					    }
		break;

		case 'class':  if ($admin_is || $region_operator_is) 	{
							echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
							listbox_rayons("markspupil.php?level=class&amp;sid=0&amp;gid=0&amp;yid=$yid&amp;rid=", $rid);
							listbox_schools("markspupil.php?level=class&amp;rid=$rid&amp;yid=$yid&amp;gid=0&amp;sid=", $rid, $sid, $yid);
						    listbox_class("markspupil.php?level=class&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=", $rid, $sid, $yid, $gid);
							listbox_discipline_ege("markspupil.php?level=class&amp;rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;gid=$gid&amp;did=", $rid, $sid, $yid, $did);
							echo '</table>';
						} else  if ($rayon_operator_is)  {
							echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
							listbox_schools("markspupil.php?level=class&amp;rid=$rid&amp;yid=$yid&amp;gid=0&amp;sid=", $rid, $sid, $yid);
						    listbox_class("markspupil.php?level=class&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=", $rid, $sid, $yid, $gid);
							listbox_discipline_ege("markspupil.php?level=class&amp;rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;gid=$gid&amp;did=", $rid, $sid, $yid, $did);
							echo '</table>';
						}  else if ($school_operator_is) {
							print_heading($strclasses.': '.$school->name, "center", 3);
							echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
						    listbox_class("markspupil.php?level=class&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=", $rid, $sid, $yid, $gid);
							listbox_discipline_ege("markspupil.php?level=class&amp;rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;gid=$gid&amp;did=", $rid, $sid, $yid, $did);
							echo '</table>';
						}

					 	if ($rid != 0 && $sid != 0 && $did != 0 && $gid != 0)  {

						    if (!$discipline_ege = get_record('monit_school_discipline_ege', 'id', $did))	{
						    	error('Discipline not found!');
						    }

							$table = table_markspupil ($level, $yid, $did, $rid, $sid, $gid);
							print_color_table($table);
					   		$options = array('rid' => $rid, 'sid' => $sid, 'did' => $did, 'yid' => $yid, 'gid' => $gid, 'level' => 'class', 'action' => 'excel');
							echo '<table align="center" border=0><tr><td>';
						    print_single_button("markspupil.php", $options, get_string("downloadexcel"));
   							echo '</td></tr></table>';
					    }
		break;

    }

	print_footer();


function table_markspupil ($level, $yid, $did, $rid = 0, $sid = 0, $gid = 0, $page = '', $perpage = '')
{
	global $CFG, $admin_is, $region_operator_is;

    if (!$discipline_ege = get_record('monit_school_discipline_ege', 'id', $did))	{
    	error('Discipline not found!');
    }

    $table->head  = array (get_string('number','block_monitoring'), get_string('code_ou', 'block_mou_ege'), get_string('class', 'block_mou_ege'),
    					   get_string('code_pp', 'block_mou_ege'), get_string('auditoria', 'block_mou_ege'),
    					   get_string('lastname'), get_string('firstname'), get_string('numvariant', 'block_mou_ege'),
    					   get_string('sidea', 'block_mou_ege'), get_string('sideb', 'block_mou_ege'), get_string('sidec', 'block_mou_ege'),  get_string('sided', 'block_mou_ege'),
    					   get_string('ball', 'block_mou_ege'), get_string('ocenka', 'block_mou_ege'));

	$table->align = array ('center', 'center', 'center', 'center', 'center', 'left', 'left', 'center', 'left', 'left', 'left', 'left', 'center', 'center');
	$table->columnwidth = array (7, 7, 7, 8, 9, 14, 25, 14, 27, 27, 27, 27, 8, 7);
    // $table->datatype = array ('char', 'char');
    $table->class = 'moutable';
   	$table->width = '90%';
    // $table->size = array ('10%', '10%');
    $table->titles = array();
    $table->titles[] = get_string('protokolproverki', 'block_mou_ege');
    $table->worksheetname = $level;

	$strsqlresults = "SELECT *  FROM {$CFG->prefix}monit_gia_results ";
	$strsqlschools = "SELECT id, code  FROM {$CFG->prefix}monit_school ";
	$strsqlclasses = "SELECT id, name FROM {$CFG->prefix}monit_school_class	";

    switch ($level)	{
		case 'region':
						$strsqlresults .= " WHERE yearid=$yid AND codepredmet={$discipline_ege->code}
										 	ORDER BY rayonid, schoolid";
						$strsqlschools .= " WHERE isclosing=0 AND yearid=$yid ";
						$strsqlclasses .= " WHERE yearid=$yid ";

						$table->titles[] = get_string('nameregion', 'block_mou_ege');
						$table->downloadfilename = 'results_region';
		break;
		case 'rayon':
						$strsqlresults .= " WHERE rayonid=$rid AND yearid=$yid AND codepredmet={$discipline_ege->code}
											ORDER BY schoolid, classid";
						$strsqlschools .= " WHERE rayonid = $rid AND isclosing=0 AND yearid=$yid ";
						$strsqlclasses .= " WHERE yearid=$yid ";

					    $rayon = get_record('monit_rayon', 'id', $rid);
						$table->titles[] = $rayon->name;
						$table->downloadfilename = 'results_rayon_'.$rid;
		break;
		case 'school':
						$strsqlresults .= " WHERE rayonid=$rid AND yearid=$yid AND schoolid=$sid AND codepredmet={$discipline_ege->code}
									 		ORDER BY classid, userid";
						$strsqlschools .= " WHERE id=$sid ";
						$strsqlclasses .= " WHERE schoolid=$sid AND yearid=$yid ";

					    $rayon = get_record('monit_rayon', 'id', $rid);
					    $school = get_record('monit_school', 'id', $sid);
	                	$table->titles[] = $school->name . " ({$rayon->name})";
						$table->downloadfilename = 'results_school_'.$sid;
		break;
		case 'class':
						$strsqlresults .= " WHERE rayonid=$rid AND yearid=$yid AND schoolid=$sid  AND classid=$gid AND codepredmet={$discipline_ege->code}
									 		ORDER BY userid";
						$strsqlschools .= " WHERE id=$sid ";
						$strsqlclasses .= " WHERE schoolid=$sid AND yearid=$yid ";

					    $rayon = get_record('monit_rayon', 'id', $rid);
					    $school = get_record('monit_school', 'id', $sid);
					    $class = get_record('monit_school_class', 'id', $gid);
	                	$table->titles[] = $school->name . " ({$rayon->name})";
	                	$table->titles[] = get_string('class', 'block_mou_ege') . ': '. $class->name;
						$table->downloadfilename = 'results_class_'.$gid;
		break;
    }

	$discipline = get_record("monit_school_discipline_ege", 'id', $did);
	$table->titles[] = $discipline->code . ' - ' . $discipline->name;
    $table->titlesrows = array(30, 30, 30, 30);

	$schoolsarray = array();
 	if ($schools = get_records_sql($strsqlschools))	{
	    foreach ($schools as $sa)  {
	        $schoolsarray[$sa->id] = $sa->code;
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

	$nowtime = time();
	
 	if ($gia_results = get_records_sql($strsqlresults, $page*$perpage, $perpage))	{
 		$i = $page*$perpage + 1;
        foreach ($gia_results as $gia)	{
            $user = get_record_sql("SELECT id, lastname, firstname FROM  {$CFG->prefix}user WHERE id = {$gia->userid}");

            $fieldsname = array ('pp', 'audit', 'variant', 'sidea', 'sideb', 'sidec', 'sided', 'ball', 'ocenka');
            $fieldsvalue = array ('-', '-', '-', '-', '-', '-', '-', '-');
            foreach ($fieldsname as $fldindex => $fldname)	{
	            if (!empty($gia->{$fldname}))	{
	                $fieldsvalue[$fldindex] = $gia->{$fldname};
	            }
	        }

		    if ($giadate = get_record_select('monit_school_gia_dates', "yearid=$yid AND codepredmet={$gia->codepredmet} AND timeload = {$gia->timeload}"))  {
			    if ($nowtime > $giadate->timepublish  || $admin_is || $region_operator_is)	{
		            $table->data[] = array ($i++ . '.', $schoolsarray[$gia->schoolid], $classesarray[$gia->classid],
		            					$fieldsvalue[0], $fieldsvalue[1], $user->lastname, $user->firstname, $fieldsvalue[2],
		            					$fieldsvalue[3], $fieldsvalue[4], $fieldsvalue[5], $fieldsvalue[6], $fieldsvalue[7], $fieldsvalue[8]);
		        } else {
   		            $t = date ("d.m.Y H:i", $giadate->timepublish);
			    	$title = get_string('timewillbepublish','block_mou_ege', $t);
					$strlinkupdate = "<img src=\"{$CFG->wwwroot}/blocks/mou_ege/i/mark.png\" alt=\"$title\" />";
		            $table->data[] = array ($i++ . '.', $schoolsarray[$gia->schoolid], $classesarray[$gia->classid],
		            					$fieldsvalue[0], $fieldsvalue[1], $user->lastname, $user->firstname, $fieldsvalue[2],
		            					'-', '-', '-', '-', '-', $strlinkupdate);
					
		        	$table->data[] = array ();
		        }
			}	    					

        }

    }  else 	{
    	$table->data[] = array ();
    }
   // print_r($gia_results);

    return $table;
}

?>
