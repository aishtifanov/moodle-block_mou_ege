<?php // $Id: gia_textbooks.php,v 1.4 2009/06/11 09:40:35 Shtifanov Exp $

    require_once("../../../config.php");
    require_once('../../monitoring/lib.php');
    require_once($CFG->libdir.'/tablelib.php');

    $rid = required_param('rid', PARAM_INT);          // Rayon id
    $sid = required_param('sid', PARAM_INT);          // School id
    $yid = optional_param('yid', '0', PARAM_INT);       // Year id
    $did = optional_param('did', '0', PARAM_INT);       // Discipline id
	$action   = optional_param('action', '');

    if ($yid == 0)	{
	    $yid = get_current_edu_year_id();
	}

    if (!$site = get_site()) {
        redirect("$CFG->wwwroot/$CFG->admin/index.php");
    }

	require_login();

	$admin_is = isadmin();
	$region_operator_is = ismonitoperator('staff');
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

    if ($action == 'excel') {
        print_excel_staffs($rid, $sid, $yid);
        exit();
	}

	if ($action == 'clear' && $did != 0) 	{
        // if (!set_field('monit_school_textbook', 'textbooksids', '', 'yearid', $yid , 'schoolid', $sid, 'discegeid', $did))  {
		if (!delete_records('monit_school_textbook', 'yearid', $yid , 'schoolid', $sid, 'discegeid', $did))  {
             notify("Could not update the school textbook record.");
        }
	}


    if ($sid != 0)	{
    	$school = get_record('monit_school', 'id', $sid, 'yearid', $yid);
   	    $strschool = $school->name;
  		$type_ou = $school->type_ou;
    }	else  {
   	    $strschool = get_string('school', 'block_monitoring');
    }

	if ($rid == 0 &&  $sid != 0) {
		$rid = $school->rayonid;
	}

    // $strstaffs = get_string('staffs', 'block_mou_att');
    $straction = get_string('action', 'block_monitoring');
    $strtitle =  get_string('title','block_mou_att');
	$strclasses = get_string('school','block_monitoring');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= "-> $strclasses";
    print_header("$site->shortname: $strclasses", $site->fullname, $breadcrumbs);

    // add_to_log(SITEID, 'monit', 'school view', 'school.php?id='.SITEID, $strschool);
	if ($rid == 0)  {
	   $rayon = get_record('monit_rayon', 'id', 1);
	}
	else if (!$rayon = get_record('monit_rayon', 'id', $rid)) {
        error(get_string('errorrayon', 'block_monitoring'), '..\rayon\rayons.php');
    }

    if ($admin_is  || $region_operator_is) {  // || $rayon_operator_is)  {
		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
		listbox_rayons("gia_textbooks.php?sid=0&amp;yid=$yid&amp;rid=", $rid);
		listbox_schools("gia_textbooks.php?rid=$rid&amp;yid=$yid&amp;sid=", $rid, $sid, $yid);
		echo '</table>';
	} else  if ($rayon_operator_is)  {
		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
		listbox_schools("gia_textbooks.php?rid=$rid&amp;yid=$yid&amp;sid=", $rid, $sid, $yid);
		echo '</table>';
	}  else if ($school_operator_is) {
		print_heading($strclasses.': '.$school->name, "center", 3);
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

	print_tabs_years($yid, "gia_textbooks.php?rid=$rid&amp;sid=$sid&amp;yid=");

    $currenttab = 'gia';
    include('tabsclasses.php');

    $currenttab = 'gia_textbook';
    include('tabsgia.php');

   	print_heading(get_string('gia_textbook', 'block_mou_ege'), "center");

    if ($rid != 0 && $sid != 0)  {


		$disciplines =  get_records_sql ("SELECT id, yearid, name  FROM  {$CFG->prefix}monit_school_discipline_ege
										  WHERE yearid=$yid ORDER BY name");
        $arr_count = array();
		if ($disciplines)	{
			foreach ($disciplines as $discipline) 	{
	        	  $arr_count[$discipline->id] = 0;
			}
		}

		$table->head  = array (get_string('disciplines_ege','block_mou_ege'), get_string("textbooks","block_mou_ege"), get_string("action","block_mou_ege"));
		$table->align = array ("left", "left", "center");
	    $table->class = 'moutable';
	   	$table->width = '90%';
	    $table->size = array ('10%', '80%', '10%');

		$strlowclass = get_string('lowclass', 'block_mou_ege');

		foreach ($disciplines as $discipline) 	{
			$arr_egeids = array();
	        if ($schooltextbooks =  get_record('monit_school_textbook',  'yearid', $yid , 'schoolid', $sid, 'discegeid', $discipline->id))  {
			    $arr_egeids = explode(',', $schooltextbooks->textbooksids);
			}
			$strtextbooks = '';
			    if (!empty($schooltextbooks->textbooksids))	{
			    	$tbids = explode(',', $schooltextbooks->textbooksids);
			    	$i = 0;
			    	foreach ($tbids as $tbid)	{
			    		if ($tbid > 0)	{
			    		    if ($textbook = get_record ('monit_textbook',  'id', $tbid))	{
					    		$strtextbooks .= ++$i.'. ' .$textbook->authors .' '. $textbook->name .'. - '. $textbook->publisher . ' (' . $textbook->numclass . ' '. $strlowclass . ')<br>';
					    	}
				    	}
			    	}
			    	/*
			    	if ($textbooks != '')  {
			    		$textbooks = substr($textbooks, 0, strlen($textbooks)- 2);
			    	} */

			    }
			    if ($strtextbooks == '')  $strtextbooks = '-';


			$title = get_string('change_textbooks_school','block_mou_ege');
			$strlinkupdate = "<a title=\"$title\" href=\"gia_textbook_edit.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;did={$discipline->id}\">";
			$strlinkupdate .= "<img src=\"{$CFG->pixpath}/i/edit.gif\" alt=\"$title\" /></a>&nbsp;";

			$title = get_string('clear_textbooks_school','block_mou_ege');
			$strlinkupdate .= "<a title=\"$title\" href=\"gia_textbooks.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;did={$discipline->id}&amp;action=clear\">";
			$strlinkupdate .=  "<img src=\"{$CFG->wwwroot}/blocks/mou_ege/i/goom.gif\" alt=\"$title\" /></a>&nbsp;";

			$table->data[] = array ($discipline->name, $strtextbooks, $strlinkupdate);
		}

		print_color_table($table);
    }

//    echo '<div align=center><b>'. get_string('attentionstaff', 'block_mou_att') . '</b></div>';
    print_footer();


function print_excel_staffs($rid, $sid, $yid)
{
    global $CFG;

	return true;
}


?>


