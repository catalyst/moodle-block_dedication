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
 * This file keeps track of upgrades to the completion status block
 *
 * @package block_dedication
 * @copyright Catalyst IT
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Handles upgrading instances of this block.
 *
 * @param int $oldversion
 * @param object $block
 */
function xmldb_block_dedication_upgrade($oldversion, $block) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2022122100) {

        // Define table block_dedication to be created.
        $table = new xmldb_table('block_dedication');

        // Adding fields to table block_dedication.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timespent', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timestart', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table block_dedication.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table block_dedication.
        $table->add_index('block_dedication', XMLDB_INDEX_NOTUNIQUE, ['userid', 'courseid']);

        // Conditionally launch create table for block_dedication.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Dedication savepoint reached.
        upgrade_block_savepoint(true, 2022122100, 'dedication');
    }

    return true;
}
