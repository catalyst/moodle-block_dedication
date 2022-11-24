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

declare(strict_types=1);

namespace block_dedication\local\entities;

use context_course;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use core_user\output\status_field;
use lang_string;
use stdClass;

/**
 * Course enrolment entity implementation - copied from 4.1 as we need to support 4.0 in this plugin.
 *
 * @package     block_dedication
 * @copyright   2022 David Matamoros <davidmc@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrolment extends base {

    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {
        return ['user_enrolments' => 'ue', 'enrol' => 'e'];
    }

    /**
     * The default title for this entity in the list of columns/conditions/filters in the report builder
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('enrolmententity', 'block_dedication');
    }

    /**
     * Initialise the entity
     *
     * @return base
     */
    public function initialise(): base {
        foreach ($this->get_all_columns() as $column) {
            $this->add_column($column);
        }

        // All the filters defined by the entity can also be used as conditions.
        foreach ($this->get_all_filters() as $filter) {
            $this
                ->add_filter($filter)
                ->add_condition($filter);
        }

        return $this;
    }

    /**
     * Returns list of all available columns
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $userenrolments = $this->get_table_alias('user_enrolments');
        $enrol = $this->get_table_alias('enrol');

        // Enrolment method column.
        $columns[] = (new column(
            'method',
            new lang_string('enrolmentmethod', 'block_dedication'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$enrol}.enrol, {$enrol}.id")
            ->set_is_sortable(true);

        // Enrolment time created.
        $columns[] = (new column(
            'timecreated',
            new lang_string('timecreated', 'moodle'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$userenrolments}.timecreated")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate']);

        // Enrolment status.
        $columns[] = (new column(
            'status',
            new lang_string('status', 'moodle'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field($this->get_status_field_sql(), 'status')
            ->add_field("{$userenrolments}.userid")
            ->set_is_sortable(true);

        // Role method column.
        $ctx = database::generate_alias();
        $ra = database::generate_alias();
        $r = database::generate_alias();
        $columns[] = (new column(
            'role',
            new lang_string('role', 'moodle'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join("LEFT JOIN {context} {$ctx}
                ON {$ctx}.instanceid = {$enrol}.courseid AND {$ctx}.contextlevel = " . CONTEXT_COURSE)
            ->add_join("LEFT JOIN {role_assignments} {$ra}
                ON {$ra}.contextid = {$ctx}.id AND {$ra}.userid = {$userenrolments}.userid")
            ->add_join("LEFT JOIN {role} {$r} ON {$r}.id = {$ra}.roleid")
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$r}.id, {$r}.name, {$r}.shortname, {$ctx}.instanceid")
            ->set_is_sortable(true, ["{$r}.shortname"])
            ->add_callback(static function(?string $value, stdClass $row): string {
                if (!$row->id) {
                    return '';
                }
                $context = context_course::instance($row->instanceid);
                return role_get_name($row, $context, ROLENAME_ALIAS);
            });

        return $columns;
    }

    /**
     * Generate SQL snippet suitable for returning enrolment status field
     *
     * @return string
     */
    private function get_status_field_sql(): string {
        $time = time();
        $userenrolments = $this->get_table_alias('user_enrolments');
        $enrol = $this->get_table_alias('enrol');

        return "
            CASE WHEN {$userenrolments}.status = " . ENROL_USER_ACTIVE . "
                 THEN CASE WHEN ({$userenrolments}.timestart > {$time})
                             OR ({$userenrolments}.timeend > 0 AND {$userenrolments}.timeend < {$time})
                             OR ({$enrol}.status = " . ENROL_INSTANCE_DISABLED . ")
                           THEN " . status_field::STATUS_NOT_CURRENT . "
                           ELSE " . status_field::STATUS_ACTIVE . "
                      END
                 ELSE " . status_field::STATUS_SUSPENDED . "
            END";
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        return [];
    }
}
