<?php // $Id: importappeal.php,v 1.5 2010/06/21 11:33:22 Shtifanov Exp $

    require_once("../../../config.php");
    require_once('../../monitoring/lib.php');
    require_once('../../mou_att/lib_att.php');
    require_once('../lib_ege.php');
	require_once($CFG->dirroot.'/lib/uploadlib.php');

	define('DELTADAY', 1);
	define('STARTHOUR', 15);

    $did = optional_param('did', 0, PARAM_INT);       // Discipline id
    $yid = optional_param('yid', 0, PARAM_INT);       // Year id
  	$action = optional_param('action', '');       // action
    $rid = $sid = $gid = $uid = 0;

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

    $strimport = get_string('importappeal', 'block_mou_ege');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= "-> $strimport";
    print_header("$SITE->shortname: $strimport", $SITE->fullname, $breadcrumbs);

	print_tabs_years($yid, "importmarks.php?yid=");

    $currenttab = 'importappeal';
    include('tabs2.php');

  
      if ($did != 0)	{
	    if (!$discipline_ege = get_record('monit_school_discipline_ege', 'id', $did))	{
	    	error('Discipline not found!');
	    }
    	$codepredmet = $discipline_ege->code;
    }

    $csv_delimiter = ';';
    $linenum = 1; // since header is line 1


	if ($action == 'upload')	{
	    echo '<hr />';
	    $um = new upload_manager('newfile'.$codepredmet, true, false, false, false, 32097152);
  		// $um = new upload_manager('userfile',false,false,null,false,0);
		$f = 0;
		if ($um->preprocess_files())  {
			$filename = $um->files['newfile'.$codepredmet]['tmp_name'];

		    @set_time_limit(0);
		    @raise_memory_limit("192M");
		    if (function_exists('apache_child_terminate')) {
		        @apache_child_terminate();
		    }

			$text = file($filename);
			if($text == FALSE)	{
				error(get_string('errorfile', 'block_monitoring'), "$CFG->wwwroot/blocks/mou_ege/class/importappeal.php?sid=$sid&amp;rid=$rid&amp;yid=$yid");
			}
			$size = sizeof($text);

            /*
			$textlib = textlib_get_instance();
  			for($i=0; $i < $size; $i++)  {
				$text[$i] = $textlib->convert($text[$i], 'win1251');
            }
            */

		    // $required = array("status" => 1, "yearid" => 1, "rayonid" => 1);
		    //  schoolid    classid    userid    pp    audit    codepredmet    variant    sidea    sideb    sidec    ball    ocenka


            // --- get and check header (field names) ---
            $header = split($csv_delimiter, $text[0]);

  			for($i = 1; $i < $size; $i++)  {

	            $line = split($csv_delimiter, $text[$i]);
 	  	        foreach ($line as $key => $value) {
 	  	        	$j = trim($header[$key]);
  	                $record[$j] = trim($value);
   	 	        }

   	 	        $yid  = $record['yearid'];
   	 	        $uid  = $record['userid'];
   	 	        $code = $record['codepredmet'];
   	 	        
   	 	        if ($code == $codepredmet)	{ // load only appeal for this predmet
                	// print_r($record); echo '<hr>';

					if ($record['status'] == 8)  	{
	                      if ($appeal = get_record('monit_appeal', 'yearid', $yid, 'userid', $uid, 'codepredmet', $code))	{
				  	     	  set_field('monit_appeal', 'timeappeal', 0, 'yearid', $yid, 'userid', $uid, 'codepredmet', $code);
		  		 	    	  set_field('monit_appeal', 'status', 8, 'yearid', $yid, 'userid', $uid, 'codepredmet', $code);
		  		 	    	  notify(get_string('dismissalofappeal','block_mou_ege', $uid), 'green', 'center');
						  } else {
		                      notify(get_string('appealnotfound','block_mou_ege', $uid));
						  }
	                } else 	if ($record['status'] == 6)  	{
	                      if ($appeal = get_record('monit_appeal', 'yearid', $yid, 'userid', $uid, 'codepredmet', $code))	{
				 	      	  set_field('monit_appeal', 'status', 6, 'id', $appeal->id);
		                      if ($gia_res = get_record('monit_gia_results', 'yearid', $yid, 'userid', $uid, 'codepredmet', $code))	{
					 	      	    set_field('monit_appeal', 'ballold', $gia_res->ball, 'id', $appeal->id);
					 	      	    set_field('monit_appeal', 'ocenkaold', $gia_res->ocenka, 'id', $appeal->id);
	                                $newgia->id = $gia_res->id;
			   		                foreach ($record as $name => $value) {
			   		                	  $newgia->{$name} = $value;
			   		                }
			   		                /*
					                print_r($record); echo '<hr>';
			   		                print_r($newgia);
			   		                set_field('monit_appeal', 'ocenka', $record['ocenka'], 'yearid', $yid, 'userid', $uid, 'codepredmet', $code);
			   		                */
									if (update_record('monit_gia_results', $newgia))		{
										 // add_to_log(1, 'school', 'one discipline added', "blocks/school/curriculum/addiscipline.php?mode=2&amp;fid=$fid&amp;sid=$sid&amp;cid=$cid", $USER->lastname.' '.$USER->firstname);
										notify(get_string('giaresultupdated','block_mou_ege', $uid), 'green', 'center');
									} else  {
										error(get_string('erroringiaresultupdated','block_mou_ege', $uid), "importappeal.php?yid=$yid");
									}
		                      } else {
			                      notify(get_string('marknotfound','block_mou_ege', $uid));
		                      }
						  } else {
		                      notify(get_string('appealnotfound','block_mou_ege', $uid));
						  }
	                } else 	{
	                      notify(get_string('statusappealnotcorrect','block_mou_ege'));
	                }
	            }    
	        }
	    }
	}

    print_heading_with_help($strimport, 'importappeal', 'mou');


