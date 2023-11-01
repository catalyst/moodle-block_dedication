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
 * User dedication datasource.
 * @package block_dedication
 * @copyright 2022 University of Canterbury
 * @author Pramith Dayananda <pramithd@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


declare(strict_types=1);

namespace block_dedication\reportbuilder\datasource;

use core_reportbuilder\datasource;
use core_reportbuilder\local\entities\course;
use core_reportbuilder\local\entities\user;
use block_dedication\local\entities\dedication;

/**
 * User dedication datasource.
 */
class user_dedication extends datasource {

    /**
     * Return user friendly name of the datasource
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('user_dedication_datasource', 'block_dedication');
    }

    /**
     * Initialise report
     */
    protected function initialise(): void {
        $dedication = new dedication();
        $dedicationalias = $dedication->get_table_alias('block_dedication');
        $this->set_main_table('block_dedication', $dedicationalias);
        $this->add_entity($dedication);

        // Add core user join.
        $usercore = new user();
        $usercorealias = $usercore->get_table_alias('user');
        $usercorejoin = "JOIN {user} {$usercorealias} ON {$usercorealias}.id = {$dedicationalias}.userid";
        $this->add_entity($usercore->add_join($usercorejoin));

        $coursecore = new course();
        $coursecorealias = $coursecore->get_table_alias('course');
        $coursecorejoin = "JOIN {course} {$coursecorealias} ON {$coursecorealias}.id = {$dedicationalias}.courseid";
        $this->add_entity($coursecore->add_join($coursecorejoin));

        $this->add_columns_from_entity($usercore->get_entity_name());
        $this->add_columns_from_entity($coursecore->get_entity_name());
        $this->add_columns_from_entity($dedication->get_entity_name());

        $this->add_filters_from_entity($usercore->get_entity_name());
        $this->add_filters_from_entity($coursecore->get_entity_name());
        $this->add_filters_from_entity($dedication->get_entity_name());

        $this->add_conditions_from_entity($usercore->get_entity_name());
        $this->add_conditions_from_entity($coursecore->get_entity_name());
        $this->add_conditions_from_entity($dedication->get_entity_name());

    }


    /**
     * Return the columns that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_columns(): array {
        return ['user:fullname', 'user:username', 'course:shortname', 'dedication:timespent'];
    }

    /**
     * Return the filters that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_filters(): array {
        return ['user:fullname', 'user:username', 'course:shortname', 'user:email'];
    }

    /**
     * Return the conditions that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_conditions(): array {
        return [];
    }
}
