<?php // $Id: i.php,v 1.21 2011/06/15 07:41:28 shtifanov Exp $

    require_once("../../../config.php");
    require_once('../../monitoring/lib.php');
    require_once('../../mou_att/lib_att.php');
    require_once('../lib_ege.php');
	require_once($CFG->dirroot.'/lib/uploadlib.php');
    require_once($CFG->dirroot.'/lib/xmlize.php');

	define('DELTADAY', 1);
	define('STARTHOUR', 15);

    $did = optional_param('did', 0, PARAM_INT);       // Discipline id
    $yid = optional_param('yid', 0, PARAM_INT);       // Year id
  	$action = optional_param('action', '');       // action
  	// $numday = optional_param('nd', 1, PARAM_INT);       // numday
  	$giad = optional_param('giad', '');       // GIA date
  	$tload = optional_param('tload', 0, PARAM_INT);       // numday
    $rid = $sid = $gid = 0;

    if ($yid == 0)	{
    	$yid = get_current_edu_year_id();
    }

	require_login();

	$admin_is = isadmin();
	$region_operator_is = ismonitoperator('region');
	if (!$admin_is && !$region_operator_is) {
        error(get_string('adminaccess', 'block_monitoring'), "$CFG->wwwroot/login/index.php");
	}

	if (isregionviewoperator() || israyonviewoperator())  {
        error(get_string('adminaccess', 'block_monitoring'));
	}

    $привет = 'Hello';
    
    echo $привет;
     
    $strimport = get_string('importmarks', 'block_mou_ege');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= "-> $strimport";
    print_header("$SITE->shortname: $strimport", $SITE->fullname, $breadcrumbs);

	print_tabs_years($yid, "i.php?yid=");

    $currenttab = 'importmarks';
    include('tabsmark.php');

		if ($action == 'upload')	{
			
	          echo '<hr />';
	          $dir = '1/appeal/marks';
	          $um = new upload_manager('newfile', false, false, false, false, 32097152);
	          // print_r($um); echo '<hr>';
	          if ($um->process_file_uploads($dir))  {
	              notify(get_string('uploadedfile'), 'green', 'center');
		          $newfile_name = $CFG->dataroot.'/'.$dir.'/'.$um->get_new_filename();
		          $newfile_name = addslashes($newfile_name);
		          // echo $newfile_name . '<hr>';
		          // print_object($um); echo '<hr>';
	          } else {
		          error(get_string("uploaderror", "assignment"), "i.php"); //submitting not allowed!
	          }
/*
	          if (!unzip_file($newfile_name, '', false)) {
	              error(get_string("unzipfileserror","error"));
	          }

	          $newfile_name = $CFG->dataroot.'/'.$dir.'/results.csv';
	          $newfile_name = addslashes($newfile_name);
*/
			  if (!file_exists($newfile_name)) {
		             error("File '$newfile_name' not found!", "i.php");
			  } else {
			         notify("OK!", 'green');
                     $contents = file_get_contents($newfile_name);
                     $data = xmlize($contents);
                     foreach ($data as $d0) {
                        foreach ($d0 as $d1)    {
                            echo $d1->Тип . '!!!!';
                            print_object($d1);
                            foreach ($d1 as $i1 => $d2) {
                                echo $i1 . $d2.'<hr>';    
                            }
                            // echo $d1['Тип'];
                            
                                                        
                        }
                     }

			  }

	    	  // $newfile_name = $CFG->wwwroot . '/file.php/1/appeal/marks/results.csv';
			  // http://mou.bsu.edu.ru/file.php/1/appeal/marks/results.csv

	    }

	    $strupload = get_string('importmarks', 'block_mou_ege');
	    print_heading_with_help($strupload, 'importmarks', 'mou');


//	    print_simple_box_start('center', '50%', 'white');
	    $CFG->maxbytes = get_max_upload_file_size();
		$struploadafile = "Загрузка ZIP-файла, содержащего оценки учеников, <br> и полученного в результате работы программы 'Автоматизированная система ОРГИА'.";
	    $strmaxsize = get_string("maxsize", "", display_size($CFG->maxbytes));
//		print_simple_box_end();

	    echo "<p align=center>$struploadafile <br>($strmaxsize)</p>";

        $name1 = 'A';
        $name2 = 'B';
        $name3 = get_string('uploadgiapredmetsmall', 'block_mou_ege', '');
        
        $strload = '<form enctype="multipart/form-data" method="post" action="i.php">';
        $strload .= '<input type="hidden" name="yid" value="'.$yid.'" />';
        $strload .= '<input type="hidden" name="action" value="upload" />';
    	$strload .= '<input type="hidden" name="MAX_FILE_SIZE" value="'. $CFG->maxbytes .'" />'."\n";
        $strload .= '<input type="file" size="40" name="newfile" alt="'. $name1 .'" />'."\n";
    	$strload .= '<input type="submit" name="'. $name2 .'" value="'. $name3 .'" />';
    	$strload .= '</form>';
        
        echo $strload;


	    print_color_table($table);

    print_footer();


?>
