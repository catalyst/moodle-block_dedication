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

global $DB, $CFG, $PAGE;

require_once("../../config.php");

require_login();

// Required params
$courseid = required_param('courseid', PARAM_INT);
$instanceid = required_param('instanceid', PARAM_INT);
$sesskey = required_param('sesskey', PARAM_RAW);

// Require course login & session key
$course = $DB->get_record("course", array("id" => $courseid));
if (!$course || !$course->category) {
    print_error('invalidcourse');
}

require_course_login($course);

if (!confirm_sesskey($sesskey)) {
    print_error('invalidrequest');
}

// Require capability to use this plugin
if (!$blockinstance = $DB->get_record('block_instances', array('id' => $instanceid, 'blockname' => 'dedication'))) {
    print_error('invalidrequest');
}
require_capability('block/dedication:use', get_context_instance(CONTEXT_BLOCK, $blockinstance->id));

// Require additional libs
require_once('dedication_lib.php');
require_once('dedication_form.php');

// Obtain $mintime, $maxtime, $limit params
$mform = new dedication_block_selection_form();
$data = $mform->get_data();
if ($data) {
    // Params from form
    $mintime = $data->mintime;
    $maxtime = $data->maxtime;
    $limit = $data->limit;
    unset($data);
} else {
    // Params from petition
    $mintime = required_param('mintime', PARAM_INT);
    $maxtime = required_param('maxtime', PARAM_INT);
    $limit = required_param('limit', PARAM_INT);
}

// Other optional params
$userid = optional_param('userid', 0, PARAM_INT); // To show dedication of an user
$calculateall = optional_param('calculateall', 0, PARAM_INT); // To show dedication of all users
$downloadxls = optional_param('downloadxls', 0, PARAM_INT); // To generate XLS file with dedication data
$sort = optional_param('sort', '', PARAM_ALPHA); // To sort user list

// Base URL with to all required params
$baseurl = $CFG->wwwroot.'/blocks/dedication/dedication.php?courseid='."$courseid&instanceid=$instanceid&sesskey=$sesskey&mintime=$mintime&maxtime=$maxtime&limit=$limit";

// In $data we will put page contents inside mainly inside dedication_lib.php functions
// $data->header : header before table
// $data->table  : table contents
// $data->footer : footer after table
if ($userid) {

    // Dedication time for an user
    $data = get_user_dedication_table($courseid, $userid, $baseurl, $mintime, $maxtime, $limit, $sort);

} else {

    // Obtain all students enrolled in this course
    $students = get_enrolled_users(get_context_instance(CONTEXT_COURSE, $course->id));
    if($students) {
        if ($calculateall) {
            if ($downloadxls) {
                get_all_users_dedication_xls($students, $course, $baseurl, $mintime, $maxtime, $limit, $calculateall, $sort);
                exit;
            }
            else {
                // Dedication time for all students
                $data = get_all_users_dedication_table($students, $courseid, $baseurl, $mintime, $maxtime, $limit, $calculateall, $sort);
            }
        } else {
            // List of all users in this course
            $data = get_users_table($students, $baseurl, $mintime, $maxtime, $limit, $calculateall, $sort);
        }
    } else {
        $data->header = get_string('nomembers', 'block_dedication');
    }

}

// Page format
$PAGE->set_pagelayout('incourse');
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->set_url($FULLME);
$PAGE->set_title($course->shortname . ': ' . get_string('blocktitle', 'block_dedication'));
$PAGE->set_heading($course->fullname);
$PAGE->set_cacheable(true);

// START PAGE: layout, headers, title, boxes...
echo $OUTPUT->header();
echo $OUTPUT->box_start();
if(isset($data->header)) {
    echo $OUTPUT->heading($data->header);
} else {
    $a->strmintime = userdate($mintime);
    $a->strmaxtime = userdate($maxtime);
    echo $OUTPUT->heading(get_string('dedicationall', 'block_dedication', $a));
}

// PAGE CONTENTS
// Form with a button: to download xls or to calculate dedication for all students
if(!$userid) {
    if($calculateall) {
        $buttontext = get_string('downloaddedication', 'block_dedication');
        $buttonurl = $baseurl . '&calculateall=1&downloadxls=1';
    } else {
        $buttontext = get_string('showdedication', 'block_dedication');
        $buttonurl = $baseurl . '&calculateall=1';
    }
    echo '<form name="dedication" method="post" action="' . $buttonurl . '">';
    echo '<center>';
    echo '<input type="submit" value="' . $buttontext . '"/>';
    echo '</center>';
    echo '</form><br><br>';
}

// Table data
if(isset($data->table)) {
    echo html_writer::tag('center', html_writer::table($data->table));
}

if(isset($data->footer)) {
    echo $OUTPUT->heading($data->footer);
}

// END PAGE
echo $OUTPUT->box_end();
echo $OUTPUT->footer();

?>