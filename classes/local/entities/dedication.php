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


use context_helper;
use context_system;
use context_user;
use core_component;
use html_writer;
use lang_string;
use moodle_url;
use stdClass;
use core_user\fields;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\boolean_select;
use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\filters\select;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\filters\user as user_filter;
use core_reportbuilder\local\helpers\user_profile_fields;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use block_dedication\lib\utils;

/**
 *
 * @package block_dedication
 * @copyright 2022 University of Canterbury
 * @author Pramith Dayananda <pramithd@catalyst.net.nz>
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
        return new lang_string('entitiy_dedication', 'block_dedication');
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


        return $this;
    }

    /**
     * Add extra columns to course report.
     * @return array
     * @throws \coding_exception
     */
    protected function get_all_columns(): array {
        $config = get_config('block_dedication');
        $dedicationalias = $this->get_table_alias('block_dedication');
        $mins = 60;

       // die();
        $columns[] = (new column(
            'timespent',
            new lang_string('column_dedicatoin', 'block_dedication'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_fields("$dedicationalias.timespent")
            ->set_type(column::TYPE_INTEGER)
            ->add_callback(static function(?int $value) {
                $format = utils::format_dedication($value);
                return $format;
            });

        return $columns;
    }

}
