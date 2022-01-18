<?php // $Id: gia_textbook_edit.php,v 1.3 2009/06/11 09:40:35 Shtifanov Exp $

    require_once("../../../config.php");
    require_once('../../monitoring/lib.php');
    require_once('../lib_ege.php');

    $rid = required_param('rid', PARAM_INT);          // Rayon id
    $sid = required_param('sid', PARAM_INT);          // School id
    $yid = required_param('yid', PARAM_INT);       // Year id
    $did = required_param('did', PARAM_INT);       // Discipline id
	$catid  = optional_param('catid', '0', PARAM_INT);       // Category textbook

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


    if ($sid != 0)	{
    	$school = get_record('monit_school', 'id', $sid, 'yearid', $yid);
   	    $strschool = $school->name;
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
	} else if (!$rayon = get_record('monit_rayon', 'id', $rid)) {
        error(get_string('errorrayon', 'block_monitoring'), '..\rayon\rayons.php');
    }

    if ($admin_is  || $region_operator_is) {  // || $rayon_operator_is)  {
		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
		listbox_rayons("gia_textbook_edit.php?sid=0&amp;yid=$yid&amp;did=$did&amp;rid=", $rid);
		listbox_schools("gia_textbook_edit.php?rid=$rid&amp;yid=$yid&amp;did=0&amp;sid=", $rid, $sid, $yid);
		listbox_discipline_ege("gia_textbook_edit.php?rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;did=", $rid, $sid, $yid, $did);
		echo '</table>';
	} else  if ($rayon_operator_is)  {
		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
		listbox_schools("gia_textbook_edit.php?rid=$rid&amp;yid=$yid&amp;did=0&amp;sid=", $rid, $sid, $yid);
		listbox_discipline_ege("gia_textbook_edit.php?rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;did=", $rid, $sid, $yid, $did);
		echo '</table>';
	}  else if ($school_operator_is) {
		listbox_discipline_ege("gia_textbook_edit.php?rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;did=", $rid, $sid, $yid, $did);
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

	print_tabs_years($yid, "gia_textbook_edit.php?rid=$rid&amp;sid=$sid&amp;yid=");

    $currenttab = 'gia';
    include('tabsclasses.php');

    $currenttab = 'gia_textbook';
    include('tabsgia.php');

	echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
	listbox_textbook("gia_textbook_edit.php?rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;did=$did&amp;catid=", $yid, $catid);
	echo '</table>';


/// If data submitted, then process and store.

    if ($schooltextbooks = data_submitted()) 		{
			if ($textbooks =  get_records('monit_textbook'))	{
				$textbooksids = '';
				foreach ($textbooks as $textbook) 	{
					$pf = 'textbook_'.$textbook->id;
					if (isset($schooltextbooks->{$pf}) && $schooltextbooks->{$pf} == 1)  {
						$textbooksids .= $textbook->id . ',';
					}
				}
				$textbooksids .= '0';

		        if ($schtextbook =  get_record('monit_school_textbook',  'yearid', $yid , 'schoolid', $sid, 'discegeid', $did))  {		        	$schtextbook->textbooksids .= $textbooksids;
		        	$schtextbook->timemodified = time();
	                if (update_record('monit_school_textbook', $schtextbook))	{	                   	redirect("gia_textbooks.php?rid=$rid&amp;yid=$yid&amp;sid=$sid", get_string("changessaved"), 0);	                }	else {
	                    print_r($schtextbook);		                error("Could not update the school textbook record.");	                }
	            } else {	            	$rec->yearid	= $yid;
	            	$rec->schoolid	= $sid;
	            	$rec->discegeid	= $did;
	            	$rec->textbooksids = $textbooksids;
	            	$rec->timemodified = time();
	                if (insert_record('monit_school_textbook', $rec))	{
	                   	redirect("gia_textbooks.php?rid=$rid&amp;yid=$yid&amp;sid=$sid", get_string("changessaved"), 0);
	                }	else {
	                    print_r($schtextbook);
		                error("Could not insert the new school textbook record.");
	                }
	            }
			}
    }


    if ($rid != 0 && $sid != 0 && $did != 0 && $catid != 0)  {


	    print_simple_box_start("center", '80%', 'white');
?>

<table class="formtable">
<form method="post" name="form" enctype="multipart/form-data" action="gia_textbook_edit.php">
<input type="hidden" name="rid" value="<?php echo $rid ?>" />
<input type="hidden" name="sid" value="<?php echo $sid ?>" />
<input type="hidden" name="yid" value="<?php echo $yid ?>" />
<input type="hidden" name="did" value="<?php echo $did ?>" />


<?php


		if ($textbooks =  get_records('monit_textbook',  'categoryid', $catid , 'authors'))	 {
		    $i = 0;
		    $strtextbooks = get_string('textbooks', 'block_mou_ege');
			echo "<tr><th>$strtextbooks:</th>";
			echo "<td>";

			$arr_egeids = array();
	        if ($schooltextbooks =  get_record('monit_school_textbook',  'yearid', $yid , 'schoolid', $sid, 'discegeid', $did))  {
			    $arr_egeids = explode(',', $schooltextbooks->textbooksids);
			}

			$strlowclass = get_string('lowclass', 'block_mou_ege');

			foreach ($textbooks as $textbook) {
				$name = 'textbook_'.$textbook->id;

				if (in_array($textbook->id, $arr_egeids))	{
					echo "<input name=$name type=checkbox checked=checked value=1>";
				} else {
					echo "<input name=$name type=checkbox value=1>";
				}
				// ++$i.'. ' .
				echo  ' '.$textbook->authors .' '. $textbook->name .'. - '. $textbook->publisher . ' (' . $textbook->numclass . ' '. $strlowclass . ')<br>';
			}
			echo "</td>";
		}


		if (!isregionviewoperator() && !israyonviewoperator())  {			echo '<tr><td colspan="2"><hr /></td></tr>';
			echo '<tr align=center><td align=right><input type="submit" value="' . get_string("savechanges") . '" /></td>';
	  		echo '</form><td align=left>';
/*
	   		$options = array();
		    $options['rid'] = $rid;
		    $options['sid'] = $sid;
		    $options['yid'] = $yid;
		    print_single_button("gia_teachers.php", $options, get_string("revert"));
*/
?>

<form method="post" name="form2" enctype="multipart/form-data" action="gia_textbooks.php">
<input type="hidden" name="rid" value="<?php echo $rid ?>" />
<input type="hidden" name="sid" value="<?php echo $sid ?>" />
<input type="hidden" name="yid" value="<?php echo $yid ?>" />
<input type="hidden" name="did" value="<?php echo $did ?>" />
<input type="submit" value="<?php print_string("revert")?>" />
</form>

<?php
	  		echo '</td></tr></table>';
  		}

	   	print_simple_box_end();
    }
    print_footer();


?>


