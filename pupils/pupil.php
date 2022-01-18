<?php // $Id: pupil.php,v 1.3 2010/05/27 08:07:18 Shtifanov Exp $

   		include('incl_pupil.php');


	    $user = $user1;

	    $personalprofile = get_string("personalprofile");
	    $participants = get_string("participants");

	    if ($user->deleted) {
	        print_heading(get_string("userdeleted"));
	    }

	    $currenttab = 'profile';
	    include('tabspupil.php');


    	echo "<table width=\"80%\" align=\"center\" border=\"0\" cellspacing=\"0\" class=\"userinfobox\">";
	    echo "<tr>";
	    echo "<td width=\"100\" valign=\"top\" class=\"side\">";
    	print_user_picture($user->id, 1, $user->picture, true, false, false);
	    echo "</td><td width=\"100%\" class=\"content\">";

    	// Print the description

    	if ($user->description) {
        	echo format_text($user->description, FORMAT_MOODLE)."<hr />";
	    }

    	// Print all the little details in a list

	    echo '<table border="0" cellpadding="0" cellspacing="0" class="list">';

    	print_row(get_string('fio', 'block_monitoring').':', $fullname);

    	print_row(get_string('rayon', 'block_monitoring').':', $rayon->name);

    	print_row(get_string('school', 'block_monitoring').':', $school->name);

    	print_row(get_string('class', 'block_mou_ege').':', $class->name);


		$i = 0;
		foreach ($profile->fields as $pf)  {
		    $printstr = get_string($pf, 'block_mou_ege');
			if (!empty($pupil->{$pf}))  {
				switch ($profile->type[$i]) {
					case 'text': case 'int':
					    $printval = $pupil->{$pf};
					break;
					case 'date':
					    $printval = convert_date($pupil->{$pf}, 'en', 'ru');
					break;
					case 'bool':
					    $printval = get_string($pf.$pupil->{$pf}, 'block_mou_ege');
					break;
				}
			} else {
				if (in_array($pf, $profile->numericfield)) {
					$printval = '0';
				} else {
					$printval = '-';
				}
			}
			$i++;
	    	print_row($printstr . ':', $printval);
		}

    	print_row('<hr>', '<hr>');

		$disciplines =  get_records_sql ("SELECT id, yearid, name
										  FROM  {$CFG->prefix}monit_school_discipline_ege
										  WHERE yearid=$yid
										  ORDER BY name");

		if ($disciplines)	{
		    $arr_egeids = explode(',', $pupil->listegeids);
		    $arr_datesids = explode(',', $pupil->listdatesids);
		    $listdisciplines = '';
			foreach ($disciplines as $discipline) 	{
				if (in_array($discipline->id, $arr_egeids))	{
					$listdisciplines .= $discipline->name;
					$index = array_search($discipline->id, $arr_egeids);
					$idgia = $arr_datesids[$index];
					$giadate =  get_record ('monit_school_gia_dates', 'id', $idgia);
					$listdisciplines .= ': ' . convert_date($giadate->date_gia, 'en', 'ru') . '<br>';    
				}
			}
		}

        print_row(get_string('disciplines_ege', 'block_mou_ege').":", $listdisciplines);
    	print_row('<hr>', '<hr>');


		$stradress = "";
	    if ($user->city or $user->country) {
	        $countries = get_list_of_countries();
			$stradress .= $countries["$user->country"].", $user->city";
	    }
    	if ($user->address) {
			$stradress .= ", $user->address";
	    }
        print_row(get_string("address").":", $stradress);

    	print_row("E-mail:", obfuscate_mailto($user->email, '', $user->emailstop));

	    if ($user->phone2) {
	    	$phone2 = $user->phone2;
    	} else  {
	    	$phone2 = '-';
    	}
        print_row(get_string('mobilephone', 'block_monitoring').":", $phone2);

	    if ($admin_is || $rayon_operator_is || $school_operator_is) 	{
	    	print_row('<hr>', '<hr>');
	       	print_row(get_string('username').':', $user->username);
	       	print_row(get_string('startpassword', 'block_mou_att').':', $pupil->pswtxt);
	    }

/*
	    if ($mycourses = get_my_courses($user->id)) {
    	   $courselisting = '';
    	   print_row('<hr>', '<hr>');
	       print_row(get_string('courses').':', '('.count($mycourses).')');
	       foreach ($mycourses as $mycourse) {
		       if ($mycourse->visible and $mycourse->category) {
    		       $courselisting = "<a href=\"$CFG->wwwroot/user/view.php?id=$user->id&amp;course=$mycourse->id\">$mycourse->fullname</a>";
       			}
		       print_row('-', $courselisting);
		    }
		}
*/
    echo "</table>";
    echo "</td></tr></table>";

    print_footer();

/// Functions ///////

function print_row($left, $right) {
    echo "\n<tr><td nowrap=\"nowrap\" valign=\"top\" class=\"label c0\" align=\"left\">$left</td><td align=\"left\" valign=\"top\" class=\"info c1\">$right</td></tr>\n";
}

?>


