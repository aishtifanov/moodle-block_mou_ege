<?php // $Id: importmarkslow.php,v 1.2 2011/06/15 06:54:01 shtifanov Exp $

    require_once("../../../config.php");
    require_once('../../monitoring/lib.php');
    require_once('../../mou_att/lib.php');
    require_once('../lib_ege.php');
	require_once($CFG->dirroot.'/lib/uploadlib.php');

    $yid = optional_param('yid', 0, PARAM_INT);       // Year id
    $rid = $sid = $gid = 0;

    if ($yid == 0)	{
    	$yid = get_current_edu_year_id();
    }

    $nowtime = time();

    if (! $site = get_site()) {
        redirect("$CFG->wwwroot/$CFG->admin/index.php");
    }

	$admin_is = isadmin();
	$region_operator_is = ismonitoperator('region');
	if (!$admin_is && !$region_operator_is) {
        error(get_string('staffaccess', 'block_mou_att'));
	}

	if (isregionviewoperator() || israyonviewoperator())  {
        error(get_string('adminaccess', 'block_monitoring'));
	}

    $strimport = get_string('importmarks', 'block_mou_ege');


	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= "-> $strimport";
    print_header("$site->shortname: $strimport", $site->fullname, $breadcrumbs);

	print_tabs_years($yid, "importmarks.php?yid=");

    $currenttab = 'importmarks';
    include('tabsmark.php');

    $csv_delimiter = ';';
    $linenum = 1; // since header is line 1

	/// If a file has been uploaded, then process it

//	if (!empty($frm) ) {
		$um = new upload_manager('userfile',false,false,null,false,0);
		$f = 0;
		if ($um->preprocess_files()) {
			$filename = $um->files['userfile']['tmp_name'];

		    @set_time_limit(0);
		    @raise_memory_limit("192M");
		    if (function_exists('apache_child_terminate')) {
		        @apache_child_terminate();
		    }

			$text = file($filename);
			if($text == FALSE)	{
				error(get_string('errorfile', 'block_monitoring'), "$CFG->wwwroot/blocks/mou_ege/class/importclasses.php?sid=$sid&amp;rid=$rid&amp;yid=$yid");
			}
			$size = sizeof($text);

			$textlib = textlib_get_instance();
  			for($i=0; $i < $size; $i++)  {
				$text[$i] = $textlib->convert($text[$i], 'win1251');
            }
            unset ($textlib);

		    $required = array("rayonid" => 1, "schoolid" => 1, "classid" => 1, "userid" => 1,
							   "pp" => 1, "audit" => 1, "codepredmet" => 1, "variant" => 1,
							   "sidea" => 1, "sideb" => 1, "sidec" => 1, "sidec" => 1);
			$optional = array("ball" => 1, "ocenka" => 1);

            // --- get and check header (field names) ---
            $header = split($csv_delimiter, $text[0]);

            // print_r($header);


            // check for valid field names
            foreach ($header as $i => $h) {
                $h = trim($h);
                $header[$i] = $h;
                if ( !(isset($required[$h]) || isset($optional[$h])) )  {
                    error(get_string('invalidfieldname', 'error', $h), "importmarks.php");
                }
                if (isset($required[$h])) {
                    $required[$h] = 0;
                }
            }

            if (!execute_sql("TRUNCATE TABLE {$CFG->prefix}monit_gia_results", false))	{
                   error('Can not TRUNCATE TABLE!', "importmarks.php");
            }

  			for($i = 1; $i < $size; $i++)  {

	            $line = split($csv_delimiter, $text[$i]);
 	  	        foreach ($line as $key => $value) {
  	                $record[$header[$key]] = trim($value);
   	 	        }

                $linenum++;
                /*
                if ($linenum > 100)	 {
					error(get_string('verybiglinenum', 'block_mou_ege'), "importclasses.php?sid=$sid&amp;rid=$rid&amp;yid=$yid");
                }
                */

                // print_r($record);
                // add fields to object $user
                foreach ($record as $name => $value) {
                    // check for required values
                    if (isset($required[$name]) and !$value) {
                        error(get_string('missingfield', 'error', $name). " ". get_string('erroronline', 'error', $linenum), "importmarks.php");
                    } else {
                       	$mark->{$name} = $value;
                    }
                }

				$mark->yearid = $yid;
                $mark->timemodified = $nowtime;

				if ($idnew = insert_record('monit_gia_results', $mark))	{
                    notify(get_string('markadded','block_mou_ege', $mark->ocenka), 'green', 'center');
				} else {
				    print_r($rec); echo '<hr>';
					error(get_string('errorinaddingmark','block_mou_ege'), "importmark.php");
				}


                unset($mark);
            }
		    $strusersnew = get_string("usersnew");
		    $linenum--;
    	    notify("Общее количество оценок: $linenum", 'green', 'center');
	        echo '<hr />';
       }

	    $strupload = get_string('importmarks', 'block_mou_ege');
	    print_heading_with_help($strupload, 'importmarks', 'mou');

		// print_heading($strclasses, "center", 3);


		/// Print the form
	    $maxuploadsize = get_max_upload_file_size();
		$strchoose = ''; // get_string("choose"). ':';

	    echo '<center>';
	    echo '<form method="post" enctype="multipart/form-data" action="importmarks.php">'.
	         $strchoose.'<input type="hidden" name="MAX_FILE_SIZE" value="'.$maxuploadsize.'">'.
   	         '<input type="hidden" name="yid" value="'.$yid.'">'.
	         '<input type="hidden" name="sesskey" value="'.$USER->sesskey.'">';

	    echo '<input type="file" name="userfile" size="50">'.
	         '<br><input type="submit" value="'.$strupload.'">'.
	         '</form>';
	    echo '</center>';

	echo '<hr>';
    print_simple_box_start_old('center', '100%', '#ffffff', 0);
?>
<h2>Импорт оценок </h2>
<p> Функция "Импорт классов" позволяет загрузить оценки учеников, полученных в результате работы программы "Автоматизированная система ОРГИА" . </p>

<?php
    print_simple_box_end_old();
    print_footer();
?>
