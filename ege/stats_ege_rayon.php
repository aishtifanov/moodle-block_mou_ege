<?PHP // $Id: stats_ege_rayon.php,v 1.5 2009/06/11 09:40:36 Shtifanov Exp $

    require_once('../../../config.php');
    require_once('../../monitoring/lib.php');

    $rid = optional_param('rid', '0', PARAM_INT);       // Rayon id
    $yid = optional_param('yid', '0', PARAM_INT);       // Year id
    // $level = optional_param('level', 'region');       // Level monitoring

    if ($yid == 0)	{
    	$yid = get_current_edu_year_id();
    }

	$action   = optional_param('action', '');
    if ($action == 'excel') {
        disciplines_ege_download($rid, $yid);
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

    $strdisciplines = get_string('stats_ege_rayons', 'block_mou_ege');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= "-> $strdisciplines";
	print_header_mou("$site->shortname: $strdisciplines", $site->fullname, $breadcrumbs);

	print_tabs_years($yid, "stats_ege_region.php?yid=");

    $currenttab = 'stats_ege_rayon';
    include('tabsege.php');

    $rayons = get_records('monit_rayon');

	$disciplines =  get_records_sql ("SELECT id, yearid, name  FROM  {$CFG->prefix}monit_school_discipline_ege
									  WHERE yearid=$yid ORDER BY name");


    $all_count = array();
    $all_count[0] = 0;
	if ($disciplines)	{
		foreach ($disciplines as $discipline) 	{
        	  $all_count[$discipline->id] = 0;
		}
	}

    $table->head  = array ();
    $table->head[] = get_string('number','block_monitoring');
    $table->head[] = get_string('rayon', 'block_monitoring');
	$table->align = array ("left", "left");
	foreach ($disciplines as $discipline) 	{
			$table->head[] = $discipline->name;
			$table->align[] = "center";
	}

    $table->class = 'moutable';
   	$table->width = '90%';
    // $table->size = array ('10%', '10%', '10%');

    $g = 1;
    foreach ($rayons as $rayon)		{

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

        $arr_count = array();
        $arr_count[0] = 0;
		if ($disciplines)	{
			foreach ($disciplines as $discipline) 	{
	        	  $arr_count[$discipline->id] = 0;
			}
		}

	    $strsql = "SELECT id, userid, classid, schoolid, listegeids
	    		   FROM {$CFG->prefix}monit_school_pupil_card
				   WHERE yearid=$yid AND classid in ($classlist) AND deleted=0";
		// echo $strsql;

		$pupils = get_records_sql ($strsql);


        // echo count($pupils);  echo '<hr>';
	    if ($pupils)	{

		        // print_r($pupils);  echo '<hr>';
	            $allistegeids = '';

	            foreach ($pupils as $pupil)		{
	            	$allistegeids  .= $pupil->listegeids. ',';
	            }


	        // echo $allistegeids; echo '<hr>';

	        $arr_disc_id = explode(',', $allistegeids);

	        // print_r($arr_disc_id);

	        foreach ($arr_disc_id as $disc_id)	{
	        	if (!empty($disc_id))	{
		        	  $arr_count[$disc_id]++;
		        }
	        }

	        // print_r($arr_count);  echo '<hr>';


			$strlinkupdate = '-';
             
			$tabledata = array ($g.'.', $rayon->name . ' <b>(' . count($pupils) . ') <b>');
			foreach ($disciplines as $discipline) 	{
                $tabledata[] = $arr_count[$discipline->id];
			}
			$table->data[] = $tabledata;

		} else {
		   // notify (get_string('dataisabsent','block_mou_ege'));
			$tabledata = array ($g.'.', $rayon->name);
			foreach ($disciplines as $discipline) 	{
                $tabledata[] = '-';
			}
			$table->data[] = $tabledata;

	    }

	    $g++;

	    foreach ($disciplines as $discipline)	{
	    	 $all_count[$discipline->id] += $arr_count[$discipline->id];
	    }

        // print_r($arr_count); echo 'arr<hr>';    print_r($all_count); echo 'ALL<hr>';

        unset($arr_count);

    }

	$tabledata = array ($g.'.', get_string('vsego', 'block_mou_ege'));
	foreach ($disciplines as $discipline) 	{
         $tabledata[] = '<b>'.$all_count[$discipline->id].'</b>';
	}
   $table->data[] = $tabledata;


   print_color_table($table);

   echo '<hr>';

   // print_heading(get_string('stats_ege_region', 'block_mou_ege'), "center", 1);
 /*
   unset($table);
   $table->head  = array (get_string('disciplines_ege','block_mou_ege'), get_string("numofstudents","block_mou_ege"));
   $table->align = array ("left", "center");
   $table->class = 'moutable';
   $table->width = '40%';
   $table->size = array ('30%', '10%');

	foreach ($disciplines as $discipline) 	{
		$table->data[] = array ($discipline->name,  $all_count[$discipline->id]);
	}

	print_color_table($table);
*/

?>
<table align="center">
	<tr>
	<td>
	<form name="download" method="post" action="<?php echo "stats_ege_rayon.php?action=excel&amp;yid=$yid" ?>">
	    <div align="center">
		<input type="submit" name="downloadexcel" value="<?php print_string("downloadexcel")?>">
	    </div>
  </form>
	</td>
	</tr>
  </table>
<?php


    print_footer();


function disciplines_ege_download($rid, $yid)
{
    global $CFG;

    $txtl = new textlib();

        require_once("$CFG->libdir/excel/Worksheet.php");
        require_once("$CFG->libdir/excel/Workbook.php");


		// HTTP headers
        header("Content-type: application/vnd.ms-excel");
        $downloadfilename = "stats_giu_rayon";
        header("Content-Disposition: attachment; filename=\"$downloadfilename.xls\"");
        header("Expires: 0");
        header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
        header("Pragma: public");

/// Creating a workbook
        $workbook = new Workbook("-");
        $myxls =& $workbook->add_worksheet($downloadfilename);

/// Print names of all the fields
		$formath1 =& $workbook->add_format();
		$formath2 =& $workbook->add_format();
		$formatp =& $workbook->add_format();
		$formatpl =& $workbook->add_format();

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
		$formath2->set_border(1);
		$formath2->set_text_wrap();

		$formatp->set_size(11);
	    $formatp->set_align('center');
	    $formatp->set_align('vcenter');
		$formatp->set_color('black');
		$formatp->set_bold(0);
		$formatp->set_border(1);
		$formatp->set_text_wrap();

		$formatpl->set_size(11);
	    $formatpl->set_align('left');
	    $formatpl->set_align('vcenter');
		$formatpl->set_color('black');
		$formatpl->set_bold(1);
		$formatpl->set_border(1);
		$formatpl->set_text_wrap();


    $rayons = get_records('monit_rayon');

	$disciplines =  get_records_sql ("SELECT id, yearid, name  FROM  {$CFG->prefix}monit_school_discipline_ege
									  WHERE yearid=$yid ORDER BY name");
    $all_count = array();
    $all_count[0] = 0;
	if ($disciplines)	{
		foreach ($disciplines as $discipline) 	{
        	  $all_count[$discipline->id] = 0;
		}
	}

	$myxls->set_column(0, 0, 7);
	$myxls->set_column(1, 1, 30);
	$j = 1;
	foreach ($disciplines as $discipline) 	{
		$j++;
		$myxls->set_column($j, $j, 10);
	}

	$myxls->set_row(0, 30);
	$strtitle = get_string('title', 'block_mou_ege') . '. ' . get_string('stats_ege_region', 'block_mou_ege') . ' (' . get_rus_format_date(time()) . ')';
	$strwin1251 =  $txtl->convert($strtitle, 'utf-8', 'windows-1251');
    $myxls->write_string(0, 0, $strwin1251, $formath1);
	$myxls->merge_cells(0, 0, 0, $j);

	$strwin1251 =  $txtl->convert(get_string('number','block_monitoring'), 'utf-8', 'windows-1251');
    $myxls->write_string(1, 0, 'N', $formath2);

	$strwin1251 =  $txtl->convert(get_string('rayon','block_monitoring'), 'utf-8', 'windows-1251');
    $myxls->write_string(1, 1, $strwin1251, $formath2);

    $jj = 2;
	foreach ($disciplines as $discipline) 	{
		$strwin1251 =  $txtl->convert($discipline->name, 'utf-8', 'windows-1251');
 	    $myxls->write_string(1, $jj++, $strwin1251, $formath2);
 	}


    $g = 1;
    $ii = 1;
    foreach ($rayons as $rayon)		{
   		$ii++;
    	$rid = $rayon->id;

	    //print_heading($rayon->name, "center", 4);

		$strsql =  "SELECT id, rayonid, name, number  FROM {$CFG->prefix}monit_school
	   				WHERE rayonid = $rid AND isclosing=0 AND yearid=$yid
	   				ORDER BY number";

	    $schoolslist = '';
	 	if ($schools = get_records_sql($strsql))	{

	        $schoolsarray = array();
		    foreach ($schools as $sa)  {
		        $schoolsarray[] = $sa->id;
		    }
		    $schoolslist = implode(',', $schoolsarray);
	    }

        $arr_count = array();
        $arr_count[0] = 0;
		if ($disciplines)	{
			foreach ($disciplines as $discipline) 	{
	        	  $arr_count[$discipline->id] = 0;
			}
		}

	    $strsql = "SELECT id, userid, classid, schoolid, listegeids
	    		   FROM {$CFG->prefix}monit_school_pupil_card
				   WHERE schoolid in ($schoolslist) AND deleted=0";
		// echo $strsql;

		$pupils = get_records_sql ($strsql);

	    if ($pupils)	{

	            $allistegeids = '';

	            foreach ($pupils as $pupil)		{
	            	$allistegeids  .= $pupil->listegeids. ',';
	            }


	        $arr_disc_id = explode(',', $allistegeids);


	        foreach ($arr_disc_id as $disc_id)	{
	        	if (!empty($disc_id))	{
		        	  $arr_count[$disc_id]++;
		        }
	        }


	 	    $myxls->write_string($ii, 0, $g.'.', $formath2);
       		$strwin1251 =  $txtl->convert($rayon->name, 'utf-8', 'windows-1251');
   		    $myxls->write_string($ii, 1, $strwin1251, $formatpl);

            $jj = 2;
			foreach ($disciplines as $discipline) 	{
				 $myxls->write($ii, $jj++, $arr_count[$discipline->id], $formath2);
			}


		} else {
	 	    $myxls->write_string($ii, 0, $g.'.', $formath2);
      		$strwin1251 =  $txtl->convert($rayon->name, 'utf-8', 'windows-1251');
   		    $myxls->write_string($ii, 1, $strwin1251, $formatpl);

            $jj = 2;
			foreach ($disciplines as $discipline) 	{
				 $myxls->write($ii, $jj++, '-', $formath2);
			}

	    }
	    $g++;

	    foreach ($disciplines as $discipline)	{
	    	 $all_count[$discipline->id] += $arr_count[$discipline->id];
	    }

        // print_r($arr_count); echo 'arr<hr>';    print_r($all_count); echo 'ALL<hr>';

        unset($arr_count);

    }

    $ii++;
	 	    // $myxls->write_string($ii, 0, $g.'.', $format);
      		$strwin1251 =  $txtl->convert(get_string('vsego', 'block_mou_ege'), 'utf-8', 'windows-1251');
   		    $myxls->write_string($ii, 1, $strwin1251, $formath2);

            $jj = 2;
			foreach ($disciplines as $discipline) 	{
				 $myxls->write($ii, $jj++, $all_count[$discipline->id], $formath2);
			}

    $workbook->close();
    exit();


}

?>