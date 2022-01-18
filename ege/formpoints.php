<?php // $Id: formpoints.php,v 1.7 2011/04/29 07:11:37 shtifanov Exp $

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
		$table = table_points ($yid);
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

/*	if (isregionviewoperator())  {
        error(get_string('adminaccess', 'block_monitoring'));
	}
*/
	// $strclasses = get_string('classes','block_mou_ege');
	$strclasses = get_string('school','block_monitoring');
	$str1 = get_string('points','block_mou_ege');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= " -> $str1";
    print_header_mou("$SITE->shortname: $str1", $SITE->fullname, $breadcrumbs);

	if (!$admin_is && !$region_operator_is && $rayon_operator_is && $rayon_operator_is != $rid)  {
		notify(get_string('selectownrayon', 'block_monitoring'));
	    print_footer();
		exit();
	}

	print_tabs_years($yid, "points.php?rid=$rid&amp;yid=");

    $currenttab = 'formpoints';
    include ('tabspoint.php');

		/// A form was submitted so process the input
		if ($frm = data_submitted())   {
			// print_r($frm);
			if (!empty($frm->add) and !empty($frm->addselect) and confirm_sesskey()) {
				foreach ($frm->addselect as $addschool) {

				    if (record_exists('monit_school_points', 'schoolid', $addschool)) {
				    	$school = get_record('monit_school', 'id', $addschool);
						notify(get_string('schoolalreadypoint', 'block_mou_school', $school->name));
					} else {
						$rec->yearid = $yid;
						$rec->rayonid = $rid;
						$rec->schoolid = $addschool;
						if (insert_record('monit_school_points', $rec))	{
							// add_to_log(1, 'monitoring', 'operator added', '/blocks/monitoring/users/operators.php?level=$levelmonit&amp;sid=$sid&amp;rid=$rid', $USER->lastname.' '.$USER->firstname);
						} else  {
							error('Error in adding point!');
						}
					}
  	            }
			} else if (!empty($frm->remove) and !empty($frm->removeselect) and confirm_sesskey()) {
				foreach ($frm->removeselect as $removeschool) {
					// delete_records('monit_school_points', 'schoolid', $removeschool);
                    check_and_delete_school_point($removeschool);
					// add_to_log(1, 'dean', 'curator deleted', '/blocks/dean/gruppa/curatorsgroups.php', $USER->lastname.' '.$USER->firstname);
				}
			} else if (!empty($frm->showall)) {
				unset($frm->searchtext);
				$frm->previoussearch = 0;
			}

		}

	if ($admin_is || $region_operator_is) {
		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
		listbox_rayons("formpoints.php?yid=$yid&amp;rid=", $rid);
		echo '</table>';
	}	

 	if ($rid != 0)  {

		$strsql =  "SELECT *  FROM {$CFG->prefix}monit_school_points
	   				WHERE rayonid = $rid and  yearid = $yid";
	    $pointmenu = array();
	 	if ($points = get_records_sql($strsql))	{
	 		foreach ($points as $point)	{
	 			$pointmenu [] = $point->schoolid;
	 		}
	 	}

		$schoolmenu = array();
		$pointname = array();
	    if ($arr_schools =  get_records_sql("SELECT id, name, codeppe  FROM {$CFG->prefix}monit_school
						     				WHERE rayonid = $rid AND isclosing=0 AND yearid=$yid
						     				ORDER BY number"))	{
	  		foreach ($arr_schools as $school) {
	  			$name = truncate_school_name($school->name);
	  			$codeppi = get_string('thispoint', 'block_mou_ege', $school->codeppe);
				if (in_array($school->id, $pointmenu))	{
					$pointname[$school->id] =  $codeppi . $name;
				} else {
					$schoolmenu[$school->id] = $codeppi . $name;
				}
			}
		}

	    print_simple_box_start("center");
	    $sesskey = !empty($USER->id) ? $USER->sesskey : '';
?>

<form name="formpoint" id="formpoint" method="post" action="formpoints.php">
<input type="hidden" name="rid" value="<?php echo $rid ?>" />
<input type="hidden" name="yid" value="<?php echo $yid ?>" />
<input type="hidden" name="sesskey" value="<?php echo $sesskey ?>" />
<table align="center" border="0" cellpadding="5" cellspacing="0">
    <tr>
      <td valign="top"> <?php echo get_string('points', 'block_mou_ege');  ?>  </td>
      <td></td>
      <td valign="top"> <?php echo get_string('schools', 'block_monitoring');?> </td>
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


function check_and_delete_school_point($removeschool)
{
    global $CFG;
    
    if ($point = get_record_select('monit_school_points', "schoolid = $removeschool", 'id'))  {
        if (record_exists('monit_school_point_number', 'pointid', $point->id))  {
            notify('Нельзя удалить пункт проведения, так как для него уже назначены предметы.');
        } else {
            delete_records('monit_school_points', 'schoolid', $removeschool);
        }        
    }    
}

?>