<?php

    require_once("../../config.php");
    require_once($CFG->libdir.'/blocklib.php');

    require_login();

    $pageid = required_param('pageid', PARAM_INT);
    $instanceid = required_param('instanceid', PARAM_INT);
    $sesskey = required_param('sesskey', PARAM_RAW);
    $pinned = optional_param('pinned', 0, PARAM_INT);

    if (! $course = get_record("course", "id", $pageid) ) {
        print_error('invalidcourse');
    }

    if (!$course->category) {
        print_error('invalidcourse');
    }

    require_login($course);

    if (!confirm_sesskey($sesskey)) {
        print_error('invalidrequest');
    }

    $dedicationblock = get_record('block', 'name', 'dedication');
    if ($pinned) {
        if ($blockinstance = get_record('block_pinned', 'id', $instanceid, 'blockid', $dedicationblock->id)) {
            $blockcontext = get_context_instance(CONTEXT_COURSE, $pageid);
        } else {
            print_error('invalidrequest');
        }
    } else {
        if ($blockinstance = get_record('block_instance', 'id', $instanceid, 'blockid', $dedicationblock->id, 'pageid', $course->id)) {
            $blockcontext = get_context_instance(CONTEXT_BLOCK, $blockinstance->id);
        } else {
            print_error('invalidrequest');
        }
    }

    require_capability('block/dedication:use', $blockcontext);

    $strblocktitle = get_string('blocktitle', 'block_dedication');
    if ($course->category) {
        print_header("$course->shortname: $strblocktitle", "$course->fullname",
                 "<a href=\"$CFG->wwwroot/course/view.php?id=$course->id\">$course->shortname</a> -> $strblocktitle", "");
    } else {
        print_header("$course->shortname: $strblocktitle", "$course->fullname",
                 "$strblocktitle", "");
    }

    print_heading(get_string('select', 'block_dedication'));
    print_box_start();
    include('selection.html');
    print_box_end();
    print_footer($course);

?>
