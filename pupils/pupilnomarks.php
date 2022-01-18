<?PHP // $Id: pupilnomarks.php,v 1.5 2013/02/25 06:16:59 shtifanov Exp $


/*
update mdl_monit_school_pupil_card set yearid=6
where yearid<>6 and schoolid in (select id from mdl_monit_school where yearid=6)
*/

    require_once('../../../config.php');
    require_once('../../monitoring/lib.php');
    require_once('../lib_ege.php');
    define('ID_SCHOOL_FOR_DELETED', 3990);

    $yid = optional_param('yid', 0, PARAM_INT);       // Year id
    $did = optional_param('did', 0, PARAM_INT);       // Discipline id
	$rid = 0;
	$sid = 0;
	$gid = 0;

    if ($yid == 0)	{
    	$yid = get_current_edu_year_id();
    }


	$action   = optional_param('action', '');
    if ($action == 'excel') {
		$table = table_pupilnomarks($yid, $did);
    	// print_r($table);
        print_table_to_excel($table, 1);
        exit();
	}

	require_login();

	$admin_is = isadmin();
	$region_operator_is = ismonitoperator('region');
	if (!$admin_is && !$region_operator_is) {
        error(get_string('adminaccess', 'block_monitoring'), "$CFG->wwwroot/login/index.php");
	}

	if (isregionviewoperator() || israyonviewoperator())  {
        error(get_string('adminaccess', 'block_monitoring'));
	}


	// $strclasses = get_string('classes','block_mou_ege');
	$strclasses = get_string('school','block_monitoring');
	$strmarks = get_string('pupilnomarks','block_mou_ege');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= " -> $strmarks";
    print_header_mou("$SITE->shortname: $strmarks", $SITE->fullname, $breadcrumbs);


	print_tabs_years($yid, "pupilnomarks.php?yid=");

    $currenttab = 'pupilnomarks';
    include('tabsmark.php');

	echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
	listbox_discipline_ege("pupilnomarks.php?yid=$yid&amp;did=", $rid, $sid, $yid, $did);
	echo '</table>';

    ignore_user_abort(false); // see bug report 5352. This should kill this thread as soon as user aborts.
    @set_time_limit(0);
    @ob_implicit_flush(true);
    @ob_end_flush();
	@raise_memory_limit("256M");
 	if (function_exists('apache_child_terminate')) {
	    @apache_child_terminate();
	}  
      
    if ($action == 'sync') {
	   $table = sync_pupil_card_gia_results($yid);
       print_color_table($table);
	   
	}

 	if ($did != 0)  {

	    if (!$discipline_ege = get_record('monit_school_discipline_ege', 'id', $did))	{
	    	error('Discipline not found!');
	    }


        $PUPILCOUNT = 0;
		$table = table_pupilnomarks ($yid, $did);

		print_color_table($table);

        print_heading(get_string('itogoregion', 'block_mou_ege') . ': ' . $PUPILCOUNT, 'center', 4);
   		$options = array('rid' => $rid, 'sid' => $sid, 'did' => $did, 'yid' => $yid, 'action' => 'excel');
		echo '<table align="center" border=0><tr><td>';
	    print_single_button("pupilnomarks.php", $options, get_string("downloadexcel"));        
		echo '</td></tr></table>';
    }
	echo '<table align="center" border=0><tr><td>';
    
    $options = array('rid' => $rid, 'sid' => $sid, 'did' => $did, 'yid' => $yid, 'action' => 'sync');
    print_single_button("pupilnomarks.php", $options,'Синхронизировать карточки учеников с результатами ГИА');
	echo '</td></tr></table>';


	print_footer();


