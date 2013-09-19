<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

// Generates a table with detailed dedication for a student
function get_user_dedication_table($courseid, $userid, $baseurl, $mintime, $maxtime, $limit, $sort) {
    global $DB, $OUTPUT, $CFG;

    // Check if the user is enrolled in this courseid
    if (!is_enrolled(get_context_instance(CONTEXT_COURSE, $courseid), $userid)) {
        print_error('usernotincourse');
    }

    $baseurl = $baseurl . "&userid=$userid";

    if (!$courseuser = $DB->get_record('user', array('id' => $userid))) {
        print_error('unknownuseraction');
    }

    $a = new stdClass();
    $a->firstname = $courseuser->firstname;
    $a->lastname = $courseuser->lastname;
    $a->strmintime = userdate($mintime);
    $a->strmaxtime = userdate($maxtime);

    $data = new stdClass();
    $data->header = get_string('userdedication', 'block_dedication', $a);

    $logs = $DB->get_records_select("log", "course = $courseid AND userid = " . $userid . " AND time >= $mintime AND time <= $maxtime", array(), "time ASC", "id,time");
    if ($logs) {

        if ($sort == 'startasc') {
            $strstartsession = "<a href='$baseurl&sort=startdesc'>" . get_string('sessionstart', 'block_dedication') . "</a> <img src='" . $CFG->wwwroot . "/pix/t/down.gif' />";
        } else if ($sort == 'startdesc') {
            $strstartsession = "<a href='$baseurl&sort=startasc'>" . get_string('sessionstart', 'block_dedication') . "</a> <img src='" . $CFG->wwwroot . "/pix/t/up.gif' />";
        } else {
            $strstartsession = "<a href='$baseurl&sort=startasc'>" . get_string('sessionstart', 'block_dedication') . "</a>";
        }

        if ($sort == 'durationasc') {
            $strduration = "<a href='$baseurl&sort=durationdesc'>" . get_string('duration', 'block_dedication') . "</a> <img src='" . $CFG->wwwroot . "/pix/t/down.gif' />";
        } else if ($sort == 'durationdesc') {
            $strduration = "<a href='$baseurl&sort=durationasc'>" . get_string('duration', 'block_dedication') . "</a> <img src='" . $CFG->wwwroot . "/pix/t/up.gif' />";
        } else {
            $strduration = "<a href='$baseurl&sort=durationasc'>" . get_string('duration', 'block_dedication') . "</a>";
        }

        $table = new html_table();
        $table->head = array($strstartsession, $strduration);
        $table->align = array('center', 'center');

        $previouslog = array_shift($logs);
        $previouslogtime = $previouslog->time;
        $sessionstart = $previouslogtime;
        $totaldedication = 0;

        $sortsessions = array();

        foreach ($logs as $log) {
            
            if (($log->time - $previouslogtime) > $limit) {

                $dedication = $previouslogtime - $sessionstart;

                $totaldedication += $dedication;

                if ($dedication > 59) {

                    $sortsessions[] = array("startsession" => $sessionstart, "dedication" => $dedication);
                }

                $sessionstart = $log->time;
            }

            $previouslogtime = $log->time;
        }

        $dedication = $previouslogtime - $sessionstart;

        $totaldedication += $dedication;

        if ($dedication > 59) {

            $sortsessions[] = array("startsession" => $sessionstart, "dedication" => $dedication);
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
            $table->data[] = array(userdate($sortsession["startsession"]), format_dedication($sortsession["dedication"]));
        }

        $data->footer = get_string('totaldedication', 'block_dedication', format_dedication($totaldedication));
        $data->table = $table;

    } else {

        $data->footer =  get_string('totaldedication', 'block_dedication', format_dedication(0));
    }

    return $data;
}

