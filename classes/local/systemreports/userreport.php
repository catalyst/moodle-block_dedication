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

namespace block_dedication\local\systemreports;

use block_dedication\local\entities\dedication;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\system_report;

/**
 * Dedication system level report.
 *
 * @package    block_dedication
 * @copyright  2022 Canterbury University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class userreport extends system_report {

    /**
     * Initialise report, we need to set the main table, load our entities and set columns/filters
     */
    protected function initialise(): void {
        global $PAGE, $USER;

        // We need to ensure page context is always set, as required by output and string formatting.
        $course = get_course($this->get_context()->instanceid);
        $PAGE->set_context($this->get_context());

        $userid = optional_param('userid', $USER->id, PARAM_INT);

        // Our main entity, it contains all of the column definitions that we need.
        $entitymain = new dedication();
        $entitymainalias = $entitymain->get_table_alias('block_dedication');

        $this->set_main_table('block_dedication', $entitymainalias);
        $this->add_entity($entitymain);

        $param1 = database::generate_param_name();
        $param2 = database::generate_param_name();

        $wheresql = "$entitymainalias.courseid = :$param1 AND $entitymainalias.userid = :$param2";

        $this->add_base_condition_sql($wheresql,
            [$param1 => $course->id, $param2 => $userid]);

        // Now we can call our helper methods to add the content we want to include in the report.
        $this->add_columns();
        $this->add_filters();

        // Set if report can be downloaded.
        $this->set_downloadable(true);
        $this->set_initial_sort_column('dedication:timestart', SORT_ASC);
    }

    /**
     * Validates access to view this report
     *
     * @return bool
     */
    protected function can_view(): bool {
        global $USER;
        $userid = optional_param('userid', $USER->id, PARAM_INT);
        if ($userid == $USER->id) {
            return true;
        }
        // Not viewing own report, check if can view others.
        return has_capability('block/dedication:viewreports', $this->get_context());
    }

    /**
     * Adds the columns we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     */
    public function add_columns(): void {
        $columns = [
            'dedication:timestart',
            'dedication:timespent',
        ];

        $this->add_columns_from_entities($columns);
    }

    /**
     * Adds the filters we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     */
    protected function add_filters(): void {
        $filters = [
            'dedication:timestart',
            'dedication:timespent',
        ];

        $this->add_filters_from_entities($filters);
    }
}
