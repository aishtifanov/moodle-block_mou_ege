<?PHP // $Id: setappealtime.php,v 1.4 2010/05/26 11:15:02 Shtifanov Exp $

    require_once('../../../config.php');
    require_once('../../monitoring/lib.php');
    require_once('../lib_ege.php');

    $yid = optional_param('yid', 0, PARAM_INT);       // Year id
    $did = 0;       // Discipline id
	$rid = 0;
	$sid = 0;
	$gid = 0;

    if ($yid == 0)	{
    	$yid = get_current_edu_year_id();
    }


	$action = optional_param('action', '');
    if ($action == 'excel') {
		$table = table_pupilnomarks($yid);
    	// print_r($table);
        print_table_to_excel($table);
        exit();
	}

	require_login();

	$admin_is = isadmin();
	$region_operator_is = ismonitoperator('region');
	if (!$admin_is && !$region_operator_is) {
        error(get_string('adminaccess', 'block_monitoring'), $CFG->wwwroot.'/blocks/mou_ege/index.php');
	}

	if (isregionviewoperator() || israyonviewoperator())  {
        error(get_string('adminaccess', 'block_monitoring'));
	}


	// $strclasses = get_string('classes','block_mou_ege');
	$strclasses = get_string('school','block_monitoring');
	$strmarks = get_string('intervalappealtime','block_mou_ege');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= " -> $strmarks";
    print_header_mou("$SITE->shortname: $strmarks", $SITE->fullname, $breadcrumbs);

	print_tabs_years($yid, "setappealtime.php?yid=");

    $currenttab = 'setappealtime';
    include('tabs2.php');

    if ($recs = data_submitted())  {
		// print_r($recs); echo '<hr>';
		$disciplines =  get_records_sql ("SELECT id, yearid, name, code, timeload, timepublish
										  FROM  {$CFG->prefix}monit_school_discipline_ege
										  WHERE yearid=$yid
										  ORDER BY code");

		$i = 0;
		if ($disciplines)	{
			foreach ($disciplines as $discipline) {
	            $did = $discipline->id;
				if ($giadates =  get_records_sql ("SELECT id, date_gia, deltatime, timeload, timestartappealhearing, 
														timefilingappealend, timefilingappealstart, timepublish 
												  FROM  {$CFG->prefix}monit_school_gia_dates
												  WHERE yearid=$yid AND discegeid={$discipline->id} ORDER BY date_gia"))  {
					foreach ($giadates as $giadate)		{
						$gdg = $giadate->date_gia;
	            
		       	   	    if ($giadate->timeload != 0)	{
		       	   	    	$idx = '0_'.$did.'_'.$gdg;
		       	   	    	$d = $recs->{'dueday'.$idx};
		       	   	    	$m = $recs->{'duemonth'.$idx};
		       	   	    	$y = $recs->{'dueyear'.$idx};
		       	   	    	$h = $recs->{'duehour'.$idx};
		       	   	    	$t = $recs->{'dueminute'.$idx};
		
		       	   	    	$timepublish = make_timestamp ($y, $m, $d, $h, $t, 0);
		           			if (!set_field('monit_school_gia_dates', 'timepublish', $timepublish, 'id', $giadate->id))	{
		                         error (get_string('errorinsetappealtime','block_mou_ege',  $discipline->name), "setappealtime.php?yid=$yid");
		           			}
		       	   	    	$idx = $did.'_'.$gdg;		
		       	   	    	$d = $recs->{'dueday_'.$idx};
		       	   	    	$m = $recs->{'duemonth_'.$idx};
		       	   	    	$y = $recs->{'dueyear_'.$idx};
		       	   	    	$h = $recs->{'duehour_'.$idx};
		       	   	    	$t = $recs->{'dueminute_'.$idx};
		
		       	   	    	$timestartappealhearing = make_timestamp ($y, $m, $d, $h, $t);
		           			if (!set_field('monit_school_gia_dates', 'timestartappealhearing', $timestartappealhearing, 'id', $giadate->id))	{
		                         error (get_string('errorinsetappealtime','block_mou_ege',  $discipline->name), "setappealtime.php?yid=$yid");
		           			}

		       	   	    	$idx = '2_'.$did.'_'.$gdg;		
		       	   	    	$d = $recs->{'dueday'.$idx};
		       	   	    	$m = $recs->{'duemonth'.$idx};
		       	   	    	$y = $recs->{'dueyear'.$idx};
		       	   	    	$h = $recs->{'duehour'.$idx};
		       	   	    	$t = $recs->{'dueminute'.$idx};
		
		       	   	    	$timefilingappealstart = make_timestamp ($y, $m, $d, $h, $t);
		           			if (!set_field('monit_school_gia_dates', 'timefilingappealstart', $timefilingappealstart, 'id', $giadate->id))	{
		                         error (get_string('errorinsetappealtime','block_mou_ege',  $discipline->name), "setappealtime.php?yid=$yid");
		           			}
		
							$idx = '3_'.$did.'_'.$gdg;	
		       	   	    	$d = $recs->{'dueday'.$idx};
		       	   	    	$m = $recs->{'duemonth'.$idx};
		       	   	    	$y = $recs->{'dueyear'.$idx};
		       	   	    	$h = $recs->{'duehour'.$idx};
		       	   	    	$t = $recs->{'dueminute'.$idx};
		
		       	   	    	$timefilingappealend = make_timestamp ($y, $m, $d, $h, $t);
		           			if (!set_field('monit_school_gia_dates', 'timefilingappealend', $timefilingappealend, 'id', $giadate->id))	{
		                         error (get_string('errorinsetappealtime','block_mou_ege',  $discipline->name), "setappealtime.php?yid=$yid");
		           			}

		           			if (!set_field('monit_school_gia_dates', 'deltatime', $recs->{'deltatime'.$did.'_'.$gdg}, 'id', $giadate->id))	{
		                         error (get_string('errorinsetappealtime','block_mou_ege',  $discipline->name), "setappealtime.php?yid=$yid");
		           			}
		
		       				notify(get_string('successsetappealtime','block_mou_ege',  $discipline->name), 'green');
		       	   	    }
		       	   	}
				}		      
       	   }
       	}
    }


	$table = table_setappealtime($yid);

	echo '<form name="form" method="post" action="setappealtime.php">';
	print_color_table($table);
?>
   <div align="center">
     <input type="hidden" name="yid" value="<?php echo $yid ?>">
     <input type="hidden" name="did" value="<?php echo $did ?>">
 	 <input type="submit" name="adddisc" value="<?php print_string('savechanges')?>">
  </div>
  </form>
<?php

	print_footer();


function table_setappealtime ($yid)
{
	global $CFG;


    $table->head  = array ('Код',  get_string('disciplinename','block_mou_ege'), get_string('timeload','block_mou_ege'), get_string('publishtimemark','block_mou_ege'),
    						get_string('intervalfilingappeal','block_mou_ege'), get_string('timestartappealhearing','block_mou_ege'),
    						get_string('deltatime','block_mou_ege'));
    $table->align = array ("center",  "left", "center", "center", "center", "center", "center");
    $table->class = 'moutable';
  	$table->width = '90%';
    $table->size = array ('5%', '10%', '15%', '25%', '25%', '25%', '10%');
	$table->columnwidth = array (4, 17, 20, 20, 20, 20, 10);
    $table->titles = array();
    $table->titles[] = get_string('disciplines_ege', 'block_mou_ege');
    $table->worksheetname = get_string('disciplines_ege', 'block_mou_ege');
    $table->titlesrows = array(30);
    $table->downloadfilename = 'disciplines_gia_time';

//	$disciplines = get_records ('school_discipline', 'curriculumid', $cid);
	$disciplines =  get_records_sql ("SELECT id, yearid, name, code, timeload, timepublish,
										timestartappealhearing, timefilingappealstart, timefilingappealend, deltatime
									  FROM  {$CFG->prefix}monit_school_discipline_ege
									  WHERE yearid=$yid
									  ORDER BY code");

	$i = 0;
	if ($disciplines)	{
		foreach ($disciplines as $discipline) {
            $did = $discipline->id;
			$strdiscipline = $discipline->name;

			if ($giadates =  get_records_sql ("SELECT id, date_gia, deltatime, timeload, timestartappealhearing, 
															timefilingappealend, timefilingappealstart, timepublish 
											  FROM  {$CFG->prefix}monit_school_gia_dates
											  WHERE yearid=$yid AND discegeid={$discipline->id} ORDER BY date_gia"))  {
				foreach ($giadates as $giadate)		{
					$gdg = $giadate->date_gia;
					$strdate = convert_date($giadate->date_gia, 'en', 'ru');

					$strtimeload = $strtimepub = $strtimeh = $strtimefs = $strtimefe = $strdeltatime = '-';
		 	   	    if ($giadate->timeload != 0)	{
		
		   			    $strtimeload =  date ("d.m.Y H:i", $giadate->timeload);
		
						$strtimepub = print_date_selector('dueday0_'.$did.'_'.$gdg, 'duemonth0_'.$did.'_'.$gdg, 'dueyear0_'.$did.'_'.$gdg, $giadate->timepublish, true);
				        $strtimepub .= "<br>";
		  		        $strtimepub .= print_time_selector('duehour0_'.$did.'_'.$gdg, 'dueminute0_'.$did.'_'.$gdg, $giadate->timepublish, 5, true);
		
						$strtimeh = print_date_selector('dueday_'.$did.'_'.$gdg, 'duemonth_'.$did.'_'.$gdg, 'dueyear_'.$did.'_'.$gdg, $giadate->timestartappealhearing, true);
				        // $strtime .= "&nbsp;-&nbsp;";
				        $strtimeh .= "<br>";
		  		        $strtimeh .= print_time_selector('duehour_'.$did.'_'.$gdg, 'dueminute_'.$did.'_'.$gdg, $giadate->timestartappealhearing, 5, true);
		
						$strtimefs = print_date_selector('dueday2_'.$did.'_'.$gdg, 'duemonth2_'.$did.'_'.$gdg, 'dueyear2_'.$did.'_'.$gdg, $giadate->timefilingappealstart, true);
				        $strtimefs .= "<br>";
		  		        $strtimefs .= print_time_selector('duehour2_'.$did.'_'.$gdg, 'dueminute2_'.$did.'_'.$gdg, $giadate->timefilingappealstart, 5, true);
		
						$strtimefe = print_date_selector('dueday3_'.$did.'_'.$gdg, 'duemonth3_'.$did.'_'.$gdg, 'dueyear3_'.$did.'_'.$gdg, $giadate->timefilingappealend, true);
				        $strtimefe .= "<br>";
		  		        $strtimefe .= print_time_selector('duehour3_'.$did.'_'.$gdg, 'dueminute3_'.$did.'_'.$gdg, $giadate->timefilingappealend, 5, true);
		
		                $minutes = array();
					    for ($i=1; $i<=10; $i++) {
					        $minutes[$i] = sprintf("%d",$i);
					    }
		             	$strdeltatime = choose_from_menu($minutes, 'deltatime'.$did.'_'.$gdg, $giadate->deltatime, '','','0',true);
					} 
		
					$i++;
					$table->data[] = array ($discipline->code.'.', $strdiscipline, $strtimeload, $strtimepub,
											$strtimefs . '<br>...<br>' . $strtimefe, $strtimeh, $strdeltatime);
				}
			}	
		
			$table->data[] = array ('<hr>', '<hr>' , '<hr>', '<hr>', '<hr>', '<hr>', '<hr>');							
		}
	}

	return $table;
}

?>
