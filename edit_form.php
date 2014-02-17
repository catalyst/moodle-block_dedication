<?php

class block_dedication_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        require_once 'dedication_lib.php';

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('selectyesno', 'config_show_dedication', get_string('show_dedication', 'block_dedication'));
        $mform->addHelpButton('config_show_dedication', 'show_dedication', 'block_dedication');
        $mform->setDefault('config_text', 0);

        $limit_opts = array();
        for ($i = 1; $i <= 150; $i++) {
            $limit_opts[$i * 60] = $i;
        }
        $mform->addElement('select', 'config_limit', get_string('limit', 'block_dedication'), $limit_opts);
        $mform->addHelpButton('config_limit', 'limit', 'block_dedication');
        $mform->setDefault('config_limit', BLOCK_DEDICATION_DEFAULT_SESSION_LIMIT);
    }
}