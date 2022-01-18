<?php // $Id: importmarks.php,v 1.21 2011/06/15 07:41:28 shtifanov Exp $

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

    $strimport = get_string('importmarks', 'block_mou_ege');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= "-> $strimport";
    print_header("$SITE->shortname: $strimport", $SITE->fullname, $breadcrumbs);

	print_tabs_years($yid, "importmarks.php?yid=");

    $currenttab = 'importmarks';
    include('tabsmark.php');

   /*
	echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
	listbox_discipline_ege("importmarks.php?yid=$yid&amp;did=", $rid, $sid, $yid, $did);
	echo '</table>';
*/
    if ($did != 0)	{
	    if (!$discipline_ege = get_record('monit_school_discipline_ege', 'id', $did))	{
	    	error('Discipline not found!', "importmarks.php");
	    }
    	$codepredmet = $discipline_ege->code;
    }

	if ($action == 'clear' && $did != 0 && $tload != 0) 	{
		if (delete_records('monit_gia_results', 'yearid', $yid , 'codepredmet', $codepredmet, 'timeload', $tload))  {
		     $discipline_ege->timeload = 0;
		     $discipline_ege->timepublish = 0;
		     $discipline_ege->timestartappealhearing = 0;
		     $discipline_ege->timefilingappealstart = 0;
		     $discipline_ege->timefilingappealend = 0;
		     update_record('monit_school_discipline_ege', $discipline_ege);
	  		 if ($giadate = get_record_select('monit_school_gia_dates', "yearid=$yid AND discegeid={$discipline_ege->id} AND timeload = $tload"))  {
		  			$discipline_ege->id = $giadate->id;
					update_record('monit_school_gia_dates', $discipline_ege);   
		  		}	

        } else {
             error("Could not delete records.", "importmarks.php");
        }
	}


		if ($action == 'upload')	{
			
	  		  if ($giadate = get_record_select('monit_school_gia_dates', "yearid=$yid AND discegeid={$discipline_ege->id} AND date_gia = '$giad'"))  {
	  		  		if ($giadate->timeload != 0 && $giadate->timepublish != 0)	{
	  		  			error(get_string('remarkimport', 'block_mou_ege'), "importmarks.php");
	  		  		}  
	  		  }	
			
	          echo '<hr />';
	          $dir = '1/appeal/marks';
	          $um = new upload_manager('newfile'.$codepredmet.'_'.$giad, true, false, false, false, 32097152);
	          // print_r($um); echo '<hr>';
	          if ($um->process_file_uploads($dir))  {
	              notify(get_string('uploadedfile'), 'green', 'center');
		          $newfile_name = $CFG->dataroot.'/'.$dir.'/'.$um->get_new_filename();
		          $newfile_name = addslashes($newfile_name);
		          // echo $newfile_name . '<hr>';
		          // print_r($um); echo '<hr>';
	          } else {
		          error(get_string("uploaderror", "assignment"), "importmarks.php"); //submitting not allowed!
	          }

	          if (!unzip_file($newfile_name, '', false)) {
	              error(get_string("unzipfileserror","error"));
	          }

	          $newfile_name = $CFG->dataroot.'/'.$dir.'/results.csv';
	          $newfile_name = addslashes($newfile_name);

			  if (!file_exists($newfile_name)) {
		             error("File '$newfile_name' not found!", "importmarks.php");
			  }

	    	  // $newfile_name = $CFG->wwwroot . '/file.php/1/appeal/marks/results.csv';
			  // http://mou.bsu.edu.ru/file.php/1/appeal/marks/results.csv

			  $nowtime =  time(); 	
			  $strsql = "ALTER TABLE {$CFG->prefix}monit_gia_results_temp MODIFY COLUMN timeload INT(10) UNSIGNED NOT NULL DEFAULT ". $nowtime;
	          if (!execute_sql($strsql, false))	{
	              error('FATAL ERROR !!! :'.$strsql, "importmarks.php");
	          }
			  
			  
	          if (!execute_sql("TRUNCATE TABLE {$CFG->prefix}monit_gia_results_temp", false))	{
	              error('Can not TRUNCATE TABLE!', "importmarks.php");
	          }
              
              $fr = fopen ($newfile_name, "r");
              $hdr = fgets($fr);
              fclose($fr); 
              $hdr = str_replace (';', ',', $hdr); 
                
              // echo $hdr;  exit();  

			  // LOAD DATA INFILE 'C:\\usr\\wwwroot\\moudata\\1\\appeal\\marks\\results.csv' INTO TABLE  mdl_monit_gia_results
			  // FIELDS TERMINATED BY ';' IGNORE 1 LINES (yearid,rayonid,schoolid,classid,userid,pp,audit,codepredmet,variant,sidea,sideb,sidec,ball,ocenka)

	   		  if ($CFG->wwwroot  == 'http://cdoc06/mou')	{
	   		  		// LOCALHOST LOCALHOST LOCALHOST LOCALHOST LOCALHOST LOCALHOST LOCALHOST
	   		  		$strsql = "LOAD DATA INFILE '$newfile_name' INTO TABLE  {$CFG->prefix}monit_gia_results_temp ";	
	   		  } else {
              		// SERVER SERVER SERVER SERVER SERVER SERVER SERVER SERVER SERVER	   		  	
	   		  		$strsql = "LOAD DATA LOCAL INFILE '$newfile_name' INTO TABLE  {$CFG->prefix}monit_gia_results_temp ";	
	   		  }
	   		  

	  		  $strsql .= " FIELDS TERMINATED BY ';' IGNORE 1 LINES ";
			  $strsql .= "($hdr);";
              // echo $strsql;  exit();
	          if (!execute_sql($strsql, false))	{
	              error('FATAL ERROR !!! :'.$strsql, "importmarks.php");
	          }
	          
			  $tusercount = count_records_sql("SELECT COUNT(*) FROM {$CFG->prefix}monit_gia_results_temp
											WHERE yearid=$yid AND codepredmet=$codepredmet");

			  if ($tusercount == 0)	{
  	              error(get_string('resulthisnotfound', 'block_mou_ege'), "importmarks.php");
			  }
	          
		   	  notify(get_string('temploadsuccess', 'block_mou_ege'), 'green', 'center');
	          echo '<hr />';
              // error('!!!!', "importmarks.php");

	  	      $strsql = "INSERT INTO {$CFG->prefix}monit_gia_results ($hdr, timeload)
	  				   SELECT $hdr, timeload
					   FROM {$CFG->prefix}monit_gia_results_temp
					   where codepredmet=$codepredmet and yearid=$yid";

            // echo $strsql;  exit();
		    if (execute_sql($strsql, false)) {
		    	  
				$startnow = usergetdate($nowtime);
				list($d, $m, $y) = array(intval($startnow['mday']), intval($startnow['mon']), intval($startnow['year']));
				$d += DELTADAY;
				$tappeal =  make_timestamp($y, $m, $d, STARTHOUR);

		  		$newrec->id = $discipline_ege->id; 
		  		$newrec->timeload = $nowtime;
		  		$newrec->timepublish = $nowtime+2*HOURSECS;
		  		$newrec->timefilingappealstart = $nowtime;
		  		$newrec->timefilingappealend = $tappeal;
		  		$newrec->timestartappealhearing = $tappeal;
		  		update_record('monit_school_discipline_ege', $newrec);
		  		
		  		if ($giadate = get_record_select('monit_school_gia_dates', "yearid=$yid AND discegeid={$discipline_ege->id} AND date_gia = '$giad'"))  {
		  			$newrec->id = $giadate->id;
					update_record('monit_school_gia_dates', $newrec);   
		  		}	
				notice('<div align=center>'.get_string('gialoadsuccess', 'block_mou_ege').'</div>', "importmarks.php");
		        echo '<hr />';
		    } else {
	            error($strsql, "importmarks.php");
		    }

	    }

	    $strupload = get_string('importmarks', 'block_mou_ege');
	    print_heading_with_help($strupload, 'importmarks', 'mou');


//	    print_simple_box_start('center', '50%', 'white');
	    $CFG->maxbytes = get_max_upload_file_size();
		$struploadafile = "Загрузка ZIP-файла, содержащего оценки учеников, <br> и полученного в результате работы программы 'Автоматизированная система ОРГИА'.";
	    $strmaxsize = get_string("maxsize", "", display_size($CFG->maxbytes));
//		print_simple_box_end();

	    echo "<p align=center>$struploadafile <br>($strmaxsize)</p>";

/*
	    $strsql = "SELECT COUNT(discegeid) as cnt FROM {$CFG->prefix}monit_school_gia_dates 
			   where yearid=$yid and discmiid=0
			   GROUP BY discegeid 
			   HAVING COUNT(discegeid)>1";

		$maxcnt = 0;			   
	    if ($cntdates = get_records_sql($strsql))	{
	    	foreach ($cntdates as $cntdate)	 {
	    		if ($cntdate->cnt > $maxcnt)	$maxcnt = $cntdate->cnt;
	    	}
	    }
	
	    $toprow = array();
	    for ($i=1; $i<=$maxcnt; $i++)	{
		    $toprow[] = new tabobject($i, "importmarks.php?did=$did&amp;yid=$yid&amp;nd=$i",
	    	            get_string('numday_i', 'block_mou_ege', $i));
	    }
	
	    $tabs = array($toprow);
	    print_tabs($tabs, $numday, NULL, NULL);
*/

		$table = table_import_marks($yid);


	    print_color_table($table);
/*
	    upload_print_form_fragment(1,array('newfile'),false,null,0,$CFG->maxbytes,false);
	    echo '<input type="submit" name="save" value="'.get_string('uploadthisfile').'" />';
*/

    print_footer();


function table_import_marks($yid)
{
    global $CFG;

    $table->head  = array ('Код',  get_string('disciplinename','block_mou_ege') . '/'. 
								get_string('giadate','block_mou_ege'),
    							 get_string('countsmark','block_mou_ege'),
    							 get_string('timeload','block_mou_ege'),
    							 get_string('publishtimemark','block_mou_ege'),
    							 get_string('timestartappealhearing','block_mou_ege'),
    							 get_string('appealperiod','block_mou_ege'),
    							 get_string('loadmark','block_mou_ege'),
    							 get_string('action','block_mou_ege'));
    $table->align = array ('center', 'left',  'center', 'center', 'center', 'center', 'center', 'center', 'center');
    $table->class = 'moutable';
  	$table->width = '60%';
    $table->size = array ('5%', '10%', '10%',  '10%', '10%', '10%', '10%', '30%', '10%');
	$table->columnwidth = array (4, 10, 10, 10, 10, 10, 10, 10, 30, 10);
    $table->titles = array();
    $table->titles[] = get_string('disciplines_ege', 'block_mou_ege');
    $table->worksheetname = get_string('disciplines_ege', 'block_mou_ege');
    $table->titlesrows = array(30);
    $table->downloadfilename = 'publish_date_gia';

//	$disciplines = get_records ('school_discipline', 'curriculumid', $cid);
	$disciplines =  get_records_sql ("SELECT id, yearid, name, code, timeload, timepublish,
											timestartappealhearing, timefilingappealstart, timefilingappealend
									  FROM  {$CFG->prefix}monit_school_discipline_ege
									  WHERE yearid=$yid
									  ORDER BY code");


	if ($disciplines)	{
	    $CFG->maxbytes = get_max_upload_file_size();
		foreach ($disciplines as $discipline) {

			if ($giadates =  get_records_sql ("SELECT id, date_gia, deltatime, timeload, timestartappealhearing, 
															timefilingappealend, timefilingappealstart, timepublish 
											  FROM  {$CFG->prefix}monit_school_gia_dates
											  WHERE yearid=$yid AND discegeid={$discipline->id} ORDER BY date_gia"))  {
				foreach ($giadates as $giadate)	{
					$strdate = convert_date($giadate->date_gia, 'en', 'ru');
					 
					$strtimeload = $strdates = $strtimeh = $strinterval = $strtimefs = $strtimefe = '-';					 
					if ($giadate->timeload != 0)	{
					   // $strdates =  get_rus_format_date($discipline->timepublish);
		   			   $strtimeload =  date ("d.m.Y H:i", $giadate->timeload);
		
					   $strdates =  date ("d.m.Y H:i", $giadate->timepublish);
		
					   $strtimeh = date ("d.m.Y H:i", $giadate->timestartappealhearing);
		   	    	   $strtimeh = "<a href=\"../appeal/setappealtime.php?yid=$yid\">" . $strtimeh . '</a>';
		
			           $startnow = usergetdate($giadate->timefilingappealstart);
			           list($d, $m, $y) = array(intval($startnow['mday']), intval($startnow['mon']), intval($startnow['year']));
			  		   $monthstring = get_string('lm_'.$m,'block_monitoring');
			           $endnow = usergetdate($giadate->timefilingappealend);
			           list($d1, $m1, $y1) = array(intval($endnow['mday']), intval($endnow['mon']), intval($endnow['year']));
			   		   $monthstring1 = get_string('lm_'.$m1,'block_monitoring');
			    	   $strinterval = "$d $monthstring -<br> $d1 $monthstring1 <br>$y1 г."; // "$d.$m.$y - $d1.$m1.$y1";
		
			    	   $strinterval = "<a href=\"../appeal/setappealtime.php?yid=$yid\">" . $strinterval . '</a>';
			    	   
 	   		    	   $startnow = $endnow = 0;
					} else $giadate->timeload = 0;
		
		
				    $usercount = count_records_sql("SELECT COUNT(*) FROM {$CFG->prefix}monit_gia_results
													WHERE yearid=$yid AND codepredmet={$discipline->code} AND timeload = {$giadate->timeload}");
		
		            $name1 = 'newfile'.$discipline->code.'_'.$giadate->date_gia;
		            $name2 = 'save'.$discipline->code.'_'.$giadate->date_gia;
		            $name3 = get_string('uploadgiapredmetsmall', 'block_mou_ege', $discipline->name . ' - ' . $strdate);
		
				    $strload = '<form enctype="multipart/form-data" method="post" action="importmarks.php">';
				    $strload .= '<input type="hidden" name="yid" value="'.$yid.'" />';
				    $strload .= '<input type="hidden" name="action" value="upload" />';
				    $strload .= '<input type="hidden" name="did" value="'.$discipline->id.'" />';
				    $strload .= '<input type="hidden" name="giad" value="'.$giadate->date_gia.'" />';
					$strload .= '<input type="hidden" name="MAX_FILE_SIZE" value="'. $CFG->maxbytes .'" />'."\n";
			        $strload .= '<input type="file" size="40" name="'. $name1 .'" alt="'. $name1 .'" />'."\n";
					$strload .= '<input type="submit" name="'. $name2 .'" value="'. $name3 .'" />';
					$strload .= '</form>';
		
					$title = get_string('deletemark','block_mou_ege', $discipline->name);
			  	 	$strlinkupdate = "<a title=\"$title\" href=\"importmarks.php?action=clear&amp;yid=$yid&amp;did={$discipline->id}&amp;tload={$giadate->timeload}\">";
					$strlinkupdate .= "<img src=\"{$CFG->pixpath}/i/cross_red_big.gif\" alt=\"$title\" /></a>&nbsp;";
		
					$table->data[] = array ($discipline->code.'.', $discipline->name . '<br>' . $strdate, $usercount, $strtimeload, $strdates, $strtimeh, $strinterval, $strload, $strlinkupdate);

				}

			}
			
			$table->data[] = array ('<hr>', '<hr>' , '<hr>', '<hr>', '<hr>', '<hr>', '<hr>', '<hr>', '<hr>');
		}
	}

	return $table;
}

/*
			  $usercount = count_records_sql("SELECT COUNT(*) FROM {$CFG->prefix}monit_gia_results
											WHERE yearid=$yid AND codepredmet=$codepredmet");

			  $tusercount = count_records_sql("SELECT COUNT(*) FROM {$CFG->prefix}monit_gia_results_temp
											WHERE yearid=$yid AND codepredmet=$codepredmet");

			  if ($tusercount == 0)	{
  	              error(get_string('resulthisnotfound', 'block_mou_ege'), "importmarks.php");
			  }

			  if ($usercount == 0 || $usercount <= $tusercount)	{
			  	    delete_records('monit_gia_results', 'yearid', $yid, 'codepredmet', $codepredmet);

			  } else {
				  	error(get_string('recordsnotmatch', 'block_mou_ege'). " ($usercount &bt; $tusercount)", "importmarks.php");
			  }

*/
					/*
					insert into `mou`.`mdl_monit_gia_results_temp` (yearid, rayonid, schoolid, classid, userid, pp, audit, codepredmet, variant, sidea, sideb, sidec, ball, ocenka)
					SELECT yearid, rayonid, schoolid, classid, userid, pp, audit, codepredmet, variant, sidea, sideb, sidec, ball, ocenka
					FROM `mou`.`mdl_monit_gia_results`
					where pp=333
					*/



?>
