<?php // $Id: giapoints.php,v 1.3 2012/06/13 06:47:04 shtifanov Exp $

    require_once("../../../config.php");
    require_once('../../monitoring/lib.php');

    $rid = required_param('rid', PARAM_INT);          // Rayon id
    $sid = optional_param('sid', '0', PARAM_INT);       // School id
	// $cid = required_param('cid', PARAM_INT);		  // Curriculum id
    $yid = optional_param('yid', '0', PARAM_INT);       // Year id

    $curryearid = get_current_edu_year_id();
    if ($yid == 0)	{
    	$yid = $curryearid;
    }

	$action   = optional_param('action', 'grades');
    if ($action == 'excel') {
        classdiscpupils_download($rid, $sid, $yid);
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
	      if ($school = get_record_sql ("SELECT id, uniqueconstcode FROM {$CFG->prefix}monit_school
	                                     WHERE rayonid=$rid AND uniqueconstcode=$sid AND yearid=$yid"))	{
	     		$sid = $school->id;
	      }
	}

	$strclasses = get_string('school','block_monitoring');

	$strdisciplines = get_string('giapoints', 'block_mou_ege');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= "-> $strclasses";
    print_header("$site->shortname: $strclasses", $site->fullname, $breadcrumbs);

	if ($rid == 0)  {
	   $rayon = get_record('monit_rayon', 'id', 1);
	}
	else if (!$rayon = get_record('monit_rayon', 'id', $rid)) {
        error(get_string('errorrayon', 'block_monitoring'), '..\rayon\rayons.php');
    }

    if ($admin_is  || $region_operator_is) {  // || $rayon_operator_is)  {

		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
		listbox_rayons("giapoints.php?sid=0&amp;yid=$yid&amp;rid=", $rid);
		listbox_schools("giapoints.php?rid=$rid&amp;yid=$yid&amp;sid=", $rid, $sid, $yid);
		echo '</table>';
	} else  if ($rayon_operator_is)  {
		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
		listbox_schools("giapoints.php?rid=$rid&amp;yid=$yid&amp;sid=", $rid, $sid, $yid);
		echo '</table>';

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

	print_tabs_years($yid, "giapoints.php?rid=$rid&amp;sid=$sid&amp;yid=");

    $currenttab = 'gia';
    include('tabsclasses.php');

    $currenttab = 'giapoints';
    include('tabsgia.php');

   	print_heading($strdisciplines, "center");


	$table = table_points_gia($rid, $sid, $yid);
	print_color_table($table);

    print_footer();



function table_points_gia($rid, $sid, $yid)
{
   global $CFG;


	$table->head  = array (get_string('disciplines_ege','block_mou_ege'), get_string('basepoint', 'block_mou_ege'),
						   get_string('reservpoint', 'block_mou_ege'), get_string('reservpoint3', 'block_mou_ege'));
	$table->align = array ("left", "left", "left", "left");
    $table->class = 'moutable';
   	$table->width = '80%';
    $table->size = array ('10%', '30%', '30%', '30%');

	$disciplines =  get_records_sql ("SELECT id, yearid, name  FROM  {$CFG->prefix}monit_school_discipline_ege
									  WHERE yearid=$yid ORDER BY name");
    $disc_name = array();
	if ($disciplines)	{
		foreach ($disciplines as $discipline) 	{
        	  $disc_name[$discipline->id] = $discipline->name;
		}
	}
/*
SELECT a.schoolid, b.id, b.number
FROM mdl_monit_school_points a INNER JOIN mdl_monit_school_point_number b ON a.id = b.pointid
WHERE  yearid = 2 and rayonid = 2
$points_num = get_records_sql (" SELECT a.schoolid, b.id as bid, b.number
									  FROM {$CFG->prefix}monit_school_points a, {$CFG->prefix}monit_school_point_number b
									  WHERE  a.yearid = $yid and a.rayonid = $rid");
*/

	$rayonpoints = get_records_sql (" SELECT id, yearid, rayonid, schoolid
									  FROM {$CFG->prefix}monit_school_points
									  WHERE  yearid = $yid and rayonid = $rid");
	if ($rayonpoints)	{
	    $points_num_num = array();
 	    $points_num_sid = array();
		foreach ($rayonpoints as $rp) 	{
				$points_num = get_records_sql (" SELECT id, pointid, number
												 FROM {$CFG->prefix}monit_school_point_number
											     WHERE  pointid = {$rp->id}");
				if ($points_num)	{
					foreach ($points_num as $pn) 	{
			        	  $points_num_num[$pn->id] = $pn->number;
						  $points_num_sid[$pn->id] = $rp->schoolid;
					}
				}
		}
	}


	$strsql =  "SELECT id, pointnumber1id, pointnumber2id, pointnumber3id, rayonid, schoolid, disciplineid
				FROM {$CFG->prefix}monit_school_point_forschool
   				WHERE rayonid = $rid and schoolid = $sid";
	if ($points = get_records_sql($strsql))	{
	    // print_r($points);
 		foreach ($points as $point)	{
 			$strdiscipline = $disc_name[$point->disciplineid];

		    $school1 = get_record('monit_school', 'id', $points_num_sid[$point->pointnumber1id], '', '', '', '', 'id, name');
			$strbasepoint = $points_num_num[$point->pointnumber1id] .'-й пункт: ' . $school1->name;

		    $school2 = get_record('monit_school', 'id', $points_num_sid[$point->pointnumber2id], '', '', '', '', 'id, name');
			$strreservpoint = $points_num_num[$point->pointnumber2id] .'-й пункт: ' . $school2->name;

		    $school3 = get_record('monit_school', 'id', $points_num_sid[$point->pointnumber3id], '', '', '', '', 'id, name');
			$strreservpoint3 = $points_num_num[$point->pointnumber3id] .'-й пункт: ' . $school3->name;

			$table->data[] = array ($strdiscipline, $strbasepoint, $strreservpoint, $strreservpoint3);
 		}
 	}

    return $table;
}



?>


