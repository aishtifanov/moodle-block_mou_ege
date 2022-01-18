<?php // $Id: lib_ege.php,v 1.24 2013/06/08 05:42:34 shtifanov Exp $

require_once("$CFG->libdir/excel/Worksheet.php");
require_once("$CFG->libdir/excel/Workbook.php");

define ('EXCEPTION_LIST_DISCIPLINE_CODE', '1, 2, 98, 99');

// Display list group as popup_form
function listbox_class($scriptname, $rid, $sid, $yid, $gid)
{
  global $CFG;

  $strtitle = get_string('selectaclass', 'block_mou_ege') . ' ...';
  $groupmenu = array();

  $classmenu[0] = $strtitle;

  if ($sid != 0 && $yid != 0)   {
		$arr_group = get_records_sql ("SELECT id, name  FROM {$CFG->prefix}monit_school_class
	 								  WHERE yearid=$yid AND schoolid=$sid
									  ORDER BY parallelnum, name");
		  if ($arr_group) 	{
				foreach ($arr_group as $gr) {
					$classmenu[$gr->id] =$gr->name;
				}
		  }
  }

  echo '<tr><td>'.get_string('class','block_mou_school').':</td><td>';
  popup_form($scriptname, $classmenu, 'switchgroup', $gid, '', '', '', false);
  echo '</td></tr>';
  return 1;
}
////// Display list Disciplinegroup as popup_form
function listbox_disiplinegroup($scriptname, $rid, $sid, $yid, $gid, $dgid)
{
  global $CFG;

  $strtitle = get_string('selectdisiplinegroup', 'block_mou_school') . ' ...';
  $discipline_group_menu = array();

  $discipline_menu[0] = $strtitle;

  if ($rid!=0 && $sid != 0 && $yid != 0 && $gid!=0)   {
  		$domain = get_records_select('monit_school_discipline_domain', "schoolid = $sid", '', 'id');
  		foreach($domain as $dom) {
    		  $arr_group_disipline = get_records_sql("SELECT * FROM {$CFG->prefix}monit_school_discipline_group
    	 								  WHERE schoolid=$sid AND disciplinedomainid={$dom->id}");
    		  if ($arr_group_disipline) 	{
    				foreach ($arr_group_disipline as $arr_gr_disip) {
    					$discipline_menu[$arr_gr_disip->id] =$arr_gr_disip->name;
    				}
    		  }  			
  		}

  }

  echo '<tr><td>'.get_string('disciplinegroup','block_mou_school').':</td><td>';
  popup_form($scriptname, $discipline_menu, 'switchgroup', $dgid, '', '', '', false);
  echo '</td></tr>';
  return 1;
}
// Display list student of group
function listbox_pupils($scriptname, $rid, $sid, $yid, $gid, $uid)
{
  global $CFG;

  $strtitle = get_string('selectapupil', 'block_mou_ege') . '...';
  $pupilmenu = array();

  $pupilmenu[0] = $strtitle;

  if ($rid != 0 && $sid != 0 && $yid != 0 && $gid != 0)  {
		$pupilsql = "SELECT u.id, u.username, u.firstname, u.lastname, u.email, u.maildisplay,
							  u.city, u.country, u.lastlogin, u.picture, u.lang, u.timezone,
                              u.lastaccess, m.classid
                            FROM {$CFG->prefix}user u
                       LEFT JOIN {$CFG->prefix}monit_school_pupil_card m ON m.userid = u.id ";

	    $pupilsql .= 'WHERE classid = '.$gid.' AND u.deleted = 0 AND u.confirmed = 1 ';
	    $pupilsql .= 'ORDER BY u.lastname';
        $pupils = get_records_sql($pupilsql);

		if(!empty($pupils)) {
            foreach ($pupils as $pupil) 	{
				$pupilmenu[$pupil->id] = fullname($pupil);
			}
			// natsort($groupmenu);
        }
  }

  echo '<tr><td>'.get_string('pupil','block_mou_ege').':</td><td>';
  popup_form($scriptname, $pupilmenu, "switchpupil", $uid, "", "", "", false);
  echo '</td></tr>';
  return 1;
}


// Display list student of group
function listbox_teachers($scriptname, $rid, $sid, $yid, $uid)
{
  global $CFG;

  $strtitle = get_string('selectateacher', 'block_mou_ege') . '...';
  $teachermenu = array();

  $teachermenu[0] = $strtitle;

  if ($rid != 0 && $sid != 0 && $yid != 0)  {

        $teachersql = "SELECT u.id, u.firstname, u.lastname
                      FROM {$CFG->prefix}user u
    	              LEFT JOIN {$CFG->prefix}monit_att_staff t ON t.userid = u.id
     	              WHERE t.schoolid=$sid AND u.deleted = 0 AND u.confirmed = 1";
		$teachersql .= ' ORDER BY u.lastname';

		$teachers = get_records_sql($teachersql);

		if(!empty($teachers)) {
            foreach ($teachers as $teacher) 	{
				$teachermenu[$teacher->id] = fullname($teacher);
			}
			// natsort($groupmenu);
        }
  }

  echo '<tr><td>'.get_string('teacher','block_mou_ege').':</td><td>';
  popup_form($scriptname, $teachermenu, "switchteacher", $uid, "", "", "", false);
  echo '</td></tr>';
  return 1;
}

function listbox_discip_class_teachers($scriptname, $rid, $sid, $yid, $did, $gid, $uid)
{
  global $CFG;

  $strtitle = get_string('selectateacher', 'block_mou_ege') . '...';
  $teachermenu = array();

  $teachermenu[0] = $strtitle;

  if ($rid != 0 && $sid != 0 && $yid != 0)  {

  	 	$teachers = get_records_sql("SELECT id, teacherid FROM {$CFG->prefix}monit_school_teacher
   	 								 WHERE schoolid=$sid AND disciplineid=$did");
        if ($teachers)  {
              foreach ($teachers as $teach)  {
                $user=get_record_sql("SELECT id, lastname, firstname FROM {$CFG->prefix}user
              						  WHERE id={$teach->teacherid}");
	           	$teachermenu[$teach->teacherid] = fullname($user);
	           	//$uid = $teach->id;
              }
              
        } 
  }

  echo '<tr><td>'.get_string('teacher','block_mou_ege').':</td><td>';
  popup_form($scriptname, $teachermenu, "switchteacher", $uid, "", "", "", false);
  echo '</td></tr>';
  return 1;
}
// Display list group as popup_form
function listbox_discipline_ege($scriptname, $rid, $sid, $yid, $did, $except = '0')
{
  global $CFG;

  $strtitle = get_string('selectdiscipline_ege', 'block_mou_ege') . '...';
  $disciplinemenu = array();

  $disciplinemenu[0] = $strtitle;

  if ($yid != 0)  {

		$disciplines =  get_records_sql ("SELECT id, yearid, name, code  FROM  {$CFG->prefix}monit_school_discipline_ege
										  WHERE yearid=$yid AND code NOT IN ($except)
										  ORDER BY name");
		if ($disciplines)	{
			foreach ($disciplines as $discipline) 	{
				$disciplinemenu[$discipline->id] = $discipline->name;
			}
		}
  }

  echo '<tr><td>'.get_string('discipline_ege','block_mou_ege').':</td><td>';
  popup_form($scriptname, $disciplinemenu, "switchdiscege", $did, "", "", "", false);
  echo '</td></tr>';
  return 1;
}


// Display list category textbook
function listbox_textbook($scriptname, $yid, $catid)
{
  global $CFG;

  $strtitle = get_string('selectacattextbook', 'block_mou_ege') . '...';
  $textbookmenu = array();

  $textbookmenu[0] = $strtitle;

  if ($yid != 0)  {
		$strsql = "SELECT m.id, m.number, m.name FROM {$CFG->prefix}monit_textbook_cat m ORDER by number";

		if($cattb = get_records_sql($strsql)) {
            foreach ($cattb as $ctb) 	{
				$textbookmenu[$ctb->id] = $ctb->name;
			}
			// natsort($groupmenu);
        }
  }

  echo '<tr><td>'.get_string('cattextbook','block_mou_ege').':</td><td>';
  popup_form($scriptname, $textbookmenu, "switchctb", $catid, "", "", "", false);
  echo '</td></tr>';
  return 1;
}


function listbox_courses($scriptname, $courseid, $category = 'all')
{
  global $CFG;

	$courses = get_courses ($category, 'fullname'); // 54

    $courseids = array();

    foreach ($courses as $ct)	{
        if ($ct->id != 1)	{
	    	$courseids[] = 	$ct->id;
	    }
    }

  	$coursemenu = array();
  	$coursemenu[0] = get_string('selectadiscipline', 'block_mou_ege') . ' ...';

	foreach ($courseids as $crsid) 	{
		if($course = get_record_sql("SELECT id, fullname FROM {$CFG->prefix}course WHERE id = $crsid"))   {
			$coursemenu[$crsid] = $course->fullname;
		}
	}

	echo '<tr><td>'.get_string('course').':</td><td>';
	popup_form($scriptname, $coursemenu, 'switchcoursestoz', $courseid, '', '', '', false);
	echo '</td></tr>';
	return 1;
}


function get_pupil_username($rid, $sid, $class)
{
    global $CFG;

    if ($rid < 10)	{
    	$code = '0'.$rid;
    }	else {
    	$code = $rid;
    }

    if (strlen($sid) < 4)	{
		$code .= '0'.$sid;
    }	else 	{
		$code .= $sid;
    }

    if ($startyear=get_start_edu_year_class($class->name))	{
    	$start_edu_year = substr($startyear, 2, 2);
    }  else {
    	$start_edu_year .= '00';
    }
	$code .= $start_edu_year;

	$shablon = $code.'%';

 	// echo $shablon . '<hr>';
	$strsql = "SELECT max(u.username) as maxu  FROM {$CFG->prefix}user u
               WHERE u.username LIKE '$shablon' AND u.deleted = 0 AND u.confirmed = 1";

	if ($maxusername = get_record_sql($strsql))  {
		// print_r($maxusername); echo '<hr>';
	    if (empty($maxusername->maxu))	{
    	   	$code .= '01';
    	} else {
    		$len = strlen($maxusername->maxu); 
    		$_99 = substr($maxusername->maxu, $len-2, 2);
    		if ($_99 == '99') {
    			/*
				$maxid = get_record_sql("SELECT max(u.id) as maxid  FROM {$CFG->prefix}user u");
	    		$len = strlen($maxid->maxid); 
    			$last2 = substr($maxid->maxid, $len-2, 2);
    			$code .= '99' . $last2;
				*/
				$code .= '9901'; 
    		} else {
    			$code = $maxusername->maxu + 1;
			    if ($rid < 10)	{
			    	$code = '0'.$code;
			    }
    		}
		    // echo $code . '<hr>';
		}
	} else {
    	$code .= '01';
	}
	
	// echo $code . '<hr>';

	return $code;
}


function get_start_edu_year_class($classname)
{
    $year = date("Y");
    $m = date("n");

    if ($numclass = get_numclass($classname))	{
	    if(($m >= 1) && ($m <= 8 )) {
			$y = $year - $numclass - 1;
	    } else {
			$y = $year - $numclass;
	    }
	} else {
		return false;
	}

	return $y;
}

function get_numclass($classname)
{
    $firstsym = substr($classname, 0, 1);
    if (is_numeric($firstsym))	{
    	if ($firstsym == 1)	 {
		    $secondsym = substr($classname, 1, 1);
		    if (is_numeric($secondsym))		{
		    	$numclass = $firstsym.$secondsym;
		    }  else {
		    	$numclass = $firstsym;
		    }
		} else {
			$numclass = $firstsym;
		}
	}	else {
		return false;
	}
	return 	$numclass;
}


function is9class($classname)
{
	$ret = false;

    $firstsym = substr($classname, 0, 1);
    if (is_numeric($firstsym))	{
    	if ($firstsym == 9)	$ret = true;
	}
	return 	$ret;
}


/**
 * Print a nicely formatted table to EXCEL.
 *
 * @param array $table is an object with several properties.
 *     <ul<li>$table->head - An array of heading names.
 *     <li>$table->align - An array of column alignments
 *     <li>$table->size  - An array of column sizes
 *     <li>$table->wrap - An array of "nowrap"s or nothing
 *     <li>$table->data[] - An array of arrays containing the data.
 *     <li>$table->width  - A percentage of the page
 *     <li>$table->tablealign  - Align the whole table
 *     <li>$table->cellpadding  - Padding on each cell
 *     <li>$table->cellspacing  - Spacing between cells
 *****************  NEW (added by shtifanov) **********************
 *     <li>$table->downloadfilename - .XLS file name (new)
 *     <li>$table->worksheetname - Name of sheet in work book  (new)
 *     <li>$table->titles  - An array of titles names in firsts rows. (new)
 *     <li>$table->titlesrows  - Height of titles rows (new)
 *     <li>$table->columnwidth  - An array of columns width in Excel table (new)
 * </ul>
 * @param bool $return whether to return an output string or echo now
 * @return boolean or $string
 * @todo Finish documenting this function
 */

function print_table_to_excel($table, $lastcols = 0, $table2 = null)
{
    global $CFG;

    $order   = array("\r\n", "\n", "\r");
    $downloadfilename = $table->downloadfilename;

    header("Content-type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"{$downloadfilename}.xls\"");
    header("Expires: 0");
    header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
    header("Pragma: public");

    $workbook = new Workbook("-");
    $txtl = new textlib();

	$strwin1251 =  $txtl->convert($table->worksheetname, 'utf-8', 'windows-1251');
    $myxls =&$workbook->add_worksheet($strwin1251);

	$numcolumn = count ($table->columnwidth) - $lastcols;
    $i=0;
    foreach ($table->columnwidth as $width)	{
		$myxls->set_column($i, $i, $width);
		$i++;
	}

	$formath1 =& $workbook->add_format();
	$formath1->set_size(12);
    $formath1->set_align('center');
    $formath1->set_align('vcenter');
	$formath1->set_color('black');
	$formath1->set_bold(1);
	// $formath1->set_italic();
	$formath1->set_text_wrap();
	// $formath1->set_border(2);

    $i = $ii = 0;
   
    foreach ($table->titles as $key => $title)	{
		$myxls->set_row($i, $table->titlesrows[$key]);
		$strwin1251 =  $txtl->convert($title, 'utf-8', 'windows-1251');
	    $myxls->write_string($i, 0, $strwin1251, $formath1);
		$myxls->merge_cells($i, 0, $i, $numcolumn-1);
		$i++;
    }

	$formath2 =& $workbook->add_format();
	$formath2->set_size(11);
    $formath2->set_align('center');
    $formath2->set_align('vcenter');
	$formath2->set_color('black');
	$formath2->set_bold(1);
	//$formath2->set_italic();
	$formath2->set_border(2);
	$formath2->set_text_wrap();

	$formath3 =& $workbook->add_format();
	$formath3->set_size(10);
    $formath3->set_align('center');
    $formath3->set_align('vcenter');
	$formath3->set_color('black');
	$formath3->set_bold(1);
	//$formath2->set_italic();
	$formath3->set_border(2);
	$formath3->set_text_wrap();

    if (!empty($table->head)) {
    	$formatp = array();
    	$numcolumn = count ($table->head) - $lastcols;
        foreach ($table->head as $key => $heading) {
        	if ($key >= $numcolumn) continue;
	   		$strwin1251 =  $txtl->convert(strip_tags($heading), 'utf-8', 'windows-1251');
	        $myxls->write_string($i, $key,  $strwin1251, $formath2);

			$formatp[$key] =& $workbook->add_format();
			$formatp[$key]->set_size(10);
		    $formatp[$key]->set_align($table->align[$key]);
		    $formatp[$key]->set_align('vcenter');
			$formatp[$key]->set_color('black');
			$formatp[$key]->set_bold(0);
			$formatp[$key]->set_border(1);
			$formatp[$key]->set_text_wrap();
        }
        $i++;
        $ii = $i;
    }

    if (!empty($table->dblhead)) {
        
		$formatpc =& $workbook->add_format();
		$formatpc->set_size(10);
	    $formatpc->set_align('center');
	    $formatpc->set_align('vcenter');
		$formatpc->set_color('black');
		$formatpc->set_bold(0);
		$formatpc->set_border(1);
		$formatpc->set_text_wrap();

		$formatpl =& $workbook->add_format();
		$formatpl->set_size(10);
	    $formatpl->set_align('left');
	    $formatpl->set_align('vcenter');
		$formatpl->set_color('black');
		$formatpl->set_bold(0);
		$formatpl->set_border(1);
		$formatpl->set_text_wrap();

        $myxls->set_row($i, 33);        

        $countcols = count($table->dblhead->head1);
        $allcols = count($table->dblhead->head2) + $countcols;

        /*
        foreach ($table->dblhead->head2 as $key2 => $heading2) {
            $myxls->write_blank($i, $key2+1,  $formath2);
        } 
        */
        
        for ($jj = 0; $jj<$allcols-1; $jj++)  {
            $myxls->write_blank($i,   $jj,  $formath2);
            $myxls->write_blank($i+1, $jj,  $formath2);
        }   
        
        $j = 0;
        foreach ($table->dblhead->head1 as $key => $heading) {
            // $heading = str_replace('&nbsp;', ' ', $heading);
            
            if (isset($table->dblhead->span1[$key])) {
            	$span1 = $table->dblhead->span1[$key];
            } else 	{
            	$span1 = '';
            }
            
            $whatspan = substr($span1, 0, 7);
            
            if ($whatspan == 'rowspan') {
                $strwin1251 =  $txtl->convert(strip_tags($heading), 'utf-8', 'windows-1251');
	            $myxls->write_string($i, $key+$j,  $strwin1251, $formath2);
                $myxls->write_string($i+1, $key+$j,  '', $formath2);
                $myxls->merge_cells($i, $key+$j, $i+1, $key+$j);                
            } else if ($whatspan == 'colspan') {
                $adelta = explode('=', $span1);
                $delta = (integer)$adelta[1];
                $strwin1251 =  $txtl->convert(strip_tags($heading), 'utf-8', 'windows-1251');
	            $myxls->write_string($i, $key+$j,  $strwin1251, $formath2);
                $myxls->merge_cells($i, $key+$j, $i, $key+$j+$delta-1);                
                for ($ii=0; $ii<$delta; $ii++) {
                    $heading = $table->dblhead->head2[$j];
                    $strwin1251 =  $txtl->convert(strip_tags($heading), 'utf-8', 'windows-1251');
    	            $myxls->write_string($i+1, $key+$j,  $strwin1251, $formath3);
                    $j++;
                }   
                $j--;
            }    
        }   

        $i  += 2;
        $ii = $i;
    }



    if (isset($table->data)) foreach ($table->data as $keyrow => $row) {
      	$numcolumn = count ($row) - $lastcols;
        foreach ($row as $keycol => $item) 	{
           	if ($keycol >= $numcolumn) continue;
            $item = str_replace($order, '<br>', $item);
            $item = str_replace ('<br>', "\n", $item);
        	$clearitem = strip_tags($item);
        	switch ($clearitem)	{
        		case '&raquo;': $clearitem = '>>'; break;
        		case '&laquo;': $clearitem = '<<'; break;
        	}
 			$strwin1251 =  $txtl->convert($clearitem, 'utf-8', 'windows-1251');
            if (!empty($table->dblhead)) {
                if ($keycol == 0)   {
			        $myxls->write($i + $keyrow, $keycol,  $strwin1251, $formatpl);
                } else {
                    $myxls->write($i + $keyrow, $keycol,  $strwin1251, $formatpc);
                }
            } else {
                $myxls->write($i + $keyrow, $keycol,  $strwin1251, $formatp[$keycol]);
            }     
			$ii = $i + $keyrow;
		}
    }
    
    if (!empty($table2)) {
    	$i = $ii + 2;
    	
    	$formatp = array();
    	$numcolumn = count ($table2->head) - $lastcols;
        foreach ($table2->head as $key => $heading) {
        	if ($key >= $numcolumn) continue;
	   		$strwin1251 =  $txtl->convert(strip_tags($heading), 'utf-8', 'windows-1251');
	        $myxls->write_string($i, $key,  $strwin1251, $formath2);

			$formatp[$key] =& $workbook->add_format();
			$formatp[$key]->set_size(10);
		    $formatp[$key]->set_align($table2->align[$key]);
		    $formatp[$key]->set_align('vcenter');
			$formatp[$key]->set_color('black');
			$formatp[$key]->set_bold(0);
			$formatp[$key]->set_border(1);
			$formatp[$key]->set_text_wrap();
        }
        $i++;
    }

    if (isset($table2->data)) foreach ($table2->data as $keyrow => $row) {
      	$numcolumn = count ($row) - $lastcols;
        foreach ($row as $keycol => $item) 	{
           	if ($keycol >= $numcolumn) continue;
            $item = str_replace($order, '<br>', $item);
            $item = str_replace ('<br>', "\n", $item);
        	$clearitem = strip_tags($item);
        	switch ($clearitem)	{
        		case '&raquo;': $clearitem = '>>'; break;
        		case '&laquo;': $clearitem = '<<'; break;
        	}
 			$strwin1251 =  $txtl->convert($clearitem, 'utf-8', 'windows-1251');
			$myxls->write($i + $keyrow, $keycol,  $strwin1251, $formatp[$keycol]);
			$ii = $i + $keyrow;
		}
    }
      

    $workbook->close();
}



function get_list_discipline($arrayegeids, $stregeids, $strdatesid = '')
{
	$list_disc = '';

	if ($strdatesid == '')	{
	    if (!empty($stregeids))	{
	    	$pli = explode(',', $stregeids);
	    	foreach ($pli as $pli1)	{
	    		if ($pli1 > 0)	{
		    		$list_disc .= $arrayegeids[$pli1] . ', ';
		    	}
	    	}
	    	if ($list_disc != '')  {
	    		$list_disc = substr($list_disc, 0, strlen($list_disc)- 2);
	    	}
	
	    }
	} else {
		
		    $arr_egeids = explode(',', $stregeids);
		    $arr_datesids = explode(',', $strdatesid);
			foreach ($arr_egeids as $index => $egeid) 	{
				if ($egeid > 0)	{
					$list_disc .= $arrayegeids[$egeid];
                    $aindex = $arr_datesids[$index];
					$giadate =  get_record_select ('monit_school_gia_dates', "id = $aindex", 'id, date_gia');
					$list_disc .= ': ' . convert_date($giadate->date_gia, 'en', 'ru') . '<br>';
				}	    
			}
			
	}    
    if ($list_disc == '')  $list_disc = '-';

	return $list_disc;
}

function generate_password2($number)
{
/*
    $arr = array('a','b','c','d','e','f', 'g','h','i','j','k','l', 'm','n','o','p','r','s', 't','u','v','x','y','z',
                 'A','B','C','D','E','F', 'G','H','I','J','K','L', 'M','N','O','P','R','S', 'T','U','V','X','Y','Z',
                 '1','2','3','4','5','6', '7','8','9','0','!', '&', '%','@','*','$','+','-');
*/
    $arr = array('1','2','3','4','5','6', '7','8','9','0', '1','2','3','4','5','6', '7','8','9','0',
    '1','2','3','4','5','6', '7','8','9','0', '1','2','3','4','5','6', '7','8','9','0', '1','2','3','4','5','6', '7','8','9','0',
    '1','2','3','4','5','6', '7','8','9','0', '1','2','3','4','5','6', '7','8','9','0');


	//  '(',')','[',']','!','?', '&','^','%','@','*','$', '<','>','/','|','+','-', '{','}','`','~');
    
    $pass = "";
    for($i = 0; $i < $number; $i++)  {
      $index = rand(0, count($arr) - 1);
      $pass .= $arr[$index];
    }
    return $pass;
}

function get_unique_classname ($class)
{
	$uname = $class->name . '_';

    $school = get_record('monit_school', 'id', $class->schoolid);

    if ($school->code == '999999')	{
    	$uname .= $school->code. '_' . $class->schoolid;
    } else {
		$uname .= $school->code;
    }

    /*
    if (strlen($class->schoolid) < 4)	{
		$uname .= '0'.$class->schoolid;
    }	else 	{
		$uname .= $class->schoolid;
    }
    */

	return $uname;
}


function truncate_school_name($schoolname)
{
    $name = $schoolname;
    for ($i=1; $i<=8; $i++)  {
        $istr = get_string('istr'.$i, 'block_mou_ege'); 
        $rstr = get_string('rstr'.$i, 'block_mou_ege');
    	$name = str_ireplace ($istr, $rstr, $name);
    }    
    /*
	$len = strlen ($name);
	if ($len > 255)  {
		// $school->name = substr($school->name, 0, 200) . ' ...';
		
	}
    */
    return $name;
}


function print_tabs_ppe($scriptname, $numday, $yid)
{
    global $CFG;

    $strsql = "SELECT COUNT(discegeid) as cnt FROM {$CFG->prefix}monit_school_gia_dates
               where yearid=$yid and  discmiid=0 
               GROUP BY discegeid 
               HAVING COUNT(discegeid)>1";
    if ($cntdates = get_records_sql($strsql))	{
    	$maxcnt = 0;
    	foreach ($cntdates as $cntdate)	 {
    		if ($cntdate->cnt > $maxcnt)	$maxcnt = $cntdate->cnt;
    	}
    }

    $toprow = array();
    $toprow[] = new tabobject('0', $scriptname. "&amp;nd=0",
    	            get_string('numday_0', 'block_mou_ege'));
    for ($i=1; $i<=$maxcnt; $i++)	{
	    $toprow[] = new tabobject($i, $scriptname. "&amp;nd=$i",
    	            get_string('numday_i', 'block_mou_ege', $i));
    }

    $tabs = array($toprow);
    print_tabs($tabs, $numday, NULL, NULL);
}





function print_table_to_word($table, $lastcols = 0, $ext = 'doc', $output = true)
{
    global $CFG;
   
    $buffer = ''; 
    if ($output)    {
        if ($ext == 'doc')  { 
        	header("Content-type: application/vnd.ms-word");
        	header("Content-Disposition: attachment; filename=\"{$table->downloadfilename}.doc\"");	
        	header("Expires: 0");
        	header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
        	header("Pragma: public");
            
           //$numcolumn = count ($table->columnwidth) - $lastcols;
            
            $buffer = '<html xmlns:v="urn:schemas-microsoft-com:vml"
        	xmlns:o="urn:schemas-microsoft-com:office:office"
        	xmlns:w="urn:schemas-microsoft-com:office:word"
        	xmlns="http://www.w3.org/TR/REC-html40">
        	<head>
        	<meta http-equiv=Content-Type content="text/html; charset=utf-8">
        	<meta name=ProgId content=Word.Document>
        	<meta name=Generator content="Microsoft Word 11">
        	<meta name=Originator content="Microsoft Word 11">
        	<title>';
        } else if ($ext == 'odt')   {
        	header("Content-type: application/vnd.oasis.opendocument.text");
        	header("Content-Disposition: attachment; filename=\"{$table->downloadfilename}.odt\"");	
        	header("Expires: 0");
        	header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
        	header("Pragma: public");
            
           //$numcolumn = count ($table->columnwidth) - $lastcols;
            
            $buffer = '<HTML><HEAD><META HTTP-EQUIV="CONTENT-TYPE" CONTENT="text/html; charset=utf-8"><title>';
        }
               
    	
       	$title = get_string('tableaccreditword', 'block_mou_ege');
    	$buffer .= $title;
    	
    	$buffer .= '</title></head><body lang=RU>';
    }

    if (isset($table->align)) {
        foreach ($table->align as $key => $aa) {
            if ($aa && $aa != 'left') {
                $align[$key] = ' align='. $aa;
            } else {
                $align[$key] = '';
            }
        }
    }
    if (isset($table->size)) {
        foreach ($table->size as $key => $ss) {
            if ($ss) {
                $size[$key] = ' width="'. $ss .'"';
            } else {
                $size[$key] = '';
            }
        }
    }
    if (isset($table->wrap)) {
        foreach ($table->wrap as $key => $ww) {
            if ($ww) {
                $wrap[$key] = ' nowrap="nowrap" ';
            } else {
                $wrap[$key] = '';
            }
        }
    }

    if (empty($table->width)) {
        $table->width = '80%';
    }

    if (empty($table->tablealign)) {
        $table->tablealign = 'center';
    }

    if (empty($table->cellpadding)) {
        $table->cellpadding = '5';
    }

    if (empty($table->cellspacing)) {
        $table->cellspacing = '1';
    }

    if (empty($table->class)) {
        $table->class = 'generaltable';
    }

    if (empty($table->headerstyle)) {
        $table->headerstyle = 'header';
    }

    if (empty($table->border)) {
        $table->border = '1';
    }

    $tableid = empty($table->id) ? '' : 'id="'.$table->id.'"';


    $i = 1;
    foreach ($table->titles as $title1)	{
    	$buffer .= print_heading($title1, 'center', $i++, '', true);
    	$buffer .= '<p></p>';
    }

	// echo '<table width="'.$table->width.' border='.$table->border;
    // echo " cellpadding=\"$table->cellpadding\" cellspacing=\"$table->cellspacing\" class=\"$table->class boxalign$table->tablealign\" $tableid>\n";
    $buffer .= '<table width="100%" border=1 align=center ';
    $buffer .= " cellpadding=\"$table->cellpadding\" cellspacing=\"$table->cellspacing\" $tableid class=\"$table->class\">\n"; //bordercolor=gray

    $countcols = 0;

    if (!empty($table->head)) {
        $countcols = count($table->head);
        $buffer .= '<tr>';
        $numcolumn = count ($table->head) - $lastcols;
        foreach ($table->head as $key => $heading) {
			if ($key >= $numcolumn) continue;
			
            if (!isset($size[$key])) {
                $size[$key] = '';
            }
            if (!isset($align[$key])) {
                $align[$key] = '';
            }
            if (isset($table->wraphead) && $table->wraphead == 'nowrap') {
            	$headwrap = ' nowrap="nowrap" ';
            } else 	{
            	$headwrap = '';
            }
            $buffer .= '<th '. $align[$key].$size[$key] . $headwrap . " class=\"$table->headerstyle\">". $heading .'</th>'; // class="header c'.$key.'
			// $output .= '<th style="vertical-align:top;'. $align[$key].$size[$key] .';white-space:nowrap;" class="header c'.$key.'" scope="col">'. $heading .'</th>';
        }
        $buffer .= '</tr>'."\n";
    }

    if (!empty($table->dblhead)) {
        $countcols = count($table->dblhead->head1);
        $buffer .= '<tr>';
        foreach ($table->dblhead->head1 as $key => $heading) {

            if (isset($table->dblhead->size[$key])) {
                $size[$key] = $table->dblhead->size[$key];
            } else {
                $size[$key] = '';
            }

            if (!isset($align[$key])) {
                $align[$key] = '';
            }
            if (isset($table->wraphead) && $table->wraphead == 'nowrap') {
            	$headwrap = ' nowrap="nowrap" ';
            } else 	{
            	$headwrap = '';
            }

            if (isset($table->dblhead->span1[$key])) {
            	$span1 = $table->dblhead->span1[$key];
            } else 	{
            	$span1 = '';
            }

            $buffer .= "<th $span1 ". $align[$key].$size[$key] . $headwrap . " class=\"$table->headerstyle\">". $heading .'</th>'; // class="header c'.$key.'
			// $output .= '<th style="vertical-align:top;'. $align[$key].$size[$key] .';white-space:nowrap;" class="header c'.$key.'" scope="col">'. $heading .'</th>';
        }
        $buffer .= '</tr>'."\n";

        $countcols = count($table->dblhead->head2);
        $buffer .= '<tr>';
        foreach ($table->dblhead->head2 as $key => $heading) {

            if (!isset($size[$key])) {
                $size[$key] = '';
            }
            if (!isset($align[$key])) {
                $align[$key] = '';
            }
            if (isset($table->wraphead) && $table->wraphead == 'nowrap') {
            	$headwrap = ' nowrap="nowrap" ';
            } else 	{
            	$headwrap = '';
            }

            $buffer .= '<th '. $align[$key].$size[$key] . $headwrap . " class=\"$table->headerstyle\">". $heading .'</th>'; // class="header c'.$key.'
			// $output .= '<th style="vertical-align:top;'. $align[$key].$size[$key] .';white-space:nowrap;" class="header c'.$key.'" scope="col">'. $heading .'</th>';
        }
        $buffer .= '</tr>'."\n";
    }

    if (!empty($table->data)) {
        $oddeven = 1;
        foreach ($table->data as $keyrow => $row) {
            $oddeven = $oddeven ? 0 : 1;
            //echo "<tr class=\"$table->class\">"."\n";
            $buffer .= "<tr>"."\n";
            
            if ($row == 'hr' and $countcols) {
                $buffer .= '<td colspan="'. $countcols .'"><div class="tabledivider"></div></td>';
            } else {  /// it's a normal row of data
          		$numcolumn = count ($row) - $lastcols;
                foreach ($row as $key => $item) {
                	if ($key >= $numcolumn) continue;
                    if (!isset($size[$key])) {
                        $size[$key] = '';
                    }
                    if (!isset($align[$key])) {
                        $align[$key] = '';
                    }
                    if (!isset($wrap[$key])) {
                        $wrap[$key] = '';
                    }
                    if (isset($table->bgcolor[$keyrow][$key])) {
                    	$tdbgcolor = ' bgcolor="#'.$table->bgcolor[$keyrow][$key].'"';
                    }
                    else {
                    	$tdbgcolor = '';
                    }
                    $buffer .= '<td '. $align[$key].$size[$key].$wrap[$key].$tdbgcolor. '>'. $item .'</td>'; //  class="'.$table->class.'"
                }
            }
            $buffer .= '</tr>'."\n";
        }
    }
    $buffer .= '</table>'."\n";
    
    if ($output)    {    
        $buffer .= '</body></html>';
        //$buffer = strip_tags($buffer,'<h1><html><head><link><STYLE><script><body><div><img><li><ul><h2><span><table><option><select><input><form><tr><td><h3>');
        $buffer = strip_tags($buffer,'<IMG>,<CAPTION>,<TABLE>,<TH>,<TD>,<TR>,<BASE>,<BODY>,<HEAD>,<HTML>,<META>,<TITLE>,<B>,<BIG>,<H1>,<H2>,<H3>,<H4>,<H5>,<H6	>, 
        <BLOCKQUOTE>,<BR>,<FONT>,<HR>,<I>,<MARQUEE>,<NOBR>,<P>,<PRE>,<S>,<SMALL>,<SUB>,<SUP>,<TT>,<U>,<ABBR>,
        <ACRONYM>,<CITE>,<CODE>,<DFN>,<EM>,<KBD>,<SAMP>,<STRONG>,<VAR>,<DD>,<DL>,<DT>,<LI>,<MENU>,
        <OL>,<UL>,<BUTTON>,<FIELDSET>,<INPUT>,<OPTGROUP>,<OPTION>,<SELECT>,<TEXTAREA>,<IFRAME>,<BGSOUND>,<DOCTYPE>,
        <ADDRESS>,<APPLET>,<AREA>,<CENTER>,<FORM>,<LEGEND>,<LINK>,<MAP>,<SCRIPT>,<SPAN>');	
       	print $buffer;
    } else {
        return $buffer;
    }     

}


// Display list variants in discipline GIA
function listbox_variant_gia($scriptname, $rid, $sid, $yid, $did, $vid)
{
  global $CFG;

  $codepredmet = 0;
  if ($discipline_ege = get_record('monit_school_discipline_ege', 'id', $did))	{
  	$codepredmet = $discipline_ege->code;  	
  }

  $strtitle = get_string('selectvariant_gia', 'block_mou_ege') . '...';
  $variantmenu = array();

  $variantmenu[0] = $strtitle;

  if ($yid != 0)  {

		$variants =  get_records_sql ("SELECT DISTINCT variant	FROM {$CFG->prefix}monit_gia_results
 										WHERE  yearid=$yid AND codepredmet=$codepredmet
										 ORDER by variant");
		if ($variants)	{
			foreach ($variants as $variant) 	{
				if ($variant->variant != 0)	{
					$variantmenu[$variant->variant] = $variant->variant;
				}	
			}
		}
  }

  echo '<tr><td>'.get_string('variant_gia','block_mou_ege').':</td><td>';
  popup_form($scriptname, $variantmenu, "switchvargia", $vid, "", "", "", false);
  echo '</td></tr>';
  return 1;
}


function listbox_mi_date($scriptname, $rid, $sid, $yid, $mi_date)
{
  global $CFG;

  $strtitle = get_string('midate', 'block_mou_ege') . '...';
  $variantmenu = array();

  $variantmenu['-'] = $strtitle;

  if ($yid != 0)  {

		$variants =  get_records_sql ("SELECT DISTINCT date_gia FROM mdl_monit_school_gia_dates
										where yearid=$yid and discmiid<>0");
		if ($variants)	{
			foreach ($variants as $variant) 	{
					$variantmenu[$variant->date_gia] = convert_date($variant->date_gia, 'en', 'ru');
			}	
		}
  }

  echo '<tr><td>'.get_string('midates','block_mou_ege').':</td><td>';
  popup_form($scriptname, $variantmenu, "switchgiadate", $mi_date, "", "", "", false);
  echo '</td></tr>';
  return 1;
}



///////////////////////ksjdv
/*
function print_table_to_ods($table, $lastcols = 0, $table2 = null)
{
    global $CFG;
    
	require_once($CFG->dirroot.'/lib/odslib.class.php');
	
    $downloadfilename = $table->downloadfilename . 'ods';

    $workbook = new MoodleODSWorkbook("-");
    
	$workbook->send($downloadfilename);
    
    $myxls =&$workbook->add_worksheet($table->worksheetname);

	$numcolumn = count ($table->columnwidth) - $lastcols;
    $i=0;
    foreach ($table->columnwidth as $width)	{
		$myxls->set_column($i, $i, $width);
		$i++;
	}

	$formath1 =& $workbook->add_format();
//	$formath1->set_size(12);
    $formath1->set_align('center');
    $formath1->set_align('vcenter');
	$formath1->set_color('black');
	$formath1->set_bold(1);
	// $formath1->set_italic();
	$formath1->set_text_wrap();
	// $formath1->set_border(2);

    $i = $ii = 0;
   
    foreach ($table->titles as $key => $title)	{
		$myxls->set_row($i, $table->titlesrows[$key]);
		//$strwin1251 =  $txtl->convert($title, 'utf-8', 'windows-1251');
	    $myxls->write_string($i, 0, $title, $formath1);
		$myxls->merge_cells($i, 0, $i, $numcolumn-1);
		$i++;
    }

	$formath2 =& $workbook->add_format();
//	$formath2->set_size(11);
    $formath2->set_align('center');
    $formath2->set_align('vcenter');
	$formath2->set_color('black');
	$formath2->set_bold(1);
	//$formath2->set_italic();
	$formath2->set_border(2);
	$formath2->set_text_wrap();

    if (!empty($table->head)) {
    	$formatp = array();
    	$numcolumn = count ($table->head) - $lastcols;
        foreach ($table->head as $key => $heading) {
        	if ($key >= $numcolumn) continue;
	   		//$strwin1251 =  $txtl->convert(strip_tags($heading), 'utf-8', 'windows-1251');
	       $myxls->write_string($i, $key, strip_tags($heading),$formath2);

			$formatp[$key] =& $workbook->add_format();
		//	$formatp[$key]->set_size(10);
		    $formatp[$key]->set_align($table->align[$key]);
		    $formatp[$key]->set_align('vcenter');
			$formatp[$key]->set_color('black');
			$formatp[$key]->set_bold(0);
			$formatp[$key]->set_border(1);
			$formatp[$key]->set_text_wrap();
        }
        $i++;
       // $ii = $i;
    }

    if (isset($table->data)) foreach ($table->data as $keyrow => $row) {
      	$numcolumn = count ($row) - $lastcols;
        foreach ($row as $keycol => $item) 	{
           	if ($keycol >= $numcolumn) continue;
        	$clearitem = strip_tags($item);
        	switch ($clearitem)	{
        		case '&raquo;': $clearitem = '>>'; break;
        		case '&laquo;': $clearitem = '<<'; break;
        	}
 			//$strwin1251 =  $txtl->convert($clearitem, 'utf-8', 'windows-1251');
			$myxls->write($i + $keyrow, $keycol, $clearitem, $formatp[$keycol]);
		//	$ii = $i + $keyrow;
		}
    }
    
    if (!empty($table2)) {
    //	$i = $ii;
    	$i += 2;
    	$formatp = array();
    	$numcolumn = count ($table2->head) - $lastcols;
        foreach ($table2->head as $key => $heading) {
        	if ($key >= $numcolumn) continue;
	   		$strwin1251 =  $txtl->convert(strip_tags($heading), 'utf-8', 'windows-1251');
	        $myxls->write_string($i, $key,  $strwin1251, $formath2);

			$formatp[$key] =& $workbook->add_format();
		//	$formatp[$key]->set_size(10);
		    $formatp[$key]->set_align($table2->align[$key]);
		    $formatp[$key]->set_align('vcenter');
			$formatp[$key]->set_color('black');
			$formatp[$key]->set_bold(0);
			$formatp[$key]->set_border(1);
			$formatp[$key]->set_text_wrap();
        }
        $i++;
    }

    if (isset($table2->data)) foreach ($table2->data as $keyrow => $row) {
      	$numcolumn = count ($row) - $lastcols;
        foreach ($row as $keycol => $item) 	{
           	if ($keycol >= $numcolumn) continue;
        	$clearitem = strip_tags($item);
        	switch ($clearitem)	{
        		case '&raquo;': $clearitem = '>>'; break;
        		case '&laquo;': $clearitem = '<<'; break;
        	}
 			$strwin1251 =  $txtl->convert($clearitem, 'utf-8', 'windows-1251');
			$myxls->write($i + $keyrow, $keycol,  $strwin1251, $formatp[$keycol]);
			//$ii = $i + $keyrow;
		}
    }
      

    $workbook->close();
}
*/


function print_table_to_excel_merge($table, $mergecol = -1, $lastcols = 0)
{
    global $CFG;
    $order   = array("\r\n", "\n", "\r");
    
    $downloadfilename = $table->downloadfilename;

    header("Content-type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"{$downloadfilename}.xls\"");
    header("Expires: 0");
    header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
    header("Pragma: public");

    $workbook = new Workbook("-");
    $txtl = new textlib();

	$strwin1251 =  $txtl->convert($table->worksheetname, 'utf-8', 'windows-1251');
    $myxls =&$workbook->add_worksheet($strwin1251);

	$numcolumn = count ($table->columnwidth) - $lastcols;
    $i=0;
    foreach ($table->columnwidth as $key => $width)	{
        if ($mergecol == $key ) continue;
		$myxls->set_column($i, $i, $width);
		$i++;
	}

	$formath1 =& $workbook->add_format();
	$formath1->set_size(12);
    $formath1->set_align('center');
    $formath1->set_align('vcenter');
	$formath1->set_color('black');
	$formath1->set_bold(1);
	// $formath1->set_italic();
	$formath1->set_text_wrap();
	// $formath1->set_border(2);

	$formatb =& $workbook->add_format();
	$formatb->set_size(10);
    $formatb->set_align('center');
    $formatb->set_align('vcenter');
	$formatb->set_color('black');
	$formatb->set_bold(1);
	// $formath1->set_italic();
	$formatb->set_text_wrap();
	$formatb->set_border(1);

    $i = $ii = 0;
   
    foreach ($table->titles as $key => $title)	{
		$myxls->set_row($i, $table->titlesrows[$key]);
		$strwin1251 =  $txtl->convert($title, 'utf-8', 'windows-1251');
	    $myxls->write_string($i, 0, $strwin1251, $formath1);
		$myxls->merge_cells($i, 0, $i, $numcolumn-2);
		$i++;
    }

	$formath2 =& $workbook->add_format();
	$formath2->set_size(11);
    $formath2->set_align('center');
    $formath2->set_align('vcenter');
	$formath2->set_color('black');
	$formath2->set_bold(1);
	//$formath2->set_italic();
	$formath2->set_border(2);
	$formath2->set_text_wrap();

    
    if (!empty($table->head)) {
    	$formatp = array();
    	$numcolumn = count ($table->head) - $lastcols;
        $sdvig = 0;
        foreach ($table->head as $key => $heading) {

			$formatp[$key] =& $workbook->add_format();
			$formatp[$key]->set_size(10);
		    $formatp[$key]->set_align($table->align[$key]);
		    $formatp[$key]->set_align('vcenter');
			$formatp[$key]->set_color('black');
			$formatp[$key]->set_bold(0);
			$formatp[$key]->set_border(1);
			$formatp[$key]->set_text_wrap();

            if ($key == $mergecol) { 
                    $sdvig = 1;
                    continue;
            }        
        	if ($key >= $numcolumn) continue;
	   		$strwin1251 =  $txtl->convert(strip_tags($heading), 'utf-8', 'windows-1251');
	        $myxls->write_string($i, $key-$sdvig,  $strwin1251, $formath2);

        }
        $i++;
    }

    $rowsdvig = 0;
    if (isset($table->data)) foreach ($table->data as $keyrow => $row) {
      	$numcolumn = count ($row) - $lastcols;
        $sdvig = 0;
        if (empty($row[$mergecol]))  {
            foreach ($row as $keycol => $item) 	{
                if ($keycol ==  $mergecol) {
                    $sdvig = 1; 
                    continue;
                }    
               	if ($keycol >= $numcolumn) continue;
                $item = str_replace($order, '<br>', $item);
                $item = str_replace ('<br>', "\n", $item);
            	$clearitem = strip_tags($item);
            	switch ($clearitem)	{
            		case '&raquo;': $clearitem = '>>'; break;
            		case '&laquo;': $clearitem = '<<'; break;
            	}
     			$strwin1251 =  $txtl->convert($clearitem, 'utf-8', 'windows-1251');
    			$myxls->write($i + $keyrow  + $rowsdvig, $keycol-$sdvig,  $strwin1251, $formatp[$keycol]);
    		}
        }  else {
           
            foreach ($row as $keycol => $item) 	{
                if ($keycol ==  $mergecol || $keycol >= $numcolumn-1) continue;
                $myxls->write($i + $keyrow  + $rowsdvig, $keycol,  '', $formatp[$keycol]);
            } 
           $clearitem = strip_tags($row[$mergecol]);
           $strwin1251 =  $txtl->convert($clearitem, 'utf-8', 'windows-1251');
           $myxls->write_string($i+$keyrow+$rowsdvig, 0, $strwin1251, $formatb);
		   $myxls->merge_cells($i+$keyrow+$rowsdvig, 0, $i + $keyrow+$rowsdvig, $numcolumn-2);
           
           if (!empty($row[$mergecol+1]))    {
               $rowsdvig++;
               foreach ($row as $keycol => $item) 	{
                    if ($keycol ==  $mergecol) {
                        $sdvig = 1; 
                        continue;
                    }    
                   	if ($keycol >= $numcolumn) continue;
                	$clearitem = strip_tags($item);
                    $item = str_replace($order, '<br>', $item);
                    $item = str_replace ('<br>', "\n", $item);
                	switch ($clearitem)	{
                		case '&raquo;': $clearitem = '>>'; break;
                		case '&laquo;': $clearitem = '<<'; break;
                	}
         			$strwin1251 =  $txtl->convert($clearitem, 'utf-8', 'windows-1251');
        			$myxls->write($i + $keyrow  + $rowsdvig, $keycol-$sdvig,  $strwin1251, $formatp[$keycol]);
        	   }
           }    
        }    
    }
    
    $workbook->close();
}

?>
