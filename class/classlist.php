<?php // $Id: classlist.php,v 1.24 2011/03/04 06:14:57 shtifanov Exp $

    require_once("../../../config.php");
    require_once('../../monitoring/lib.php');

    $rid = required_param('rid', PARAM_INT);          // Rayon id
    $sid = optional_param('sid', '0', PARAM_INT);       // School id
	// $cid = required_param('cid', PARAM_INT);		  // Curriculum id
    $yid = optional_param('yid', '0', PARAM_INT);       // Year id

    $curryearid = get_current_edu_year_id();
    if ($yid == 0)	{
    	$yid = $curryearid;
    }

	$action   = optional_param('action', 'grades');
    if ($action == 'excel') {
        classlistpupils_download($rid, $sid, $yid);
        exit();
	}


    if (! $site = get_site()) {
        redirect("$CFG->wwwroot/$CFG->admin/index.php");
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
	      if ($school = get_record_sql ("SELECT id, uniqueconstcode, name FROM {$CFG->prefix}monit_school
	                                     WHERE rayonid=$rid AND uniqueconstcode=$sid AND yearid=$yid"))	{
	     		$sid = $school->id;
	      }
	}

	$strclasses = get_string('school','block_monitoring');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= "-> $strclasses";
    print_header("$site->shortname: $strclasses", $site->fullname, $breadcrumbs);

	if ($rid == 0)  {
	   $rayon = get_record('monit_rayon', 'id', 1);
	}
	else if (!$rayon = get_record('monit_rayon', 'id', $rid)) {
        error(get_string('errorrayon', 'block_monitoring'), '..\rayon\rayons.php');
    }

    if ($admin_is  || $region_operator_is) {  // || $rayon_operator_is)  {
		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
		listbox_rayons("classlist.php?sid=0&amp;yid=$yid&amp;rid=", $rid);
		listbox_schools("classlist.php?rid=$rid&amp;yid=$yid&amp;sid=", $rid, $sid, $yid);
		echo '</table>';
	} else  if ($rayon_operator_is)  {
		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
		listbox_schools("classlist.php?rid=$rid&amp;yid=$yid&amp;sid=", $rid, $sid, $yid);
		echo '</table>';
	}  else if ($school_operator_is) {
		print_heading($strclasses.': '.$school->name, "center", 3);
	}

	if ($rid == 0 ||  $sid == 0) {
	    print_footer();
	 	exit();
	}

	if ($rayon_operator_is && $rayon_operator_is != $rid)  {
		notify(get_string('selectownrayon', 'block_monitoring'));
	    print_footer();
		exit();
	}


//	print_heading($strclasses, "center");

	// print_tabs_years($yid, "classlist.php?rid=$rid&amp;sid=$sid&amp;yid=");
	print_tabs_years_link("classlist.php?", $rid, $sid, $yid);

    $currenttab = 'listclasses';
    include('tabsclasses.php');

	$table->head  = array (get_string('class','block_mou_ege'), get_string("numofstudents","block_mou_ege"), get_string("action","block_mou_ege"));
	$table->align = array ("center", "center", "center");
    $table->class = 'moutable';
   	$table->width = '40%';
    $table->size = array ('10%', '10%', '10%');

	$classes = get_records_sql ("SELECT * FROM {$CFG->prefix}monit_school_class
									  WHERE schoolid=$sid AND yearid=$yid
									  ORDER BY parallelnum");
	if ($classes)	{

		foreach ($classes as $class) {


			// if ($admin_is || $region_operator_is) 	{
				$title = get_string('editclass','block_mou_ege');
				$strlinkupdate = "<a title=\"$title\" href=\"addclass.php?mode=edit&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid={$class->id}\">";
				$strlinkupdate .= "<img src=\"{$CFG->pixpath}/i/edit.gif\" alt=\"$title\" /></a>&nbsp;";
			/*
				$title = get_string('deleteclass','block_mou_ege');
			    $strlinkupdate .= "<a title=\"$title\" href=\"delclass.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid={$class->id}\">";
				$strlinkupdate .= "<img src=\"{$CFG->pixpath}/i/cross_red_big.gif\" alt=\"$title\" /></a>&nbsp;";

			if ($admin_is || $region_operator_is || ($rayon_operator_is == $rayon->id)) 	{
				$title = get_string('clearclass','block_mou_ege');
				$strlinkupdate .= "<a title=\"$title\" href=\"delclass.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid={$class->id}&amp;action=clear\">";
				$strlinkupdate .=  "<img src=\"{$CFG->wwwroot}/blocks/mou_ege/i/goom.gif\" alt=\"$title\" /></a>&nbsp;";
			}
		
			}
			else	{
				$strlinkupdate = '-';
			}
			*/

			$title = get_string('pupils','block_mou_ege');
			$countpupils = count_records('monit_school_pupil_card', 'classid',  $class->id, 'deleted', 0);
			$table->data[] = array ("<strong><a title=\"$title\" href=\"classpupils.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid={$class->id}\">$class->name</a></strong>",
									$countpupils, $strlinkupdate);
		}

		print_color_table($table);
		// if ($admin_is || $region_operator_is || ($rayon_operator_is == $rayon->id)) 	{

?>
<table align="center">	<tr>
			<form name="download" method="post" action="classlist.php">
			    <div align="center">
				<input type="hidden" name="rid" value="<?php echo $rid ?>" />
				<input type="hidden" name="sid" value="<?php echo $sid ?>" />
				<input type="hidden" name="yid" value="<?php echo $yid ?>" />
				<input type="hidden" name="sesskey" value="<?php echo $USER->sesskey ?>">
				<input type="hidden" name="action" value="excel" />
				<input type="submit" name="downloadexcel" value="<?php print_string('downloadexcel_school', 'block_mou_ege')?>">
			    </div>
		  </form>
			</td>
 		  </tr>
		  </table>
		  <p>
		  
<?php
		// }
	} else {/*
?>
<table align="center">	<tr><td>
	     <form name="form2" id="form2" method="post" action="<?php echo "addclass.php?mode=new&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid"; ?>">
		   <input type="submit" name="newgroups" value="<?php print_string('addgroup','block_school') ?>"/>
         </form>
		  </td></tr></table>
<?php
*/	}
	$remark = get_string('remarkmupclass', 'block_mou_ege');
	
	// http://mou.bsu.edu.ru/blocks/mou_school/class/classlist.php?rid=1&sid=1436&yid=3
	echo "<a href=\"{$CFG->wwwroot}/blocks/mou_school/class/classlist.php?rid=$rid&amp;sid=$sid&amp;yid=$yid\"> $remark </a>";  

    print_footer();


function classlistpupils_download($rid, $sid, $yid)
{
    global $CFG;

        require_once("$CFG->libdir/excel/Worksheet.php");
        require_once("$CFG->libdir/excel/Workbook.php");

	    $rayon = get_record('monit_rayon', 'id', $rid);

	    $school = get_record('monit_school', 'id', $sid);

		$txtl = new textlib();

		// HTTP headers
        header("Content-type: application/vnd.ms-excel");
        $downloadfilename = clean_filename("pupilschool_".$rid. '_' . $sid);
        header("Content-Disposition: attachment; filename=\"{$downloadfilename}.xls\"");
        header("Expires: 0");
        header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
        header("Pragma: public");

		/// Creating a workbook
        $workbook = new Workbook("-");
        $myxls =& $workbook->add_worksheet($strwin1251);

		/// Print names of all the fields
		$formath1 =& $workbook->add_format();
		$formath2 =& $workbook->add_format();
		$formatp =& $workbook->add_format();

		$formath1->set_size(12);
	    $formath1->set_align('center');
	    $formath1->set_align('vcenter');
		$formath1->set_color('black');
		$formath1->set_bold(1);
		$formath1->set_italic();
		// $formath1->set_border(2);

		$formath2->set_size(11);
	    $formath2->set_align('center');
	    $formath2->set_align('vcenter');
		$formath2->set_color('black');
		$formath2->set_bold(1);
		//$formath2->set_italic();
		$formath2->set_border(2);
		$formath2->set_text_wrap();

		$formatp->set_size(11);
	    $formatp->set_align('left');
	    $formatp->set_align('vcenter');
		$formatp->set_color('black');
		$formatp->set_bold(0);
		$formatp->set_border(1);
		$formatp->set_text_wrap();

		$myxls->set_column(0,0,4);
		$myxls->set_column(1,1,29);
		$myxls->set_column(2,2,42);
		$myxls->set_column(3,3,10);
		$myxls->set_row(0, 60);

	    $strtitle =  $rayon->name .', ';
		$myxls->set_row(0, 30);
		$strwin1251 =  $txtl->convert($strtitle, 'utf-8', 'windows-1251');
	    $myxls->write_string(0, 0, $strwin1251, $formath1);
		$myxls->merge_cells(0, 0, 0, 3);

	    $strtitle =  $school->name;
		$myxls->set_row(1, 30);
		$strwin1251 =  $txtl->convert($strtitle, 'utf-8', 'windows-1251');
	    $myxls->write_string(1, 0, $strwin1251, $formath1);
		$myxls->merge_cells(1, 0, 1, 3);

   		$strwin1251 =  $txtl->convert('№', 'utf-8', 'windows-1251');
        $myxls->write_string(2, 0,  $strwin1251, $formath2);

   		$strwin1251 =  $txtl->convert(get_string('pupil_fio', 'block_mou_ege'), 'utf-8', 'windows-1251');
        $myxls->write_string(2, 1, $strwin1251, $formath2);

   		$strwin1251 =  $txtl->convert(get_string('disciplines_ege', 'block_mou_ege'), 'utf-8', 'windows-1251');
        $myxls->write_string(2, 2, $strwin1251, $formath2);

   		$strwin1251 =  $txtl->convert(get_string('pupil_sign', 'block_mou_ege'), 'utf-8', 'windows-1251');
        $myxls->write_string(2, 3, $strwin1251, $formath2);

       // $workbook->close();       exit;

        $i = 3;

		$classes = get_records_sql ("SELECT * FROM {$CFG->prefix}monit_school_class
										  WHERE schoolid=$sid AND yearid=$yid
										  ORDER BY name");
		if ($classes)	{

			$disciplines =  get_records_sql ("SELECT id, yearid, name  FROM  {$CFG->prefix}monit_school_discipline_ege
											  WHERE yearid=$yid ORDER BY name");
			if ($disciplines)	{
				$listegeids = array();
				foreach ($disciplines as $discipline) 	{
					$listegeids [$discipline->id] = $discipline->name;
				}
			}

			$rowstart = array();
			$rowend = array();

			foreach ($classes as $class) {

				$strtitle = get_string('class','block_mou_ege') . ' ' . $class->name;
		   		$strwin1251 =  $txtl->convert($strtitle, 'utf-8', 'windows-1251');
   			    $myxls->write_string($i, 0, $strwin1251, $formath1);
			    $myxls->merge_cells($i, 0, $i, 3);

                $rowstart[] = $i + 2;

                $gid = $class->id;

				  // get_string('city'), get_string('country'), get_string('lastaccess'));

		        $studentsql = "SELECT u.id, u.username, u.firstname, u.lastname, u.email, u.maildisplay,
									  u.city, u.country, u.lastlogin, u.picture, u.lang, u.timezone,
		                              u.lastaccess, m.classid
		                            FROM {$CFG->prefix}user u
		                       LEFT JOIN {$CFG->prefix}monit_school_pupil_card m ON m.userid = u.id
		                       WHERE classid = $gid AND u.deleted = 0 AND u.confirmed = 1
		                       ORDER BY u.lastname";

		 	 // print_r($studentsql);
		        $students = get_records_sql($studentsql);



		        if(!empty($students)) {
                     $k = 1;
		             foreach ($students as $student) {
				    	$pupil = get_record('monit_school_pupil_card', 'userid', $student->id);

				    	$list_disc = '';
					    if (!empty($pupil->listegeids))	{
					    	$pli = explode(',', $pupil->listegeids);
					    	foreach ($pli as $pli1)	{
					    		$list_disc .= $listegeids[$pli1] . ', ';
					    	}
					    	if ($list_disc != '')  {
					    		$list_disc = substr($list_disc, 0, strlen($list_disc)- 4);
					    	}

					    }
					    if ($list_disc == '')  $list_disc = '-';


					    $i++;
		    	       	$myxls->write_string($i,0, $k.'.',$formatp);
		    	       	$k++;

				   		$strwin1251 =  $txtl->convert(fullname($student), 'utf-8', 'windows-1251');
		        	    $myxls->write_string($i, 1, $strwin1251,$formatp);

				   		$strwin1251 =  $txtl->convert($list_disc, 'utf-8', 'windows-1251');
		    	       	$myxls->write_string($i, 2, $strwin1251, $formatp);

				   		$strwin1251 =  $txtl->convert(' ', 'utf-8', 'windows-1251');
		           	    $myxls->write_string($i, 3, $strwin1251, $formatp);
			 		 }
                     $rowend[] = $i+1;
			  	     $i++;
				}
			}

   		     $strwin1251 =  $txtl->convert(get_string('vsego','block_mou_ege'), 'utf-8', 'windows-1251');
 	   		 $myxls->write_string($i, 2, $strwin1251, $formath1);


             $strformula = "=COUNTA(";
             foreach ($rowstart as $key => $value) {
             	  $strformula .= "D$value:D" .  $rowend[$key] . ',';
             }
             $strformula = substr ($strformula, 0, strlen($strformula)-1) . ')';
      		 $myxls->write_formula($i, 3, $strformula, $formath1);

 	   		 //$myxls->write_string($i, 3, $strformula, $formath1);


		}

       $workbook->close();
       exit;
}

?>


