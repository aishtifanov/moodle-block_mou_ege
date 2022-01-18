<?php // $Id: block_mou_ege.php,v 1.16 2009/10/06 09:30:47 Shtifanov Exp $


class block_mou_ege extends block_list {

    function init() {
        $this->title = get_string('title','block_mou_ege');
        $this->version = 2008120500;
    }

    function get_content() {
        global $CFG;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        if (empty($this->instance)) {
            $this->content = '';
        } else {
            $this->load_content();
        }

        return $this->content;
        }

    function load_content() {
        global $CFG, $yearmonit;

		$yid = $yearmonit;
		
		$admin_is = isadmin();
		$staff_operator_is = ismonitoperator('staff');
		$region_operator_is = ismonitoperator('region');
		$rayon_operator_is  = ismonitoperator('rayon', 0, 0, 0, true);
		if  (!$admin_is && !$region_operator_is && $rayon_operator_is) 	{
			$rid = $rayon_operator_is;
		}	else {
			$rid = 0;
		}
		$sid = ismonitoperator('school', 0, 0, 0, true);
		$college_operator_is = ismonitoperator('college', 0, 0, 0, true);

		$staffview_operator = isstaffviewoperator();

		if ($admin_is  || $region_operator_is || $rayon_operator_is)	 {

			// $this->content->items[] = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/curriculum/curriculum.php?mode=1&amp;fid=0&amp;sid=0">'.get_string('curriculums','block_dean').'</a>';
			// $this->content->icons[] = '<img src="'.$CFG->pixpath.'/mod/wiki/icon.gif" height="16" width="16" alt="" />';
			$this->content->items[] = '<a href="'.$CFG->wwwroot."/blocks/mou_ege/ege/disciplines_ege.php?mode=1&amp;rid=$rid&amp;sid=0&amp;cid=0&amp;yid=$yid\">".get_string('disciplines_ege','block_mou_ege').'</a>';
 	        $this->content->icons[] = '<img src="'.$CFG->wwwroot.'/blocks/mou_ege/i/ege.gif" height="16" width="16" alt="" />';


			$this->content->items[] = '<a href="'.$CFG->wwwroot."/blocks/mou_ege/class/classlist.php?mode=1&amp;rid=$rid&amp;sid=0&amp;cid=0&amp;yid=$yid\">".get_string('school','block_monitoring').'</a>';
 	        $this->content->icons[] = '<img src="'.$CFG->wwwroot.'/blocks/mou_ege/i/classes2.gif" height="16" width="16" alt="" />';

			$this->content->items[] = '<a href="'.$CFG->wwwroot."/blocks/mou_ege/class/classpupils.php?mode=1&amp;rid=$rid&amp;sid=0&amp;cid=0&amp;gid=0&amp;yid=$yid\">".get_string('class','block_mou_ege').'</a>';
 	        $this->content->icons[] = '<img src="'.$CFG->pixpath.'/i/group.gif" height="16" width="16" alt="" />';

        	$this->content->items[] = '<a href="'.$CFG->wwwroot."/blocks/mou_ege/pupils/pupil.php?mode=1&amp;rid=$rid&amp;sid=0&amp;cid=0&amp;gid=0&amp;yid=$yid\">".get_string('pupil','block_mou_ege').'</a>';
        	$this->content->icons[] = '<img src="'.$CFG->pixpath.'/i/guest.gif" height="16" width="16" alt="" />';

	        // $this->content->items[] = '<a href="'.$CFG->wwwroot.'/blocks/dean/journal/journalgroup.php">'.get_string('journalgroup','block_dean').'</a>';
 	        // $this->content->icons[] = '<img src="'.$CFG->wwwroot.'/blocks/dean/i/journal.gif" height="16" width="16" alt="" />';

			// $this->content->items[] = '<a href="' .$CFG->wwwroot . "/blocks/mou_ege/pupils/pupil_card.php?mode=1&amp;rid=0&amp;sid=0&amp;cid=0&amp;did=0&amp;uid=0\">Учащиеся</a>";
			// $this->content->icons[] = '<img src="'.$CFG->pixpath.'/i/groups.gif" height="16" width="16" alt="" />';

	        // $this->content->items[] = '<a href="'.$CFG->wwwroot.'/blocks/dean/journal/curatorsgroups.php">'.get_string('curatorsgroups','block_dean').'</a>';
	        // $this->content->icons[] = '<img src="'.$CFG->wwwroot.'/blocks/dean/i/curators.gif" height="16" width="16" alt="" />';

	        $this->content->items[] = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/pupils/searchpupil.php">'.get_string('searchpupil', 'block_mou_ege').'</a>';
	        $this->content->icons[] = '<img src="'.$CFG->pixpath.'/i/search.gif" height="16" width="16" alt="" />';

	        $this->content->items[] = '<a href="'.$CFG->wwwroot."/blocks/mou_ege/pupils/markspupil.php?rid=$rid&amp;sid=0&amp;yid=$yid\">".get_string('markspupil', 'block_mou_ege').'</a>';
	        $this->content->icons[] = '<img src="'.$CFG->wwwroot.'/blocks/mou_ege/i/journal.gif" height="16" width="16" alt="" />';

	        $this->content->items[] = '<a href="'.$CFG->wwwroot."/blocks/mou_ege/pupils/statsmarkspupil.php?rid=$rid&amp;sid=0&amp;yid=$yid\">".get_string('statsmarkspupil', 'block_mou_ege').'</a>';
	        $this->content->icons[] = '<img src="'.$CFG->wwwroot.'/blocks/mou_ege/i/journal.gif" height="16" width="16" alt="" />';

	        $this->content->items[] = '<a href="'.$CFG->wwwroot."/blocks/mou_ege/pupils/difficulty.php?rid=$rid&amp;sid=0&amp;yid=$yid\">".get_string('difficulty', 'block_mou_ege').'</a>';
	        $this->content->icons[] = '<img src="'.$CFG->wwwroot.'/blocks/mou_ege/i/journal.gif" height="16" width="16" alt="" />';

	        $this->content->items[] = '<a href="'.$CFG->wwwroot."/blocks/mou_ege/appeal/checkappeal.php?rid=$rid&amp;sid=0&amp;yid=$yid\">".get_string('checkappeal', 'block_mou_ege').'</a>';
	        $this->content->icons[] = '<img src="'.$CFG->wwwroot.'/blocks/mou_ege/i/a.png" height="16" width="16" alt="" />';

	        $this->content->items[] = '<a href="'.$CFG->wwwroot."/blocks/mou_ege/ege/setpoints.php?rid=$rid&amp;did=0&amp;yid=$yid\">".get_string('points', 'block_mou_ege').'</a>';
	        $this->content->icons[] = '<img src="'.$CFG->wwwroot.'/blocks/mou_ege/i/school_small.gif" height="16" width="16" alt="" />';

	        $this->content->items[] = '<a href="'.$CFG->wwwroot."/blocks/mou_ege/textbook/textbook.php?rid=$rid&amp;sid=0\">".get_string('textbooks', 'block_mou_ege').'</a>';
	        $this->content->icons[] = '<img src="'.$CFG->wwwroot.'/blocks/mou_ege/i/textbooks.gif" height="16" width="16" alt="" />';


		    $this->content->footer = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title', 'block_mou_ege').'</a>'.' ...';
	    }

		if (!$admin_is && !$region_operator_is && !$rayon_operator_is && $sid) {
	       if ($school = get_record('monit_school', 'id', $sid)) {
			    $rid = $school->rayonid;

				$this->content->items[] = '<a href="'.$CFG->wwwroot."/blocks/mou_ege/class/classlist.php?mode=1&amp;rid=$rid&amp;sid=$sid&amp;cid=0&amp;yid=$yid\">".get_string('school','block_monitoring').'</a>';
	 	        $this->content->icons[] = '<img src="'.$CFG->wwwroot.'/blocks/mou_ege/i/groups.gif" height="16" width="16" alt="" />';

				$this->content->items[] = '<a href="'.$CFG->wwwroot."/blocks/mou_ege/class/classpupils.php?mode=1&amp;rid=$rid&amp;sid=$sid&amp;cid=0&amp;gid=0&amp;yid=$yid\">".get_string('class','block_mou_ege').'</a>';
	 	        $this->content->icons[] = '<img src="'.$CFG->pixpath.'/i/group.gif" height="16" width="16" alt="" />';

	        	$this->content->items[] = '<a href="'.$CFG->wwwroot."/blocks/mou_ege/pupils/pupil.php?mode=1&amp;rid=$rid&amp;sid=$sid&amp;cid=0&amp;gid=0&amp;yid=$yid\">".get_string('pupil','block_mou_ege').'</a>';
	        	$this->content->icons[] = '<img src="'.$CFG->pixpath.'/i/guest.gif" height="16" width="16" alt="" />';
/*
	        $this->content->items[] = '<a href="'.$CFG->wwwroot."/blocks/mou_ege/textbook/textbook.php?rid=$rid&amp;sid=0\">".get_string('textbooks', 'block_mou_ege').'</a>';
	        $this->content->icons[] = '<img src="'.$CFG->wwwroot.'/blocks/mou_ege/i/textbooks.gif" height="16" width="16" alt="" />';
*/

			    $this->content->footer = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title', 'block_mou_ege').'</a>'.' ...';
			}
		}

		$pupil_is = ispupil();
        if ($pupil_is)	{
	        $this->content->items[] = '<a href="'.$CFG->wwwroot."/blocks/mou_ege/appeal/marks.php?yid=$yid\">".get_string('markspupil', 'block_mou_ege').'</a>';
	        $this->content->icons[] = '<img src="'.$CFG->wwwroot.'/blocks/mou_ege/i/journal.gif" height="16" width="16" alt="" />';

		    $this->content->footer = '<a href="'.$CFG->wwwroot.'/blocks/mou_ege/index.php">'.get_string('title', 'block_mou_ege').'</a>'.' ...';
	    }

    }
  }
 ?>
