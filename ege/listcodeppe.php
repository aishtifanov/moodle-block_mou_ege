<?PHP // $Id: listcodeppe.php,v 1.5 2010/04/06 10:18:12 Shtifanov Exp $

    require_once('../../../config.php');
    require_once('../../monitoring/lib.php');
    require_once('../lib_ege.php');


    $yid = optional_param('yid', 0, PARAM_INT);       // Year id
    $rid = $did = 0;

    if ($yid == 0)	{
    	$yid = get_current_edu_year_id();
    }


	$action   = optional_param('action', '');
    if ($action == 'excel') {
		$table = table_listcodeppe($yid);
    	// print_r($table);
        print_table_to_excel($table);
        exit();
	}

	if (! $site = get_site()) {
        redirect("$CFG->wwwroot/$CFG->admin/index.php");
    }

	require_login();

	$admin_is = isadmin();
	$region_operator_is = ismonitoperator('region');
	if (!$admin_is && !$region_operator_is) {
        error(get_string('adminaccess', 'block_monitoring'), "$CFG->wwwroot/login/index.php");
	}

/*	if (isregionviewoperator())  {
        error(get_string('adminaccess', 'block_monitoring'));
	}
*/
	// $strclasses = get_string('classes','block_mou_ege');
	$strclasses = get_string('school','block_monitoring');
	$str1 = get_string('listcodeppe','block_mou_ege');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= " -> $str1";
    print_header_mou("$site->shortname: $str1", $site->fullname, $breadcrumbs);

	print_tabs_years($yid, "listcodeppe.php?yid=");

    $currenttab = 'listcodeppe';
    include ('tabspoint.php');

	// echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
	// listbox_rayons("pupilppe.php?yid=$yid&amp;did=$did&amp;rid=", $rid);
	// listbox_discipline_ege("pupilppe.php?yid=$yid&amp;rid=$rid&amp;did=", $rid, 0, $yid, $did, EXCEPTION_LIST_DISCIPLINE_CODE);
	// echo '</table>';

// 	if ($did != 0)  {
		$table = table_listcodeppe ($yid);
		print_color_table($table);

		$options = array('yid' => $yid, 'action' => 'excel');
		echo '<table align="center" border=0><tr><td>';
	    print_single_button("listcodeppe.php", $options, get_string("downloadexcel"));
		echo '</td></tr></table>';
//	}

	print_footer();


function table_listcodeppe ($yid)
{
	global $CFG;

    $table->head  = array (get_string('code_ou','block_mou_ege'),
    					   get_string ('codeppe','block_mou_ege'),
    					   get_string('name_ou','block_mou_ege'));
	$table->align = array ( 'center', 'center', 'left');
	$table->columnwidth = array (8, 6, 67);

    $table->class = 'moutable';
   	$table->width = '90%';

    $table->titlesrows = array(30);
	$table->titles = array();
    $table->titles[] = get_string('listcodeppe', 'block_mou_ege');
	$table->downloadfilename = 'listcodeppe';
    $table->worksheetname = $table->downloadfilename;

    $rayons = get_records('monit_rayon');

	    foreach ($rayons as $rayon)		{

	    	$rid = $rayon->id;
	    	$table->data[] = array('<hr>', '<hr>', '<b>'.$rayon->name.'</b>');

			$schools =  get_records_sql("SELECT *  FROM {$CFG->prefix}monit_school
					     				WHERE rayonid = {$rayon->id} AND isclosing=0 AND yearid=$yid
					     				ORDER BY codeppe");

			// echo $strsql; echo '<hr>';
		    if ($schools)  {
		    	foreach ($schools as $school)	{
			    	$table->data[] = array($school->code, $school->codeppe, $school->name);
		    	}
		    }

	    }

    return $table;
}

?>
