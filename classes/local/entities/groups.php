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

use block_dedication\local\filters\select;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\report\{column, filter};
use lang_string;

/**
 * Course group entity implementation - copied from 4.1 as we need to support 4.0 in this plugin.
 *
 * @package     block_dedication
 * @copyright   2022 Michael Kotlyar <michael.kotlyar@catalyst.net.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class groups extends base {

    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {
        return ['groups' => 'g'];
    }

    /**
     * The default title for this entity in the list of columns/conditions/filters in the report builder
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('groupentity', 'block_dedication');
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
     * Add extra columns to course report.
     * @return array
     * @throws \coding_exception
     */
    protected function get_all_columns(): array {
        $groups = $this->get_table_alias('groups');

        $columns[] = (new column(
            'groupnames',
            new lang_string('group', 'block_dedication'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$groups}.groupnames");

        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $groupsalias = $this->get_table_alias('groups');

        // Module name filter.
        $filters[] = (new filter(
            select::class,
            'group',
            new lang_string('group', 'block_dedication'),
            $this->get_entity_name(),
            "$groupsalias.groupids"
        ))
            ->add_joins($this->get_joins())
            ->set_options_callback(static function(): array {
                global $PAGE, $USER;
                if ($PAGE->course->groupmode == VISIBLEGROUPS || has_capability('moodle/site:accessallgroups', $PAGE->context)) {
                    $groups = groups_get_all_groups($PAGE->course->id, 0, $PAGE->course->defaultgroupingid);
                } else {
                    $groups = groups_get_all_groups($PAGE->course->id, $USER->id, $PAGE->course->defaultgroupingid);
                }
                $grouplist = [];
                foreach ($groups as $group) {
                    $grouplist[$group->id] = $group->name;
                }
                return $grouplist;
            });

        return $filters;
    }
}
