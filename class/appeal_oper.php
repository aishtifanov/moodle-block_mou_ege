<?php // $Id: appeal_oper.php,v 1.15 2010/05/26 11:15:00 Shtifanov Exp $

    require_once('../../../config.php');
    require_once('../../monitoring/lib.php');
    require_once('../lib_ege.php');

    $rid = required_param('rid', PARAM_INT);          // Rayon id
    $sid = required_param('sid', PARAM_INT);       // School id
    $yid = required_param('yid', PARAM_INT);       // Year id
    $gid = required_param('gid', PARAM_INT);          // Class id
    $uid = required_param('uid', PARAM_INT);       // User id
	$did = optional_param('did', 0, PARAM_INT);       // Code predmet id
  	$action = optional_param('action', '');       // action

    if ($yid == 0)	{
	    $yid = get_current_edu_year_id();
    }

    // print_r ($action);
    if ($action == 'appellant') {
		form_download($rid, $sid, $yid, $uid, $did, $action);
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

	$strclasses = get_string('classes','block_mou_ege');
	$strclass = get_string('class','block_mou_ege');
	$strpupils = get_string('pupils', 'block_mou_ege');
	$strappeal = get_string('appeal','block_mou_ege');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= "-> <a href=\"{$CFG->wwwroot}/blocks/mou_ege/class/classlist.php?rid=$rid&amp;sid=$sid&amp;yid=$yid\">$strclasses</a>";
	$breadcrumbs .= "-> <a href=\"{$CFG->wwwroot}/blocks/mou_ege/class/classpupils.php?rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid\">$strpupils</a>";
	$breadcrumbs .= "-> $strappeal";
    print_header("$SITE->shortname: $strappeal", $SITE->fullname, $breadcrumbs);

    $pupil = get_record('monit_school_pupil_card', 'userid', $uid);
	$school = get_record('monit_school', 'id', $sid);
	$class = get_record('monit_school_class', 'id', $gid);
	$rayon = get_record('monit_rayon', 'id', $rid);

    if (!$user = get_record('user', 'id', $uid) ) {
        error('No such pupil in this class!', '..\index.php');
	}

   	$fullname = fullname($user);

	echo '<table cellspacing="0" cellpadding="10" align="center" class="generaltable generalbox">';
	listbox_discipline_ege("appeal_oper.php?rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;gid=$gid&amp;uid=$uid&amp;did=", $rid, $sid, $yid, $did);
	echo '</table>';

    if ($did == 0)	{
	    print_footer();
	    exit();
    }


    if (!$discipline_ege = get_record('monit_school_discipline_ege', 'id', $did))	{
    	error('Discipline not found!');
    }

	$strsqlresults = "SELECT *  FROM {$CFG->prefix}monit_gia_results
					  WHERE  userid = $uid AND codepredmet = {$discipline_ege->code} AND yearid=$yid";

 	if (!$gia_result = get_record_sql($strsqlresults))	{
	   	print_heading($fullname, 'center', 2);
 		notice (get_string('thismerknotfound', 'block_mou_ege', $discipline_ege->name), "appeal_oper.php?rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;gid=$gid&amp;uid=$uid&amp;did=$did");
 	}


	if ($action == 'upload')	{

          $dir = "1/appeal/$sid/$uid/$did";

          require_once($CFG->dirroot.'/lib/uploadlib.php');

          $um = new upload_manager('newfile',true,false,1,false,2097152);

          if ($um->process_file_uploads($dir))  {
	          $newfile_name = addslashes($um->get_new_filepath());
	          // $newfile_name = $um->get_new_filename();
	          // echo $newfile_name . '<hr>';
	          // print_r($um);
	          // echo $um->files['name'] . '<hr>';
	          // print_r($um->files); //['name'] . '<hr>';
              // print_heading(get_string('uploadedfile'), 'center', 4);

              if ($appeal = get_record('monit_appeal', 'yearid', $yid, 'userid', $uid,  'codepredmet', $discipline_ege->code))	{
              	  set_field('monit_appeal', 'fullpath', $newfile_name, 'yearid', $yid, 'userid', $uid, 'codepredmet', $discipline_ege->code);
              	  set_field('monit_appeal', 'timemodified', $gia_result->timeload, 'yearid', $yid, 'userid', $uid, 'codepredmet', $discipline_ege->code);
              } else {
              		$rec->yearid = $yid;
              		$rec->rayonid = $rid;
              		$rec->schoolid = $sid;
              		$rec->userid = $uid;
              		$rec->codepredmet = $discipline_ege->code;
              		$rec->classid = $gid;
              		$rec->timemodified = $gia_result->timeload;
              		$rec->fullpath = $newfile_name;
					if (insert_record('monit_appeal', $rec))	{
						 // add_to_log(1, 'dean', 'one academygroup added', "blocks/dean/groups/addgroup.php?mode=new&amp;fid=$fid&amp;sid=$sid&amp;cid=$cid", $USER->lastname.' '.$USER->firstname);
					} else {
						error(get_string('errorsaveappeal','block_mou_ege'), "rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;did=$did");
					}
              }

              notice(get_string('uploadedfile'), "appeal_oper.php?rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;gid=$gid&amp;uid=$uid&amp;did=$did");
          } else {
	          error(get_string("uploaderror", "assignment"), "appeal_oper.php?rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;gid=$gid&amp;uid=$uid&amp;did=$did"); //submitting not allowed!
          }
	}

   	print_heading($fullname, 'center', 2);


	$strinterval = get_string('notdefined','block_mou_ege');
	
	if ($giadate = get_record_select('monit_school_gia_dates', "yearid=$yid AND discegeid={$discipline_ege->id} AND timeload = {$gia_result->timeload}"))  {
		
		$timefilingappealstart = $giadate->timefilingappealstart;
		$timefilingappealend = $giadate->timefilingappealend;
	
	    $startnow = $endnow = 0;
	    if ($giadate->timeload != 0)	{
	           $startnow = usergetdate($timefilingappealstart);
	           list($d, $m, $y) = array(intval($startnow['mday']), intval($startnow['mon']), intval($startnow['year']));
	  		   $monthstring = get_string('lm_'.$m,'block_monitoring');
	           $endnow = usergetdate($timefilingappealend);
	           list($d1, $m1, $y1) = array(intval($endnow['mday']), intval($endnow['mon']), intval($endnow['year']));
	   		   $monthstring1 = get_string('lm_'.$m1,'block_monitoring');
	    	   $strinterval = get_string('timeconstraints','block_mou_ege', "$d $monthstring - $d1 $monthstring1 $y1");
	    }
	}	  

    print_heading($strinterval, 'center', 3);

    $nownow = time ();
    if ($nownow > $timefilingappealstart && $nownow < $timefilingappealend )	{

        $temlatezajavlenie = get_string('temlatezajavlenie', 'block_mou_ege');
	    print_simple_box_start('center', '50%', 'white');
	    echo '<table border=0 align=center> <tr valign="top">';
		echo "<td align=center><form name='downloadinfcard' method='post' action='appeal_oper.php?rid=$rid&amp;yid=$yid&amp;sid=$sid&amp;gid=$gid&amp;uid=$uid&amp;did=$did&amp;action=appellant'>";
	 	echo "<input type='submit' name='infcard' value='".$temlatezajavlenie."'>";
		echo "</form></td></table>";
	    print_simple_box_end();

	    print_simple_box_start('center', '50%', 'white');
//		echo '<B><p align=center><a href="' . $CFG->wwwroot. '/mod/assignment/view.php?id=156">Загрузить заполненную информационную карту в систему ЭМОУ</a>';
		print_heading("Загрузка заполненного заявления на апелляцию в систему", "center", 4);
?>
<p>1. Загрузите шаблон заявления на апелляцию на свой компьютер, используя кнопку "Шаблон заявления на апелляцию".</p>
<p>2. Заполните заявление, подпишите его и заверьте его у директора школы. </p>
<p>3. Отсканируйте подписанное и заверенное заявление и сохраните скан-копию в одном из следующих графических форматов:
JPEG, GIF, PNG.
<p>4. <strong>Внимание! В имени файла разрешается использовать только буквы латинского алфавита и цифры.
 Например, Zajvlenie_Ivanova.png</strong></p>
<p>5. Полученный файл необходимо «загрузить» в систему ЭМОУ. Для этого необходимо выполнить следующие действия:
<br />- на данной странице нажмите на кнопку &quot;Обзор&quot; и в открывшемся диалоговом окне «Выбор файлов»
выберите файл, содержащий скан-копию заполненного заявления, и нажмите на кнопку &quot;Открыть&quot;;
<br />- убедитесь, что в строке для отправки файла правильно указан путь к файлу и нажмите на кнопку &quot;Отправить&quot;. </p>
<?php
	    print_simple_box_end();

	    print_simple_box_start('center', '50%', 'white');
        $CFG->maxbytes = 2097152;

        $struploadafile = "Загрузить файл, содержащий скан-копию заполненного заявления на апелляцию";// get_string("uploadafile");
        $strmaxsize = get_string("maxsize", "", display_size($CFG->maxbytes));

        echo '<p><div style="text-align:center">';
        echo '<form enctype="multipart/form-data" method="post" action="appeal_oper.php">';
        echo '<fieldset class="invisiblefieldset">';
        echo "<p>$struploadafile <br>($strmaxsize)</p>";
        echo '<input type="hidden" name="rid" value="'.$rid.'" />';
        echo '<input type="hidden" name="sid" value="'.$sid.'" />';
        echo '<input type="hidden" name="yid" value="'.$yid.'" />';
        echo '<input type="hidden" name="gid" value="'.$gid.'" />';
        echo '<input type="hidden" name="uid" value="'.$uid.'" />';
        echo '<input type="hidden" name="did" value="'.$did.'" />';
        echo '<input type="hidden" name="action" value="upload" />';
        require_once($CFG->libdir.'/uploadlib.php');
        upload_print_form_fragment(1, array('newfile'), false, null, 0, $CFG->maxbytes, false);
        echo '<input type="submit" name="save" value="'.get_string('uploadthisfile').'" />';
        echo '</fieldset>';
        echo '</form>';
        echo '</div>';
        print_simple_box_end();
    }
        print_simple_box_start('center', '50%', 'white');
		print_heading("Файл с заявлением по предмету '$discipline_ege->name':", "center", 4);
		// $filearea = '1/appeal/'.$uid;
		$filearea = "1/appeal/$sid/$uid/$did";
        if ($basedir = make_upload_directory($filearea))   {
            if ($files = get_directory_list($basedir)) {
                require_once($CFG->libdir.'/filelib.php');
                foreach ($files as $key => $file) {
                    $icon = mimeinfo('icon', $file);
                    if ($CFG->slasharguments) {
                        $ffurl = "$CFG->wwwroot/file.php/$filearea/$file";
                    } else {
                        $ffurl = "$CFG->wwwroot/file.php?file=/$filearea/$file";
                    }

                    $output = '<img align="middle" src="'.$CFG->pixpath.'/f/'.$icon.'" class="icon" alt="'.$icon.'" />'.
                            '<a href="'.$ffurl.'" target=_blank>'.$file.'</a><br />';
                }
            } else {
            	$output = get_string('no');
            }
        }

        echo '<div class="files">'.$output.'</div>';
	    print_simple_box_end();


	$strsqlresults = "SELECT *  FROM {$CFG->prefix}monit_appeal
					  WHERE yearid=$yid AND codepredmet={$discipline_ege->code} AND userid=$uid";
	$strappeal = get_string ('statusappeal', 'block_mou_ege');
 	if ($gia = get_record_sql($strsqlresults))	 {
        $recstatus = get_record('monit_status', 'id', $gia->status);
        // print_r($recstatus);
		$strformrkpu_status = $recstatus->name; // get_string('status'.$gia->status, "block_monitoring");
		$strcolor = $recstatus->color; // get_string('status'.$gia->status.'color',"block_monitoring");
        if ($gia->status == 6 && $gia->ballold != 0)	{
        	$strformrkpu_status .= '<br>';
        	$strformrkpu_status .= get_string('oldresults', 'block_mou_ege', "{$gia->ocenkaold}({$gia->ballold})");

			$newgia = get_record_sql("SELECT id, ocenka, ball FROM {$CFG->prefix}monit_gia_results
									 WHERE yearid=$yid AND codepredmet={$discipline_ege->code} AND userid=$uid");

        	$strformrkpu_status .= '<br>';
        	$strformrkpu_status .= get_string('newresults', 'block_mou_ege', "{$newgia->ocenka}({$newgia->ball})");

        }

 	} else {
		$strformrkpu_status = get_string('status1', 'block_monitoring');
		$strcolor = get_string('status1color', 'block_monitoring');
 	}

    print_simple_box_start('center', '50%', '#'.$strcolor);
   	print_heading($strappeal, 'center', 4);
   	echo $strformrkpu_status;
    print_simple_box_end();

    print_simple_box_start('center', '50%', 'lightgray');
   	print_heading( get_string ('datetimeappelant', 'block_mou_ege') . ':', 'center', 4);
    $timeappeal = '-';
    if ($gia->timeappeal > 0)	{
   		$timeappeal = userdate($gia->timeappeal, get_string('strftimedaydatetime1', 'block_mou_ege'));
   	}
   	echo $timeappeal;
    print_simple_box_end();

	print_footer();


function form_download($rid, $sid, $yid, $uid, $did, $action)
{
	global $CFG;

	$textlib = textlib_get_instance();

	$fp = fopen('../appeal/'.$action.'.doc', "r");
//	if (
	$fstat = fstat($fp);
	$buffer = fread($fp, $fstat['size']);

//    echo $buffer;
    switch ($action)	{
    	case 'appellant':
	    	    if (!$discipline_ege = get_record('monit_school_discipline_ege', 'id', $did))	{
 				   	error('Discipline not found!');
			    }

				$rayon = get_record('monit_rayon', 'id', $rid);

				$school = get_record('monit_school', 'id', $sid);

			    $gia_rez = get_record('monit_gia_results', 'yearid', $yid, 'codepredmet', $discipline_ege->code, 'userid', $uid);

				$giadate = get_record_select('monit_school_gia_dates', "yearid=$yid AND discegeid={$discipline_ege->id} AND timeload = {$gia_rez->timeload}");
				
			    $user = get_record('user', 'id', $uid);

			    // print_r($user);

			 	$buffer = str_replace('_c', $discipline_ege->code, $buffer);
				$buffer = str_replace('_discipline', $textlib->convert($discipline_ege->name, 'utf-8', 'windows-1251'), $buffer);
			 	$buffer = str_replace('_variant', $gia_rez->variant, $buffer);

				$lastname = $textlib->convert($user->lastname, 'utf-8', 'windows-1251');
				$buffer = str_replace('_lastname', $lastname, $buffer);

				$firstname = $textlib->convert($user->firstname, 'utf-8', 'windows-1251');

				$f_s_name = explode (' ', $firstname);
				$buffer = str_replace('_firstname', $f_s_name[0], $buffer);
				if (isset($f_s_name[1]) && !empty($f_s_name[1]))	{
					$buffer = str_replace('_secondname', $f_s_name[1], $buffer);
				} else {
				 	$buffer = str_replace('_secondname', ' ', $buffer);
				}

				$buffer = str_replace('_rayon', $textlib->convert($rayon->name, 'utf-8', 'windows-1251'), $buffer);
			 	$buffer = str_replace('_scode', $school->code, $buffer);
				$buffer = str_replace('_school', $textlib->convert($school->name, 'utf-8', 'windows-1251'), $buffer);

				$gia_rezpp = $gia_rez->pp;
                if ($gia_rez->pp < 10)	{
					$gia_rezpp = '00'.$gia_rez->pp;
				}  else if ($gia_rez->pp < 100)	{
					$gia_rezpp = '0'.$gia_rez->pp;
				}

			 	$buffer = str_replace('_pp', $gia_rezpp, $buffer);
				if ($ppe_school = get_record('monit_school', 'yearid', $yid, 'codeppe', $gia_rezpp, 'isclosing', 0))	{
				 	$buffer = str_replace('ppe__', $textlib->convert($ppe_school->name, 'utf-8', 'windows-1251'), $buffer);
				} else {
				 	$buffer = str_replace('ppe__', '_________________________________________', $buffer);
				}
			 	$buffer = str_replace('_audit', $gia_rez->audit, $buffer);

			 	$buffer = str_replace('__d', date('d', time()), $buffer);
			 	$buffer = str_replace('__m', date('m', time()), $buffer);
			 	$buffer = str_replace('__y', date('y', time()), $buffer);

			 	$buffer = str_replace('d__', date('d', $giadate->timepublish), $buffer);
			 	$buffer = str_replace('m__', date('m', $giadate->timepublish), $buffer);
			 	$buffer = str_replace('y__', date('y', $giadate->timepublish), $buffer);

			 	$fio = $lastname . ' ' . substr($firstname, 0, 1) . '.';
				if (isset($f_s_name[1]) && !empty($f_s_name[1]))	{
					$fio .= ' ' . substr($f_s_name[1], 0, 1) . '.';
				}
				$buffer = str_replace('_fio', $fio, $buffer);


    	break;

    	case 'expertzakl':
    	break;
    }

    $fn = $action.'_'.$uid.'.doc';

	header("Content-type: application/vnd.ms-word");
	header("Content-Disposition: attachment; filename=\"{$fn}\"");
	header("Expires: 0");
	header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
	header("Pragma: public");

	print $buffer;
}


?>


