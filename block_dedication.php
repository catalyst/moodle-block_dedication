<?php

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
