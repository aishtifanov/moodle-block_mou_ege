<?PHP // $Id: reportpoints.php,v 1.15 2012/06/13 06:47:03 shtifanov Exp $

    require_once('../../../config.php');
    require_once('../../monitoring/lib.php');
    require_once('../lib_ege.php');

    $rid = required_param('rid', PARAM_INT);       // Rayon id
    $did = required_param('did', PARAM_INT);       // Discipline id
    $yid = optional_param('yid', 0, PARAM_INT);       // Year id
    $numday = optional_param('nd', '0', PARAM_INT);       // numday

    if ($yid == 0)	{
    	$yid = get_current_edu_year_id();
    }


	require_login();

	$admin_is = isadmin();
	$region_operator_is = ismonitoperator('region');
	$rayon_operator_is  = ismonitoperator('rayon', 0, 0, 0, true);
	if (!$admin_is && !$region_operator_is && !$rayon_operator_is) {
        error(get_string('adminaccess', 'block_monitoring'), "$CFG->wwwroot/blocks/mou_ege/index.php");
	}

/*
	if (isregionviewoperator())  {
        error(get_string('adminaccess', 'block_monitoring'));
	}
*/

	$action   = optional_param('action', '');
    if ($action == 'excel') {
		$table = table_reportpoints ($rid, $did, $yid, $numday);
    	// print_r($table);
        print_table_to_excel($table);
        exit();
	} else if ($action == 'word') {
		$table = table_reportpoints ($rid, $did, $yid, $numday);
    	// print_r($table);
        print_table_to_word($table);
        exit();
    }   

	// $strclasses = get_string('classes','block_mou_ege');
	$strclasses = get_string('school','block_monitoring');
	$str1 = get_string('reportpoints','block_mou_ege');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= " -> $str1";
    print_header_mou("$SITE->shortname: $str1", $SITE->fullname, $breadcrumbs);

	if (!$admin_is && !$region_operator_is && $rayon_operator_is && $rayon_operator_is != $rid)  {
		notify(get_string('selectownrayon', 'block_monitoring'));
	    print_footer();
		exit();
	}

	print_tabs_years($yid, "reportpoints.php?rid=$rid&amp;yid=");

    $currenttab = 'reportpoints';
    include ('tabspoint.php');

	echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
	listbox_discipline_ege("reportpoints.php?yid=$yid&amp;rid=$rid&amp;did=", $rid, 0, $yid, $did, EXCEPTION_LIST_DISCIPLINE_CODE);
	echo '</table>';

	print_tabs_ppe("reportpoints.php?yid=$yid&amp;rid=$rid&amp;did=$did", $numday, $yid);

 	if ($did != 0)  {
		$table = table_reportpoints ($rid, $did, $yid, $numday);
		print_color_table($table);

		echo '<table align="center" border=0><tr><td>';
		$options = array('rid' => $rid, 'did' => $did, 'yid' => $yid, 'nd' => $numday, 'action' => 'excel');
	    print_single_button("reportpoints.php", $options, get_string("downloadexcel"));
        echo '</td><td>';
		$options = array('rid' => $rid, 'did' => $did, 'yid' => $yid, 'nd' => $numday, 'action' => 'word');
	    print_single_button("reportpoints.php", $options, get_string("downloadword", 'block_mou_att'));
		echo '</td></tr></table>';
	}

	print_footer();


