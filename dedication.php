<?php

    require_once("../../config.php");

    require_login();

    $courseid = required_param('courseid', PARAM_INT);
    $instanceid = required_param('instanceid', PARAM_INT);
    $sesskey = required_param('sesskey', PARAM_RAW);
    $limit = required_param('limit', PARAM_INT);
    $userid = optional_param('userid', 0, PARAM_INT);
    $startday = optional_param('startday', 0, PARAM_INT);
    $startmonth = optional_param('startmonth', 0, PARAM_INT);
    $startyear = optional_param('startyear', 0, PARAM_INT);
    $starthour = optional_param('starthour', 0, PARAM_INT);
    $startminute = optional_param('startminute', 0, PARAM_INT);
    $endday = optional_param('endday', 0, PARAM_INT);
    $endmonth = optional_param('endmonth', 0, PARAM_INT);
    $endyear = optional_param('endyear', 0, PARAM_INT);
    $endhour = optional_param('endhour', 0, PARAM_INT);
    $endminute = optional_param('endminute', 0, PARAM_INT);
    $mintime = optional_param('mintime', 0, PARAM_INT);
    $maxtime = optional_param('maxtime', 0, PARAM_INT);
    $calculateall = optional_param('calculateall', 0, PARAM_INT);
    $downloadxls = optional_param('downloadxls', 0, PARAM_INT);
    $sort = optional_param('sort', '', PARAM_ALPHA);
    $pinned = optional_param('pinned', 0, PARAM_INT);

    if (! $course = get_record("course", "id", $courseid) ) {
        print_error('invalidcourse');
    }

    if (!$course->category) {
        print_error('invalidcourse');
    }

    require_login($course);

    if (!confirm_sesskey($sesskey)) {
        print_error('invalidrequest');
    }

    $dedicationblock = get_record('block', 'name', 'dedication');
    if ($pinned) {
        if ($blockinstance = get_record('block_pinned', 'id', $instanceid, 'blockid', $dedicationblock->id)) {
            $blockcontext = get_context_instance(CONTEXT_COURSE, $courseid);
        } else {
            print_error('invalidrequest');
        }
    } else {
        if ($blockinstance = get_record('block_instance', 'id', $instanceid, 'blockid', $dedicationblock->id, 'pageid', $course->id)) {
            $blockcontext = get_context_instance(CONTEXT_BLOCK, $blockinstance->id);
        } else {
            print_error('invalidrequest');
        }
    }

    require_capability('block/dedication:use', $blockcontext);

    $context = get_context_instance(CONTEXT_COURSE, $course->id);

    if (!$mintime & !$maxtime) {
        $mintime = make_timestamp($startyear, $startmonth, $startday, $starthour, $startminute, 0);
        $maxtime = make_timestamp($endyear, $endmonth, $endday, $endhour, $endminute, 0);
    }

    $limitinseconds = $limit*60;

    $strblocktitle = get_string('blocktitle', 'block_dedication');

    if (!$downloadxls) {
        if ($course->category) {
            print_header("$course->shortname: $strblocktitle", "$course->fullname",
                     "<a href=\"$CFG->wwwroot/course/view.php?id=$course->id\">$course->shortname</a> -> $strblocktitle", "");
        } else {
            print_header("$course->shortname: $strblocktitle", "$course->fullname",
                     "$strblocktitle", "");
        }
    }

    if ($userid) { /// DETAILED DEDICATION OF A COURSE USER

        require_capability('moodle/course:view', $context, $userid, $doanything=false);

        $baseurl = $CFG->wwwroot."/blocks/dedication/dedication.php?courseid=$courseid&mintime=$mintime&maxtime=$maxtime&limit=$limit&userid=$userid&instanceid=$instanceid&pinned=$pinned&sesskey=$sesskey";

        if ($courseuser = get_record('user', 'id', $userid)) {

            $a->firstname = $courseuser->firstname;
            $a->lastname = $courseuser->lastname;
            $a->strmintime = userdate($mintime);
            $a->strmaxtime = userdate($maxtime);

            print_heading(get_string('userdedication', 'block_dedication', $a));

            if ($logs = get_records_select("log", "course = $courseid AND userid = ".$userid." AND time >= $mintime AND time <= $maxtime", "time ASC", "id,time")) {

                if ($sort == 'startasc') {
                    $strstartsession = "<a href='$baseurl&sort=startdesc'>".get_string('sessionstart', 'block_dedication')."</a> <img src='".$CFG->pixpath."/t/down.gif' />";
                } else if ($sort == 'startdesc') {
                    $strstartsession = "<a href='$baseurl&sort=startasc'>".get_string('sessionstart', 'block_dedication')."</a> <img src='".$CFG->pixpath."/t/up.gif' />";
                } else {
                    $strstartsession = "<a href='$baseurl&sort=startasc'>".get_string('sessionstart', 'block_dedication')."</a>";
                }

                if ($sort == 'durationasc') {
                    $strduration = "<a href='$baseurl&sort=durationdesc'>".get_string('duration', 'block_dedication')."</a> <img src='".$CFG->pixpath."/t/down.gif' />";
                } else if ($sort == 'durationdesc') {
                    $strduration = "<a href='$baseurl&sort=durationasc'>".get_string('duration', 'block_dedication')."</a> <img src='".$CFG->pixpath."/t/up.gif' />";
                } else {
                    $strduration = "<a href='$baseurl&sort=durationasc'>".get_string('duration', 'block_dedication')."</a>";
                }

                $table->head = array ($strstartsession, $strduration);
                $table->align = array ("center", "center");
                $table->width = "0%";

                $previouslog = array_shift($logs);
                $previouslogtime = $previouslog->time;
                $sessionstart = $previouslogtime;
                $totaldedication = 0;

                $sortsessions = array();

                foreach ($logs as $log) {

                    if (($log->time - $previouslogtime) > $limitinseconds) {

                        $dedication = $previouslogtime - $sessionstart;

                        $totaldedication += $dedication;

                        if ($dedication > 59) {

                            $sortsessions[] = array ("startsession" => $sessionstart, "dedication" => $dedication);

                        }

                        $sessionstart = $log->time;
                    }

                    $previouslogtime = $log->time;

                }

                $dedication = $previouslogtime - $sessionstart;

                $totaldedication += $dedication;

                if ($dedication > 59) {

                    $sortsessions[] = array ("startsession" => $sessionstart, "dedication" => $dedication);

                }

                if ($sort) {
                    foreach ($sortsessions as $key => $sortsession) {
                        $startsessions[$key] = $sortsession["startsession"];
                        $dedications[$key] = $sortsession["dedication"];
                    }
                }

                switch ($sort) {
                    case 'startasc':
                        array_multisort($startsessions, SORT_ASC, $sortsessions);
                        break;
                    case 'startdesc':
                        array_multisort($startsessions, SORT_DESC, $sortsessions);
                        break;
                    case 'durationasc':
                        array_multisort($dedications, SORT_ASC, $sortsessions);
                        break;
                    case 'durationdesc':
                        array_multisort($dedications, SORT_DESC, $sortsessions);
                        break;
                }

                foreach ($sortsessions as $sortsession) {

                    $table->data[] = array (userdate($sortsession["startsession"]), format_dedication($sortsession["dedication"]));

                }

                echo "<br/><center>";
                print_string('totaldedication', 'block_dedication', format_dedication($totaldedication));
                echo "</center><br/><br/>";

                print_table($table);

            } else {

                echo "<br/><center>";
                print_string('totaldedication', 'block_dedication', format_dedication(0));
                echo "</center><br/><br/>";

            }
        } else {
            print_error('unknownuseraction');
        }

    } else { /// COURSE USERS TABLE
		print_r($context);
        if ($students = get_users_by_capability($context, 'moodle/course:view', '', '', '', '', '', '', false)) {

            $baseurl = $CFG->wwwroot."/blocks/dedication/dedication.php?courseid=$courseid&mintime=$mintime&maxtime=$maxtime&limit=$limit&calculateall=$calculateall&instanceid=$instanceid&pinned=$pinned&sesskey=$sesskey";

            if ($sort == 'firstnameasc') {
                $strfirstname = "<a href='$baseurl&sort=firstnamedesc'>".get_string("firstname")."</a> <img src='".$CFG->pixpath."/t/down.gif' />";
            } else if ($sort == 'firstnamedesc') {
                $strfirstname = "<a href='$baseurl&sort=firstnameasc'>".get_string("firstname")."</a> <img src='".$CFG->pixpath."/t/up.gif' />";
            } else {
                $strfirstname = "<a href='$baseurl&sort=firstnameasc'>".get_string("firstname")."</a>";
            }

            if ($sort == 'lastnameasc') {
                $strlastname = "<a href='$baseurl&sort=lastnamedesc'>".get_string("lastname")."</a> <img src='".$CFG->pixpath."/t/down.gif' />";
            } else if ($sort == 'lastnamedesc') {
                $strlastname = "<a href='$baseurl&sort=lastnameasc'>".get_string("lastname")."</a> <img src='".$CFG->pixpath."/t/up.gif' />";
            } else {
                $strlastname = "<a href='$baseurl&sort=lastnameasc'>".get_string("lastname")."</a>";
            }


            if ($calculateall) { /// COURSE USERS TABLE (WITH FIRSTNAME, LASTNAME AND DEDICATION)

                if ($sort == 'dedicationasc') {
                    $strdedication = "<a href='$baseurl&sort=dedicationdesc'>".get_string('dedication', 'block_dedication')."</a> <img src='".$CFG->pixpath."/t/down.gif' />";
                } else if ($sort == 'dedicationdesc') {
                    $strdedication = "<a href='$baseurl&sort=dedicationasc'>".get_string('dedication', 'block_dedication')."</a> <img src='".$CFG->pixpath."/t/up.gif' />";
                } else {
                    $strdedication = "<a href='$baseurl&sort=dedicationdesc'>".get_string('dedication', 'block_dedication')."</a>";
                }

                $table->head = array ("$strlastname, $strfirstname", $strdedication);
                $table->align = array ("left", "center");
                $table->width = "0%";

                $sortusers = array();

                foreach ($students as $student) {
                    if ($logs = get_records_select("log", "course = $courseid AND userid = ".$student->id." AND time >= $mintime AND time <= $maxtime", "time ASC", "id,time")) {
                        $previouslog = array_shift($logs);
                        $previouslogtime = $previouslog->time;
                        $sessionstart = $previouslogtime;
                        $dedication = 0;

                        foreach ($logs as $log) {
                            if (($log->time - $previouslogtime) > $limitinseconds) {
                                $dedication += $previouslogtime - $sessionstart;
                                $sessionstart = $log->time;
                            }
                            $previouslogtime = $log->time;
                        }
                        $dedication += $previouslogtime - $sessionstart;
                    } else {
                        $dedication = 0;
                    }
                    $sortusers[] = array ("firstname" => $student->firstname, "lastname" => $student->lastname, "dedication" => $dedication, "id" => $student->id);
                }

                if ($sort) {
                    foreach ($sortusers as $key => $sortuser) {
                        $firstnames[$key] = strtolower($sortuser["firstname"]);
                        $lastnames[$key] = strtolower($sortuser["lastname"]);
                        $dedications[$key] = $sortuser["dedication"];
                    }
                }

                switch ($sort) {
                    case 'dedicationasc':
                        array_multisort($dedications, SORT_ASC, $sortusers);
                        break;
                    case 'dedicationdesc':
                        array_multisort($dedications, SORT_DESC, $sortusers);
                        break;
                    case 'lastnameasc':
                        array_multisort($lastnames, SORT_ASC, $sortusers);
                        break;
                    case 'lastnamedesc':
                        array_multisort($lastnames, SORT_DESC, $sortusers);
                        break;
                    case 'firstnameasc':
                        array_multisort($firstnames, SORT_ASC, $sortusers);
                        break;
                    case 'firstnamedesc':
                        array_multisort($firstnames, SORT_DESC, $sortusers);
                        break;
                }

                foreach ($sortusers as $sortuser) {

                    $name = "<a href='$baseurl&userid=".$sortuser["id"]."'>".$sortuser["lastname"].', '.$sortuser["firstname"].'</a>';

                    $table->data[] = array ($name, format_dedication($sortuser["dedication"]));

                }

                if ($downloadxls) {

                    require_once("../../lib/excellib.class.php");
                    $downloadfilename = clean_filename("$course->shortname dedication.xls");
                    $workbook = new MoodleExcelWorkbook("-");
                    $workbook->send($downloadfilename);
                    //$myxls =& $workbook->add_worksheet($strgrades);
                    $myxls =& $workbook->add_worksheet("");
                    $format =& $workbook->add_format();
                    $format->set_num_format('[HH]:MM');

                    $i = 0;
                    foreach ($sortusers as $sortuser) {

                        $myxls->write_string($i, 0, $sortuser["lastname"].', '.$sortuser["firstname"]);
                        $myxls->write_number($i, 1, ($sortuser["dedication"]/86400), $format);
                        $i++;

                    }

                    $workbook->close();
                    exit;
                }

            } else { /// COURSE USERS TABLE (WITH FIRSTNAME AND LASTNAME ONLY)

                $table->head = array ("$strlastname, $strfirstname");
                $table->align = array ("left");
                $table->width = "0%";

                $baseurl = "dedication.php?courseid=$courseid&mintime=$mintime&maxtime=$maxtime&limit=$limit&instanceid=$instanceid&pinned=$pinned&sesskey=$sesskey";

                if ($sort) {

                    foreach ($students as $student) {
                        $sortusers[] = array ("firstname" => $student->firstname, "lastname" => $student->lastname, "id" => $student->id);
                    }
                    foreach ($sortusers as $key => $sortuser) {
                        $firstnames[$key] = strtolower($sortuser["firstname"]);
                        $lastnames[$key] = strtolower($sortuser["lastname"]);
                    }
                    switch ($sort) {
                        case 'lastnameasc':
                            array_multisort($lastnames, SORT_ASC, $sortusers);
                            break;
                        case 'lastnamedesc':
                            array_multisort($lastnames, SORT_DESC, $sortusers);
                            break;
                        case 'firstnameasc':
                            array_multisort($firstnames, SORT_ASC, $sortusers);
                            break;
                        case 'firstnamedesc':
                            array_multisort($firstnames, SORT_DESC, $sortusers);
                            break;
                    }
                    foreach ($sortusers as $sortuser) {
                        $name = "<a href='$baseurl&userid=".$sortuser["id"]."'>".$sortuser["lastname"].', '.$sortuser["firstname"].'</a>';
                        $table->data[] = array ($name);
                    }

                } else {
                    foreach ($students as $student) {
                        $name = "<a href='$baseurl&userid=".$student->id."'>".$student->lastname.', '.$student->firstname.'</a>';
                        $table->data[] = array ($name);
                    }
                }
            }

            $a->strmintime = userdate($mintime);
            $a->strmaxtime = userdate($maxtime);

            print_heading(get_string('dedicationall', 'block_dedication', $a));

            echo '<form name="dedication" method="post" action="dedication.php">';
            echo '<center>';
            if (!$calculateall) {
                echo '<input type="submit" value="'.get_string('showdedication', 'block_dedication').'" />';
            } else {
                echo '<input type="hidden" name="downloadxls" value="1"/>';
                echo '<input type="submit" value="'.get_string('downloaddedication', 'block_dedication').'" />';
            }
            echo '</center>';
            echo '<input type="hidden" name="courseid" value="'.$courseid.'"/>';
            echo '<input type="hidden" name="limit" value="'.$limit.'"/>';
            echo '<input type="hidden" name="mintime" value="'.$mintime.'"/>';
            echo '<input type="hidden" name="maxtime" value="'.$maxtime.'"/>';
            echo '<input type="hidden" name="calculateall" value="1"/>';
            echo '<input type="hidden" name="instanceid" value="'.$instanceid.'"/>';
            echo '<input type="hidden" name="pinned" value="'.$pinned.'"/>';
            echo '<input type="hidden" name="sesskey" value="'.$sesskey.'"/>';
            echo '</form><br><br>';

            print_table($table);

        } else {

            print_heading(get_string('nomembers', 'block_dedication'));

        }
    }

    print_footer($course);

    function format_dedication($time) {

        $a->hours = intval($time/3600);
        $a->minutes = intval(fmod($time, 3600)/60);
        return ($a->hours != 0 ? get_string('hoursandminutes', 'block_dedication', $a) : get_string('minutes', 'block_dedication', $a->minutes) );

/*      /// CALCULATE IN SECONDS
        $seconds = $time - $hours*3600 - $minutes*60;
        if ($hours) {
            $result = "$hours horas y $minutes minutos";
        } else {
            if ($minutes) {
                $result = "$minutes minutos";
            } else {
                $result = "$seconds segundos";
            }
        }
*/

    }


?>
