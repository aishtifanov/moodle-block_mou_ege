<?php // $Id: classdisc.php,v 1.13 2011/03/10 08:40:45 shtifanov Exp $

    require_once("../../../config.php");
    require_once('../../monitoring/lib.php');
    require_once('../lib_ege.php');
    
    $rid = required_param('rid', PARAM_INT);          // Rayon id
    $sid = optional_param('sid', '0', PARAM_INT);       // School id
	// $cid = required_param('cid', PARAM_INT);		  // Curriculum id
    $yid = optional_param('yid', '0', PARAM_INT);       // Year id
    $numday = optional_param('nd', '0', PARAM_INT);       // numday

    $curryearid = get_current_edu_year_id();
    if ($yid == 0)	{
    	$yid = $curryearid;
    }

	$action   = optional_param('action', '');
    if ($action == 'excel') {
    	$table = table_stats_ege ($rid, $yid, $sid, $numday);
        print_table_to_excel($table);
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

	$strclasses = get_string('school','block_monitoring');

	$strdisciplines = get_string('disciplines_ege_full', 'block_mou_ege');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= "-> $strclasses";
    print_header("$SITE->shortname: $strclasses", $SITE->fullname, $breadcrumbs);

	if ($rid == 0)  {
	   $rayon = get_record('monit_rayon', 'id', 1);
	}
	else if (!$rayon = get_record('monit_rayon', 'id', $rid)) {
        error(get_string('errorrayon', 'block_monitoring'), '..\rayon\rayons.php');
    }

    if ($admin_is  || $region_operator_is) {  // || $rayon_operator_is)  {

		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
		listbox_rayons("classdisc.php?sid=0&amp;yid=$yid&amp;rid=", $rid);
		listbox_schools("classdisc.php?rid=$rid&amp;yid=$yid&amp;sid=", $rid, $sid, $yid);
		echo '</table>';
	} else  if ($rayon_operator_is)  {
		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
		listbox_schools("classdisc.php?rid=$rid&amp;yid=$yid&amp;sid=", $rid, $sid, $yid);
		echo '</table>';

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

	print_tabs_years($yid, "classdisc.php?rid=$rid&amp;sid=$sid&amp;yid=");

    $currenttab = 'gia';
    include('tabsclasses.php');

    $currenttab = 'disciplines_ege';
    include('tabsgia.php');

   	print_heading($strdisciplines, "center");

    // $strsql = "SELECT COUNT(discegeid) as cnt FROM {$CFG->prefix}monit_school_gia_dates GROUP BY discegeid  HAVING COUNT(discegeid)>1";

    $strsql = "SELECT COUNT(discegeid) as cnt FROM {$CFG->prefix}monit_school_gia_dates 
			   WHERE yearid=$yid and discmiid=0
			   GROUP BY discegeid 
			   HAVING COUNT(discegeid)>1";
				
    if ($cntdates = get_records_sql($strsql))	{
    	$maxcnt = 0;
    	foreach ($cntdates as $cntdate)	 {
    		if ($cntdate->cnt > $maxcnt)	$maxcnt = $cntdate->cnt;
    	}
    }

    $toprow = array();
    $toprow[] = new tabobject('0', $CFG->wwwroot."/blocks/mou_ege/class/classdisc.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;nd=0",
    	            get_string('numday_0', 'block_mou_ege'));
    for ($i=1; $i<=$maxcnt; $i++)	{
	    $toprow[] = new tabobject($i, $CFG->wwwroot."/blocks/mou_ege/class/classdisc.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;nd=$i",
    	            get_string('numday_i', 'block_mou_ege', $i));
    }

    $tabs = array($toprow);
    print_tabs($tabs, $numday, NULL, NULL);

	$table = table_stats_ege ($rid, $yid, $sid, $numday);
	print_color_table($table);

    echo  '<table align="center"><tr><td>';
	echo  '<form name="download" method="post" action="classdisc.php">';
	echo  '<input type="hidden" name="rid" value="' . $rid . '">';
	echo  '<input type="hidden" name="sid" value="' . $sid . '">';
	echo  '<input type="hidden" name="yid" value="' . $yid . '">';
	echo  '<input type="hidden" name="nd" value="' . $numday . '">';
	echo  '<input type="hidden" name="action" value="excel">';
	echo  '<div align="center">';
    echo  '<input type="submit" name="downloadexcel" value="'. get_string('downloadexcel') . '">';
    echo  '</div></form></td></tr> </table>';

    print_footer();


function table_stats_ege ($rid, $yid, $sid, $numday = 0)
{
	global $CFG;

	$sa = get_record('monit_school', 'id', $sid);

	$disciplines =  get_records_sql ("SELECT id, yearid, name  FROM  {$CFG->prefix}monit_school_discipline_ege
									  WHERE yearid=$yid ORDER BY name");
	$matrix = array();
	if ($disciplines)	{
		foreach ($disciplines as $discipline) 	{
        	  $dates_gia = get_records_sql ("SELECT id, yearid, discegeid
        	  								FROM  {$CFG->prefix}monit_school_gia_dates
									  		WHERE yearid=$yid AND discegeid = {$discipline->id}
									  		ORDER BY date_gia");
			  $matrix[$numday][$discipline->id] = 0;
			  if ($dates_gia)	{
			    $i= 0;
			  	foreach ($dates_gia as $d_gia)	{
			  		$i++;
			  		if ($i == $numday)	{
				  		$matrix[$numday][$discipline->id] = $d_gia->id;
			  		}
			  	}
			  }
		}
	}

       // print_r($matrix);

    $table->head  = array ();
    $table->head[] = get_string('number','block_monitoring');
    $table->head[] = get_string('school', 'block_monitoring') . ' / ' . get_string('class', 'block_mou_ege');
	$table->align = array ("left", "left");
    $table->datatype = array ('char', 'char');
   	$table->columnwidth = array (5, 20);
	foreach ($disciplines as $discipline) 	{
		$table->head[] = $discipline->name;
		$table->align[] = "center";
		$table->datatype[] = 'int';
		$table->columnwidth[] = 10;
	}

    $table->class = 'moutable';
   	$table->width = '90%';
    $table->size = array ('10%', '10%');

    $table->titles = array();
    if ($numday > 0)	{
	    $table->titles[] = get_string('stats_ege_school', 'block_mou_ege') . '. '. get_string('numday_i', 'block_mou_ege', $numday);
	} else {
		$table->titles[] = get_string('stats_ege_school', 'block_mou_ege') . '. '. get_string('numday_0', 'block_mou_ege');
	}
    $table->titlesrows = array(30);
    $table->worksheetname = $numday;
	$table->downloadfilename = 'stats_school_'.$sid.'_'.$numday;

    $strsql = "SELECT id, userid, classid, schoolid, listegeids, listdatesids
    		   FROM {$CFG->prefix}monit_school_pupil_card
			   WHERE schoolid = $sid AND deleted=0";
	// echo $strsql;
	$pupils = get_records_sql ($strsql);
    if ($pupils)	{
              $ass_count = get_stats_with_gia_dates($numday, $disciplines, $matrix, $pupils);
		// $tabledata = array ($sa->number.'.', '<b>'.$sa->name.'</b>');
		$tabledata = array ($sa->number.'.', '<b>'. get_string('school', 'block_monitoring').'</b>');
		foreach ($disciplines as $discipline) 	{
               $tabledata[] = '<b>'.$ass_count[$discipline->id].'</b>';
		}
		$table->data[] = $tabledata;


		$classes = get_records_sql ("SELECT * FROM {$CFG->prefix}monit_school_class
										  WHERE schoolid=$sid AND yearid=$yid AND name like '9%'
										  ORDER BY name");
		if ($classes)	{
			foreach ($classes as $class)  {
				$pupils = get_records_sql ("SELECT id, userid, classid, schoolid, listegeids, listdatesids
										  FROM {$CFG->prefix}monit_school_pupil_card
										  WHERE classid={$class->id} AND schoolid=$sid AND deleted=0");
			    if ($pupils)	{
					$ass_count = get_stats_with_gia_dates($numday, $disciplines, $matrix, $pupils);
					$tabledata = array ('&raquo;', $class->name);
					foreach ($disciplines as $discipline) 	{
		                $tabledata[] = $ass_count[$discipline->id];
					}
					$table->data[] = $tabledata;
			    }
			}
		}


    }   else {
		$tabledata = array ($sa->number.'.', '<i><u>'.$sa->name.'</i></u>');
		foreach ($disciplines as $discipline) 	{
               $tabledata[] = '-';
		}
		$table->data[] = $tabledata;

    }

	return $table;
}

function get_stats_with_gia_dates($numday, $disciplines, $matrix, $pupils)
{
    $arr_count = array();
    $arr_count[0] = 0;
	if ($disciplines)	{
		foreach ($disciplines as $discipline) 	{
        	  $arr_count[$discipline->id] = 0;
        }
    }

   if ($numday > 0)	 {
            $allistegeids =  $allistdatesids = '';
            foreach ($pupils as $pupil)		{
            	$allistegeids  .= $pupil->listegeids. ',';
            	$allistdatesids .= $pupil->listdatesids. ',';
            }
   } else {
            $allistegeids =  '';
            foreach ($pupils as $pupil)		{
            	$allistegeids  .= $pupil->listegeids. ',';
            }
   }

   if ($numday > 0)	 {
        $arr_disc_id = explode(',', $allistegeids);
        $arr_dates_id = explode(',', $allistdatesids);
        foreach ($arr_disc_id as $key => $disc_id)	{
        	if (!empty($disc_id))	{
                  if ($arr_dates_id[$key] == $matrix[$numday][$disc_id]) 	{
		        	  $arr_count[$disc_id]++;
		          }
	        }
        }
   } else {
        $arr_disc_id = explode(',', $allistegeids);
        foreach ($arr_disc_id as $disc_id)	{
        	if (!empty($disc_id))	{
	        	  $arr_count[$disc_id]++;
	        }
        }
   }

   return $arr_count;
}

?>