// Generates a table with all students names of this course
function get_users_table($students, $baseurl, $mintime, $maxtime, $limit, $calculateall, $sort) {

    // Table headers links
    if ($sort == 'firstnameasc') {
        $strfirstname = "<a href='$baseurl&calculateall=$calculateall&sort=firstnamedesc'>" . get_string("firstname") . "</a> <img src='" . $CFG->pixpath . "/t/down.gif' />";
    } else if ($sort == 'firstnamedesc') {
        $strfirstname = "<a href='$baseurl&calculateall=$calculateall&sort=firstnameasc'>" . get_string("firstname") . "</a> <img src='" . $CFG->pixpath . "/t/up.gif' />";
    } else {
        $strfirstname = "<a href='$baseurl&calculateall=$calculateall&sort=firstnameasc'>" . get_string("firstname") . "</a>";
    }

    if ($sort == 'lastnameasc') {
        $strlastname = "<a href='$baseurl&calculateall=$calculateall&sort=lastnamedesc'>" . get_string("lastname") . "</a> <img src='" . $CFG->pixpath . "/t/down.gif' />";
    } else if ($sort == 'lastnamedesc') {
        $strlastname = "<a href='$baseurl&calculateall=$calculateall&sort=lastnameasc'>" . get_string("lastname") . "</a> <img src='" . $CFG->pixpath . "/t/up.gif' />";
    } else {
        $strlastname = "<a href='$baseurl&calculateall=$calculateall&sort=lastnameasc'>" . get_string("lastname") . "</a>";
    }

    // Config table
    $table = new html_table();
    $table->head = array("$strlastname, $strfirstname");
    $table->align = array('left');

    // Sort students if needed
    if ($sort) {

        foreach ($students as $student) {
            $sortusers[] = array("firstname" => $student->firstname, "lastname" => $student->lastname, "id" => $student->id);
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
            $name = "<a href='$baseurl&userid=" . $sortuser["id"] . "'>" . $sortuser["lastname"] . ', ' . $sortuser["firstname"] . '</a>';
            $table->data[] = array($name);
        }
    } else {
        foreach ($students as $student) {
            $name = "<a href='$baseurl&userid=" . $student->id . "'>" . $student->lastname . ', ' . $student->firstname . '</a>';
            $table->data[] = array($name);
        }
    }

    $data = new stdClass();
    $data->table = $table;
    return $data;
}

// Generates a table with all students dedication
function get_all_users_dedication_table($students, $courseid, $baseurl, $mintime, $maxtime, $limit, $calculateall, $sort) {
    global $CFG;

    $sortusers = get_all_users_dedication_sorted($students, $courseid, $baseurl, $mintime, $maxtime, $limit, $calculateall, $sort);

    // Table headers links
    if ($sort == 'firstnameasc') {
        $strfirstname = "<a href='$baseurl&calculateall=$calculateall&sort=firstnamedesc'>" . get_string("firstname") . "</a> <img src='" . $CFG->wwwroot . "/pix/t/down.gif' />";
    } else if ($sort == 'firstnamedesc') {
        $strfirstname = "<a href='$baseurl&calculateall=$calculateall&sort=firstnameasc'>" . get_string("firstname") . "</a> <img src='" . $CFG->wwwroot . "/pix/t/up.gif' />";
    } else {
        $strfirstname = "<a href='$baseurl&calculateall=$calculateall&sort=firstnameasc'>" . get_string("firstname") . "</a>";
    }

    if ($sort == 'lastnameasc') {
        $strlastname = "<a href='$baseurl&calculateall=$calculateall&sort=lastnamedesc'>" . get_string("lastname") . "</a> <img src='" . $CFG->wwwroot . "/pix/t/down.gif' />";
    } else if ($sort == 'lastnamedesc') {
        $strlastname = "<a href='$baseurl&calculateall=$calculateall&sort=lastnameasc'>" . get_string("lastname") . "</a> <img src='" . $CFG->wwwroot . "/pix/t/up.gif' />";
    } else {
        $strlastname = "<a href='$baseurl&calculateall=$calculateall&sort=lastnameasc'>" . get_string("lastname") . "</a>";
    }

    if ($sort == 'dedicationasc') {
        $strdedication = "<a href='$baseurl&calculateall=$calculateall&sort=dedicationdesc'>" . get_string('dedication', 'block_dedication') . "</a> <img src='" . $CFG->wwwroot . "/pix/t/down.gif' />";
    } else if ($sort == 'dedicationdesc') {
        $strdedication = "<a href='$baseurl&calculateall=$calculateall&sort=dedicationasc'>" . get_string('dedication', 'block_dedication') . "</a> <img src='" . $CFG->wwwroot . "/pix/t/up.gif' />";
    } else {
        $strdedication = "<a href='$baseurl&calculateall=$calculateall&sort=dedicationdesc'>" . get_string('dedication', 'block_dedication') . "</a>";
    }

    $table = new html_table();
    $table->head = array("$strlastname, $strfirstname", $strdedication);
    $table->align = array('left', 'center');

    foreach ($sortusers as $sortuser) {

        $name = "<a href='$baseurl&userid=" . $sortuser["id"] . "'>" . $sortuser["lastname"] . ', ' . $sortuser["firstname"] . '</a>';

        $table->data[] = array($name, format_dedication($sortuser["dedication"]));
    }

    $data = new stdClass();
    $data->table = $table;
    return $data;
}

