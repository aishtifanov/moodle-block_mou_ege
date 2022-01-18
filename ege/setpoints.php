<?php // $Id: setpoints.php,v 1.17 2012/06/13 06:47:03 shtifanov Exp $

    require_once('../../../config.php');
    require_once('../../monitoring/lib.php');
    require_once('../lib_ege.php');

    $rid = required_param('rid', PARAM_INT);       // Rayon id
    $did = required_param('did', PARAM_INT);       // Discipline id
    $yid = optional_param('yid', 0, PARAM_INT);    // Year id
    $pp1 = optional_param('pp1', 0, PARAM_INT);    // Point 1 id
    $pp2 = optional_param('pp2', 0, PARAM_INT);    // Point 2 id
    $pp3 = optional_param('pp3', 0, PARAM_INT);    // Point 3 id    

    if ($yid == 0)	{
    	$yid = get_current_edu_year_id();
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

	print_tabs_years($yid, "points.php?rid=$rid&amp;did=$did&amp;yid=");

    $currenttab = 'setpoints';
    include ('tabspoint.php');

		/// A form was submitted so process the input
		if ($frm = data_submitted())   {
			// print_r($frm);
			if (!empty($frm->add) and !empty($frm->addselect) and confirm_sesskey()) {
				foreach ($frm->addselect as $addschool) {

				    if (record_exists_select('monit_school_point_forschool', "rayonid = $rid and schoolid = $addschool and disciplineid = $did and pointnumber1id = $pp1 and pointnumber2id = $pp2 and pointnumber3id = $pp3"))  {
				    	$school = get_record('monit_school', 'id', $addschool);
						notify(get_string('schoolalreadysetpoint', 'block_mou_ege', $school->name));
					} else if (record_exists_select('monit_school_point_forschool', "rayonid = $rid and schoolid = $addschool and disciplineid = $did and pointnumber1id = $pp1 "))  {
		   					delete_records('monit_school_point_forschool', 'schoolid', $addschool, 'disciplineid', $did, 'pointnumber1id', $pp1);
							$rec->pointnumber1id = $pp1;
							$rec->pointnumber2id = $pp2;
                            $rec->pointnumber3id = $pp3;
							$rec->disciplineid = $did;
							$rec->rayonid = $rid;
							$rec->schoolid = $addschool;
							if (insert_record('monit_school_point_forschool', $rec))	{
								// add_to_log(1, 'monitoring', 'operator added', '/blocks/monitoring/users/operators.php?level=$levelmonit&amp;sid=$sid&amp;rid=$rid', $USER->lastname.' '.$USER->firstname);
							} else  {
								error('Error in adding set point!');
							}
					} else {
						$rec->pointnumber1id = $pp1;
						$rec->pointnumber2id = $pp2;
                        $rec->pointnumber3id = $pp3;
						$rec->disciplineid = $did;
						$rec->rayonid = $rid;
						$rec->schoolid = $addschool;
						if (insert_record('monit_school_point_forschool', $rec))	{
							// add_to_log(1, 'monitoring', 'operator added', '/blocks/monitoring/users/operators.php?level=$levelmonit&amp;sid=$sid&amp;rid=$rid', $USER->lastname.' '.$USER->firstname);
						} else  {
							error('Error in adding set point!');
						}
					}
  	            }
			} else if (!empty($frm->remove) and !empty($frm->removeselect) and confirm_sesskey()) {
				foreach ($frm->removeselect as $removeschool) {
					delete_records('monit_school_point_forschool', 'schoolid', $removeschool, 'disciplineid', $did);
					// add_to_log(1, 'dean', 'curator deleted', '/blocks/dean/gruppa/curatorsgroups.php', $USER->lastname.' '.$USER->firstname);
				}
			} else if (!empty($frm->showall)) {
				unset($frm->searchtext);
				$frm->previoussearch = 0;
			}
		}


	echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
	listbox_discipline_ege("setpoints.php?yid=$yid&amp;rid=$rid&amp;did=", $rid, 0, $yid, $did, EXCEPTION_LIST_DISCIPLINE_CODE);
    if ($admin_is  || $region_operator_is) {  // || $rayon_operator_is)  {
		listbox_rayons("setpoints.php?yid=$yid&amp;did=$did&amp;rid=", $rid);
	}
	if ($pp1 != 0 && $pp2 == 0)	{  
	   $pp2 = $pp1;
       $pp3 = $pp1;
    }   
	listbox_pps("setpoints.php?yid=$yid&amp;did=$did&amp;rid=$rid&amp;", $yid, $did, $rid, $pp1, $pp2, $pp3);
	echo '</table>';


 	if ($did != 0 && $rid != 0 && $pp1 != 0 && $pp2 != 0)  {

		$strsql =  "SELECT id, pointnumber1id, pointnumber2id, pointnumber3id, rayonid, schoolid, disciplineid
					FROM {$CFG->prefix}monit_school_point_forschool
	   				WHERE rayonid = $rid and disciplineid = $did and pointnumber1id = $pp1 and pointnumber2id = $pp2 and pointnumber3id = $pp3";
        // echo $strsql . '<br>';
                    
	    $pointmenu = array();
	 	if ($points = get_records_sql($strsql))	{
	 		// print_r($points);
	 		foreach ($points as $point)	{
	 			$pointmenu [] = $point->schoolid;
	 		}
	 	}

        if ($rid == 23) {
    		$strsql =  "SELECT id, pointnumber1id, pointnumber2id, pointnumber3id, rayonid, schoolid, disciplineid
    					FROM {$CFG->prefix}monit_school_point_forschool
    	   				WHERE rayonid in (21, 23) and disciplineid = $did ";
        } else {
    		$strsql =  "SELECT id, pointnumber1id, pointnumber2id, pointnumber3id, rayonid, schoolid, disciplineid
    					FROM {$CFG->prefix}monit_school_point_forschool
    	   				WHERE rayonid = $rid and disciplineid = $did ";
        }                
	    $pointmenu2 = array();
	 	if ($points2 = get_records_sql($strsql))	{
	 		// print_r($points);
	 		foreach ($points2 as $point)	{
	 			$pointmenu2 [] = $point->schoolid;
	 		}
	 	}

		$schoolmenu = array();
		$pointname = array();
        if ($rid == 23) {
            $strsql = "SELECT id, name, number  FROM {$CFG->prefix}monit_school
    				   WHERE rayonid in (21, 23) AND isclosing=0 AND yearid=$yid
    				   ORDER BY rayonid, number";
            
        } else {
            $strsql = "SELECT id, name, number  FROM {$CFG->prefix}monit_school
    				   WHERE rayonid = $rid AND isclosing=0 AND yearid=$yid
    				   ORDER BY number";
            
        }
	    if ($arr_schools =  get_records_sql($strsql))	{
	  		foreach ($arr_schools as $school) {
	  			$name = truncate_school_name($school->name);
	  			if (in_array($school->id, $pointmenu))	{
					$pointname[$school->id] = $name;
				} if (!in_array($school->id, $pointmenu2))	{
					$schoolmenu[$school->id] = $school->number . '. '. $name;
				}
			}
		}

	    print_simple_box_start("center");
	    $sesskey = !empty($USER->id) ? $USER->sesskey : '';
?>

<form name="formpoint" id="formpoint" method="post" action="setpoints.php">
<input type="hidden" name="did" value="<?php echo $did ?>" />
<input type="hidden" name="rid" value="<?php echo $rid ?>" />
<input type="hidden" name="yid" value="<?php echo $yid ?>" />
<input type="hidden" name="pp1" value="<?php echo $pp1 ?>" />
<input type="hidden" name="pp2" value="<?php echo $pp2 ?>" />
<input type="hidden" name="pp3" value="<?php echo $pp3 ?>" />
<input type="hidden" name="sesskey" value="<?php echo $sesskey ?>" />
<table align="center" border="0" cellpadding="5" cellspacing="0">
    <tr>
      <td valign="top"> <?php echo get_string('schoolsetpp', 'block_mou_ege');  ?>  </td>
      <td></td>
      <td valign="top"> <?php echo get_string('schoolrayonnothaveppe', 'block_mou_ege');?> </td>
    </tr>
    <tr>
      <td valign="top">
          <select name="removeselect[]" size="20" id="removeselect"  multiple
                  onFocus="document.formpoint.add.disabled=true;
                           document.formpoint.remove.disabled=false;
                           document.formpoint.addselect.selectedIndex=-1;" />
          <?php
          if (!empty($pointname))	{
              foreach ($pointname as $key => $pm) {
                  echo "<option value=\"$key\">" . $pm . "</option>\n";
              }
          }
          ?>
          </select></td>
      <td valign="top">
        <br />
        <input name="add" type="submit" id="add" value="&larr;" />
        <br />
        <input name="remove" type="submit" id="remove" value="&rarr;" />
        <br />
      </td>
      <td valign="top">
          <select name="addselect[]" size="20" id="addselect"  multiple
                  onFocus="document.formpoint.add.disabled=false;
                           document.formpoint.remove.disabled=true;
                           document.formpoint.removeselect.selectedIndex=-1;">
          <?php
          if (!empty($schoolmenu))	{
              foreach ($schoolmenu as $key => $sm) {
                  echo "<option value=\"$key\">" . $sm . "</option>\n";
              }
          }
          ?>
         </select>
       </td>
    </tr>
  </table>
</form>

<?php
   print_simple_box_end();
   }
   print_footer();


// Display list group as popup_form
function listbox_pps($scriptname, $yid, $did, $rid, $pp1=0, $pp2=0, $pp3=0)
{
  global $CFG;

  $strtitle = get_string('selectapp', 'block_mou_ege') . ' ...';
  $pp1menu = array();
  $pp1menu[0] = $strtitle;
  $pp2menu = array();
  $pp2menu[0] = $strtitle;
  $pp3menu = array();
  $pp3menu[0] = $strtitle;


/*
SELECT schoolid, number
FROM `mou`.`mdl_monit_school_points` a INNER JOIN `mou`.`mdl_monit_school_point_number` b ON a.id = b.pointid
WHERE  yearid = 2 and rayonid = 1 AND disciplineid = 1

SELECT distinct schoolid
FROM `mou`.`mdl_monit_school_points` a LEFT JOIN `mou`.`mdl_monit_school_point_number` b ON a.id = b.pointid
WHERE rayonid = 1
*/

  if ($yid != 0 && $did != 0 && $rid != 0)   {
        $strsql = "SELECT a.schoolid, b.id, b.number
				  FROM {$CFG->prefix}monit_school_points a INNER JOIN {$CFG->prefix}monit_school_point_number b ON a.id = b.pointid ";
		if ($rid == 23 || $rid == 25)	{
			$strsql .= "WHERE  disciplineid = $did ORDER BY number";
		} else {
			$strsql .= "WHERE  rayonid = $rid AND disciplineid = $did";
		}

		// echo $strsql; echo '<hr>';
		
		$arr_pps = get_records_sql ($strsql);
		
		// print_r($arr_pps);

		  if ($arr_pps) 	{
				foreach ($arr_pps as $pp) {
				    $school = get_record('monit_school', 'id', $pp->schoolid, '', '', '', '', 'id, name');
				    $name = truncate_school_name($school->name);
					$pp1menu[$pp->id] = $pp->number.'-й пункт: '. $name;
					$pp2menu[$pp->id] = $pp->number.'-й пункт: '. $name;
                    $pp3menu[$pp->id] = $pp->number.'-й пункт: '. $name;
				}
		  }
  }

  echo '<tr><td>'.get_string('basepoint','block_mou_ege').':</td><td>';
  popup_form($scriptname."pp3=$pp3&pp2=$pp2&pp1=", $pp1menu, 'switchpp1', $pp1, '', '', '', false);
  echo '</td></tr>';

  echo '<tr><td>'.get_string('reservpoint','block_mou_ege').':</td><td>';
  popup_form($scriptname."pp1=$pp1&pp3=$pp3&pp2=", $pp2menu, 'switchpp2', $pp2, '', '', '', false);
  echo '</td></tr>';

  echo '<tr><td>'.get_string('reservpoint3','block_mou_ege').':</td><td>';
  popup_form($scriptname."pp1=$pp1&pp2=$pp2&pp3=", $pp3menu, 'switchpp3', $pp3, '', '', '', false);
  echo '</td></tr>';

  return 1;
}

/*
SELECT rayonid, schoolid, disciplineid, count(*)
FROM `mou`.`mdl_monit_school_point_forschool`
GROUP by schoolid, disciplineid
HAVING count(*)>1
*/

?>