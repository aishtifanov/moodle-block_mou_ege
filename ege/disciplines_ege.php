<?PHP // $Id: disciplines_ege.php,v 1.15 2011/04/21 06:51:58 shtifanov Exp $

    require_once('../../../config.php');
    require_once('../../monitoring/lib.php');
    require_once('../lib_ege.php');

    $rid = optional_param('rid', '0', PARAM_INT);       // Rayon id
    $yid = optional_param('yid', '0', PARAM_INT);       // Year id

    if ($yid == 0)	{
    	$yid = get_current_edu_year_id();
    }

	$action   = optional_param('action', '');
    if ($action == 'excel') 	{
		$table = table_discipline_ege($yid);
		print_table_to_excel($table, 1);
        exit();
	} else if ($action == 'open')  {
	   set_field('config', 'value', 1, 'name', 'monit_open_close_9');
       $CFG->monit_open_close_9 = 1;
	} else if ($action == 'close')  {
	   set_field('config', 'value', 0, 'name', 'monit_open_close_9');
       $CFG->monit_open_close_9 = 0;
	}


	if (! $site = get_site()) {
        redirect("$CFG->wwwroot/$CFG->admin/index.php");
    }

	require_login();

	$admin_is = isadmin();
	$region_operator_is = ismonitoperator('region');
	$rayon_operator_is  = ismonitoperator('rayon', 0, 0, 0, true);
	if (!$admin_is && !$region_operator_is && !$rayon_operator_is) {
        error(get_string('adminaccess', 'block_monitoring'), "$CFG->wwwroot/login/index.php");
	}

    $strdisciplines = get_string('disciplines_ege', 'block_mou_ege');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= "-> $strdisciplines";
	print_header_mou("$site->shortname: $strdisciplines", $site->fullname, $breadcrumbs);

	print_tabs_years_link("disciplines_ege.php?", $rid, 0, $yid);

    $currenttab = 'disciplines_ege';
    include('tabsege.php');

	$table = table_discipline_ege($yid);

	// echo "<hr />";
	// print_heading($strdisciplines, "center", 4);
	// print_heading(get_string("disciplinesterm","block_mou_ege"), "center", 4);
    print_heading($strdisciplines, "center", 4);

    print_color_table($table);

	if 	($admin_is || $region_operator_is && !isregionviewoperator())  {

?>
<table align="center">
	<tr>
	<td>
  <form name="adddiscipl" method="post" action="<?php echo "addiscipline.php?mode=new&amp;yid=$yid"; ?>">
	    <div align="center">
		<input type="submit" name="adddiscipline" value="<?php print_string('addiscipline','block_mou_ege')?>">
	    </div>
  </form>
  </td>
	<td>
	<form name="download" method="post" action="<?php echo "disciplines_ege.php?action=excel&amp;yid=$yid" ?>">
	    <div align="center">
		<input type="submit" name="downloadexcel" value="<?php print_string("downloadexcel")?>">
	    </div>
  </form>
	</td>
	</tr>
  </table>
<?php
   
        if ($CFG->monit_open_close_9)   {
            $stropenclose = get_string('close9', 'block_mou_ege');
            $options = array('action' => 'close');
        } else {
            $stropenclose = get_string('open9', 'block_mou_ege');
            $options = array('action' => 'open');
        }
   		echo '<table align="center" border=0><tr><td>';
	    print_single_button("disciplines_ege.php", $options, $stropenclose);
		echo '</td><td>';
		echo '</td></tr></table>';
	}

    print_footer();



function table_discipline_ege($yid)
{
    global $CFG, $admin_is, $region_operator_is;

    $table->head  = array (get_string('codepredmet','block_mou_ege'),  get_string('disciplinename','block_mou_ege'), get_string('giadates','block_mou_ege'), get_string('action','block_mou_ege'));
    $table->align = array ("center",  "left", "left", "center");
    $table->class = 'moutable';
  	$table->width = '60%';
    $table->size = array ('5%', '15%', '20%', '10%');
	$table->columnwidth = array (4, 17, 20, 10);
    $table->titles = array();
    $table->titles[] = get_string('disciplines_ege', 'block_mou_ege');
    $table->worksheetname = get_string('disciplines_ege', 'block_mou_ege');
    $table->titlesrows = array(30);
    $table->downloadfilename = 'disciplines_gia';

//	$currcourse = get_records ('school_discipline', 'curriculumid', $cid);
	$currcourse =  get_records_sql ("SELECT id, yearid, name, code
									  FROM  {$CFG->prefix}monit_school_discipline_ege
									  WHERE yearid=$yid
									  ORDER BY code");

	$i = 0;
	if ($currcourse)	{
		foreach ($currcourse as $discipline) {

			$strdiscipline = $discipline->name;

			$strdates = '';

			if ($giadates =  get_records_sql ("SELECT id, date_gia FROM  {$CFG->prefix}monit_school_gia_dates
											  WHERE yearid=$yid AND discegeid={$discipline->id} ORDER BY date_gia"))  {
				foreach ($giadates as $giadate)	{
					 $strdates .= convert_date($giadate->date_gia, 'en', 'ru') . ', ';
				}

				$strdates = substr($strdates, 0, strlen($strdates)- 2);
			}


			if 	($admin_is || $region_operator_is)	 {
				$title = get_string('editdiscipline','block_mou_ege');
				$strlinkupdate = "<a title=\"$title\" href=\"addiscipline.php?mode=edit&amp;yid=$yid&amp;did={$discipline->id}\">";
				$strlinkupdate = $strlinkupdate . "<img src=\"{$CFG->pixpath}/i/edit.gif\" alt=\"$title\" /></a>&nbsp;";
				$title = get_string('deletingdiscipline','block_mou_ege');
		  	 	$strlinkupdate = $strlinkupdate . "<a title=\"$title\" href=\"deldiscipline.php?yid=$yid&amp;did={$discipline->id}\">";
				$strlinkupdate = $strlinkupdate . "<img src=\"{$CFG->pixpath}/i/cross_red_big.gif\" alt=\"$title\" /></a>&nbsp;";
			}
			else	{
				$strlinkupdate = '-';
			}

			$i++;
			$table->data[] = array ($discipline->code, $strdiscipline, $strdates, $strlinkupdate);
		}
	}

	return $table;
}

?>