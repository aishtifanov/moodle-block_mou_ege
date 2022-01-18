<?php // $Id: marks.php,v 1.7 2010/05/26 11:15:02 Shtifanov Exp $

    require_once('../../../config.php');
    require_once('../../monitoring/lib.php');
    require_once('../lib_ege.php');

    $rid = optional_param('rid', '0', PARAM_INT);   // Rayon id
    $sid = optional_param('sid', '0', PARAM_INT);	// School id
    $yid = optional_param('yid', '0', PARAM_INT);       // Year id
    $uid = optional_param('uid', 0, PARAM_INT);       // User id
    $gid = optional_param('gid', 0, PARAM_INT);       // User id
    $tab = optional_param('tab', 'profile');
  	$action = optional_param('action', '');       // action

    if ($yid == 0)	{
	    $yid = get_current_edu_year_id();
    }

    // print_r ($action);
    if ($action == 'appellant') {
		form_download($rid, $sid, $yid, $action);
        exit();
	}

 	require_login();

	$admin_is = isadmin();
	$pupil_is = ispupil();
	$region_operator_is = ismonitoperator('region');
	if (!$admin_is && !$pupil_is && !$region_operator_is) {
        error(get_string('adminaccess', 'block_monitoring'), "$CFG->wwwroot/login/index.php");
	}

	$uid = $USER->id;

	if ($admin_is || $region_operator_is)	{
		$uid = 58260;
	}


	$strmarks = get_string('markspupil','block_mou_ege');
	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= " -> $strmarks";
    print_header_mou("$SITE->shortname: $strmarks", $SITE->fullname, $breadcrumbs);


    $pupil = get_record('monit_school_pupil_card', 'userid', $uid);

    if ($sid == 0)	{
    	$sid = $pupil->schoolid;
    }
	$school = get_record('monit_school', 'id', $sid);

    if ($gid == 0)	{
    	$gid = $pupil->classid;
    }
	$class = get_record('monit_school_class', 'id', $gid);


    if ($rid == 0)	{
    	$rid = $school->rayonid;
    }
	$rayon = get_record('monit_rayon', 'id', $rid);

    if (!$user = get_record('user', 'id', $uid) ) {
        error('No such pupil in this class!', '..\index.php');
	}

    $currenttab = 'markspupil';
    include('tabs.php');

   	$fullname = fullname($user);

   	print_heading($fullname, 'center', 2);

		$disciplines =  get_records_sql ("SELECT id, yearid, name, code  FROM  {$CFG->prefix}monit_school_discipline_ege
										  WHERE yearid=$yid ORDER BY name");
        $arr_count = array();
        $arr_count[0] = 0;
        $arr_id = array();
        $arr_id[0] = 0;
		if ($disciplines)	{
			foreach ($disciplines as $discipline) 	{
	        	  $arr_count[$discipline->code] = $discipline->name;
	        	  $arr_id[$discipline->code] = $discipline->id;
			}
		}


    $table->head  = array (get_string('disciplines_ege','block_mou_ege'), get_string('numvariant', 'block_mou_ege'),
    					   get_string('ball', 'block_mou_ege'), get_string('ocenka', 'block_mou_ege'), get_string('action', 'block_mou_ege'));

	$table->align = array ('left', 'center', 'center', 'center', 'center');
	$table->columnwidth = array (7, 7, 7, 8, 9, 14, 25, 14, 8, 7);
    // $table->datatype = array ('char', 'char');
    $table->class = 'moutable';
   	$table->width = '50%';
    // $table->size = array ('10%', '10%');
    $table->titles = array();
    $table->titles[] = get_string('protokolproverki', 'block_mou_ege');
    // $table->worksheetname = $level;

	$strsqlresults = "SELECT *  FROM {$CFG->prefix}monit_gia_results ";
	$strsqlresults .= " WHERE  userid = $uid";

	$table->titles[] = $discipline->name;
    $table->titlesrows = array(30, 30, 30, 30);

	$nowtime = time();

 	if ($gia_results = get_records_sql($strsqlresults))	{
 		    foreach($gia_results as $gia)	{

				if ($giadate = get_record_select('monit_school_gia_dates', "yearid=$yid AND codepredmet={$gia->codepredmet} AND timeload = {$gia->timeload}"))  {
					if ($nowtime > $giadate->timepublish || $admin_is || $region_operator_is)	{
						$title = get_string('putappeal', 'block_mou_ege');
						$strlinkupdate = "<a title=\"$title\" href=\"appeal.php?mode=4&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid&amp;uid=$uid&amp;did={$arr_id[$gia->codepredmet]}\">";
						// $strlinkupdate = $strlinkupdate . "<img src=\"{$CFG->wwwroot}/blocks/mou_ege/i/a3.gif\" alt=\"$title\" /></a>&nbsp;";
						$strlinkupdate .= "<img src=\"{$CFG->wwwroot}/blocks/mou_ege/i/a.png\" alt=\"$title\" /></a>";
		
			            $table->data[] = array ($arr_count[$gia->codepredmet], $gia->variant, $gia->ball, $gia->ocenka, $strlinkupdate);
			        } else {
	   		            $t = date ("d.m.Y H:i", $giadate->timepublish);
				    	$title = get_string('timewillbepublish','block_mou_ege', $t);
						$strlinkupdate = "<img src=\"{$CFG->wwwroot}/blocks/mou_ege/i/mark.png\" alt=\"$title\" />";
			        	
			        	$table->data[] = array ($arr_count[$gia->codepredmet], $gia->variant, $strlinkupdate, $strlinkupdate, '');
			        }
				}	    
	        }
    }  else 	{
    	$table->data[] = array ();
    }
   // print_r($gia_results);

	print_color_table($table);

    print_footer();


?>


