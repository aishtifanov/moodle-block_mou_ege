<?PHP // $Id: addclass.php,v 1.11 2009/11/12 10:47:53 Shtifanov Exp $

    require_once('../../../config.php');
    require_once('../../monitoring/lib.php');
    require_once('../../mou_att/lib_att.php');
    require_once('../lib_ege.php');


    $mode = required_param('mode');    // new, add, edit, update
    $rid = required_param('rid', PARAM_INT);          // Rayon id
    $sid = required_param('sid', PARAM_INT);       // School id
	$cid = 0; // $cid = required_param('cid', PARAM_INT);		  // Curriculum id
    $yid = required_param('yid', PARAM_INT);       // Year id
    $gid = optional_param('gid', '0', PARAM_INT);      // Class id


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

	$strclasses = get_string('classes','block_mou_ege');
	$strclass = get_string('class','block_mou_ege');
	$strpupils = get_string('pupils', 'block_mou_ege');

    if ($mode === "new" || $mode === "add" ) $straddclass = get_string('addclass', 'block_mou_ege');
	else $straddclass = get_string('updateclass', 'block_mou_ege');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= "-> <a href=\"{$CFG->wwwroot}/blocks/mou_ege/class/classlist.php?rid=$rid&amp;sid=$sid&amp;yid=$yid\">$strclasses</a>";
	$breadcrumbs .= " -> $straddclass";
    print_header_mou("$site->shortname: $straddclass", $site->fullname, $breadcrumbs);

    /*
    print_heading('Страница в стадии разарботки.', 'center', 3);
	print_footer();
    exit();
    */

	$rec->rayonid = $rid;
	$rec->yearid = $yid;
	$rec->schoolid = $sid;
	$rec->curriculumid = $cid;
	$rec->name = '';
	$rec->description = '';

	if ($mode === 'add')  {
		$rec->name = required_param('name', PARAM_TEXT);
		$rec->parallelnum = (int)$rec->name;;
		$rec->description = required_param('description');

		if (find_form_class_errors($rec, $err) == 0) {
			$rec->timecreated = time();
			if (insert_record('monit_school_class', $rec))	{
				 // add_to_log(1, 'dean', 'one academygroup added', "blocks/dean/groups/addgroup.php?mode=new&amp;fid=$fid&amp;sid=$sid&amp;cid=$cid", $USER->lastname.' '.$USER->firstname);
				 notice(get_string('classadded','block_mou_ege'), $CFG->wwwroot."/blocks/mou_ege/class/classlist.php?mode=3&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid");
			} else
				error(get_string('errorinaddingclass','block_mou_ege'), $CFG->wwwroot."/blocks/mou_ege/class/classlist.php?mode=3&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid");
		}
		else $mode = "new";
	}
	else if ($mode === 'edit')	{
		if ($gid > 0) 	{
			$class = get_record('monit_school_class', 'id', $gid);
			$rec->id = $class->id;
			$rec->name = $class->name;
			$rec->parallelnum = (int)$rec->name;
			$rec->description = $class->description;
		}
	}
	else if ($mode === 'update')	{
		$rec->id = required_param('classid', PARAM_INT);
		$rec->name = required_param('name', PARAM_TEXT);
		$rec->parallelnum = (int)$rec->name;;		
		$rec->description = required_param('description');

		if (find_form_class_errors($rec, $err) == 0) {
			$rec->timeadded = time();
			if (update_record('monit_school_class', $rec))	{
				 // add_to_log(1, 'dean', 'academygroup update', "blocks/dean/groups/addgroup.php?mode=new&amp;fid=$fid&amp;sid=$sid&amp;cid=$cid", $USER->lastname.' '.$USER->firstname);
				 notice(get_string('classupdate','block_mou_ege'), $CFG->wwwroot."/blocks/mou_ege/class/classlist.php?mode=3&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid");
			} else
				error(get_string('errorinupdatingclass','block_mou_ege'), $CFG->wwwroot."/blocks/mou_ege/class/classlist.php?mode=3&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid");
		}
	}

	$rayon = get_record('monit_rayon', 'id', $rid);

	$school = get_record('monit_school', 'id', $sid);

	print_heading($straddclass, "center", 3);

    print_simple_box_start("center", "70%");
?>

<form name="addform" method="post" action="<?php if ($mode === 'new') echo "addclass.php?mode=add&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid";
												else  echo "addclass.php?mode=update&amp;rid=$rid&amp;sid=$sid&amp;yid=$yid&amp;gid=$gid";?>">
<center>
<table cellpadding="5">
<tr valign="top">
    <td align="right"><b><?php  print_string("rayon","block_monitoring") ?>:</b></td>
    <td align="left"> <?php p($rayon->name) ?> </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("school","block_monitoring") ?>:</b></td>
    <td align="left"> <?php p($school->name) ?> </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string('class', 'block_mou_ege') ?>:</b></td>
    <td align="left">
		<input type="text" id="name" name="name" size="70" value="<?php p($rec->name) ?>" />
		<?php if (isset($err["name"])) formerr($err["name"]); ?>
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string("description") ?>:</b></td>
    <td align="left">
		<input type="text" id="description" name="description" size="70" value="<?php p($rec->description) ?>" />
    </td>
</tr>
</table>
<?php  if (!isregionviewoperator() && !israyonviewoperator())  {  ?>
   <div align="center">
     <input type="hidden" name="classid" value="<?php p($gid)?>">
 	 <input type="submit" name="addclass1" value="<?php print_string('savechanges')?>">
  </div>
<?php  } ?>
 </center>
</form>


<?php
    print_simple_box_end();

	print_footer();


/// FUNCTIONS ////////////////////
function find_form_class_errors(&$rec, &$err)
{

  $textlib = textlib_get_instance();
  $rec->name = $textlib->strtoupper($rec->name);

  $rec->name = translit_english_utf8($rec->name);

  $symbols = array (' ', '\"', "\'", "`", '-', '#', '*', '+', '_', '=');
  foreach ($symbols as $sym)	{
	  $rec->name = str_replace($sym, '', $rec->name);
  }

  if ($classexist = get_record('monit_school_class', 'schoolid', $rec->schoolid, 'yearid', $rec->yearid, 'name', $rec->name))	{
    	if (isset($rec->id)) {
    		if ($classexist->id != $rec->id)		{
			    $err["name"] = get_string('classalreadyexist', 'block_mou_ege');
			}
		} else {
		    $err["name"] = get_string('classalreadyexist', 'block_mou_ege');
		}
	}
    if (empty($rec->name))	{
	    $err["name"] = get_string("missingname");
	}

    return count($err);
}

?>