function table_pupilnomarks ($yid, $did)
{
	global $CFG, $USER, $PUPILCOUNT;

    if (!$discipline_ege = get_record('monit_school_discipline_ege', 'id', $did))	{
    	error('Discipline not found!');
    }

    $strdisciplines = get_string('disciplines_ege', 'block_mou_ege');
    $straction = get_string('action', 'block_monitoring');
    $strschool = get_string('school', 'block_monitoring');

    $table->head  = array ($strschool, '', get_string('fullname'), get_string('username'),
   						   $strdisciplines,  $straction);
    $table->align = array ('left', 'center', 'left', 'center', 'center', 'center');
	$table->class = 'moutable';


	$table->columnwidth = array (36, 1, 32, 12, 25, 14);
    // $table->datatype = array ('char', 'char');
    $table->class = 'moutable';
   	$table->width = '90%';
    // $table->size = array ('10%', '10%');
    $table->titles = array();
    $table->titles[] = get_string('pupilnomarks', 'block_mou_ege');
	$table->titles[] = get_string('nameregion', 'block_mou_ege');
	$table->titles[] = $discipline_ege->code . ' - ' . $discipline_ege->name;
    $table->titlesrows = array(30, 30, 30, 30);
    $table->worksheetname = 'pupilnomarks';
	$table->downloadfilename = 'pupilnomarks';

	$disciplines =  get_records_sql ("SELECT id, yearid, name  FROM  {$CFG->prefix}monit_school_discipline_ege
									  WHERE yearid=$yid ORDER BY name");
	$listegeids = array();
	if ($disciplines)	{
		foreach ($disciplines as $discipline) 	{
			$listegeids [$discipline->id] = $discipline->name;
		}
	}

	// $strsqlschools = "SELECT id, code  FROM {$CFG->prefix}monit_school  WHERE isclosing=0 AND yearid=$yid ";
	/*
	$schoolsarray = array();
 	if ($schools = get_records_sql($strsqlschools))	{
	    foreach ($schools as $sa)  {
	        $schoolsarray[$sa->id] = $sa->code;
	    }
	}
    */
	$strsqlresults = "SELECT id, yearid, rayonid, schoolid, classid, userid, pp, codepredmet, ocenka
					  FROM {$CFG->prefix}monit_gia_results
					 WHERE yearid=$yid AND codepredmet={$discipline_ege->code}
				 	 ORDER BY rayonid, schoolid";
                     
    $grarray = array();
 	if ($gia_results = get_records_sql($strsqlresults))	  {
        foreach ($gia_results as $gia)	{
       	    $grarray[$gia->userid] = $gia->ocenka;
       	}
 	} else {
 	   notify ('Результаты по данному предмету не найдены.');
       return $table;  
 	}
    
    $classes = get_records_sql ("SELECT id, name FROM {$CFG->prefix}monit_school_class
		                         WHERE yearid=$yid AND name like '9%'");
    $strclasslist = '';                             
    foreach ($classes as $class)	{
        $strclasslist .= $class->id. ',';
	}    
    $strclasslist .= '0';

	// SELECT id, userid, concat('0,',listegeids) as egeids FROM mdl_monit_school_pupil_card
	$strsql = "SELECT id, rayonid, userid, schoolid, classid, deleted, concat('0,',listegeids) as egeids
			   FROM  {$CFG->prefix}monit_school_pupil_card
			   WHERE yearid=$yid AND classid in ($strclasslist)";
	$template = ',' . $discipline_ege->id . ',';
    $egeidsarray = array();
 	if ($egeids = get_records_sql($strsql))	{
	    foreach ($egeids as $egeid)   {
			$pos = strpos($egeid->egeids, $template, 1);
			if ($pos) {
			    // echo $egeid->egeids . '<br>';
		        if (!isset($grarray[$egeid->userid]))	{
		         	// find !!!
		         	$PUPILCOUNT++;
		           $studentsql = "SELECT id, username, firstname, lastname, picture, city
	 	                          FROM {$CFG->prefix}user
	                              WHERE (id = $egeid->userid) AND (deleted = 0) AND (confirmed = 1)";
	         		if ($student = get_record_sql($studentsql))	{

		                $rid 	= $egeid->rayonid;
		                $sid 	= $egeid->schoolid;
		                $gid	= $egeid->classid;
		                $mesto	= $student->city;

						if ($school = get_record_sql("SELECT id, name FROM {$CFG->prefix}monit_school WHERE id=$sid")) 	{
		                    $mesto = "<a href=\"{$CFG->wwwroot}/blocks/mou_ege/class/classlist.php?rid=$rid&amp;yid=$yid&amp;sid=$sid\">". $school->name . '(' . $mesto . ')</a>';
						}

						$list_disc = get_list_discipline($listegeids, $egeid->egeids);

						$title = get_string('editprofilepupil','block_mou_ege');
						$strlinkupdate = "<a href=\"{$CFG->wwwroot}/blocks/mou_ege/pupils/pupilcard.php?mode=4&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid&amp;uid={$student->id}\">";
						$strlinkupdate .= "<img src=\"{$CFG->pixpath}/i/edit.gif\" alt=\"$title\" /></a>&nbsp;";

						$title = get_string('deleteprofilepupil','block_mou_ege');
					    $strlinkupdate .= "<a title=\"$title\" href=\"{$CFG->wwwroot}/blocks/mou_ege/pupils/delpupil.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid&amp;uid={$student->id}&amp;sesskey=$USER->sesskey\">";
						$strlinkupdate .= "<img src=\"{$CFG->pixpath}/i/cross_red_big.gif\" alt=\"$title\" /></a>&nbsp;";

		                $table->data[] = array ($mesto, print_user_picture($student->id, 1, $student->picture, false, true),
										    "<div align=left><strong><a href=\"pupil.php?mode=4&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid&amp;uid={$student->id}\">".fullname($student)."</a></strong></div>",
		                                    "<strong>$student->username</strong>",
		                                    $list_disc,
											$strlinkupdate);
					}
		        }
		    }
	    }
    }  else 	{
    	$table->data[] = array ();
    }


    // print_r($schoolsarray); echo '<hr>';

    // echo $strsqlresults;

   // print_r($gia_results);

    return $table;
}


function sync_pupil_card_gia_results($yid)
{
	global $CFG, $db;

    $strdisciplines = get_string('disciplines_ege', 'block_mou_ege');
    $straction = get_string('action', 'block_monitoring');
    $strschool = get_string('school', 'block_monitoring');

    $table->head  = array ('№', get_string('fullname'), 'В результатах ЕГЭ', 'В карточке',  'Действие');
    $table->align = array ('left', 'left', 'left', 'left', 'left');
	$table->class = 'moutable';
   	$table->width = '90%';
   
    
	$sql = "SELECT userid, CONCAT(rayonid, '_', schoolid, '_', classid) as ids
    		  FROM {$CFG->prefix}monit_gia_results
    		  WHERE yearid=$yid 
    	 	  ORDER BY rayonid, schoolid, classid";
    $egeidsarray = get_records_sql_menu($sql);                 

    $classes = get_records_sql_menu ("SELECT id as id1, id as id2 FROM {$CFG->prefix}monit_school_class
		                              WHERE yearid=$yid AND name like '9%'");
    $strclasslist = implode(',', $classes);

	// SELECT id, userid, concat('0,',listegeids) as egeids FROM mdl_monit_school_pupil_card
	$strsql = "SELECT userid, CONCAT(rayonid, '_', schoolid, '_', classid) as ids
			   FROM  {$CFG->prefix}monit_school_pupil_card
			   WHERE yearid=$yid AND classid in ($strclasslist)
               ORDER BY rayonid, schoolid, classid";
	
    $pupilcards = get_records_sql_menu($strsql);
    
    //$diffids = array_diff_assoc($egeidsarray, $pupilcards);
    // $diffids = array_udiff_assoc($egeidsarray, $pupilcards,  "comp_func_userid");   
    // print_object($diffids);
    
    $diffids = array();
    $diffids3 = array();
    foreach ($egeidsarray as $uid => $ids)    {
        if (!isset($pupilcards[$uid])) $diffids3[$uid] = $ids;
        else if ($ids != $pupilcards[$uid]) $diffids[$uid] = $ids;
        
    }
    
    if (empty($diffids) && empty($diffids3)) return $table;
    // print_object($diffids);
    // print_object($diffids3);
     
    $userids = array();
    $rayonids = array();
    $schoolids = array();
    $classids = array();
    foreach ($diffids as $userid => $diffid) {
        $userids[] = $userid;
        list ($r, $s, $c) = explode('_', $egeidsarray[$userid]);        
        $rayonids[] = $r;
        $schoolids[] = $s;
        $classids[] = $c; 
        list ($r, $s, $c) = explode('_', $pupilcards[$userid]);        
        $rayonids[] = $r;
        $schoolids[] = $s;
        $classids[] = $c;          
    } 
    
    foreach ($diffids3 as $userid => $diffid) {
        $userids[] = $userid;
        list ($r, $s, $c) = explode('_', $egeidsarray[$userid]);        
        $rayonids[] = $r;
        $schoolids[] = $s;
        $classids[] = $c; 
    } 
    
    $fullnames = get_records_select_menu('user', 'id in (' . implode(',', $userids) . ')', '', "id, concat(lastname, ' ', firstname) as fio");
    $rayons = get_records_select_menu('monit_rayon', 'id in (' . implode(',', $rayonids) . ')', '', "id,name");
    $schools = get_records_select_menu('monit_school', 'id in (' . implode(',', $schoolids) . ')', '', "id,name");
    $classes = get_records_select_menu('monit_school_class', 'id in (' . implode(',', $classids) . ')', '', "id,name");
    
    $i = 0;
    foreach ($diffids as $userid => $diffid) {
        $tabledata = array(++$i);
        $tabledata[] = $fullnames[$userid];
        list ($r, $s, $c) = explode('_', $egeidsarray[$userid]);        
        $tabledata[] = $rayons[$r] . ',<br> ' . $schools[$s] . ',<br> '. $classes[$c]; 
        list ($rp, $sp, $cp) = explode('_', $pupilcards[$userid]);
        $tabledata[] = $rayons[$rp] . ',<br> ' . $schools[$sp] . ',<br> '. $classes[$cp];
        if ($s == ID_SCHOOL_FOR_DELETED)    {
            $tabledata[] = 'не выполнено';
        } else {
            $strsql = "UPDATE mdl_monit_gia_results set rayonid=$rp, schoolid=$sp, classid=$cp
                       where yearid=6 and userid=$userid";
            if (execute_sql($strsql))  {
               $tabledata[] = '<strong>выполнено</strong>';
            }  else {
               $tabledata[] = 'не выполнено';
            }         
        }
        $table->data[] = $tabledata;
    }     
    
    foreach ($diffids3 as $userid => $diffid) {
        $tabledata = array(++$i);
        $tabledata[] = $fullnames[$userid];
        list ($r, $s, $c) = explode('_', $egeidsarray[$userid]);        
        $tabledata[] = $rayons[$r] . ',<br> ' . $schools[$s] . ',<br> '. $classes[$c]; 
        $tabledata[] = 'в 9-х классах карточка не найдена';
        $table->data[] = $tabledata;
    }     
    return $table;
}

function comp_func_userid($a, $b)   
{
    print_object($a);
    
    print_object($b);
    echo '<hr>';
    
    return 0;
}

?>
