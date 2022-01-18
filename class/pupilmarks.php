<?PHP // $Id: pupilmarks.php,v 1.5 2011/06/15 06:54:01 shtifanov Exp $

    require_once('../../../config.php');
    require_once('../../monitoring/lib.php');
    require_once('../lib_ege.php');

    $rid = required_param('rid', PARAM_INT);          // Rayon id
    $sid = required_param('sid', PARAM_INT);       // School id
    $gid = required_param('gid', PARAM_INT);          // Class id
    $uid = optional_param('uid', 0, PARAM_INT);       // User id    
    $yid = optional_param('yid', 0, PARAM_INT);       // Year id
    $did = optional_param('did', 0, PARAM_INT);       // Discipline id

    if ($yid == 0)	{
    	$yid = get_current_edu_year_id();
    }


	$action   = optional_param('action', '');
    if ($action == 'excel') {
		$table = table_markspupil ($yid, $rid, $sid, $gid, $uid);
    	// print_r($table);
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
	      if ($school = get_record_sql ("SELECT id, uniqueconstcode, name FROM {$CFG->prefix}monit_school
	                                     WHERE rayonid=$rid AND uniqueconstcode=$sid AND yearid=$yid"))	{
	     		$sid = $school->id;
	      } else {
				$school = get_record('monit_school', 'id', $sid);
	      }
	}

	$strclasses = get_string('classes','block_mou_ege');
	$strclass = get_string('class','block_mou_ege');
	$strpupils = get_string('pupils', 'block_mou_ege');
	$strtitle = get_string('markspupil','block_mou_ege');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= "-> <a href=\"{$CFG->wwwroot}/blocks/mou_ege/class/classlist.php?rid=$rid&amp;sid=$sid&amp;yid=$yid\">$strclasses</a>";
	$breadcrumbs .= "-> <a href=\"{$CFG->wwwroot}/blocks/mou_ege/class/classpupils.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid\">$strpupils</a>";
	$breadcrumbs .= "-> $strtitle";
    print_header("$SITE->shortname: $strtitle", $SITE->fullname, $breadcrumbs);


   if ($admin_is  || $region_operator_is) {  // || $rayon_operator_is)  {

		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
		listbox_rayons("pupilmarks.php?sid=0&amp;yid=$yid&amp;gid=0&amp;rid=", $rid);
		listbox_schools("pupilmarks.php?rid=$rid&amp;yid=$yid&amp;gid=0&amp;sid=", $rid, $sid, $yid);
	    listbox_class("pupilmarks.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=", $rid, $sid, $yid, $gid);
	    listbox_pupils("pupilmarks.php?mode=4&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid&amp;uid=", $rid, $sid, $yid, $gid, $uid);	    
		echo '</table>';

	} else  if ($rayon_operator_is)  {

		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
		listbox_schools("pupilmarks.php?rid=$rid&amp;yid=$yid&amp;gid=0&amp;sid=", $rid, $sid, $yid);
	    listbox_class("pupilmarks.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=", $rid, $sid, $yid, $gid);
	    listbox_pupils("pupilmarks.php?mode=4&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid&amp;uid=", $rid, $sid, $yid, $gid, $uid);	    
		echo '</table>';

	} else  if ($school_operator_is)  {
		echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
	    listbox_class("pupilmarks.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=", $rid, $sid, $yid, $gid);
	    listbox_pupils("pupilmarks.php?mode=4&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid&amp;uid=", $rid, $sid, $yid, $gid, $uid);	    
		echo '</table>';
	}


 	if ($rid != 0 && $sid != 0 && $gid != 0 && $uid != 0)  {
		$table = table_markspupil ($yid, $rid, $sid, $gid, $uid);
		print_color_table($table);
   		$options = array('rid' => $rid, 'sid' => $sid, 'uid' => $uid, 'yid' => $yid, 'gid' => $gid, 'level' => 'class', 'action' => 'excel');
		echo '<table align="center" border=0><tr><td>';
	    print_single_button("pupilmarks.php", $options, get_string("downloadexcel"));
		echo '</td></tr></table>';
    }

	print_footer();


function table_markspupil ($yid, $rid, $sid, $gid, $uid)
{
	global $CFG, $admin_is, $region_operator_is;

    $table->head  = array (get_string('codepredmet','block_mou_ege'), get_string('discipline', 'block_mou_ege'), 
    					   get_string('code_pp', 'block_mou_ege'), get_string('auditoria', 'block_mou_ege'),
    					   get_string('numvariant', 'block_mou_ege'),
    					   get_string('sidea', 'block_mou_ege'), get_string('sideb', 'block_mou_ege'), get_string('sidec', 'block_mou_ege'),
    					   get_string('ball', 'block_mou_ege'), get_string('ocenka', 'block_mou_ege'));

	$table->align = array ('center', 'center', 'center', 'center', 'center', 'left', 'left', 'center', 'left', 'left', 'left', 'center', 'center');
	$table->columnwidth = array (7, 20, 7, 8, 9, 27, 27, 27, 8, 7);
    // $table->datatype = array ('char', 'char');
    $table->class = 'moutable';
   	$table->width = '90%';
    // $table->size = array ('10%', '10%');
    $table->titles = array();
    $table->titles[] = get_string('protokolproverki', 'block_mou_ege');
    $table->worksheetname = 'markspupil';

	$strsqlresults = "SELECT * FROM {$CFG->prefix}monit_gia_results
 					 WHERE userid=$uid AND yearid=$yid
				 	 ORDER BY codepredmet";

    $rayon = get_record('monit_rayon', 'id', $rid);
    $school = get_record('monit_school', 'id', $sid);
    $class = get_record('monit_school_class', 'id', $gid);
	$table->titles[] = $school->name . " ({$rayon->name})";
	$table->titles[] = get_string('class', 'block_mou_ege') . ': '. $class->name;
	$table->downloadfilename = 'results_pupil_'.$uid;

	$user = get_record_sql("SELECT id, lastname, firstname FROM  {$CFG->prefix}user WHERE id = $uid");
            
	$table->titles[] = fullname($user);
    $table->titlesrows = array(30, 30, 30, 30);

    // echo $strsqlresults;

	$nowtime = time();
	
 	if ($gia_results = get_records_sql($strsqlresults))	{
 		$i = 1;
        foreach ($gia_results as $gia)	{
            $fieldsname = array ('pp', 'audit', 'variant', 'sidea', 'sideb', 'sidec', 'sided', 'ball', 'ocenka');
            $fieldsvalue = array ('-', '-', '-', '-', '-', '-', '-', '-');
            foreach ($fieldsname as $fldindex => $fldname)	{
	            if (!empty($gia->{$fldname}))	{
	                $fieldsvalue[$fldindex] = $gia->{$fldname};
	            }
	        }

			$discipline_ege = get_record('monit_school_discipline_ege', 'code', $gia->codepredmet, 'yearid', $yid);
			
		    if ($giadate = get_record_select('monit_school_gia_dates', "yearid=$yid AND discegeid={$discipline_ege->id} AND timeload = {$gia->timeload}"))  {
			    if ($nowtime > $giadate->timepublish || $admin_is || $region_operator_is)	{
		            $table->data[] = array ($i++ . '.', $discipline_ege->name, 
		            					$fieldsvalue[0], $fieldsvalue[1], $fieldsvalue[2],
		            					$fieldsvalue[3], $fieldsvalue[4], $fieldsvalue[5], $fieldsvalue[6], $fieldsvalue[7]);
		        } else {
   		            $t = date ("d.m.Y H:i", $giadate->timepublish);
			    	$title = get_string('timewillbepublish','block_mou_ege', $t);
					$strlinkupdate = "<img src=\"{$CFG->wwwroot}/blocks/mou_ege/i/mark.png\" alt=\"$title\" />";
		            $table->data[] = array ($i++ . '.', $discipline_ege->name, 
		            					$fieldsvalue[0], $fieldsvalue[1], $fieldsvalue[2],
		            					'-', '-', '-', '-', $strlinkupdate);
					
		        	$table->data[] = array ();
		        }
			}	    					
        }
    }  else 	{
    	$table->data[] = array ();
    }
   // print_r($gia_results);

    return $table;
}

?>