function table_reportpoints ($rid, $did, $yid, $numday = 0)
{
	global $CFG, $admin_is, $region_operator_is, $rayon_operator_is;

    $exception = EXCEPTION_LIST_DISCIPLINE_CODE;
	$discipline =  get_record ('monit_school_discipline_ege', 'id', $did);
    $dates_gia = get_records_sql ("SELECT id, yearid, discegeid
   	  								FROM  {$CFG->prefix}monit_school_gia_dates
							  		WHERE yearid=$yid AND discegeid = $did
							  		ORDER BY date_gia");
    $i= 0;
  	foreach ($dates_gia as $d_gia)	{
  		$i++;
  		$matrix[$i] = $d_gia->id;
	}

    $table->head  = array (get_string ('codeppe','block_mou_ege'),
    					   get_string('point','block_mou_ege'),
    					   get_string('code_ou','block_mou_ege'),
    					   get_string('name_ou','block_mou_ege'),
    					   get_string('kol9class','block_mou_ege'));
	$table->align = array ('center', 'left', 'center', 'left', 'center');
	$table->columnwidth = array (5, 52, 8, 35, 9);

    $table->class = 'moutable';
   	$table->width = '90%';

    $table->titlesrows = array(30);
	$table->titles = array();
    $table->titles[] = get_string('reportpoints', 'block_mou_ege') . '. ' . $discipline->name;
	$table->downloadfilename = 'report_ppe_'.$rid.'_'.$discipline->code.'_'.$numday;
    $table->worksheetname = $table->downloadfilename;

	if ($admin_is || $region_operator_is) {
    	$rayons = get_records('monit_rayon');
    } else {
    	$rayons = get_records('monit_rayon', 'id', $rid);
    }	


    foreach ($rayons as $rayon)		{

    	$rid = $rayon->id;
	    $rppecountpupil = 0;

	    if ($rid == 23 || $rid == 25) continue;

    	// $table->data[] = 'hr';
    	$table->data[] = array('<hr>', '<b>'.$rayon->name.'</b>', '<hr>', '<hr>', '<hr>');

        $strsql = "SELECT a.schoolid, b.id, b.number
			  	   FROM {$CFG->prefix}monit_school_points a INNER JOIN {$CFG->prefix}monit_school_point_number b ON a.id = b.pointid ";

        if ($rid == 21) {
            $strsql .= "WHERE rayonid in (21, 23) and disciplineid = $did ";
        } else {
		    $strsql .= "WHERE rayonid = $rid AND disciplineid = $did";            
        }    

        $stritogoschool = '<b>' . get_string('itogoppe', 'block_mou_ege') . '</b>';
        $stritogorayon =  '<b>' . get_string('itogorayon', 'block_mou_ege') . '</b>';
	    if ($points = get_records_sql ($strsql)) 	{

				foreach ($points as $point) {
					$ppecountpupil = 0;
				    $school = get_record('monit_school', 'id', $point->schoolid);
			    	$table->data[] = array($school->codeppe, $school->name, '', '', '');

					$strsql =  "SELECT id, pointnumber1id, pointnumber2id, pointnumber3id, rayonid, schoolid, disciplineid
								FROM {$CFG->prefix}monit_school_point_forschool ";
					switch ($numday)   {
                        case 2:
                            $strsql .=  "WHERE rayonid in ($rid, 23, 25) and disciplineid = $did and pointnumber2id = {$point->id}";
                        break;    
					    case 3:
                            $strsql .=  "WHERE rayonid in ($rid, 23, 25) and disciplineid = $did and pointnumber3id = {$point->id}";
                        break;
                        default:
						$strsql .=  "WHERE rayonid in ($rid, 23, 25) and disciplineid = $did and pointnumber1id = {$point->id}";
					}
				 	if ($forschools = get_records_sql($strsql))	{
				 		foreach ($forschools as $fs)	{
						    $fschool = get_record('monit_school', 'id', $fs->schoolid, '', '', '', '', 'id, code, name');
						    $name = truncate_school_name($fschool->name);
					        $countpupil = 0;
						    $strsql = "SELECT id, userid, classid, schoolid, listegeids, listdatesids
						    		   FROM {$CFG->prefix}monit_school_pupil_card
									   WHERE schoolid = {$fschool->id} AND deleted=0";
						    if ($pupils = get_records_sql ($strsql))	{

					            $allistegeids =  $allistdatesids = '';
					            foreach ($pupils as $pupil)		{
					            	$allistegeids  .= $pupil->listegeids. ',';
					            	$allistdatesids .= $pupil->listdatesids. ',';
					            }

						        $arr_disc_id = explode(',', $allistegeids);
						        $arr_dates_id = explode(',', $allistdatesids);
						        foreach ($arr_disc_id as $key => $disc_id)	{
   								    if ($numday == 0)	 {
							        	if ($disc_id == $did)	$countpupil++;
							        } else {
                                        if ($disc_id == $did && $arr_dates_id[$key] == $matrix[$numday]) 	{
                                        	$countpupil++;
                                        }
							        }
						        }

						 	}
						 	$ppecountpupil += $countpupil;
                            if ($countpupil >0) {
					    	  $table->data[] = array('', '', $fschool->code, $name, $countpupil);
                            }  
					    }
				    	$table->data[] = array('', '', '', $stritogoschool, '<b>' . $ppecountpupil. '</b>');
    				    $rppecountpupil += $ppecountpupil;
					}
				}
		    	$table->data[] = array('',  '', '',  $stritogorayon, '<b>' . $rppecountpupil. '</b>');

	    }
    }

    return $table;
}

?>
