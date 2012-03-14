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

global $CFG;

require_once($CFG->libdir . '/formslib.php');

// Form to select start and end date ranges and session time
class dedication_block_selection_form extends moodleform {

    function definition() {
        $mform = & $this->_form;

        $mform->addElement('date_time_selector', 'mintime', get_string('start', 'block_dedication'));

        $mform->addElement('date_time_selector', 'maxtime', get_string('end', 'block_dedication'));

        //$mform->addElement('duration', 'limit', get_string('limit', 'block_dedication'));
        $limitoptions = array();
        for ($i=1; $i<=150; $i++) {
            $limitoptions[$i*60] = $i;
        }
        $mform->addElement('select', 'limit', get_string('limit', 'block_dedication'), $limitoptions);
        unset($limitoptions);

        // Buttons
        $this->add_action_buttons(false, get_string('calculatededication', 'block_dedication'));
    }

}

?>
