<?php // $Id: exportxml.php,v 1.27 2012/06/13 06:47:03 shtifanov Exp $

    require_once('../../../config.php');
    require_once('../../monitoring/lib.php');
    require_once('../lib_ege.php');
    require_once ($CFG->dirroot.'/backup/lib.php');
    require_once ($CFG->dirroot.'/backup/backuplib.php');

    // define('ID_SCHOOL_FOR_DELETED', 2769); // 2116
    define('ID_SCHOOL_FOR_DELETED', 3385); // 2116    
    
	$action = optional_param('action', '');
    $yid = optional_param('yid', '0', PARAM_INT);       // Year id

    if ($yid == 0)	{
    	$yid = get_current_edu_year_id();
    }

	$id = 1;
   // require_login();

	require_login();

	$admin_is = isadmin();
	$region_operator_is = ismonitoperator('region');
	if (!$admin_is && !$region_operator_is) {
        error(get_string('adminaccess', 'block_monitoring'), "$CFG->wwwroot/login/index.php");
	}

	if (isregionviewoperator())  {
        error(get_string('adminaccess', 'block_monitoring'));
	}

    if ($action == 'csv') {
	    print_pupil_to_csv($yid);
        exit();
    }

    //Check necessary functions exists. Thanks to gregb@crowncollege.edu
    backup_required_functions();


    $strmonit = get_string('xmlexport', 'block_mou_ege');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= "-> $strmonit";
    print_header("$SITE->shortname: $strmonit", $SITE->fullname, $breadcrumbs);

    print_heading($strmonit);


    if ($action == 'create') 	{
	    print_simple_box_start("center");

	    //Adjust some php variables to the execution of this script
	    @ini_set("max_execution_time","3000");
	    raise_memory_limit("192M");

	    if (!$course = get_record("course", "id", $id)) {
	        error("Course ID was incorrect (can't find it)");
	    }

	    //Calculate the backup string
	    //Calculate the date format string

		$backup_date_format = "%d-%m-%Y_%H-%M";
	    $backup_name = 'mou_data_';
	    //The date format
	    $backup_name .= userdate(time(),$backup_date_format,99,false);
	    //The extension
	    $backup_name .= ".zip";
	    //And finally, clean everything
	    $backup_name = clean_filename($backup_name);

	    $preferences = new StdClass;
	    backup_fetch_prefs_from_request($preferences,$count,$course);
	    //Another Info
	    $preferences->backup_name = $backup_name;
	    $preferences->backup_unique_code = time();
	    $preferences->moodle_version = $CFG->version;
	    $preferences->moodle_release = $CFG->release;
	    $preferences->backup_version = $CFG->backup_version;
	    $preferences->backup_release = $CFG->backup_release;


	    //Start the main table
	    echo '<table cellpadding=5><tr><td align=right><b>';
	    echo get_string("name").':</b></td><td>';
	    echo $preferences->backup_name;
	    echo "</td></tr>";

	    //Start the main tr, where all the backup progress is done
	    echo "<tr><td colspan=\"2\">";

	    //Start the main ul
	    echo "<ul>";

	    //Check for temp and backup and backup_unique_code directory
	    //Create them as needed
	    echo "<li>".get_string("creatingtemporarystructures").'</li>';
	    $status = check_and_create_backup_dir($preferences->backup_unique_code);
	    //Empty dir
	    if ($status) {
	        $status = clear_backup_dir($preferences->backup_unique_code);
	    }

	    //Delete old_entries from backup tables
	    echo "<li>".get_string("deletingolddata").'</li>';
	    $status = backup_delete_old_data();
	    if (!$status) {
	        error ("An error occurred deleting old backup data");
	    }

	    //Create the moodle.xml file
	    if ($status) {

	        echo "<li>".get_string("creatingxmlfile");
	        //Begin a new list to xml contents
	        echo "<ul>";
	        echo "<li>".get_string("writingheader").'</li>';
	        //Obtain the xml file (create and open) and print prolog information
	        $backup_file = backup_open_xml_ege($preferences->backup_unique_code);
	        echo "<li>".get_string("writinggeneralinfo").'</li>';

			$disciplines =  get_records_sql ("SELECT id, yearid, name, code  FROM  {$CFG->prefix}monit_school_discipline_ege
											  WHERE yearid=$yid ORDER BY name");
	        $code2id = array();
			if ($disciplines)	{
				foreach ($disciplines as $discipline) 	{
		        	  $code2id[$discipline->id] = $discipline->code;
				}
			}

			backup_years ($backup_file);
	        backup_disciplines ($backup_file);
	        backup_gia_dates ($backup_file);
	        backup_points ($backup_file);

	        //Prints general info about backup to file
            $i=0;
	        if ($backup_file) {
	            if ($rayons = get_records('monit_rayon'))	{
			        //Start new ul (for rayon)
	                echo "<li>".get_string('writingrayons', 'block_mou_ege').'</li>';
			        echo "<ul>";
                    fwrite ($backup_file, start_tag('RAYONS', 1, true));

		            foreach ($rayons as $rayon)  {
		              
                        $GLOBLACOUNTPUPIL = array();

					    $points_num_num = array();
				 	    $points_num_sid = array();
				 	    $strsql = "SELECT id, yearid, rayonid, schoolid  FROM {$CFG->prefix}monit_school_points ";

                        if ($rayon->id == 21) {
                            $strsql .= "WHERE yearid = $yid and rayonid in (21, 23)";
						} else if ($rayon->id == 23 || $rayon->id == 25)	{
							$strsql .= "WHERE yearid = $yid ";
						} else {
							$strsql .= "WHERE yearid = $yid and rayonid = {$rayon->id}";
						}

						if ($rayonpoints = get_records_sql ($strsql))	{
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

			            if (!$status = backup_rayon($backup_file, $rayon)) {
		 	               notify("An error occurred while backing up general info");
		  		        }
                        
                        echo '<b>' . count($GLOBLACOUNTPUPIL) . '</b><hr>';
                        
                        //////////// CHECK
                        /*                            
                                	$rid = $rayon->id;
                            
                            	    //print_heading($rayon->name, "center", 4);
                            
                            		$strsql =  "SELECT id, rayonid  FROM {$CFG->prefix}monit_school
                            	   				WHERE rayonid = $rid AND isclosing=0 AND yearid=$yid";
                            
                            	    $schoolslist = '';
                            	 	if ($schools = get_records_sql($strsql))	{
                            
                            	        $schoolsarray = array();
                            		    foreach ($schools as $sa)  {
                            		        $schoolsarray[] = $sa->id;
                            		    }
                            		    $schoolslist = implode(',', $schoolsarray);
                            	    }
                            
                                    
                                    $strsql = "SELECT id, name FROM {$CFG->prefix}monit_school_class
                            		          WHERE yearid=$yid AND schoolid in ($schoolslist) AND name like '9%'";
                            		$classlist = '';
                                    if ($classes = get_records_sql ($strsql))	{
                                        $schoolsarray = array();
                            		    foreach ($classes as $sa)  {
                            		        $schoolsarray[] = $sa->id;
                            		    }
                            		    $classlist = implode(',', $schoolsarray);
                            	    }
                            
                           
                            	    $strsql = "SELECT id, userid, classid, schoolid, listegeids
                            	    		   FROM {$CFG->prefix}monit_school_pupil_card
                            				   WHERE yearid=$yid AND classid in ($classlist) AND deleted=0";
                            		// echo $strsql;
                           
                                    
                            	    if ($pupils = get_records_sql ($strsql))	{
                                            echo count($pupils);  echo '<hr>';
                            		        // print_r($pupils);  echo '<hr>';
                           
                            	            foreach ($pupils as $pupil)		{
                            	            	if (in_array($pupil->id, $GLOBLACOUNTPUPIL)) continue;
                                                print_r($pupil); echo '<hr>'; 
                            	            }
                                    }        
                                                    
                        
                        
                        
                        
                        $i++;
                        
                        /////////////
                        if ($i==4)
                        break; // !!!!!!!!!!!!!!!!!!!!!
                        
                        */
                        
		  		    }
 		            fwrite ($backup_file, end_tag('RAYONS', 1, true));
		  		}
	        }

	        //Close the xml file and xml data
	        if ($backup_file) {
	            backup_close_xml_ege($backup_file);
	        }

	        //End xml contents (close ul)
	        echo "</ul></li>";
	    }

	    echo "</ul></li>";

	    //Now, zip all the backup directory contents
	    if ($status) {
	        echo "<li>".get_string("zippingbackup").'</li>';
	        if (!$status = backup_zip ($preferences)) {
	            notify("An error occurred while zipping the backup");
	        }
	    }

	    //Now, copy the zip file to course directory
	    if ($status) {
	        echo "<li>".get_string("copyingzipfile").'</li>';
	        if (!$status = copy_zip_to_course_dir ($preferences)) {
	            notify("An error occurred while copying the zip file to the course directory");
	        }
	    }

	    //Now, clean temporary data (db and filesystem)
	    if ($status) {
	        echo "<li>".get_string("cleaningtempdata").'</li>';
	        if (!$status = clean_temp_data ($preferences)) {
	            notify("An error occurred while cleaning up temporary data");
	        }
	    }

	    //Ends th main ul
	    echo "</ul>";

	    //End the main tr, where all the backup is done
	    echo "</td></tr>";

	    //End the main table
	    echo "</table>";

	    if (!$status) {
	        error ("The process did not complete successfully.");
	    }

        print_simple_box(get_string('createxmlfinished', 'block_mou_ege'),"center");

	    print_simple_box_end();


	} else  if ($action == 'delete') {
 	    if ($basedir = make_upload_directory('1/backupdata'))   {
	        //Delete recursively
 	       $status = delete_dir_contents($basedir);
 	    }
	} else  if ($action == 'syncppe') {
        sync_codeppe($yid);
	}


	print_heading(get_string('listxmlinzip','block_mou_ege'), 'center', 4);

	$filearea = '1/backupdata';
    if ($basedir = make_upload_directory($filearea))   {
           if ($files = get_directory_list($basedir)) {
               require_once($CFG->libdir.'/filelib.php');
               $output = '';
               foreach ($files as $key => $file) {
                   $icon = mimeinfo('icon', $file);
                   if ($CFG->slasharguments) {
                       $ffurl = "$CFG->wwwroot/file.php/$filearea/$file";
                   } else {
                       $ffurl = "$CFG->wwwroot/file.php?file=/$filearea/$file";
                   }

                   $output .= '<img align="middle" src="'.$CFG->pixpath.'/f/'.$icon.'" class="icon" alt="'.$icon.'" />'.
                           '<a href="'.$ffurl.'" >'.$file.'</a><br />';
               }
           } else {
           	$output = get_string('filenotfound', 'block_mou_ege');
          }
   }

   print_simple_box_start("center", '50%', 'white');
   echo '<div class="files" align=center>'.$output.'</div>';
   print_simple_box_end();

?>	<table align="center"><tr></tr><tr><td>
		  <form name="createxml" method="post" action="<?php echo "exportxml.php?backup_name=1&amp;backup_unique_code=1&amp;action=create" ?>">
			    <div align="center">
				<input type="submit" name="btncreatexml" value="<?php print_string('btncreatexml','block_mou_ege')?>">
			    </div>
		  </form>
		  </td>
			<td>
			<form name="delxml" method="post" action="<?php echo "exportxml.php?backup_name=1&amp;backup_unique_code=1&amp;action=delete" ?>">
			    <div align="center">
				<input type="submit" name="btndelxml" value="<?php print_string('btndelxml', 'block_mou_ege')?>">
			    </div>
		  </form>
			</td>
		  <tr><td>
		  <form name="createcsv" method="post" action="<?php echo "exportxml.php?backup_name=1&amp;backup_unique_code=1&amp;action=syncppe" ?>">
			    <div align="center">
				<input type="submit" name="btncreatecsv" value="Синхронизировать коды ППЭ">
			    </div>
		  </form> </td>	
          <td>
		  <form name="createcsv" method="post" action="<?php echo "exportxml.php?backup_name=1&amp;backup_unique_code=1&amp;action=csv" ?>">
			    <div align="center">
				<input type="submit" name="btncreatecsv" value="<?php print_string('downloadcsv','block_mou_ege')?>">
			    </div>
		  </form> </td>	</tr>
<?php
	echo '</table>';



    print_footer();

    //Function to create, open and write header of the xml file
    function backup_open_xml_ege($backup_unique_code) {

        global $CFG;

        $status = true;

        //Open for writing

        $file = $CFG->dataroot."/temp/backup/".$backup_unique_code."/moodle.xml";
        $backup_file = fopen($file,"w");
        //Writes the header
        $status = fwrite ($backup_file,"<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
        if ($status) {
            $status = fwrite ($backup_file,start_tag("MONITORING_EGE",0,true));
        }
        if ($status) {
            return $backup_file;
        } else {
            return false;
        }
    }

    function backup_close_xml_ege($backup_file) {
        $status = fwrite ($backup_file,end_tag("MONITORING_EGE",0,true));
        return fclose($backup_file);
    }


    function backup_years ($bf)
    {

        global $CFG, $yid;

        echo "<li>".get_string('writingyears', 'block_mou_ege').'</li>';

        fwrite ($bf, start_tag('YEARS', 1, true));

		$years =  get_records_sql ("SELECT id, name
								  	FROM  {$CFG->prefix}monit_years
								  	ORDER BY id");

		if ($years)	{
			foreach ($years as $year) {

				fwrite ($bf, start_tag('YEAR', 2, true));

		        fwrite ($bf,full_tag("ID",3,false,$year->id));
		        fwrite ($bf,full_tag("NAME",3,false,$year->name));

                fwrite ($bf,end_tag('YEAR',2,true));
            }
        }
        $status = fwrite ($bf,end_tag('YEARS',1,true));

        return $status;
    }


    function backup_disciplines ($bf)
    {

        global $CFG, $yid;

        echo "<li>".get_string('writingdisciplines', 'block_mou_ege').'</li>';

        fwrite ($bf, start_tag('DISCIPLINES', 1, true));

		$currcourse =  get_records_sql ("SELECT id, yearid, name, code
									  FROM  {$CFG->prefix}monit_school_discipline_ege
									  WHERE yearid=$yid
									  ORDER BY id");

		if ($currcourse)	{
			foreach ($currcourse as $discipline) {

				fwrite ($bf, start_tag('DISCIPLINE', 2, true));

		        fwrite ($bf,full_tag("ID",3,false,$discipline->id));
		        fwrite ($bf,full_tag("YEARID",3,false,$discipline->yearid));
		        fwrite ($bf,full_tag("NAME",3,false,$discipline->name));
		        fwrite ($bf,full_tag("CODE",3,false,$discipline->code));

                fwrite ($bf,end_tag('DISCIPLINE',2,true));
            }
        }
        $status = fwrite ($bf,end_tag('DISCIPLINES',1,true));

        return $status;
    }


    function backup_gia_dates ($bf)
    {

        global $CFG, $yid;

        echo "<li>".get_string('writinggia_dates', 'block_mou_ege').'</li>';

        fwrite ($bf, start_tag('GIA_DATES', 1, true));

		$gia_dates =  get_records_sql ("SELECT id, yearid, codepredmet, date_gia
									  FROM  {$CFG->prefix}monit_school_gia_dates
									  WHERE yearid=$yid
									  ORDER BY id");

		if ($gia_dates)	{
			foreach ($gia_dates as $gia_date) {

				fwrite ($bf, start_tag('GIA_DATE', 2, true));

		        fwrite ($bf,full_tag("ID",3,false,$gia_date->id));
		        fwrite ($bf,full_tag("YEARID",3,false,$gia_date->yearid));
		        $giadate = convert_date($gia_date->date_gia, 'en', 'ru');
		        fwrite ($bf,full_tag("DATE_GIA",3,false,$giadate));
		        fwrite ($bf,full_tag("CODE",3,false,$gia_date->codepredmet));

                fwrite ($bf,end_tag('GIA_DATE',2,true));
            }
        }
        $status = fwrite ($bf,end_tag('GIA_DATES',1,true));

        return $status;
    }


    function backup_points ($bf)
    {

        global $CFG, $yid;

        echo "<li>".get_string('writingpoints', 'block_mou_ege').'</li>';

        fwrite ($bf, start_tag('POINTS', 1, true));

		$points =  get_records_select('monit_school_points', "yearid = $yid", '', "id, rayonid, schoolid");

		if ($points)	{
			foreach ($points as $point) {

				fwrite ($bf, start_tag('POINT', 2, true));

		        fwrite ($bf,full_tag("RID",3,false, $point->rayonid));
		        fwrite ($bf,full_tag("SID",3,false, $point->schoolid));

                fwrite ($bf,end_tag('POINT',2,true));
            }
        }
        $status = fwrite ($bf,end_tag('POINTS',1,true));

        return $status;
    }

    function backup_rayon ($bf, $rayon)
    {

        global $CFG;

        echo "<li>".get_string('writinginfoaboutrayon', 'block_mou_ege', $rayon->name).'</li>';

        fwrite ($bf,start_tag("RAYON",1,true));

        fwrite ($bf,full_tag("ID",2,false,$rayon->id));
        //The name of the rayon
        fwrite ($bf,full_tag("NAME",2,false,$rayon->name));

        $status = fwrite ($bf,start_tag("SCHOOLS",2,true));

	    $yid = get_current_edu_year_id();

/*
        if ($rayon->id == 23) {
            $strsql = "SELECT id, yearid, code, name, codeppe, timemodified FROM {$CFG->prefix}monit_school
    				   WHERE rayonid in (21, 23) AND isclosing=0 AND yearid=$yid
				       ORDER BY number";
            
        } else {
        }
*/

        $strsql = "SELECT id, yearid, code, name, codeppe, timemodified FROM {$CFG->prefix}monit_school
				   WHERE rayonid = {$rayon->id} AND isclosing=0 AND yearid=$yid
			       ORDER BY number";

		if ($schools =  get_records_sql($strsql))	{
	        echo "<ul>";
	        foreach ($schools as $school)	{
               if ($school->id == ID_SCHOOL_FOR_DELETED) continue;
        	   backup_school ($bf, $school);
	        }
	        echo "</ul>";
	    }

        $status = fwrite ($bf,end_tag("SCHOOLS",2,true));

        $status = fwrite ($bf,end_tag("RAYON",1,true));

        return $status;
    }


    function backup_school ($bf, $school)
    {

        global $CFG, $points_num_num, $points_num_sid, $code2id;


        echo "<li>".get_string('writinginfoaboutschool', 'block_mou_ege', $school->name).'</li>';

        fwrite ($bf,start_tag("SCHOOL",3,true));

        fwrite ($bf,full_tag("ID",4,false,$school->id));
        fwrite ($bf,full_tag("CODE",4,false,$school->code));
        //The name of the school
        fwrite ($bf,full_tag("NAME",4,false,$school->name));
        fwrite ($bf,full_tag("YEARID",4,false,$school->yearid));
        fwrite ($bf,full_tag("TIMEMODIFIED",4,false,$school->timemodified));

        $strbasepoint = $strrespoint = $strrespoint3 = '';
		$strsql =  "SELECT id, pointnumber1id, pointnumber2id, pointnumber3id, schoolid, disciplineid
					FROM {$CFG->prefix}monit_school_point_forschool
	   				WHERE schoolid = {$school->id}";
	 	if ($points = get_records_sql($strsql))	{
		    // print_r($points);
	 		foreach ($points as $point) 	{
                $strbasepoint  .= $code2id[$point->disciplineid] . '-' . $points_num_num[$point->pointnumber1id] . ',';
                $strrespoint   .= $code2id[$point->disciplineid] . '-' . $points_num_num[$point->pointnumber2id] . ',';
                $strrespoint3  .= $code2id[$point->disciplineid] . '-' . $points_num_num[$point->pointnumber3id] . ',';
	 		}
	 		$strbasepoint .= '0';
	 		$strrespoint  .= '0';
	 	}

        fwrite ($bf,full_tag("CODEPPE", 4, false, $school->codeppe));
        fwrite ($bf,full_tag("BASEPOINTS", 4, false, '0,' . $strbasepoint));
        fwrite ($bf,full_tag("RESERVPOINTS", 4, false, '0,' . $strrespoint));
        fwrite ($bf,full_tag("RESERVPOINTS3", 4, false, '0,' . $strrespoint3));

        $status = fwrite ($bf,start_tag("CLASSES",4,true));

	    $yid = get_current_edu_year_id();

		if ($classes = get_records_sql ("SELECT id, name FROM {$CFG->prefix}monit_school_class
									  WHERE schoolid={$school->id} AND yearid=$yid AND name like '9%'
									  ORDER BY name"))	{

	        echo "<ul>";
	        foreach ($classes as $class)	{
	        	backup_class ($bf, $class);
	        	backup_flush(30);
	        }
	        echo "</ul>";
	    }

        $status = fwrite ($bf,end_tag("CLASSES",4,true));

        $status = fwrite ($bf,end_tag("SCHOOL",3,true));

        return $status;
    }


    function backup_class ($bf, $class)
    {
        global $CFG;

        echo "<li>".get_string('writinginfoaboutclass', 'block_mou_ege', $class->name).'</li>';

        fwrite ($bf,start_tag("CLASS",5,true));

        fwrite ($bf,full_tag("ID",6,false,$class->id));

        //The name of the clss
        fwrite ($bf,full_tag("NAME",6,false,$class->name));

        $status = fwrite ($bf,start_tag("PUPILS",6,true));

        // id, yearid, rayonid, userid, schoolid, classid, typedocuments, serial, number, pol, birthday, who_hands, when_hands, pswtxt, listegeids, listmiids, listdatesids, timemodified, deleted, listmidatesids, nsop, typeemployment
		if ($pupils = get_records_sql ("SELECT id, userid, listegeids, listdatesids, timemodified FROM {$CFG->prefix}monit_school_pupil_card
									  WHERE classid={$class->id} AND deleted = 0"))		{

	        echo "<ul>";
	        foreach ($pupils as $pupil)	{
	        	backup_pupil ($bf, $pupil);
	        }
	        echo "</ul>";
		}

        $status = fwrite ($bf,end_tag("PUPILS",6,true));

        $status = fwrite ($bf,end_tag("CLASS",5,true));

        return $status;
    }

    function backup_pupil ($bf, $pupil)
    {

        global $CFG, $code2id, $GLOBLACOUNTPUPIL;
        
        // echo "<li>".get_string('writinginfoaboutpupil', 'block_mou_ege', $class->name).'</li>';

        fwrite ($bf,start_tag("PUPIL",7,true));

        fwrite ($bf,full_tag("ID",8,false,$pupil->userid));

		if ($user = get_record_sql ("SELECT id, lastname, firstname FROM {$CFG->prefix}user
								  WHERE id={$pupil->userid}"))	{
	        fwrite ($bf,full_tag("LASTNAME",8,false,$user->lastname));
	        fwrite ($bf,full_tag("FIRSTNAME",8,false,$user->firstname));
            $GLOBLACOUNTPUPIL[]=$pupil->id;
	    }

        // id, yearid, rayonid, userid, schoolid, classid, typedocuments, serial, number, pol, birthday, who_hands, when_hands, pswtxt, listegeids, listmiids, listdatesids, timemodified, deleted, listmidatesids, nsop, typeemployment
		// if ($pupil = get_record_sql ("SELECT id, listegeids, listdatesids, timemodified FROM {$CFG->prefix}monit_school_pupil_card WHERE userid={$pupil->userid}"))	{

	        fwrite ($bf,full_tag('TYPEDOCUMENTS' , 8, false, '')); // $pupil->typedocuments));
			fwrite ($bf,full_tag('SERIAL' , 8, false, '')); // $pupil->serial));
			fwrite ($bf,full_tag('NUMBER' , 8, false, '')); // $pupil->number));
			fwrite ($bf,full_tag('WHO_HANDS' , 8, false, '')); // $pupil->who_hands));
			fwrite ($bf,full_tag('WHEN_HANDS', 8, false, '')); // $pupil->when_hands));
			fwrite ($bf,full_tag('LISTEGEIDS', 8, false, '0,' . $pupil->listegeids));
			fwrite ($bf,full_tag('LISTDATESIDS', 8, false,'0,' . $pupil->listdatesids));

			$listcodes = '';
        	$arr_disc_id = explode(',', $pupil->listegeids);
	        foreach ($arr_disc_id as $disc_id)	{
	              if ($disc_id > 0) {
		        	  $listcodes .= $code2id[$disc_id] . ',';
		          }
	        }
       	    $listcodes .= '0';
			fwrite ($bf,full_tag('LISTCODES', 8, false, '0,' . $listcodes));
            fwrite ($bf,full_tag('TIMEMODIFIED', 8, false,$pupil->timemodified));
        // }

        $status = fwrite ($bf,end_tag("PUPIL",7,true));

        return $status;
    }


function print_pupil_to_csv($yid)
{
  global $CFG;

	/*
	SELECT u.id, u.username, u.firstname, u.lastname, m.pswtxt
	FROM mdl_user u LEFT JOIN mdl_monit_school_pupil_card m ON m.userid = u.id
	WHERE u.deleted = 0 AND u.confirmed = 1 AND classid in (SELECT id FROM mdl_monit_school_class WHERE name NOT LIKE '9%')
	*/

  $studentsql = "SELECT u.id, u.username, u.firstname, u.lastname, m.pswtxt
                 FROM {$CFG->prefix}user u LEFT JOIN {$CFG->prefix}monit_school_pupil_card m ON m.userid = u.id
				WHERE u.deleted = 0 AND u.confirmed = 1 AND
				classid in (SELECT id FROM {$CFG->prefix}monit_school_class WHERE name NOT LIKE '9%')";

 // print_r($studentsql); echo '<hr>';

  if( $students = get_records_sql($studentsql)) {

	    $filename = 'mou_pupils_'.userdate(time(),"%d-%m-%Y_%H-%M",99,false);
	    $filename .= '.csv';
	    header("Content-Type: application/download\n");
	    header("Content-Disposition: attachment; filename=$filename");
	    header("Expires: 0");
	    header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
	    header("Pragma: public");

	    echo "username;firstname;lastname;pswtxt\n";

	    foreach ($students as $student) {
            $text  = $student->username . ';';
            $text .= $student->firstname . ';';
            $text .= $student->lastname . ';';
            $text .= $student->pswtxt . "\n";
		    echo $text;
        }
		return true;
  } else {
	    return false;
  }

}


function sync_codeppe($yid)
{
	$strsql =  "SELECT  spn.id, s.codeppe, spn.number
                FROM mdl_monit_school_points sp 
                inner join mdl_monit_school s on s.id=sp.schoolid
                inner join mdl_monit_school_point_number spn on sp.id=spn.pointid
                where sp.yearid=$yid";
    // echo $strsql;           
    if ($ppes = get_records_sql ($strsql))	{
        //echo '<pre>'; print_r ($ppes); echo '</pre>';
        $cnt = 0;  
	    foreach ($ppes as $ppe)  {
	          if ($ppe->codeppe != $ppe->number)  {
	              notify ("Коды не совпадают: $ppe->codeppe и $ppe->number "); // ($ppe->name)
                  set_field('monit_school_point_number', 'number', $ppe->codeppe, 'id', $ppe->id);
                  $cnt++; 
	          }
	    }
        
        if ($cnt > 0)   {
            notify("Синхронизировано $cnt кодов ППЭ", 'green'); 
        } else {
            notify("Несовпадающие коды ППЭ не обнаружены.", 'green');
        }
    }
}
?>
