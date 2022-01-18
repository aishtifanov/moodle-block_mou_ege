<?PHP // $Id: deldiscipline.php,v 1.4 2009/06/11 09:40:35 Shtifanov Exp $

    require_once("../../../config.php");
    require_once('../../monitoring/lib.php');

	$yid = required_param('yid', PARAM_INT);			// Year id
	$did 	= required_param('did', PARAM_INT);			// Discipline id
	$confirm = optional_param('confirm');

    if (! $site = get_site()) {
        redirect("$CFG->wwwroot/$CFG->admin/index.php");
    }

	require_login();

	$admin_is = isadmin();
	$region_operator_is = ismonitoperator('region');
	if (!$admin_is && !$region_operator_is) {
        error(get_string('adminaccess', 'block_monitoring'), "$CFG->wwwroot/login/index.php");
	}

	if (isregionviewoperator())  {
        error(get_string('adminaccess', 'block_monitoring'));
	}

    $strdisciplines = get_string('disciplines_ege', 'block_mou_ege');
    $strdeldiscipline = get_string('deletingdiscipline', 'block_mou_ege');

	$breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= "-> <a href=\"{$CFG->wwwroot}/blocks/mou_ege/ege/disciplines_ege.php?yid=$yid\">$strdisciplines</a>";
	$breadcrumbs .= "-> $strdeldiscipline";
    print_header_mou("$site->shortname: $strdeldiscipline", $site->fullname, $breadcrumbs);

	if (isset($confirm)) {
		delete_records('monit_school_discipline_ege', 'id', $did);
		//  add_to_log(1, 'school', 'Discipline deleted', 'deldiscipline.php', $USER->lastname.' '.$USER->firstname);
		redirect("disciplines_ege.php?yid=$yid", get_string('disciplinedeleted','block_mou_ege'), 1);
	}

	$adiscipl = get_record("monit_school_discipline_ege", "id", $did);

	print_heading(get_string('deletingdiscipline','block_mou_ege') .' :: ' .$adiscipl->name);

    $str = get_string('disciplinelow', 'block_mou_ege') . ' ' . "'$adiscipl->name'";

	notice_yesno(get_string('deletecheckfull', '', $str),
               "deldiscipline.php?yid=$yid&amp;did=$did&amp;confirm=1", "disciplines_ege.php?yid=$yid");

	print_footer();
?>
