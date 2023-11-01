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

use block_dedication\lib\utils;
/**
 * Dedication block definition.
 *
 * @package    block_dedication
 * @copyright  2008 CICEI http://http://www.cicei.com
 * @author     2008 Borja Rubio Reyes
 *             2011 Aday Talavera Hierro (update to Moodle 2.x)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_dedication extends block_base {

    /**
     * Initialise.
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_dedication');
    }

    /** Block level config. */
    public function specialization() {
        // Previous block versions didn't have config settings.
        if ($this->config === null) {
            $this->config = new stdClass();
        }
        // Set always show_dedication config settings to avoid errors.
        if (!isset($this->config->show_dedication)) {
            $this->config->show_dedication = 1;
        }
    }

    /**
     * Output block.
     *
     * @return stdclass
     */
    public function get_content() {
        global $USER, $COURSE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $lastruntime = get_config('block_dedication', 'lastcalculated');
        if (empty($lastruntime)) {
            $this->content->text = html_writer::tag('p', get_string('timespenttasknotrunning', 'block_dedication'),
                                                    ['class' => 'warning']);
            return $this->content;
        }
        $showtimespent = empty($this->config->show_dedication) ? false : true;
        if ($showtimespent) {
            $timespent = utils::timespent($COURSE->id, $USER->id);
            $this->content->text .= html_writer::tag('p', get_string('timespent_estimation', 'block_dedication'));
            $this->content->text .= html_writer::tag('p', $timespent);

            $lastupdated = get_config('block_dedication', 'lastcalculated');
            if (!empty($lastupdated)) {
                $this->content->footer .= html_writer::span(get_string('lastupdated', 'block_dedication',
                    userdate($lastupdated, get_string('strftimedatetimeshort', 'core_langconfig'))), 'dimmed_text');
            }
        }
        if (has_capability('block/dedication:viewreports', context_course::instance($COURSE->id))) {
            $url = new moodle_url('/blocks/dedication/index.php', ['id' => $COURSE->id]);
            $this->content->footer .= html_writer::tag('p', html_writer::link($url,
                                                       get_string('timespentreport', 'block_dedication')));
        } else if ($showtimespent) {
            $url = new moodle_url('/blocks/dedication/user.php', ['id' => $COURSE->id, 'userid' => $USER->id]);
            $this->content->footer .= html_writer::tag('p', html_writer::link($url,
                                                       get_string('timespentreport', 'block_dedication')));
        }

        return $this->content;
    }

    /**
     * Page types that can add this block.
     *
     * @return array
     */
    public function applicable_formats() {
        return ['admin' => false,
                'site-index' => true,
                'course-view' => true,
                'mod' => false,
                'my' => false];
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