//	    print_simple_box_start('center', '50%', 'white');
    $CFG->maxbytes = get_max_upload_file_size();
	$struploadafile = "Загрузка CSV-файла, содержащего оценки учеников, измененные после расмотрения апелляций'.";
    $strmaxsize = get_string("maxsize", "", display_size($CFG->maxbytes));
//		print_simple_box_end();

    echo "<p align=center>$struploadafile <br>($strmaxsize)</p>";
	$table = table_import_appeal($yid);
    print_color_table($table);
/*
	    upload_print_form_fragment(1,array('newfile'),false,null,0,$CFG->maxbytes,false);
	    echo '<input type="submit" name="save" value="'.get_string('uploadthisfile').'" />';
*/

    print_footer();


function table_import_appeal($yid)
{
    global $CFG;

    $table->head  = array ('Код',  get_string('disciplinename','block_mou_ege'),
    							 get_string('countsmark','block_mou_ege'),
    							 get_string('countsappeal','block_mou_ege'),
    							 get_string('countsappealok','block_mou_ege'),
    							 get_string('loadmark','block_mou_ege'));
    							 // get_string('countsappealno','block_mou_ege'),
    							 // get_string('action','block_mou_ege'));
    $table->align = array ('center', 'left',  'center', 'center',  'center');
    $table->class = 'moutable';
  	$table->width = '60%';
    $table->size = array ('5%', '10%', '10%', '10%', '10%');
	$table->columnwidth = array (4, 10, 10, 10, 10, 10);
    $table->titles = array();
    $table->titles[] = get_string('disciplines_ege', 'block_mou_ege');
    $table->worksheetname = get_string('disciplines_ege', 'block_mou_ege');
    $table->titlesrows = array(30);
    $table->downloadfilename = 'import_appeal';

//	$disciplines = get_records ('school_discipline', 'curriculumid', $cid);
	$disciplines =  get_records_sql ("SELECT id, yearid, name, code, timepublish, timestartappealhearing, timefilingappealstart, timefilingappealend
									  FROM  {$CFG->prefix}monit_school_discipline_ege
									  WHERE yearid=$yid
									  ORDER BY code");

	$i = 0;
	if ($disciplines)	{
	    $CFG->maxbytes = get_max_upload_file_size();
		foreach ($disciplines as $discipline) {

		    $usercount = count_records_sql("SELECT COUNT(*) FROM {$CFG->prefix}monit_gia_results
											WHERE yearid=$yid AND codepredmet={$discipline->code}");

		    $countsappeal = count_records_sql("SELECT COUNT(*) FROM {$CFG->prefix}monit_appeal
											WHERE yearid=$yid AND codepredmet={$discipline->code}");

		    $countsappealok = count_records_sql("SELECT COUNT(*) FROM {$CFG->prefix}monit_appeal
											WHERE yearid=$yid AND codepredmet={$discipline->code} AND status = 6");

            $name1 = 'newfile'.$discipline->code;
            $name2 = 'save'.$discipline->code;
            $name3 = get_string('uploadappealthispredmet', 'block_mou_ege', $discipline->name);

		    $strload = '<form enctype="multipart/form-data" method="post" action="importappeal.php">';
		    $strload .= '<input type="hidden" name="yid" value="'.$yid.'" />';
		    $strload .= '<input type="hidden" name="action" value="upload" />';
		    $strload .= '<input type="hidden" name="did" value="'.$discipline->id.'" />';
			$strload .= '<input type="hidden" name="MAX_FILE_SIZE" value="'. $CFG->maxbytes .'" />'."\n";
	        $strload .= '<input type="file" size="50" name="'. $name1 .'" alt="'. $name1 .'" />'."\n";
			$strload .= '<input type="submit" name="'. $name2 .'" value="'. $name3 .'" />';
			$strload .= '</form>';

			$i++;
			$table->data[] = array ($discipline->code.'.', $discipline->name, $usercount, $countsappeal, $countsappealok, $strload);
		}
	}

	return $table;
}

?>
