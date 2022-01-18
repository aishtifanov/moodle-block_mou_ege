<?PHP // $Id: addiscipline.php,v 1.10 2012/01/11 06:26:48 shtifanov Exp $

    require_once('../../../config.php');
    require_once('../../monitoring/lib.php');

    $mode = required_param('mode', PARAM_ALPHA);    // new, add, edit, update
	$yid = required_param('yid', PARAM_INT);			// Year id
	$did = optional_param('did', 0, PARAM_INT);			// Discipline id

	require_login();

	$admin_is = isadmin();
	$region_operator_is = ismonitoperator('region');
	if (!$admin_is && !$region_operator_is) {
        error(get_string('adminaccess', 'block_monitoring'), "$CFG->wwwroot/login/index.php");
	}

    $strdisciplines = get_string('disciplines_ege', 'block_mou_ege');
    if ($mode === "new" || $mode === "add" ) {
    	$straddisc = get_string('addiscipline','block_mou_ege');
    } else {
    	$straddisc = get_string('updatediscipline','block_mou_ege');
    }


	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= "-> <a href=\"{$CFG->wwwroot}/blocks/mou_ege/ege/disciplines_ege.php?yid=$yid\">$strdisciplines</a>";
	$breadcrumbs .= "-> $straddisc";
    print_header("$SITE->shortname: $straddisc", $SITE->fullname, $breadcrumbs);

	$rec->yearid = $yid;
	$rec->name = '';
	$rec->date_a = $rec->date_b = $rec->date_c = '';
    $rec->code = 0; 
    
	if ($mode === 'add')  {
		$rec->name = required_param('name');
		$rec->code = required_param('codepredmet');

		if (find_form_disc_errors($rec, $err) == 0) {
			$rec->timemodified = time();
			if ($did = insert_record('monit_school_discipline_ege', $rec))		{
			     if ($frm = data_submitted()) {
						save_gia_dates($frm, $yid, $did);
			     }
				 // add_to_log(1, 'school', 'one discipline added', "blocks/school/curriculum/addiscipline.php?mode=2&amp;fid=$fid&amp;sid=$sid&amp;cid=$cid", $USER->lastname.' '.$USER->firstname);
				 // notice(get_string('disciplineadded','block_mou_ege'), "disciplines_ege.php?yid=$yid");
                 redirect("disciplines_ege.php?yid=$yid", get_string('disciplineadded','block_mou_ege'));
			} else
				error(get_string('errorinaddingdisc','block_mou_ege'), "disciplines_ege.php?yid=$yid");
		}
		else $mode = "new";
	}
	else if ($mode === 'edit')	{
		if ($did > 0) 	{
			$disc = get_record('monit_school_discipline_ege', 'id', $did);
			$rec->id = $disc->id;
			$rec->name = $disc->name;
			$rec->code = $disc->code;
		}
	}
	else if ($mode === 'update')	{
		$rec->id = required_param('did', PARAM_INT);
		$rec->name = required_param('name');
		$rec->code = required_param('codepredmet');

		if (find_form_disc_errors($rec, $err) == 0) {
			$rec->timemodified = time();
			if (update_record('monit_school_discipline_ege', $rec))	{
			     if ($frm = data_submitted()) {
						save_gia_dates($frm, $yid, $did);
			     }
				 // add_to_log(1, 'school', 'discipline update', "blocks/school/curriculum/addiscipline.php?mode=2&amp;fid=$fid&amp;sid=$sid&amp;cid=$cid", $USER->lastname.' '.$USER->firstname);
				 // notice(get_string('disciplineupdate','block_mou_ege'), "disciplines_ege.php?yid=$yid");
                 redirect("disciplines_ege.php?yid=$yid", get_string('disciplineupdate','block_mou_ege'));
			} else  {
				error(get_string('errorinupdatingdisc','block_mou_ege'), "disciplines_ege.php?yid=$yid");
			}
		}
	}


	print_heading($straddisc, "center", 3);

    print_simple_box_start("center");

	if ($mode === 'new') $newmode='add';
	else 				 $newmode='update';

?>

<form name="addform" method="post" action="addiscipline.php">
<center>
<table cellpadding="5">
<tr valign="top">
    <td align="right"><b><?php  print_string('discipline', 'block_mou_ege') ?>:</b></td>
    <td align="left">
		<input type="text"  name="name" size="50" value="<?php p($rec->name) ?>" />
		<?php if (isset($err["name"])) formerr($err["name"]); ?>
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string('codepredmet', 'block_mou_ege') ?>:</b></td>
    <td align="left">
		<input type="text"  name="codepredmet" size="4" maxlength="4" value="<?php p($rec->code) ?>" />
		<?php if (isset($err["codepredmet"])) formerr($err["codepredmet"]); ?>
    </td>
</tr>

