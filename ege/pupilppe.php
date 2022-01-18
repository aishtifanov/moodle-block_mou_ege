<?PHP // $Id: pupilppe.php,v 1.3 2010/04/06 10:18:12 Shtifanov Exp $

    require_once('../../../config.php');
    require_once('../../monitoring/lib.php');
    require_once('../lib_ege.php');

	
    $rid = required_param('rid', PARAM_INT);       // Rayon id
    $did = required_param('did', PARAM_INT);       // Discipline id
    $yid = optional_param('yid', 0, PARAM_INT);       // Year id

    if ($yid == 0)	{
    	$yid = get_current_edu_year_id();
    }

	$action   = optional_param('action', '');
    if ($action == 'excel') {
		$table = table_pupilppe ($rid, $did, $yid);
    	// print_r($table);
        print_table_to_excel($table);
        exit();
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
	// $strclasses = get_string('classes','block_mou_ege');
	$strclasses = get_string('school','block_monitoring');
	$str1 = get_string('pupilppe','block_mou_ege');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= " -> $str1";
    print_header_mou("$SITE->shortname: $str1", $SITE->fullname, $breadcrumbs);

	if (!$admin_is && !$region_operator_is && $rayon_operator_is && $rayon_operator_is != $rid)  {
		notify(get_string('selectownrayon', 'block_monitoring'));
	    print_footer();
		exit();
	}

	print_tabs_years($yid, "pupilppe.php?rid=$rid&amp;yid=");

    $currenttab = 'pupilppe';
    include ('tabspoint.php');

	echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
	// listbox_rayons("pupilppe.php?yid=$yid&amp;did=$did&amp;rid=", $rid);
	listbox_discipline_ege("pupilppe.php?yid=$yid&amp;rid=$rid&amp;did=", $rid, 0, $yid, $did, EXCEPTION_LIST_DISCIPLINE_CODE);
	echo '</table>';

 	if ($did != 0)  {
		$table = table_pupilppe ($rid, $did, $yid);
		print_color_table($table);

		$options = array('rid' => $rid, 'did' => $did, 'yid' => $yid, 'action' => 'excel');
		echo '<table align="center" border=0><tr><td>';
	    print_single_button("pupilppe.php", $options, get_string("downloadexcel"));
		echo '</td></tr></table>';
	}

	print_footer();


function table_pupilppe ($rid, $did, $yid)
{
	global $CFG, $admin_is, $region_operator_is, $rayon_operator_is;

    // $exception = EXCEPTION_LIST_DISCIPLINE_CODE;
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


	// SELECT distinct schoolid FROM `mou`.`mdl_monit_school_pupil_card` where rayonid=1 and listegeids like '%8,%'



    $table->head  = array (get_string('code_ou','block_mou_ege'),
    					   get_string('name_ou','block_mou_ege'),
    					   get_string('kol9class','block_mou_ege'));
	$table->align = array ( 'center', 'left', 'center');
	$table->columnwidth = array (8, 55, 9);

    $table->class = 'moutable';
   	$table->width = '90%';

    $table->titlesrows = array(30);
	$table->titles = array();
    $table->titles[] = get_string('pupilppe', 'block_mou_ege') . '. ' . $discipline->name;
	$table->downloadfilename = 'not_ppe_'.$discipline->code;
    $table->worksheetname = $table->downloadfilename;

	if ($admin_is || $region_operator_is) {
    	$rayons = get_records('monit_rayon');
    } else {
    	$rayons = get_records('monit_rayon', 'id', $rid);
    }	


	foreach ($matrix as $key => $d_gia_id)	{
	   	$table->data[] = array('<hr><br><hr>', '<p align = center><b>' . get_string('numday_i', 'block_mou_ege', $key) . '</b></p>',  '<hr><br><hr>');

	    foreach ($rayons as $rayon)		{

	    	$rid = $rayon->id;
		    $rppecountpupil = 0;

		    // if ($rid == 23 || $rid == 25) continue;
	        $flag = true;
	    	// $table->data[] = 'hr';

			$shablon = '%,'.	$d_gia_id . ',%';
			$strsql = "SELECT DISTINCT schoolid FROM {$CFG->prefix}monit_school_pupil_card
						WHERE deleted=0 AND rayonid=$rid and listdatesids like '$shablon'";
			// echo $strsql; echo '<hr>';
		    if ($schools = get_records_sql($strsql))  {

		    	foreach ($schools as $school)	{
						$strsql =  "SELECT id, pointnumber1id, pointnumber2id, rayonid, schoolid, disciplineid
									FROM {$CFG->prefix}monit_school_point_forschool
							        WHERE disciplineid = $did and schoolid = {$school->schoolid}";
					 	if (!$fs = get_record_sql($strsql))	{

					 	        if ($flag == true)	{
						 	    	$table->data[] = array('<hr>','<b>'.$rayon->name.'</b>', '<hr>');
						 	    	$flag = false;
						 	    }
							    $fschool = get_record('monit_school', 'id', $school->schoolid, '', '', '', '', 'id, code, name');
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
	                                    if ($disc_id == $did && $arr_dates_id[$key] == $d_gia_id) 	{
	                                       	$countpupil++;
	                                    }
							        }
							 	}
							 	// $ppecountpupil += $countpupil;
						    	$table->data[] = array($fschool->code, $name, $countpupil);
					 	}
		    	}
		    }

	    }
	}
    return $table;
}

?>
