<?php  // $Id: edittextbook.php,v 1.1 2009/10/05 13:34:51 Shtifanov Exp $

    require_once("../../../config.php");
    require_once('../../monitoring/lib.php');
    require_once('../lib_ege.php');
    require_once('edittb_form.php');

    $rid = required_param('rid', PARAM_INT);          // Rayon id
    $sid = required_param('sid', PARAM_INT);       // School id
    $yid = required_param('yid', PARAM_INT);       // Year id
	$catid  = required_param('catid', PARAM_INT);       // Category textbook
	$tbid  = required_param('tbid', PARAM_INT);       // Textbook ID

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

	if (isregionviewoperator() || israyonviewoperator())  {
        error(get_string('adminaccess', 'block_monitoring'));
	}

	 $strtextbook = get_string('textbooks', 'block_mou_ege');
	 $strtitle = get_string('edittextbook', 'block_mou_ege');


	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= "-> <a href=\"textbook.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;catid=$catid\">$strtextbook</a>";
	$breadcrumbs .= " -> $strtitle";
    print_header_mou("$SITE->shortname: $strtitle", $SITE->fullname, $breadcrumbs);

	// print_tabs_years($yid, "edittextbook.php?rid=$rid&amp;sid=$sid&amp;yid=");

	$redirlink = "textbook.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;catid=$catid";
	
	if ($tbid)	{
    	if (!$textbook = get_record('monit_textbook', 'id', $tbid))	{
    		error('Text book not found!', $redirlink);
    	}
    } else {
    	$textbook = null;
    }	

	$editform = new edittb_form('edittextbook.php');
	    // now override defaults if course already exists
 	if (!empty($textbook)) {
        $editform->set_data($textbook);
    }
	    
    if ($editform->is_cancelled())	{
		redirect($redirlink, '', 0);
    } else if ($data = $editform->get_data()) 	{
            // print_r($data); echo  '<hr>';
	    	if (!empty($textbook))	 {
		    	$data->id =  $textbook->id;
		    	// print_r($data);
		        if (update_record('monit_textbook', $data)) {
		            // notice(get_string('giaresultupdated', 'block_mou_ege', $data->id));
		            redirect($redirlink, get_string('textbookupdated', 'block_mou_ege', $data->id), 0);
		        } else {
		            error('Error in update textbook.', $redirlink);
		        }
		    } else {
		    	$rec->categoryid  = $catid;
		    	$rec->authors = $data->authors;
		    	$rec->name = $data->name;
		    	$rec->numclass  = $data->numclass;
		    	$rec->publisher  = $data->publisher;
		        if ($newid = insert_record('monit_textbook', $rec)) {
		            // notice(get_string('giaresultupdated', 'block_mou_ege', $data->id));
		            redirect($redirlink, get_string('textbookupdated', 'block_mou_ege', $newid), 0);
		        } else {
		            print_r($rec);
		            error('Error in insert pupil mark.');
		        }

		    }
	} 
	
	$editform->display();
 
    print_footer();

?>