<?php
	if ($giadates =  get_records_sql ("SELECT id, date_gia FROM  {$CFG->prefix}monit_school_gia_dates
									  WHERE yearid=$yid AND discegeid=$did ORDER BY date_gia"))  {
		foreach ($giadates as $giadate)	{
			 $date_gia = convert_date($giadate->date_gia, 'en', 'ru');
			 $fieldname = 'date_'. $giadate->id;
			 echo '<tr valign="top"><td align="right"><b>';
		     print_string('giadate', 'block_mou_ege');
		     echo ':</b></td> <td align="left">';
		  	 echo '<input type="text"  name="'. $fieldname . '" size="10" value="' . $date_gia . '" />';
			 if (isset($err[$fieldname])) formerr($err[$fieldname]);
		     echo '</td> </tr>';
		}
	}
?>
<tr valign="top">
    <td align="right"><b><?php  print_string('giadate', 'block_mou_ege') ?>:</b></td>
    <td align="left">
		<input type="text"  name="date_a" size="10" value="<?php p($rec->date_a) ?>" />
		<?php if (isset($err["date_a"])) formerr($err["date_a"]); ?>
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string('giadate', 'block_mou_ege') ?>:</b></td>
    <td align="left">
		<input type="text"  name="date_b" size="10" value="<?php p($rec->date_b) ?>" />
		<?php if (isset($err["date_b"])) formerr($err["date_b"]); ?>
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string('giadate', 'block_mou_ege') ?>:</b></td>
    <td align="left">
		<input type="text"  name="date_c" size="10" value="<?php p($rec->date_c) ?>" />
		<?php if (isset($err["date_c"])) formerr($err["date_c"]); ?>
    </td>
</tr>

</table>
<?php  if (!isregionviewoperator() && !israyonviewoperator())  {  ?>
   <div align="center">
     <input type="hidden" name="mode" value="<?php echo $newmode ?>">
     <input type="hidden" name="yid" value="<?php echo $yid ?>">
     <input type="hidden" name="did" value="<?php echo $did ?>">
 	 <input type="submit" name="adddisc" value="<?php print_string('savechanges')?>">
  </div>
<?php  }  ?>
 </center>
</form>


<?php
    print_simple_box_end();

	print_footer();


/// FUNCTIONS ////////////////////
function find_form_disc_errors(&$rec, &$err, $mode='add') 
{

	// print_r($rec);
    if (empty($rec->name)) {
            $err["name"] = get_string("missingname");
	}

	if ($rec->date_a != '')	{
		if (!is_date($rec->date_a)) {
  			$err['date_a'] = get_string('missingdate', 'block_mou_att');
  		}	
  	}
	
//    $day = $month = $year = 0;
//    list($day, $month, $year) = explode(".", $strdate);

	if (!empty($rec->date_a) && !is_date($rec->date_a)) {
  		$err['date_a'] = get_string('missingdate', 'block_mou_att');
  	}
	if (!empty($rec->date_b) && !is_date($rec->date_b)) {
  		$err['date_b'] = get_string('missingdate', 'block_mou_att');
  	}
	if (!empty($rec->date_c) && !is_date($rec->date_c)) {
  		$err['date_c'] = get_string('missingdate', 'block_mou_att');
  	}


    return count($err);
}


function save_gia_dates($frm, $yid, $did)
{
	foreach ($frm as $key => $value) {
		// echo "$key => $value<br>";
		$sym = substr($key, 0, 4);
		if ($sym == 'date')   {
			$nums = explode ('_', $key);
			$giadateid = $nums[1];
			if ($giadate =  get_record("monit_school_gia_dates", 'id', $giadateid))  {
			    if ($value == 0) {
			         delete_records('monit_school_gia_dates', 'id', $giadate->id);
			    }  else {
    			    $date_gia = convert_date($value, 'ru', 'en');
    				if ($giadate->date_gia != $date_gia)	{
    					$giadate->date_gia = $date_gia;
    
    	       			if (!update_record('monit_school_gia_dates', $giadate))	{
    	     				print_r($giadate);
    						error('Error update monit_school_gia_dates!', 'disciplines_ege.php');
    					}
    				}
                }    
			} else {
				if (!empty($value))	{
					if ($did > 0) 	{
						$discipline_ege = get_record('monit_school_discipline_ege', 'id', $did);
					}
	
				    unset($giadate);
					$giadate->yearid 		= $yid;
					$giadate->discegeid 	= $did;
					$giadate->codepredmet	= $discipline_ege->code;
					$giadate->date_gia 		= convert_date($value, 'ru', 'en');
					$giadate->timemodified 	= time();
	     			if (!insert_record('monit_school_gia_dates', $giadate))	{
	     				print_r($giadate);
						error('Error insert monit_school_gia_dates!', 'disciplines_ege.php');
					}
				}
			}
		} // if z
	} // foreach
}

?>