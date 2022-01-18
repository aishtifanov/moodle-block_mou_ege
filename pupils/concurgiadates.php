<?PHP // $Id: concurgiadates.php,v 1.5 2011/03/09 11:29:46 shtifanov Exp $

    require_once('../../../config.php');
    require_once('../../monitoring/lib.php');
    require_once('../lib_ege.php');

    $yid = optional_param('yid', 0, PARAM_INT);       // Year id
	$rid = optional_param('rid', 0, PARAM_INT);
	$sid = optional_param('sid', 0, PARAM_INT);
	$gid = optional_param('gid', 0, PARAM_INT);
    $did = optional_param('did', 0, PARAM_INT);       // Discipline id
	$uid = optional_param('uid', 0, PARAM_INT);
   	$level = optional_param('level', 'rayon');
	$action   = optional_param('action', '');
    $page    = optional_param('page', 0, PARAM_INT);
    $perpage = optional_param('perpage', 30, PARAM_INT);        // how many per page


    if ($yid == 0)	{
    	$yid = get_current_edu_year_id();
    }

	$action   = optional_param('action', '');
    if ($action == 'excel') {
		$table = table_concurgiadates ($level, $yid, $did, $rid, $sid, $gid);
    	// print_r($table);
        print_table_to_excel($table, 1);
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
	      if ($school = get_record_sql ("SELECT id, uniqueconstcode, name FROM {$CFG->prefix}monit_school
	                                     WHERE rayonid=$rid AND uniqueconstcode=$sid AND yearid=$yid"))	{
	     		$sid = $school->id;
	      } else {
				$school = get_record('monit_school', 'id', $sid);
	      }
	}

	// $strclasses = get_string('classes','block_mou_ege');
	$strclasses = get_string('school','block_monitoring');
	$strmarks = get_string('concurgiadates','block_mou_ege');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= " -> $strmarks";
    print_header_mou("$SITE->shortname: $strmarks", $SITE->fullname, $breadcrumbs);


	print_tabs_years($yid, "concurgiadates.php?rid=$rid&amp;yid=");

    $currenttab = 'concur'.$level;
    $toprow = array();
    $toprow[] = new tabobject('concurrayon', "concurgiadates.php?level=rayon&amp;rid=$rid&amp;yid=$yid",
	               get_string('concurrayon', 'block_mou_ege'));
	if ($admin_is || $region_operator_is) {
	    $toprow[] = new tabobject('concurregion', "concurgiadates.php?level=region&amp;yid=$yid",
 	               get_string('concurregion', 'block_mou_ege'));
	}
    $tabs = array($toprow);
    print_tabs($tabs, $currenttab, NULL, NULL);


    switch ($level)	{
		case 'region':  if ($admin_is || $region_operator_is) 	{

							$table = table_concurgiadates ($level, $yid, 0, 0, 0, 0, $page, $perpage);

						    // print_paging_bar($usercount, $page, $perpage, "concurgiadates.php?level=region&amp;yid=$yid&amp;perpage=$perpage&amp;did=$did&amp;");
							print_color_table($table);
						    // print_paging_bar($usercount, $page, $perpage, "concurgiadates.php?level=region&amp;yid=$yid&amp;perpage=$perpage&amp;did=$did&amp;");

					   		$options = array('rid' => $rid, 'sid' => $sid, 'did' => $did, 'yid' => $yid, 'level' => 'region', 'action' => 'excel');
							echo '<table align="center" border=0><tr><td>';
						    print_single_button("concurgiadates.php", $options, get_string("downloadexcel"));
   							echo '</td></tr></table>';
						}
		break;

		case 'rayon':   if ($admin_is || $region_operator_is) 	{
							echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
							listbox_rayons("concurgiadates.php?level=rayon&amp;yid=$yid&amp;rid=", $rid);
							echo '</table>';
						}

					 	if ($rid != 0)  {

							$table = table_concurgiadates ($level, $yid, $did, $rid, 0, 0, $page, $perpage);

						    // print_paging_bar($usercount, $page, $perpage, "concurgiadates.php?level=rayon&amp;rid=$rid&amp;yid=$yid&amp;perpage=$perpage&amp;did=$did&amp;");
							print_color_table($table);
						    // print_paging_bar($usercount, $page, $perpage, "concurgiadates.php?level=rayon&amp;rid=$rid&amp;yid=$yid&amp;perpage=$perpage&amp;did=$did&amp;");

					   		$options = array('rid' => $rid, 'sid' => $sid, 'did' => $did, 'yid' => $yid, 'level' => 'rayon', 'action' => 'excel');
							echo '<table align="center" border=0><tr><td>';
						    print_single_button("concurgiadates.php", $options, get_string("downloadexcel"));
   							echo '</td></tr></table>';
					    }
		break;
    }

	print_footer();


function table_concurgiadates ($level, $yid, $did = 0, $rid = 0, $sid = 0, $gid = 0, $page = '', $perpage = '')
{
	global $CFG;


	if ($disciplines =  get_records_sql ("SELECT id, code, name  FROM  {$CFG->prefix}monit_school_discipline_ege
									  WHERE yearid=$yid ORDER BY name"))	{
		$disc_name = array();
		foreach ($disciplines as $discipline) 	{
			$disc_name[$discipline->id] = $discipline->name;
		}
	}


    $strsql = "SELECT id, date_gia FROM {$CFG->prefix}monit_school_gia_dates 
			   where yearid=$yid and discmiid=0";


 	if ($gia_dates = get_records_sql($strsql))	{
        $garray = array();
        $garray[0] = 0;
	    foreach ($gia_dates as $ga)  {
	        $garray[$ga->id] = $ga->date_gia;
	    }
    }
    
    // print_r($garray); echo '<hr>';

    $strdisciplines = get_string('disciplines_ege', 'block_mou_ege');
    $straction = get_string('action', 'block_monitoring');
    $strschool = get_string('school', 'block_monitoring');

    $table->head  = array ('№', $strschool, '', get_string('fullname'), $strdisciplines,  $straction);
 	$table->align = array ('left', 'left', 'left', 'left', 'left', 'center');
	$table->class = 'moutable';

	$table->columnwidth = array (5, 45, 5, 40, 40, 5);
    // $table->datatype = array ('char', 'char');
   	$table->width = '90%';
    $table->size = array ('3%', '30%', '5%', '25%', '25%', '5%');
    $table->titles = array();
    $table->titles[] = get_string('concurregion', 'block_mou_ege');
    $table->worksheetname = $level;

	$strsqlresults = "SELECT DISTINCT listdatesids FROM {$CFG->prefix}monit_school_pupil_card 
					  WHERE yearid=$yid ";
	$strsqlschools = "SELECT id, name  FROM {$CFG->prefix}monit_school ";

    switch ($level)	{
		case 'region':
						$strsqlschools .= " WHERE isclosing=0 AND yearid=$yid ";

						$table->titles[] = get_string('nameregion', 'block_mou_ege');
						$table->downloadfilename = 'dates_region';
		break;
		case 'rayon':
						$strsqlresults .= " AND rayonid=$rid ";
						$strsqlschools .= " WHERE rayonid = $rid AND isclosing=0 AND yearid=$yid ";

					    $rayon = get_record('monit_rayon', 'id', $rid);
						$table->titles[] = $rayon->name;
						$table->downloadfilename = 'dates_rayon_'.$rid;
		break;
		case 'school':
						$strsqlresults .= " AND schoolid=$sid ";
						$strsqlschools .= " WHERE id=$sid ";

					    $rayon = get_record('monit_rayon', 'id', $rid);
					    $school = get_record('monit_school', 'id', $sid);
	                	$table->titles[] = $school->name . " ({$rayon->name})";
						$table->downloadfilename = 'dates_school_'.$sid;
		break;
    }

    $table->titlesrows = array(30, 30);

	$schoolsarray = array();
 	if ($schools = get_records_sql($strsqlschools))	{
	    foreach ($schools as $sa)  {
	        $schoolsarray[$sa->id] = $sa->name;
	    }
	}

    // print_r($schoolsarray); echo '<hr>';

    // echo $strsqlresults;

    // print_r($gia_results); echo '<hr>';

    if ($pupils = get_records_sql($strsqlresults))  {
    	$templatesids = array();
	    foreach ($pupils as $pupil)	{
		    $arrdatesids = explode(',', $pupil->listdatesids);
		    $arrdates = array();
		    foreach ($arrdatesids as $key => $ae)  {
   		    	if ($ae == 0 || !isset($garray[$ae])) continue;

		    	if (!in_array($garray[$ae], $arrdates))	{
			    	$arrdates[$key] = $garray[$ae];
			    } else {
				    $templatesids[] = $pupil->listdatesids;
    			    break;
			    }
		    }
		}

        // print_r($templatesids);  echo '<hr>';
		$i = 1;
		foreach ($templatesids as $tdate)	{

			$strsqlresults = "SELECT id, yearid, rayonid, userid, schoolid, classid, listegeids, listdatesids FROM {$CFG->prefix}monit_school_pupil_card
				 			  WHERE listdatesids='$tdate' AND deleted=0 AND yearid=$yid ";
		    switch ($level)	{
				case 'region':  $strsqlresults .= "";
				break;
				case 'rayon':
								$strsqlresults .= " AND rayonid=$rid
													ORDER BY schoolid, classid";
				break;
				case 'school':
								$strsqlresults .= " AND schoolid=$sid
											 		ORDER BY classid, userid";
				break;
		    }

            // echo $strsqlresults; echo '<hr>';
		    // if ($pupils = get_records_sql($strsqlresults, $page*$perpage, $perpage))  {
			if ($pupils = get_records_sql($strsqlresults))  {
				// print_r($pupils);
		 		// $i = $page*$perpage + 1;
			    foreach ($pupils as $pupil)	{
				    $arrdatesids = explode(',', $pupil->listdatesids);
   				    $arregeids = explode(',', $pupil->listegeids);
			    	$strdates = '';
				    foreach ($arrdatesids as $key => $idx)  {
		   		    	if ($idx == 0) continue;
	    			   	$strdates .= convert_date($garray[$idx], 'en', 'ru') . ' г. - ' . $disc_name[$arregeids[$key]] . "<br>\n";
	    			}

			    	$uid = $pupil->userid;
		            $user = get_record_sql("SELECT id, lastname, firstname, picture FROM  {$CFG->prefix}user WHERE id=$uid");
                    $student = get_record('monit_school_pupil_card', 'userid', $uid, 'yearid', $yid);
	                $rid 	= $student->rayonid;
	                $sid 	= $student->schoolid;
	                $gid	= $student->classid;

					$title = get_string('editprofilepupil','block_mou_ege');
					$strlinkupdate = "<a href=\"{$CFG->wwwroot}/blocks/mou_ege/pupils/pupilcard.php?mode=4&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid&amp;uid=$uid\">";
					$strlinkupdate .= "<img src=\"{$CFG->pixpath}/i/edit.gif\" alt=\"$title\" /></a>&nbsp;";

		            $table->data[] = array ($i++ . '.', $schoolsarray[$pupil->schoolid],
		            						print_user_picture($user->id, 1, $user->picture, false, true),
		            						fullname($user), $strdates, $strlinkupdate);
			    }
		    }
		}
    }  else  {
    	// $table->data[] = array ();
    }

    return $table;
}

?>
