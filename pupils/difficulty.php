<?PHP // $Id: difficulty.php,v 1.7 2010/06/07 09:17:47 Shtifanov Exp $

    require_once('../../../config.php');
    require_once('../../monitoring/lib.php');
    require_once('../lib_ege.php');

    $yid = optional_param('yid', 0, PARAM_INT);       // Year id
	$rid = optional_param('rid', 0, PARAM_INT);
	$sid = optional_param('sid', 0, PARAM_INT);
	$gid = optional_param('gid', 0, PARAM_INT);
    $did = optional_param('did', 0, PARAM_INT);       // Discipline id
    $vid = optional_param('vid', 0, PARAM_INT);       // Variant id    
	$uid = optional_param('uid', 0, PARAM_INT);
   	$level = optional_param('level', 'rayon');
	$action   = optional_param('action', '');

    if ($yid == 0)	{
    	$yid = get_current_edu_year_id();
    }


    switch ($level)	{
		case 'region':
		break;
		case 'rayon': $rid = required_param('rid', PARAM_INT);       // Rayon id
		break;
    }

	$action = optional_param('action', '');
    if ($action == 'excel') {
		$table = table_difficulty($level, $yid, $did, $rid, $vid);
        print_table_to_excel($table);
        exit();
	} else  if ($action == 'excelsidec') {
		$table = table_difficulty_sidec($level, $yid, $did, $rid, $vid);
        print_table_to_excel($table);
        exit();
	}

	require_login();

	$admin_is = isadmin();
	$region_operator_is = ismonitoperator('region');
	$rayon_operator_is  = ismonitoperator('rayon', 0, 0, 0, true);
	if (!$admin_is && !$region_operator_is && !$rayon_operator_is) {
        error(get_string('adminaccess', 'block_monitoring'), "$CFG->wwwroot/login/index.php");
	}

	// $strclasses = get_string('classes','block_mou_ege');
	$strclasses = get_string('school','block_monitoring');
	$strmarks = get_string('statsmarkspupil','block_mou_ege');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= " -> $strmarks";
    print_header_mou("$SITE->shortname: $strmarks", $SITE->fullname, $breadcrumbs);

	print_tabs_years($yid, "difficulty.php?rid=$rid&amp;yid=");

    $currenttab = 'difficulty'.$level;

    $toprow = array();
    $toprow[] = new tabobject('difficultyrayon', "difficulty.php?level=rayon&amp;rid=$rid&amp;yid=$yid",
	               get_string('difficultyrayon', 'block_mou_ege'));
	if ($admin_is || $region_operator_is) {
	    $toprow[] = new tabobject('difficultyregion', "difficulty.php?level=region&amp;yid=$yid",
 	               get_string('difficultyregion', 'block_mou_ege'));
	}
    $tabs = array($toprow);
    print_tabs($tabs, $currenttab, NULL, NULL);


    switch ($level)	{
		case 'region':  if ($admin_is || $region_operator_is) 	{
      						echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
							listbox_discipline_ege("difficulty.php?level=region&amp;yid=$yid&amp;did=", $rid, 0, $yid, $did);
							listbox_variant_gia("difficulty.php?level=region&amp;yid=$yid&amp;did=$did&amp;vid=", $rid, 0, $yid, $did, $vid);							
							echo '</table>';

						 	if ($did != 0)  {

							    if (!$discipline_ege = get_record('monit_school_discipline_ege', 'id', $did))	{
							    	error('Discipline not found!');
							    }
							    
							    if ($vid != 0)	{
							    	print_heading(get_string('variant_gia_a','block_mou_ege', $vid), 'center', 3);	
							    }
								
								
								$table = table_difficulty($level, $yid, $did, 0, $vid);
								print_color_table($table);
						   		$options = array('rid' => $rid, 'sid' => 0, 'did' => $did, 'yid' => $yid, 'vid' => $vid, 'level' => 'region', 'action' => 'excel');
								echo '<table align="center" border=0><tr><td>';
							    print_single_button("difficulty.php", $options, get_string("downloadexcel"));
	   							echo '</td></tr></table>';

								$table = table_difficulty_sidec($level, $yid, $did, 0, $vid);
								echo '<hr>';
	                            print_heading(get_string('sidec','block_mou_ege'), 'center', 3);
								print_color_table($table);
						   		$options = array('rid' => $rid, 'sid' => 0, 'did' => $did, 'yid' => $yid, 'vid' => $vid, 'level' => 'region', 'action' => 'excelsidec');
								echo '<table align="center" border=0><tr><td>';
							    print_single_button("difficulty.php", $options, get_string("downloadexcel"));
	   							echo '</td></tr></table>';

						    }
						}
		break;

		case 'rayon':   if ($admin_is || $region_operator_is) 	{
							echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
							listbox_rayons("difficulty.php?level=rayon&amp;yid=$yid&amp;rid=", $rid);
							listbox_discipline_ege("difficulty.php?level=rayon&amp;rid=$rid&amp;yid=$yid&amp;did=", $rid, $sid, $yid, $did);
							listbox_variant_gia("difficulty.php?level=rayon&amp;rid=$rid&amp;yid=$yid&amp;did=$did&amp;vid=", $rid, $sid, $yid, $did, $vid);
							echo '</table>';
						} else  if ($rayon_operator_is)  {
							echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
							listbox_discipline_ege("difficulty.php?level=rayon&amp;rid=$rid&amp;yid=$yid&amp;did=", $rid, $sid, $yid, $did);
							listbox_variant_gia("difficulty.php?level=rayon&amp;rid=$rid&amp;yid=$yid&amp;did=$did&amp;vid=", $rid, $sid, $yid, $did, $vid);							
							echo '</table>';
						}


					 	if ($rid != 0 && $did != 0)  {

						    if (!$discipline_ege = get_record('monit_school_discipline_ege', 'id', $did))	{
						    	error('Discipline not found!');
						    }
						    
						    if ($vid != 0)	{
						    	print_heading(get_string('variant_gia_a','block_mou_ege', $vid), 'center', 3);	
						    }
						    
						    $mintimepublish = 0;
							if ($giadate =  get_record_sql ("SELECT min(timepublish)  as mintimepublish FROM  {$CFG->prefix}monit_school_gia_dates
	 						   								WHERE yearid=$yid AND discegeid=$did"))  {
	 								$mintimepublish = $giadate->mintimepublish;
	 						}
  
						    $nowtime = time();
						    if ($nowtime < $mintimepublish && !$admin_is && !$region_operator_is)	{
						            $t = date ("d.m.Y H:i", $mintimepublish);
	  							    print_heading(get_string('timewillbepublish','block_mou_ege', $t), 'center', 4);
						    } else {

								$table = table_difficulty($level, $yid, $did, $rid, $vid);
								print_color_table($table);
						   		$options = array('rid' => $rid, 'sid' => $sid, 'did' => $did, 'yid' => $yid, 'vid' => $vid, 'level' => 'rayon', 'action' => 'excel');
								echo '<table align="center" border=0><tr><td>';
							    print_single_button("difficulty.php", $options, get_string("downloadexcel"));
	   							echo '</td></tr></table>';

								$table = table_difficulty_sidec($level, $yid, $did, $rid, $vid);
								echo '<hr>';
	                            print_heading(get_string('sidec','block_mou_ege'), 'center', 3);
								print_color_table($table);
						   		$options = array('rid' => $rid, 'sid' => $sid, 'did' => $did, 'yid' => $yid, 'vid' => $vid, 'level' => 'rayon', 'action' => 'excelsidec');
								echo '<table align="center" border=0><tr><td>';
							    print_single_button("difficulty.php", $options, get_string("downloadexcel"));
	   							echo '</td></tr></table>';
	   						}

					    }
		break;
    }

	print_footer();


function table_difficulty($level, $yid, $did, $rid, $vid = 0)
{
	global $CFG;

    if (!$discipline_ege = get_record('monit_school_discipline_ege', 'id', $did))	{
    	error('Discipline not found!');
    }

    switch ($level)	{
		case 'region':
					$strtitle = get_string('rayon', 'block_monitoring');
		break;
		case 'rayon':
					$strtitle = get_string('school', 'block_monitoring');
		break;
    }

    $table->head  = array (get_string('numbertask','block_mou_ege'), get_string('kolpupilingia', 'block_mou_ege'), get_string('copewithtask', 'block_mou_ege'),
						   get_string('notcopewithtask', 'block_mou_ege'), get_string('notcopewithtaskproc', 'block_mou_ege'));

	$table->align = array ('left',  'center',   'center',  'center',  'center');
    $table->size = array ('30%', '20%', '20%', '20%');
	$table->columnwidth = array (20, 12, 12, 12, 12);
    $table->class = 'moutable';
   	$table->width = '90%';
    $table->titles = array();
    $table->worksheetname = get_string('difficulty', 'block_mou_ege');
    $table->titles[] = $table->worksheetname;
    $table->titlesrows = array(30, 30);


    switch ($level)	{
		case 'region':  $strsql = "SELECT id, sidea, sideb, sidec FROM {$CFG->prefix}monit_gia_results
							       WHERE  yearid=$yid AND codepredmet={$discipline_ege->code} ";
												       
						if ($vid == 0)	{
							$strsql .= ' AND variant <> 0';
						} else {
							$strsql .= " AND variant=$vid ";
						}

						if ($pupils = get_records_sql($strsql))	{
							 calc_difficulty($pupils, $table);
          				}
						$table->titles[] = get_string('nameregion', 'block_mou_ege');
						$table->downloadfilename = 'difficulty_region_'.$vid;

		break;
		case 'rayon':   $rayon = get_record('monit_rayon', 'id', $rid);
						$i = 1;
						$rid = $rayon->id;

						$strsql = "SELECT id, sidea, sideb, sidec FROM {$CFG->prefix}monit_gia_results
							       WHERE  rayonid=$rid AND yearid=$yid AND codepredmet={$discipline_ege->code} ";
												       
						if ($vid == 0)	{
							$strsql .= ' AND variant <> 0';
						} else {
							$strsql .= " AND variant=$vid ";
						}

						if ($pupils = get_records_sql($strsql))	{
							 calc_difficulty($pupils, $table);
          				}
						$table->titles[] = $rayon->name;
						$table->downloadfilename = 'difficulty_rayon_'.$rid.'_'.$vid;
		break;

    }


    return $table;
}



function calc_difficulty($pupils, &$table)
{
		$pupilcount = count($pupils);
		$sidenames  = array ('sidea', 'sideb'); // , 'sidec');
		$pupil = current($pupils);
		$countasksidea = $countasksideb = 0;
		if ($tasksidea = explode(',', $pupil->sidea))	{
			$countasksidea = count($tasksidea);
			if ($countasksidea == 1 && empty($tasksidea[0]))	{
				$countasksidea = 0;
			}
		}
		if ($tasksideb = explode(',', $pupil->sideb))	{
			$countasksideb = count($tasksideb);
			if ($countasksideb == 1 && empty($tasksideb[0]))	{
				$countasksideb = 0;
			}
		}

		$sidecounts = array ($countasksidea, $countasksideb);
		$copewithtask = array();
		$notcopewithtask = array();
		foreach ($sidecounts as $key => $sidecount)	{
			for ($i = 0; $i < $sidecount; $i++)	{
				$copewithtask[$sidenames[$key]][$i] = 0;
				$notcopewithtask[$sidenames[$key]][$i] = 0;
			}
		}

		foreach ($sidenames as $index => $side)	{
            $table->data[] = array ('<b>'.get_string($side, 'block_mou_ege').'</b>', '<hr>', '<hr>', '<hr>', '<hr>');
			foreach ($pupils as $pupil)	{
				$taskside = explode(',', $pupil->{$side});
				for ($i = 0; $i < $sidecounts[$index]; $i++)	{
                     if ($taskside[$i] == 0) {
                     	$notcopewithtask[$side][$i]++;
                     } else {
                     	$copewithtask[$side][$i]++;
                     }
				}
			}
			for ($i = 0; $i < $sidecounts[$index]; $i++)	{
				$proc = number_format($notcopewithtask[$side][$i]/$pupilcount*100, 2, ',', '') . '%';
     		    $table->data[] = array (get_string('tasnumber', 'block_mou_ege', ($i+1)),
     		      						$pupilcount, $copewithtask[$side][$i], $notcopewithtask[$side][$i],
     		      						$proc);
 	        }
	    }

}


function table_difficulty_sidec($level, $yid, $did, $rid, $vid = 0)
{
	global $CFG;

    if (!$discipline_ege = get_record('monit_school_discipline_ege', 'id', $did))	{
    	error('Discipline not found!');
    }

    switch ($level)	{
		case 'region':
					$strtitle = get_string('rayon', 'block_monitoring');
		break;
		case 'rayon':
					$strtitle = get_string('school', 'block_monitoring');
		break;
    }

    $table->head  = array (get_string('numbertask','block_mou_ege'), get_string('kolpupilingia', 'block_mou_ege'),
    '"0" баллов<br>(всего)', '"0" баллов(%)', '"1" балл<br>(всего)',  '"1" балл(%)',
    '"2" балла<br> (всего)', '"2" балла(%)',  '"3" балла<br>(всего)', '"3" балла(%)',
	'"4" балла<br> (всего)', '"4" балла(%)',  '"5" баллов<br>(всего)', '"5" баллов(%)');

	$table->align = array ( 'left',  'center',
						   'center',  'center',  'center',   'center',  'center',  'center',
						   'center', 'center',  'center',  'center',  'center',  'center');

    // $table->size = array ('30%', '20%', '20%', '20%');
	$table->columnwidth = array (20, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10);
    $table->class = 'moutable';
   	$table->width = '90%';
    $table->titles = array();
    $table->worksheetname = get_string('sidec', 'block_mou_ege');
    $table->titles[] = $table->worksheetname;
    $table->titlesrows = array(30, 30);

    switch ($level)	{
		case 'region':
						$strsql = "SELECT id, sidea, sideb, sidec FROM {$CFG->prefix}monit_gia_results
							       WHERE  yearid=$yid AND codepredmet={$discipline_ege->code}";
												       
						if ($vid == 0)	{
							$strsql .= ' AND variant <> 0';
						} else {
							$strsql .= " AND variant=$vid ";
						}
						if ($pupils = get_records_sql($strsql))	{
							 calc_difficulty_sidec($pupils, $table);
          				}
						$table->titles[] = get_string('nameregion', 'block_mou_ege');
						$table->downloadfilename = 'difficulty_region_sidec'.$vid;

		break;
		case 'rayon':   $rayon = get_record('monit_rayon', 'id', $rid);
						$i = 1;
						$rid = $rayon->id;

						$strsql = "SELECT id, sidea, sideb, sidec FROM {$CFG->prefix}monit_gia_results
							       WHERE  rayonid=$rid AND yearid=$yid AND codepredmet={$discipline_ege->code}";
												       
						if ($vid == 0)	{
							$strsql .= ' AND variant <> 0';
						} else {
							$strsql .= " AND variant=$vid ";
						}
						if ($pupils = get_records_sql($strsql))	{
							 calc_difficulty_sidec($pupils, $table);
          				}
						$table->titles[] = $rayon->name;
						$table->downloadfilename = 'difficulty_rayon_sidec_'.$rid.'_'.$vid;
		break;

    }

    return $table;
}


function calc_difficulty_sidec($pupils, &$table)
{
		$pupilcount = count($pupils);
		$pupil = current($pupils);
		$countasksidec = 0;
		if ($tasksidec = explode(',', $pupil->sidec))	{
			$countasksidec = count($tasksidec);
			if ($countasksidec == 1 && empty($tasksidec[0]))	{
				$countasksidec = 0;
			}
		}
		// $ocenki = array(0,0,0,0,0,0);
		$ocenki = array();
		for ($i = 0; $i < $countasksidec; $i++)	{
			for ($j = 0; $j <= 5; $j++)	{
				$ocenki[$i][$j] = 0;
			}
		}


		foreach ($pupils as $pupil)	{
			$taskside = explode(',', $pupil->sidec);
			for ($i = 0; $i < $countasksidec; $i++)	{
				$ocenki[$i][$taskside[$i]]++;
			}
		}

		for ($i = 0; $i < $countasksidec; $i++)	{
			$data = array();
			$data[] = get_string('tasnumber', 'block_mou_ege', ($i+1));
			$data[] = $pupilcount;
			for ($j = 0; $j <= 5; $j++)	{
				$data[] = $ocenki[$i][$j];
				$data[] = number_format($ocenki[$i][$j]/$pupilcount*100, 2, ',', '') . '%';
			}
			$table->data[] = $data;
        }
}

?>