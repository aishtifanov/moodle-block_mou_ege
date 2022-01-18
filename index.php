<?php // $Id: index.php,v 1.25 2010/04/14 08:52:32 Shtifanov Exp $

    require_once('../../config.php');
    require_once('../monitoring/lib.php');

    if (!$site = get_site()) {
        redirect('index.php');
    }

    $strmonit = get_string('title', 'block_mou_ege');

    print_header_mou("$site->shortname: $strmonit", $site->fullname, $strmonit);

    print_heading($strmonit);

    $table->align = array ('right', 'left');
    // $table->class = 'moutable';

	require_login();

	$admin_is = isadmin();
	// $staff_operator_is = ismonitoperator('staff');
	$region_operator_is = ismonitoperator('region');
	$rayon_operator_is  = ismonitoperator('rayon', 0, 0, 0, true);
	if  (!$admin_is && !$region_operator_is && $rayon_operator_is) 	{
		$rid = $rayon_operator_is;
	}	else {
		$rid = 0;
	}
	$sid = ismonitoperator('school', 0, 0, 0, true);
	$college_operator_is = ismonitoperator('college', 0, 0, 0, true);

	// $staffview_operator = isstaffviewoperator();

	if ($admin_is  || $region_operator_is || $rayon_operator_is)	 {
	    	$table->data[] = array('<strong><a href="'.$CFG->wwwroot."/blocks/mou_ege/ege/disciplines_ege.php?mode=1&amp;rid=$rid&amp;sid=0&amp;cid=0\">".get_string('disciplines_ege','block_mou_ege').'</a></strong>',
 	                          get_string('description_disciplines_ege','block_mou_ege'));


	    	$table->data[] = array('<strong><a href="'.$CFG->wwwroot."/blocks/mou_ege/class/classlist.php?mode=1&amp;rid=$rid&amp;sid=0&amp;cid=0\">".get_string('school','block_monitoring').'</a></strong>',
 	                          get_string('description_classes','block_mou_ege'));

	    	$table->data[] = array('<strong><a href="'.$CFG->wwwroot."/blocks/mou_ege/class/classpupils.php?mode=1&amp;rid=$rid&amp;sid=0&amp;cid=0&amp;gid=0\">".get_string('class','block_mou_ege').'</a></strong>',
 	                          get_string('description_class','block_mou_ege'));

	    	$table->data[] = array('<strong><a href="'.$CFG->wwwroot."/blocks/mou_ege/pupils/pupil.php?mode=1&amp;rid=$rid&amp;sid=0&amp;cid=0&amp;gid=0&amp;yid=0\">".get_string('pupil','block_mou_ege').'</a></strong>',
 	                          get_string('description_pupil','block_mou_ege'));

	    	$table->data[] = array('<strong><a href="'.$CFG->wwwroot.'/blocks/mou_ege/pupils/searchpupil.php">'.get_string('searchpupil', 'block_mou_ege').'</a></strong>',
 	                          get_string('description_searchpupil','block_mou_ege'));

	    	$table->data[] = array('<strong><a href="'.$CFG->wwwroot."/blocks/mou_ege/pupils/markspupil.php?rid=$rid&amp;sid=0\">".get_string('markspupil', 'block_mou_ege').'</a></strong>',
 	                          get_string('description_markspupil','block_mou_ege'));

	    	$table->data[] = array('<strong><a href="'.$CFG->wwwroot."/blocks/mou_ege/pupils/statsmarkspupil.php?rid=$rid&amp;sid=0\">".get_string('statsmarkspupil', 'block_mou_ege').'</a></strong>',
 	                          get_string('description_statsmarkspupil','block_mou_ege'));

	    	$table->data[] = array('<strong><a href="'.$CFG->wwwroot."/blocks/mou_ege/pupils/difficulty.php?rid=$rid&amp;sid=0\">".get_string('difficulty', 'block_mou_ege').'</a></strong>',
 	                          get_string('description_difficulty','block_mou_ege'));

	    	$table->data[] = array('<strong><a href="'.$CFG->wwwroot."/blocks/mou_ege/textbook/textbook.php?rid=$rid&amp;sid=0\">".get_string('textbooks', 'block_mou_ege').'</a></strong>',
 	                          get_string('description_textbook','block_mou_ege'));

	    	$table->data[] = array('<strong><a href="'.$CFG->wwwroot."/blocks/mou_ege/pupils/concurgiadates.php?rid=$rid\">".get_string('concurgiadates', 'block_mou_ege').'</a></strong>',
 	                          get_string('description_concurgiadates','block_mou_ege'));

	    	$table->data[] = array('<strong><a href="'.$CFG->wwwroot."/blocks/mou_ege/appeal/checkappeal.php?rid=$rid&amp;sid=$sid\">".get_string('checkappeal', 'block_mou_ege').'</a></strong>',
 	                          get_string('description_checkappeal','block_mou_ege'));

	}

	if (!$admin_is && !$region_operator_is && $rayon_operator_is) 	{
		    	$table->data[] = array('<strong><a href="'.$CFG->wwwroot."/blocks/mou_ege/ege/setpoints.php?rid=$rid&amp;did=0\">".get_string('points', 'block_mou_ege').'</a></strong>',
 	                          get_string('description_points','block_mou_ege'));
 	}


	if (!$admin_is && !$region_operator_is && !$rayon_operator_is && $sid) {
	       if ($school = get_record('monit_school', 'id', $sid)) {
			    $rid = $school->rayonid;
		    	$table->data[] = array('<strong><a href="'.$CFG->wwwroot."/blocks/mou_ege/class/classlist.php?mode=1&amp;rid=$rid&amp;sid=$sid&amp;cid=0\">".get_string('school','block_monitoring').'</a></strong>',
	 	                          get_string('description_classes','block_mou_ege'));

		    	$table->data[] = array('<strong><a href="'.$CFG->wwwroot."/blocks/mou_ege/class/classpupils.php?mode=1&amp;rid=$rid&amp;sid=$sid&amp;cid=0&amp;gid=0\">".get_string('class','block_mou_ege').'</a></strong>',
	 	                          get_string('description_class','block_mou_ege'));

		    	$table->data[] = array('<strong><a href="'.$CFG->wwwroot."/blocks/mou_ege/pupils/pupil.php?mode=1&amp;rid=$rid&amp;sid=$sid&amp;cid=0&amp;gid=0&amp;yid=0\">".get_string('pupil','block_mou_ege').'</a></strong>',
	 	                          get_string('description_pupil','block_mou_ege'));

		    	$table->data[] = array('<strong><a href="'.$CFG->wwwroot."/blocks/mou_ege/pupils/markspupil.php?rid=$rid&amp;sid=$sid\">".get_string('markspupil', 'block_mou_ege').'</a></strong>',
	 	                          get_string('description_markspupil','block_mou_ege'));
/*
       	    	$table->data[] = array('<strong><a href="'.$CFG->wwwroot."/blocks/mou_ege/textbook/textbook.php?rid=$rid&amp;sid=0\">".get_string('textbooks', 'block_mou_ege').'</a></strong>',
 	                          get_string('description_textbook','block_mou_ege'));
*/                  

		   }
	}

	if ($admin_is  || $region_operator_is)	 {
	    	$table->data[] = array('<strong><a href="'.$CFG->wwwroot.'/blocks/mou_ege/ege/exportxml.php?backup_name=1&amp;backup_unique_code=1">'.get_string('xmlexport', 'block_mou_ege').'</a></strong>',
 	                          get_string('description_xmlexport','block_mou_ege'));
	    	$table->data[] = array('<strong><a href="'.$CFG->wwwroot."/blocks/mou_ege/test/import.php\">".get_string('importresults', 'block_mou_ege').'</a></strong>',
 	                          get_string('description_importresults','block_mou_ege'));
	    	$table->data[] = array('<strong><a href="'.$CFG->wwwroot."/blocks/mou_ege/ege/formpoints.php?rid=0\">".get_string('points', 'block_mou_ege').'</a></strong>',
 	                          get_string('description_points','block_mou_ege'));

	    	$table->data[] = array('<hr>', '<hr>');

	}

	$pupil_is = ispupil();
    if ($pupil_is || $admin_is || $region_operator_is)	{
	    	$table->data[] = array('<strong><a href="'.$CFG->wwwroot."/blocks/mou_ege/appeal/profile.php\">".get_string('profilepupil', 'block_mou_ege').'</a></strong>',
 	                          get_string('profilepupil','block_mou_ege'));
	    	$table->data[] = array('<strong><a href="'.$CFG->wwwroot."/blocks/mou_ege/appeal/marks.php\">".get_string('markspupil', 'block_mou_ege').'</a></strong>',
 	                          get_string('description_markspupil','block_mou_ege'));
	    	$table->data[] = array('<strong><a href="'.$CFG->wwwroot."/blocks/mou_ege/appeal/appeal.php\">".get_string('appeal', 'block_mou_ege').'</a></strong>',
 	                          get_string('description_appeal','block_mou_ege'));
    }

    print_table($table);
    // print_color_table($table);

  // Параметр $number - сообщает число символов в пароле
