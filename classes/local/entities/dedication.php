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

use lang_string;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use block_dedication\lib\utils;
use core_reportbuilder\local\filters\duration;

/**
 * Dedication entity
 * @package block_dedication
 * @copyright 2022 University of Canterbury
 * @author Pramith Dayananda <pramithd@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dedication extends base {

    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {
        return [
            'block_dedication' => 'td',
            'user' => 'auser',
            'course' => 'c',
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('entity_dedication', 'block_dedication');
    }

    /**
     * Initialise the entity, add all user fields and all 'visible' user profile fields
     *
     * @return base
     */
    public function initialise(): base {

        $columns = $this->get_all_columns();

        foreach ($columns as $column) {
            $this->add_column($column);
        }

        $filters = $this->get_all_filters();
        foreach ($filters as $filter) {
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
        $dedicationalias = $this->get_table_alias('block_dedication');

        $columns[] = (new column(
            'timespent',
            new lang_string('sessionduration', 'block_dedication'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_fields("$dedicationalias.timespent")
            ->set_type(column::TYPE_INTEGER)
            ->add_callback(static function(?int $value) {
                $format = utils::format_dedication($value);
                return $format;
            });

        $columns[] = (new column(
            'timestart',
            new lang_string('sessionstart', 'block_dedication'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_fields("$dedicationalias.timestart")
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_callback([format::class, 'userdate']);

        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {

        $filters = [];
        $dedicationalias = $this->get_table_alias('block_dedication');

        // Module name filter.
        $filters[] = (new filter(
            duration::class,
            'timespent',
            new lang_string('sessionduration', 'block_dedication'),
            $this->get_entity_name(),
            "$dedicationalias.timespent"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            date::class,
            'timestart',
            new lang_string('sessionstart', 'block_dedication'),
            $this->get_entity_name(),
            "$dedicationalias.timestart"
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }

}
