<?PHP // $Id: stats_ege.php,v 1.16 2010/03/11 11:49:58 Shtifanov Exp $

/*

SELECT id, listmiids, listmidatesids, listegeids, listdatesids FROM `mou`.`mdl_monit_school_pupil_card`
where rayonid=19 and deleted=0

UPDATE `mou`.`mdl_monit_school_pupil_card` SET listdatesids = '0'
where listegeids='0'

UPDATE `mou`.`mdl_monit_school_pupil_card` SET listmidatesids = '0'
where listmiids='0'


*/

    require_once('../../../config.php');
    require_once('../../monitoring/lib.php');
    require_once('../lib_ege.php');

    $rid = optional_param('rid', '0', PARAM_INT);       // Rayon id
    $yid = optional_param('yid', '0', PARAM_INT);       // Year id
    $numday = optional_param('nd', '0', PARAM_INT);       // numday

    if ($yid == 0)	{
    	$yid = get_current_edu_year_id();
    }

	if ($rid == 0)  {
	   $rayon = get_record('monit_rayon', 'id', 1);
	}
	else if (!$rayon = get_record('monit_rayon', 'id', $rid)) {
        error(get_string('errorrayon', 'block_monitoring'), '..\rayon\rayons.php');
    }

	$action   = optional_param('action', '');
    if ($action == 'excel') {
    	$table = table_stats_ege ($rid, $yid, $numday);
    	// print_r($table);
        // stats_ege_download($rid, $yid, $table);
        print_table_to_excel($table);
        exit();
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

    $strdisciplines = get_string('stats_ege_school', 'block_mou_ege');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= "-> $strdisciplines";
	print_header_mou("$site->shortname: $strdisciplines", $site->fullname, $breadcrumbs);

	print_tabs_years_link("disciplines_ege.php?", $rid, 0, $yid);

    $currenttab = 'stats_ege';
    include('tabsege.php');

    if ($admin_is  || $region_operator_is) {  // || $rayon_operator_is)  {
		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
		listbox_rayons("stats_ege.php?yid=$yid&amp;rid=", $rid);
		echo '</table>';
	}

	if ($rid == 0) {
	    print_footer();
	 	exit();
	}

    $strsql = "SELECT COUNT(discegeid) as cnt FROM {$CFG->prefix}monit_school_gia_dates 
			   where yearid=$yid and discmiid=0
			   GROUP BY discegeid 
			   HAVING COUNT(discegeid)>1";

	$maxcnt = 0;			   
    if ($cntdates = get_records_sql($strsql))	{
    	foreach ($cntdates as $cntdate)	 {
    		if ($cntdate->cnt > $maxcnt)	$maxcnt = $cntdate->cnt;
    	}
    }

    $toprow = array();
    $toprow[] = new tabobject('0', $CFG->wwwroot."/blocks/mou_ege/ege/stats_ege.php?rid=$rid&amp;yid=$yid&amp;nd=0",
    	            get_string('numday_0', 'block_mou_ege'));
    for ($i=1; $i<=$maxcnt; $i++)	{
	    $toprow[] = new tabobject($i, $CFG->wwwroot."/blocks/mou_ege/ege/stats_ege.php?rid=$rid&amp;yid=$yid&amp;nd=$i",
    	            get_string('numday_i', 'block_mou_ege', $i));
    }

    $tabs = array($toprow);
    print_tabs($tabs, $numday, NULL, NULL);


	$table = table_stats_ege ($rid, $yid, $numday);

	print_color_table($table);

?>
<table align="center">
	<tr>
	<td>
	<form name="download" method="post" action="<?php echo "stats_ege.php?action=excel&amp;rid=$rid&amp;yid=$yid&amp;nd=$numday" ?>">
	    <div align="center">
		<input type="submit" name="downloadexcel" value="<?php print_string("downloadexcel")?>">
	    </div>
  </form>
	</td>
	</tr>
  </table>
<?php

    print_footer();



function table_stats_ege ($rid, $yid, $numday = 0)
{
	global $CFG, $rayon;

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

    // print_r($matrix); echo '<hr>';

    $table->head  = array ();
    $table->head[] = get_string('number','block_monitoring');
    $table->head[] = get_string('rayon', 'block_monitoring');
	$table->align = array ("left", "left");
    $table->datatype = array ('char', 'char');
   	$table->columnwidth = array (5, 20);
	if ($disciplines)	{
		foreach ($disciplines as $discipline) 	{
			$table->head[] = $discipline->name;
			$table->align[] = "center";
			$table->datatype[] = 'int';
			$table->columnwidth[] = 10;
		}
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
	$table->downloadfilename = 'stats_rayon_'.$rayon->id.'_'.$numday;

	if (!$disciplines)	{
		return $table;
	}
		 
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


    $strsql = "SELECT id, userid, classid, schoolid, listegeids, listdatesids
    		   FROM {$CFG->prefix}monit_school_pupil_card
			   WHERE yearid=$yid AND classid in ($classlist) AND deleted=0";
			   // where rayonid=19 and deleted=0 and listmiids = '0' and listegeids <> '0' and yearid = 3
	// echo $strsql;

	$pupils = get_records_sql ($strsql);
    if ($pupils)	{
            $arr_count = get_stats_with_gia_dates($numday, $disciplines, $matrix, $pupils);
			$tabledata = array ('<b>'.$rayon->number.'.</b>', '<b>'.$rayon->name. ' (' . count($pupils) . ') </b>');
			foreach ($disciplines as $discipline) 	{
                $tabledata[] = $arr_count[$discipline->id];
			}
			$table->data[] = $tabledata;
			// print_r($arr_count); exit(0);

            $k = 1;
			foreach ($schools as $sa)  {
			 
                $strsql = "SELECT id, name FROM {$CFG->prefix}monit_school_class
            	          WHERE yearid=$yid AND schoolid = {$sa->id} AND name like '9%'";
            	$classlist = '';
                if ($classes = get_records_sql ($strsql))	{
                    $schoolsarray = array();
            	    foreach ($classes as $cla)  {
            	        $schoolsarray[] = $cla->id;
            	    }
            	    $classlist = implode(',', $schoolsarray);
                }
            
                if ($classlist == '') {
					$tabledata = array ($sa->number.'.', '<i><u>'.$sa->name.'</i></u>');
					foreach ($disciplines as $discipline) 	{
		                $tabledata[] = '-';
					}
					$table->data[] = $tabledata;
                    continue;
                }    
                
                $strsql = "SELECT id, userid, classid, schoolid, listegeids, listdatesids
                		   FROM {$CFG->prefix}monit_school_pupil_card
            			   WHERE yearid=$yid AND classid in ($classlist) AND deleted=0";
             
/*
			    $strsql = "SELECT id, userid, classid, schoolid, listegeids, listdatesids
			    		   FROM {$CFG->prefix}monit_school_pupil_card
						   WHERE schoolid = {$sa->id} AND deleted=0 and listegeids <> '0'";
*/                           
				// echo $strsql;
				$pupils = get_records_sql ($strsql);
			    if ($pupils)	{
	                $ass_count = get_stats_with_gia_dates($numday, $disciplines, $matrix, $pupils);
					$tabledata = array ($sa->number.'.', '<i>'.$sa->name. ' (' . count($pupils) . ')</i>');
					foreach ($disciplines as $discipline) 	{
		                $tabledata[] = '<i>'.$ass_count[$discipline->id].'</i>';
					}
					$table->data[] = $tabledata;


					$classes = get_records_sql ("SELECT * FROM {$CFG->prefix}monit_school_class
													  WHERE schoolid={$sa->id} AND yearid=$yid  AND name like '9%'
													  ORDER BY name");
					if ($classes)	{
						foreach ($classes as $class)  {
							$pupils = get_records_sql ("SELECT id, userid, classid, schoolid, listegeids, listdatesids
													  FROM {$CFG->prefix}monit_school_pupil_card
													  WHERE classid={$class->id} AND schoolid={$sa->id} AND deleted=0");
						    if ($pupils)	{
								$ass_count = get_stats_with_gia_dates($numday, $disciplines, $matrix, $pupils);
								$tabledata = array ('&raquo;', $class->name . ' <b>(' . count($pupils) . ') </b>');
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
                $k++;
		    }

	} else {
		   // notify (get_string('dataisabsent','block_mou_ege'));
			$tabledata = array ($rayon->number.'.', '<b><u>'.$rayon->name.'</u></b>');
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
            	// echo $pupil->listegeids. ' === ' . $pupil->listdatesids . '<br>';
            	$allistegeids  .= $pupil->listegeids. ',';
            	$allistdatesids .= $pupil->listdatesids. ',';
            }
            
            // echo $allistegeids . '!<br>';
            // echo $allistdatesids . '!<br>';
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
        		/*
				  if ($disc_id == 17)	{        		
        		  		echo "{$arr_dates_id[$key]} == {$matrix[$numday][$disc_id]}<br>";
        		  		echo "{$arr_disc_id[$key]} == {$arr_dates_id[$key]}<br>";
				  }
				*/  	
                  if ($arr_dates_id[$key] == $matrix[$numday][$disc_id]) 	{
		        	  $arr_count[$disc_id]++;
		          }
	        }
        }
        // print_r($arr_disc_id); echo '<hr>';
        // print_r($arr_dates_id); echo '<hr>';
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


