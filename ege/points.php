<?PHP // $Id: points.php,v 1.9 2012/06/13 06:47:03 shtifanov Exp $

    require_once('../../../config.php');
    require_once('../../monitoring/lib.php');
    require_once('../lib_ege.php');

	$rid = required_param('rid', PARAM_INT);       // Rayon id
	$yid = optional_param('yid', 0, PARAM_INT);       // Year id
    $did = 0;

    if ($yid == 0)	{
    	$yid = get_current_edu_year_id();
    }

	$action   = optional_param('action', '');
    if ($action == 'excel') {
		$table = table_points ($yid, $rid);
    	// print_r($table);
        print_table_to_excel($table);
        exit();
	}


	require_login();

	$admin_is = isadmin();
	$region_operator_is = ismonitoperator('region');
	$rayon_operator_is  = ismonitoperator('rayon', 0, 0, 0, true);
	if (!$admin_is && !$region_operator_is && !$rayon_operator_is) {
        error(get_string('adminaccess', 'block_monitoring'), "$CFG->wwwroot/blocks/mou_ege/index.php");
	}

/*	

	if (isregionviewoperator())  {
        error(get_string('adminaccess', 'block_monitoring'));
	}
*/

	if (!$admin_is && !$region_operator_is && $rayon_operator_is && $rayon_operator_is != $rid)  {
		notify(get_string('selectownrayon', 'block_monitoring'));
	    print_footer();
		exit();
	}

	// $strclasses = get_string('classes','block_mou_ege');
	$strclasses = get_string('school','block_monitoring');
	$str1 = get_string('points','block_mou_ege');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= " -> $str1";
    print_header_mou("$SITE->shortname: $str1", $SITE->fullname, $breadcrumbs);

	print_tabs_years($yid, "points.php?rid=$rid&amp;yid=");

    $currenttab = 'points';
    include ('tabspoint.php');

	/// A form was submitted so process the input
	if ($recs = data_submitted())  {
		// print_r($recs); echo '<hr>';

        $fields = array();
		foreach($recs as $name => $number)	{
			if ($number != '')	{
	            $mask = substr($name, 0, 4);
	            if ($mask == 'num_')	{
   				    $fields[] = $name;
	            	$ids = explode('_', $name);
	            	$pointid = $ids[1];
	            	$did = $ids[2];
	            	$codeppe = (string)$ids[3];
	            	if (record_exists('monit_school_point_number', 'pointid', $pointid, 'disciplineid', $did))	{
	            		if ($number == 'on')	{
	            			set_field('monit_school_point_number', 'number', $codeppe, 'pointid', $pointid, 'disciplineid', $did);
	            		}
	            	} else {
	            		$newrec->yearid = $yid;
	            		$newrec->pointid = $pointid;
	            		$newrec->disciplineid = $did;
	            		$newrec->number = (string)$codeppe;
				       if (!insert_record('monit_school_point_number', $newrec))	{
							error(get_string('errorinaddingpp','block_mou_ege'), "points.php?yid=$yid");
					   }

	            	}
	            }
	        }
		}
		// delete
        /*
		if ($admin_is || $region_operator_is)	{
	
			$spns = get_records('monit_school_point_number', 'yearid', $yid);
			foreach($spns as $spn)	{
				$name = 'num_' . $spn->pointid . '_' . $spn->disciplineid .  '_' . $spn->number;
				if (!in_array($name, $fields))	{
					delete_records('monit_school_point_number', 'pointid', $spn->pointid, 'disciplineid',  $spn->disciplineid);
					delete_records('monit_school_point_forschool', 'pointnumber1id', $spn->id);
					delete_records('monit_school_point_forschool', 'pointnumber2id', $spn->id);
				}
			}
		} else if ($rayon_operator_is)   {
		 */ 
        if ($rid != 0)  {
			/*			
			SELECT b.id, b.pointid, b.disciplineid, b.number
			FROM mdl_monit_school_points a INNER JOIN mdl_monit_school_point_number b ON a.id = b.pointid
			WHERE  b.yearid = 3 and a.rayonid = 1
			*/			
			$strsql = "SELECT b.id, b.pointid, b.disciplineid, b.number
					   FROM {$CFG->prefix}monit_school_points a INNER JOIN {$CFG->prefix}monit_school_point_number b ON a.id = b.pointid
					   WHERE  a.yearid = $yid and a.rayonid = $rid";
			$spns = get_records_sql($strsql);
			foreach($spns as $spn)	{
				$name = 'num_' . $spn->pointid . '_' . $spn->disciplineid .  '_' . $spn->number;
				if (!in_array($name, $fields))	{ 
					delete_records('monit_school_point_number', 'pointid', $spn->pointid, 'disciplineid',  $spn->disciplineid);
					delete_records('monit_school_point_forschool', 'pointnumber1id', $spn->id);
					delete_records('monit_school_point_forschool', 'pointnumber2id', $spn->id);
                    delete_records('monit_school_point_forschool', 'pointnumber3id', $spn->id);
				}
			}
		}	

        // notice(get_string('succesavedata','block_monitoring'), "points.php?rid=$rid&amp;yid=$yid");
		redirect("points.php?rid=$rid&amp;yid=$yid", get_string('succesavedata','block_monitoring'), 0);
	}

	if ($admin_is || $region_operator_is) {
		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
		listbox_rayons("points.php?yid=$yid&amp;rid=", $rid);
		echo '</table>';
	}	

 	if ($rid != 0)  {
    	$table = table_points ($yid, $rid);
    	echo  '<form name="points" method="post" action="points.php">';
    	echo  '<input type="hidden" name="yid" value="' .  $yid . '">';
    	echo  '<input type="hidden" name="rid" value="' .  $rid . '">';	
    	echo  '<div align="center">';
    	print_color_table($table);
    	echo  '<input type="submit" name="savepoints" value="'. get_string('savechanges') . '"></div>';
    	echo  '</form>';
    }    
