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
 * Dedication block definition
 *
 * @package    block
 * @subpackage dedication
 * @copyright  2008 CICEI http://http://www.cicei.com
 * @author     2008 Borja Rubio Reyes
 *             2011 Aday Talavera Hierro (update to Moodle 2.x)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_dedication extends block_list {

    function init() {
        $this->title = get_string('blocktitle','block_dedication');
        $this->version = 2008112701;
    }

    function check_permission() {

        if (empty($this->instance->pinned)) {
            $context = get_context_instance(CONTEXT_BLOCK, $this->instance->id);
        } else {
            $context = get_context_instance(CONTEXT_COURSE, $this->instance->pageid);
        }

        if (has_capability('block/dedication:use', $context)) {
            return true;
        } else {
            return false;
        }
    }

    function get_content() {

        if($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        if (empty($this->instance) || ! $this->check_permission() ) {
            $this->content = '';
        } else {
            $this->load_content();
        }

        return $this->content;
    }

    function load_content() {
        global $CFG, $USER;

        if ($this->check_permission()) {
            if (isset($this->instance->pinned)) {
                $script = $CFG->wwwroot.'/blocks/dedication/selection.php?pageid='.$this->instance->pageid.'&instanceid='.$this->instance->id.'&pinned='.$this->instance->pinned.'&sesskey='.$USER->sesskey;
            } else {
                $script = $CFG->wwwroot.'/blocks/dedication/selection.php?pageid='.$this->instance->pageid.'&instanceid='.$this->instance->id.'&sesskey='.$USER->sesskey;
            }
            $this->content->items[] = '<a title="'.get_string('calculate','block_dedication').'" href="'.$script.'">'.get_string('calculate','block_dedication').'</a>';
        }
    }

    function applicable_formats() {
        return array('course' => true);
    }

}

?>
