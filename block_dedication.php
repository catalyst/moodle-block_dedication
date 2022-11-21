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

/**
 * Dedication block definition.
 *
 * @package    block
 * @subpackage dedication
 * @copyright  2008 CICEI http://http://www.cicei.com
 * @author     2008 Borja Rubio Reyes
 *             2011 Aday Talavera Hierro (update to Moodle 2.x)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_dedication extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_dedication');
    }

    public function get_content() {
        global $OUTPUT, $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $dm = new block_dedication\lib\manager($this->page->course);
        $dedicationtime = $dm->get_user_dedication($USER, true);
        $this->content->text .= html_writer::tag('p', get_string('dedication_estimation', 'block_dedication'));
        $this->content->text .= html_writer::tag('p', block_dedication\lib\utils::format_dedication($dedicationtime));

        if (has_capability('block/dedication:viewreports', context_block::instance($this->instance->id))) {
            $this->content->footer .= html_writer::tag('hr', null);
            $url = new moodle_url('/blocks/dedication/report.php', ['courseid' => $this->page->course->id]);
            $this->content->footer .= $OUTPUT->single_button($url, get_string('timespentreport', 'block_dedication'), 'get');
        }

        return $this->content;
    }

    public function applicable_formats() {
        return ['admin' => false,
                'site-index' => false,
                'course-view' => true,
                'mod' => false,
                'my' => true];
    }

    /**
     * Controls global configurability of block.
     *
     * @return bool
     */
    public function has_config(): bool {
        return true;
    }

}