/*
	if ($admin_is)	{
	  for ($i = 0; $i<100; $i++)	{
	   	echo  '<center>' . generate_password2(6) . '&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;' . generate_password(10) . '</center><br>';
	  }
	}

*/
    print_footer($site);



/*

D:\Repository/mou_ege/class/classlist.php,v  <--  classlist.php
new revision: 1.14; previous revision: 1.13
done
Checking in class/classpupils.php;
D:\Repository/mou_ege/class/classpupils.php,v  <--  classpupils.php
new revision: 1.26; previous revision: 1.25
done
Checking in class/import9classes.php;
D:\Repository/mou_ege/class/import9classes.php,v  <--  import9classes.php
new revision: 1.2; previous revision: 1.1
done
Checking in pupils/delpupil.php;
D:\Repository/mou_ege/pupils/delpupil.php,v  <--  delpupil.php
new revision: 1.6; previous revision: 1.5
done
Checking in pupils/pupilcard.php;
D:\Repository/mou_ege/pupils/pupilcard.php,v  <--  pupilcard.php
new revision: 1.8; previous revision: 1.7
done
Checking in pupils/tabspupil.php;
D:\Repository/mou_ege/pupils/tabspupil.php,v  <--  tabspupil.php
new revision: 1.4; previous revision: 1.3
done
Checking in pupils/movepupil.php,v 1.1
*/

?>

