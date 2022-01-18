<?PHP // $Id: textbook.php,v 1.5 2011/03/04 06:17:26 shtifanov Exp $

    require_once('../../../config.php');
    require_once('../../monitoring/lib.php');
    require_once('../lib_ege.php');

    $rid = required_param('rid', PARAM_INT);          // Rayon id
    $sid = required_param('sid', PARAM_INT);       // School id
    $yid = optional_param('yid', 0, PARAM_INT);       // Year id
	$catid  = optional_param('catid', 0, PARAM_INT);       // Category textbook

    if ($yid == 0)	{
    	$yid = get_current_edu_year_id();
    }


	$action   = optional_param('action', '');
    if ($action == 'excel') {
        disciplines_ege_download('xls', $sid, $fid, $cid, $term);
        exit();
	}


	require_login();

	$admin_is = isadmin();
	$region_operator_is = ismonitoperator('region');
	$rayon_operator_is  = ismonitoperator('rayon', 0, 0, 0, true);
	$school_operator_is = ismonitoperator('school', 0, $rid, $sid);
	if (!$admin_is && !$region_operator_is && !$rayon_operator_is && !$school_operator_is) {
        error(get_string('adminaccess', 'block_monitoring'), "$CFG->wwwroot/login/index.php");
	}

	if (!$admin_is && !$region_operator_is && !$rayon_operator_is && $school_operator_is)  {
	      if ($school = get_record_sql ("SELECT id, uniqueconstcode FROM {$CFG->prefix}monit_school
	                                     WHERE rayonid=$rid AND uniqueconstcode=$sid AND yearid=$yid"))	{
	     		$sid = $school->id;
	      }
	}
	
    $strtextbook = get_string('textbooks', 'block_mou_ege');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= "-> $strtextbook";
	print_header_mou("$SITE->shortname: $strtextbook", $SITE->fullname, $breadcrumbs);

	print_tabs_years($yid, "textbook.php?yid=");

	echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
	listbox_textbook("textbook.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;catid=", $yid, $catid);
	echo '</table>';


/*
    $currenttab = 'disciplines_ege';
    include('tabsege.php');
*/
    if ($yid  != 0 &&  $catid != 0)		{
	    $table->head  = array ('№', get_string('authors','block_mou_ege'), get_string('textbookname','block_mou_ege'),
							    get_string('textbooknumclass','block_mou_ege'), get_string('publisher','block_mou_ege'),
							    get_string("action","block_mou_ege"));

	    $table->align = array ('center', 'left', 'left', 'center', 'left', 'center');
	    $table->class = 'moutable';
	  	$table->width = '80%';
	    $table->size = array ('5%', '30%', '20%', '10%', '10%', '5%');


	//	$currcourse = get_records ('school_discipline', 'curriculumid', $cid);
		$textbooks =  get_records('monit_textbook',  'categoryid', $catid, 'authors');


		if ($textbooks)	{
		    $i = 0;
			foreach ($textbooks as $textbook) {
				$title = get_string('edittextbook','block_mou_ege');
				$strlinkupdate = "<a title=\"$title\" href=\"edittextbook.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;catid=$catid&amp;tbid={$textbook->id}\">";
				$strlinkupdate = $strlinkupdate . "<img src=\"{$CFG->pixpath}/i/edit.gif\" alt=\"$title\" /></a>&nbsp;";

				if 	($admin_is || $region_operator_is)	 {
					$title = get_string('deletetextbook','block_mou_ege');
			  	 	$strlinkupdate = $strlinkupdate . "<a title=\"$title\" href=\"deltextbook.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;catid=$catid&amp;tbid={$textbook->id}\">";
					$strlinkupdate = $strlinkupdate . "<img src=\"{$CFG->pixpath}/i/cross_red_big.gif\" alt=\"$title\" /></a>&nbsp;";
				}

				$table->data[] = array (++$i.'.', $textbook->authors, $textbook->name, $textbook->numclass, $textbook->publisher, $strlinkupdate);
			}
		}


		// echo "<hr />";
		// print_heading($strdisciplines, "center", 4);
		// print_heading(get_string("disciplinesterm","block_mou_ege"), "center", 4);
	    print_heading($strtextbook, "center", 4);

	    print_color_table($table);

		if 	($admin_is || $region_operator_is || $rayon_operator_is)	 {
			?>
			<table align="center">
				<tr>
				<td>
			  <form name="adddiscipl" method="post" action="<?php echo "edittextbook.php" ?>">
					<input type="hidden" name="rid" value="<?php echo $rid ?>">
					<input type="hidden" name="sid" value="<?php echo $sid ?>">
					<input type="hidden" name="yid" value="<?php echo $yid ?>">
					<input type="hidden" name="catid" value="<?php echo $catid ?>">
					<input type="hidden" name="tbid" value="0">
			  	    <div align="center">
					<input type="submit" name="adddiscipline" value="<?php print_string('addtextbook','block_mou_ege')?>">
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

		}
    }
    print_footer();


function disciplines_ege_download($download, $sid, $fid, $cid, $term)
{
    global $CFG;


}

?>