/*
	$options = array('rid' => $rid, 'yid' => $yid, 'action' => 'excel');
	echo '<table align="center" border=0><tr><td>';
    print_single_button("points.php", $options, get_string("downloadexcel"));
	echo '</td></tr></table>';
*/
	print_footer();


function table_points ($yid, $rid)
{
	global $CFG, $admin_is, $region_operator_is, $rayon_operator_is;

    $exception = EXCEPTION_LIST_DISCIPLINE_CODE;
	$disciplines =  get_records_sql ("SELECT id, yearid, name, code  FROM  {$CFG->prefix}monit_school_discipline_ege
									  WHERE yearid=$yid and code not in ($exception)
									  ORDER BY name");

    $table->head  = array (get_string ('codeppe','block_mou_ege'), get_string('school','block_monitoring'), get_string('numberpoints','block_mou_ege'));
	$table->align = array ('center', 'left', 'center');
	$table->columnwidth = array (10, 25, 10);

    $table->class = 'moutable';
   	$table->width = '60%';

	$table->titles = array();
    $table->titles[] = get_string('points', 'block_mou_ege');
    $table->worksheetname = $yid;

/*
	if ($admin_is || $region_operator_is) {
    	$rayons = get_records('monit_rayon');
    } else {
    	$rayons = get_records('monit_rayon', 'id', $rid);
    }	
*/
    $rayons = get_records('monit_rayon', 'id', $rid);
    
    foreach ($rayons as $rayon)		{

    	$rid = $rayon->id;
		$strsql =  "SELECT *  FROM {$CFG->prefix}monit_school_points
	   				WHERE rayonid = $rid and  yearid = $yid";
	 	if ($points = get_records_sql($strsql))	{
	    	$table->data[] = 'hr';
	    	$table->data[] = array('<hr>', '<b>'.$rayon->name.'</b>', '<hr>');
	 		foreach ($points as $point)	{

	 			$school = get_record_sql ("SELECT id, name, codeppe  FROM {$CFG->prefix}monit_school
										   WHERE id = {$point->schoolid}");

			    $numbers = array();
				foreach ($disciplines as $discipline) 	{
			    	$numbers[$discipline->id] = '';
			    }

                if ($point_discs = get_records_sql ("SELECT * FROM {$CFG->prefix}monit_school_point_number
										   WHERE pointid = {$point->id}"))	{
						foreach ($point_discs as $pd) 	{
					        $numbers[$pd->disciplineid] = $pd->number;
						}
				}

				$insidetable = '<table align="left" border=0>';
				foreach ($disciplines as $discipline) 	{
					$check = $num = '';
				    if (isset($numbers[$discipline->id]) && !empty($numbers[$discipline->id]))	{
   		                $num = $numbers[$discipline->id];
				    	$check = 'checked';
				    }
					$insidetable .= '<tr align="left">';
					$insidetable .= "<td align=right><input type=checkbox $check name=num_{$point->id}_{$discipline->id}_{$school->codeppe}></td>";
	                $insidetable .= '<td>' . $discipline->name . '</td></tr>';
				/*
	                $insidetable .= '<tr align="left"><td>' . $discipline->name . '</td>';

	                $insidetable .= "<td><input type=text  name=num_{$point->id}_{$discipline->id} size=1 value=$num></td></tr>";
	            */
				}
				$insidetable .= '</table>';


				$tabledata = array ($school->codeppe, $school->name, $insidetable);
				$table->data[] = $tabledata;
            }
        }
    }

    return $table;
}

?>
