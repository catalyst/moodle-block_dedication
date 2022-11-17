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

namespace block_dedication\form;

defined('MOODLE_INTERNAL') || die();

use coding_exception;
use dml_exception;
use html_writer;
use moodle_exception;
use moodleform;
use stdClass;

global $CFG, $DB;
require_once($CFG->libdir . '/formslib.php');

// Form to select start and end date ranges and session time.
class admin_filter extends moodleform {

    public function definition() {

        
        $mform = & $this->_form;

        $courseoptions = array();
        $courses = get_courses();
        foreach ($courses as $course) {
            if ($course->id != 1) {
                $courseoptions[$course->id] = $course->fullname;
            }
        }
        $mform->addElement('select', 'courseid', get_string('admin_filter_courseid', 'block_dedication'), $courseoptions);
        $mform->addHelpButton('courseid', 'admin_filter_courseid', 'block_dedication');

        $mform->addElement('header', 'general', get_string('admin_filter_form', 'block_dedication'));
        $mform->addHelpButton('general', 'admin_filter_form', 'block_dedication');

        $mform->addElement('html', html_writer::tag('p', get_string('admin_filter_form_text', 'block_dedication')));

        $mform->addElement('date_time_selector', 'mintime', get_string('admin_filter_mintime', 'block_dedication'));
        $mform->addHelpButton('mintime', 'admin_filter_mintime', 'block_dedication');

        $mform->addElement('date_time_selector', 'maxtime', get_string('admin_filter_maxtime', 'block_dedication'));
        $mform->addHelpButton('maxtime', 'admin_filter_maxtime', 'block_dedication');

        $limitoptions = array();
        for ($i = 1; $i <= 150; $i++) {
            $limitoptions[$i * 60] = $i;
        }
        $mform->addElement('select', 'limit', get_string('admin_filter_limit', 'block_dedication'), $limitoptions);
        $mform->addHelpButton('limit', 'admin_filter_limit', 'block_dedication');

        // Buttons.
        $this->add_action_buttons(false, get_string('admin_filter_submit', 'block_dedication'));
    }

}
