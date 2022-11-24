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
 * Dedication report.
 *
 * @package    block_dedication
 * @copyright  2022 Canterbury University
 * @author     Dan Marsden
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once("{$CFG->libdir}/adminlib.php");
require_once($CFG->dirroot.'/grade/lib.php');

use core_reportbuilder\system_report_factory;
use block_dedication\local\systemreports\course;
use block_dedication\lib\utils;

$courseid = required_param('id', PARAM_INT);

if (!$course = $DB->get_record('course', ['id' => $courseid])) {
    throw new \moodle_exception('invalidcourseid');
}
require_login($course);
$context = context_course::instance($course->id);
require_capability('block/dedication:viewreports', $context);

$PAGE->set_url('/blocks/dedication/index.php', ['id' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->add_body_class('limitedwidth');
$PAGE->set_title("$course->fullname: ".get_string('sessionduration', 'block_dedication'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
$average = \block_dedication\lib\utils::get_average($course->id);

echo $OUTPUT->heading(get_string('timespentincourse', 'block_dedication'));
echo html_writer::div(get_string('totaltimespent', 'block_dedication', $average['total']));
echo html_writer::div(get_string('averagetimespent', 'block_dedication', $average['average']));

$config = get_config('block_dedication');

if (!empty($config->ignore_sessions_limit)) {
    echo html_writer::div(get_string('excludesessionslessthan', 'block_dedication',
                              utils::format_dedication($config->ignore_sessions_limit)));
}
if (!empty($config->lastcalculated)) {
    echo html_writer::span(get_string('lastupdated', 'block_dedication',
        userdate($config->lastcalculated, get_string('strftimedatetimeshort', 'core_langconfig'))), 'dimmed_text');
}

$report = system_report_factory::create(course::class, context_course::instance($courseid));

echo $report->output();
echo $OUTPUT->footer();