// Generates a XLS Excel file with all students dedication
function get_all_users_dedication_xls($students, $course, $baseurl, $mintime, $maxtime, $limit, $calculateall, $sort) {
    global $CFG;

    $sortusers = get_all_users_dedication_sorted($students, $course->id, $baseurl, $mintime, $maxtime, $limit, $calculateall, $sort);

    require_once($CFG->libdir. '/excellib.class.php');

    $downloadfilename = clean_filename($course->shortname . 'dedication.xls');
    $workbook = new MoodleExcelWorkbook('-');
    $workbook->send($downloadfilename);
    //$myxls =& $workbook->add_worksheet($strgrades);
    $myxls = & $workbook->add_worksheet('');
    $format = & $workbook->add_format();
    $format->set_num_format('[HH]:MM');

    $i = 0;
    $myxls->write_string($i, 0, get_string("lastname") . ', ' . get_string("firstname"));
    $myxls->write_string($i, 1, get_string('dedication', 'block_dedication'));
    $i++;
    foreach ($sortusers as $sortuser) {

        $myxls->write_string($i, 0, $sortuser['lastname'] . ', ' . $sortuser['firstname']);
        $myxls->write_number($i, 1, ($sortuser['dedication'] / 86400), $format);
        $i++;
    }

    $workbook->close();

    return $workbook;
}

// Generates a sorted array with all students dedication
function get_all_users_dedication_sorted($students, $courseid, $baseurl, $mintime, $maxtime, $limit, $calculateall, $sort) {
    global $DB;

    $sortusers = array();

    foreach ($students as $student) {
        if ($logs = $DB->get_records_select("log", "course = $courseid AND userid = " . $student->id . " AND time >= $mintime AND time <= $maxtime", array(), "time ASC", "id,time")) {
            $previouslog = array_shift($logs);
            $previouslogtime = $previouslog->time;
            $sessionstart = $previouslogtime;
            $dedication = 0;

            foreach ($logs as $log) {
                if (($log->time - $previouslogtime) > $limit) {
                    $dedication += $previouslogtime - $sessionstart;
                    $sessionstart = $log->time;
                }
                $previouslogtime = $log->time;
            }
            $dedication += $previouslogtime - $sessionstart;
        } else {
            $dedication = 0;
        }
        $sortusers[] = array("firstname" => $student->firstname, "lastname" => $student->lastname, "dedication" => $dedication, "id" => $student->id);
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

    return $sortusers;
}

// Formats time
function format_dedication($time) {
    $a = new stdClass();
    $a->hours = intval($time / 3600);
    $a->minutes = intval(fmod($time, 3600) / 60);
    return ($a->hours != 0 ? get_string('hoursandminutes', 'block_dedication', $a) : get_string('minutes', 'block_dedication', $a->minutes) );

    /*/// CALCULATE IN SECONDS
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
