<?php // $Id: import.php,v 1.1 2009/05/14 09:20:29 Shtifanov Exp $
	require_once("../../../config.php");
    require_once('../../monitoring/lib.php');
	require_once($CFG->libdir.'/uploadlib.php');
	require_once($CFG->dirroot.'/mod/hotpot/db/update_to_v2.php');

	$frm = data_submitted(); /// load up any submitted data

	$workforms = get_string('importresults', 'block_mou_ege');

	$admin_is = isadmin();
	$region_operator_is = ismonitoperator('region');
	if (!$admin_is && !$region_operator_is) {
        error(get_string('adminaccess', 'block_monitoring'), "$CFG->wwwroot/login/index.php");
	}

    $breadcrumbs = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title','block_mou_ege').'</a>';
	$breadcrumbs .= " -> $workforms";
    print_header("$SITE->shortname: $workforms", $SITE->fullname, $breadcrumbs);
	print_simple_box_start("center", "%100");

	if (!empty($frm) ) {
		$um = new upload_manager('userfile',false,false,null,false,0);
		$f = 0;
		if ($um->preprocess_files()) {
			$filename = $um->files['userfile']['tmp_name'];
			$text = file($filename);
//			$text = my_file_get_contents($filename);
			echo "<center>";
			if($text==''){
				error(get_string('errorfile', 'block_cdoadmin'), "$CFG->wwwroot/blocks/cdoadmin/loadtest.php");
			}

			$textlib = textlib_get_instance();
			$size = sizeof($text);

			list($data->login, $data->quiz, $data->sumgrades) = explode(";", $text[0]);

			if(($data->login != 'login')&&($data->quiz != 'quiz')&&($data->sumgrades != 'sumgrades')) {
				redirect("$CFG->wwwroot/blocks/mou_ege/index.php", get_string('errorimportfile', 'block_mou_ege'));

			} else {
				for($i=1; $i < $size; $i++)  {
					list($data->login, $data->quiz, $data->sumgrades) = explode(";", $text[$i]);
					$sql = get_record('user', 'username', $data->login);
// ball := ((((ball * 4) + 20) * 7) + 15) * 5;

$data->sumgrades = (((($data->sumgrades/5)-15)/7)-20)/4;

					$answers->userid = $sql->id;
					$answers->sumgrades = number_format($data->sumgrades, 2, '.', '');

//$number = 1234.5678;
// английский формат без разделителей групп
//$english_format_number = number_format($number, 2, '.', '');

					$answers->layout = '0';
					$answers->preview = 0;
					$answers->timestart = time() - 2000;
					$answers->timefinish = time();
					$answers->timemodified = time();
					$answers->quiz = $data->quiz;

					$id_temp = get_record_select('course_sections', "sequence LIKE '%$data->quiz%'", 'id, course');
//print "sequence=$data->quiz<br>";

					$id_temp = get_record_select('course_modules', "course=$id_temp->course AND section=$id_temp->id AND visible=1", 'instance as id');

//					$id_temp->id = $data->quiz;


					$answers->quiz = $id_temp->id;
/*
					switch ($data->quiz) {
						case 671:
							$answers->quiz = 71;
						break;
						case 673:
							$answers->quiz = 72;
						break;
						case 675:
							$answers->quiz = 73;
						break;
						case 677:
							$answers->quiz = 74;
						break;
						case 681:
							$answers->quiz = 76;
						break;
						case 683:
							$answers->quiz = 77;
						break;
						case 684:
							$answers->quiz = 75;
						break;
					}
*/


					$sql = get_record_sql("select max(uniqueid) as c from {$CFG->prefix}quiz_attempts");
					$answers->uniqueid = $sql->c + 1;
					$answers->attempt = 1;
					$sql = get_record_sql("select id from {$CFG->prefix}quiz_attempts where userid=$answers->userid and quiz=$answers->quiz");
					if(!$sql) {//print_object($answers);
						insert_record("quiz_attempts", $answers);

						$quiz->quiz = $answers->quiz;
						$quiz->userid = $answers->userid;
						$quiz->grade = $answers->sumgrades;
						$quiz->timemodified = $answers->timemodified;
						insert_record("quiz_grades", $quiz);

						$quiz_0->modulename = 'quiz';
						insert_record('question_attempts', $quiz_0);
					}
				}
				redirect("$CFG->wwwroot/blocks/mou_ege/index.php", get_string('completeimportfile', 'block_mou_ege'));
			}
		}
	}  else  {
 		print_heading(get_string('filenameresultstest', 'block_mou_ege'), 'center', 3);
   	    echo "<table cellspacing='0' cellpadding='10' align='center' class='generaltable generalbox'><tr><td align=center>";
		echo '<form method="post" enctype="multipart/form-data" action="import.php">'.
		'<input type="hidden" name="sesskey" value="'.$USER->sesskey.'">'.
		'<input type="file" name="userfile" size="30">'.
		'<p><input type="submit" name="load" value="'.get_string('upload', 'block_monitoring').'">';
		echo '</form></td></tr></table>';
	}

	print_simple_box_end();
    print_footer();
?>
