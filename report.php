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

/**
 *
 * @package block_dedication
 * @copyright 2022 University of Canterbury
 * @author Pramith Dayananda <pramithd@catalyst.net.nz>
 */

use block_dedication\form\admin_filter;
use block_dedication\lib\manager;
use block_dedication\lib\util;

require('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

global $CFG, $PAGE;


$courseid = required_param('courseid', PARAM_ALPHANUM);
$action = optional_param('action', 'all', PARAM_ALPHANUM);
$id = optional_param('id', 0, PARAM_INT);
$download = optional_param('download', false, PARAM_BOOL);

// Current url.
$pageurl = new moodle_url('/blocks/dedication/report.php');
$pageurl->params(array(
    'courseid' => $courseid,
    'instanceid' => 11,
    'action' => $action,
    'id' => $id,
));

if (!$course = $DB->get_record('course', ['id' => $courseid])) {
    throw new \moodle_exception('invalidcourseid');
}
require_login($course);
$context = context_course::instance($course->id);
require_capability('block/dedication:viewreports', $context);

// Page format.
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->set_pagetype('course-view-' . $course->format);
//$PAGE->navbar->add(get_string('pluginname', 'block_dedication'), new moodle_url('/admin/blocks/index.php', array('courseid' => $courseid, 'instanceid' => $instanceid)));
$PAGE->set_url($pageurl);
$PAGE->set_title(get_string('reporttitle', 'block_dedication', $course->shortname));
$PAGE->set_heading($course->fullname);

// Load calculate params from form, request or set default values.

$mform = new admin_filter();
if ($mform->is_submitted()) {
    // Params from form post.
    $formdata = $mform->get_data();
    $mintime = $formdata->mintime;
    $maxtime = $formdata->maxtime;
} else {
    // Params from request or default values.
    $mintime = optional_param('mintime', $course->startdate, PARAM_INT);
    $maxtime = optional_param('maxtime', time(), PARAM_INT);
    $mform->set_data(array('mintime' => $mintime, 'maxtime' => $maxtime));
}

// Url with params for links inside tables.
$pageurl->params(array(
    'mintime' => $mintime,
    'maxtime' => $maxtime
));

// Object to store view data.
$view = new stdClass();
$view->header = array();

$tablestyles = \block_dedication\lib\utils::get_table_styles();
$view->table = new html_table();
$view->table->attributes = array('class' => $tablestyles['table_class'] . " table-$action");

switch ($action) {
    case 'group':
    case 'all':
    default:
        $students = array();
        $groups = groups_get_all_groups($course->id);

        if ($action == 'group') {
            $groupid = required_param('id', PARAM_INT);
            if (groups_group_exists($groupid)) {
                $students = groups_get_members($groupid);
            } else {
                // TODO: PUT ERROR STRING NO GROUP.
            }
        } else {
            // Get all students in this course or ordered by group.
            if ($course->groupmode == NOGROUPS) {
                //$students = get_enrolled_users(context_course::instance($course->id));
            } else {
                $students = array();
                foreach ($groups as $group) {
                    $members = groups_get_members($group->id);
                    $students = array_replace($students, $members);
                }
                // Empty groups or missconfigured, get all students anyway.
                if (!$students) {
                    $students = get_enrolled_users(context_course::instance($course->id));
                }
            }
        }

        if (!$students) {
            //print_error('noparticipants');
        }
        $dm = new \block_dedication\lib\manager($course, $mintime, $maxtime);
        $rows = $dm->get_students_dedication($students);
        if ($download) {
            $dm->download_students_dedication($rows);
            exit;
        }

        // Table formatting & total count.
        $totaldedication = 0;
        foreach ($rows as $index => $row) {
            $totaldedication += $row->dedicationtime;
            $userurl = new moodle_url('/blocks/dedication/user.php', ['id' => $course->id, 'userid' => $row->user->id]);
            $rows[$index] = array(
                $OUTPUT->user_picture($row->user, array('courseid' => $course->id)),
                html_writer::link($userurl, $row->user->firstname),
                html_writer::link($userurl, $row->user->lastname),
                \block_dedication\lib\utils::format_dedication($row->dedicationtime),
            );
        }

        $view->table->head = array('', get_string('firstname'), get_string('lastname'),
            get_string('dedicationrow', 'block_dedication'));
        $view->table->data = $rows;
        break;
}

// START PAGE: layout, headers, title, boxes...
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('timespentincourse', 'block_dedication'));

$totaldedication = $DB->get_field_sql("SELECT SUM(timespent)
                                         FROM {block_dedication}
                                        WHERE courseid = ?",
                                      ['courseid' => $courseid]);
$totalusers = $DB->get_field_sql("SELECT count(DISTINCT userid)
                                    FROM {block_dedication}
                                   WHERE courseid = ?",
                                ['courseid' => $courseid]);

$averagededication = \block_dedication\lib\utils::format_dedication(!empty($totalusers) ? $totaldedication / $totalusers : 0);
$totaldedication = \block_dedication\lib\utils::format_dedication($totaldedication);

// Form.
$mform->display();

echo html_writer::div(get_string('totaltimespent', 'block_dedication', $totaldedication));
echo html_writer::div(get_string('averagetimespent', 'block_dedication', $averagededication));

// Download button.
echo html_writer::start_tag('div', array('class' => 'download-dedication'));
echo html_writer::start_tag('p');
echo $OUTPUT->single_button(new moodle_url($pageurl, array('download' => true)), get_string('downloadexcel'), 'get');
echo html_writer::end_tag('p');
echo html_writer::end_tag('div');

// Format table headers if they exists.
if (!empty($view->table->head)) {
    $headers = array();
    foreach ($view->table->head as $header) {
        $cell = new html_table_cell($header);
        $cell->style = $tablestyles['header_style'];
        $headers[] = $cell;
    }
    $view->table->head = $headers;
}
echo html_writer::table($view->table);

// END PAGE.
echo $OUTPUT->footer();
