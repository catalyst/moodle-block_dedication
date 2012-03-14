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

require_once("../../config.php");
require_once('dedication_form.php');

global $DB, $OUTPUT, $PAGE, $CFG;

require_login();

// Required params
$courseid = required_param('courseid', PARAM_INT);
$instanceid = required_param('instanceid', PARAM_INT);
$sesskey = required_param('sesskey', PARAM_RAW);

// Check for a valid course
$course = $DB->get_record("course", array("id" => $courseid));
if (!$course || !$course->category) {
    print_error('invalidcourse');
}

require_course_login($course);

if (!confirm_sesskey($sesskey)) {
    print_error('invalidrequest');
}

// Obtain dedication block instance context
$blockinstance = $DB->get_record('block_instances', array('id' => $instanceid, 'blockname' => 'dedication'));
if (!$blockinstance) {
    print_error('invalidrequest');
}

// Require capability to use dedication block
require_capability('block/dedication:use', get_context_instance(CONTEXT_BLOCK, $blockinstance->id));

// Page format
$PAGE->set_pagelayout('incourse');
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->set_url($FULLME);
$PAGE->set_title($course->shortname . ': ' . get_string('blocktitle', 'block_dedication'));
$PAGE->set_heading($course->fullname);
$PAGE->set_cacheable(true);

// Page start
echo $OUTPUT->header();
echo $OUTPUT->box_start();
echo $OUTPUT->heading(get_string('select', 'block_dedication'));

// Form display
$mform = new dedication_block_selection_form($CFG->wwwroot . '/blocks/dedication/dedication.php?courseid=' . $courseid . '&instanceid=' . $instanceid . '&sesskey=' . $sesskey);
$mform->set_data(array('limit' => 3600, 'mintime' => $course->startdate));
$mform->display();

// Page end
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
?>