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
use core_course\reportbuilder\local\entities\course_category;
use core_course\reportbuilder\local\entities\enrolment;
use core_enrol\reportbuilder\local\entities\enrol;
use core_reportbuilder\local\entities\user;
use core_role\reportbuilder\local\entities\role;
use block_dedication\local\entities\dedication;
use core_group\reportbuilder\local\entities\group;
use core_cohort\reportbuilder\local\entities\cohort;
use core_course\reportbuilder\local\entities\access;
use core_course\reportbuilder\local\entities\completion;
use core_reportbuilder\local\helpers\database;

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
        $userentity = new user();
        $user = $userentity->get_table_alias('user');
        $usercorejoin = "JOIN {user} {$user} ON {$user}.id = {$dedicationalias}.userid";
        $this->add_entity($userentity->add_join($usercorejoin));

        $courseentity = new course();
        $course = $courseentity->get_table_alias('course');
        $context = $courseentity->get_table_alias('context');
        $coursecorejoin = "JOIN {course} {$course} ON {$course}.id = {$dedicationalias}.courseid";
        $this->add_entity($courseentity->add_join($coursecorejoin));

        // Join the course category entity.
        $coursecatentity = new course_category();
        $categories = $coursecatentity->get_table_alias('course_categories');
        $this->add_entity($coursecatentity
            ->add_join("JOIN {course_categories} {$categories} ON {$categories}.id = {$course}.category"));

        // Join the enrolment method entity.
        $enrolentity = new enrol();
        $enrol = $enrolentity->get_table_alias('enrol');
        $this->add_entity($enrolentity
            ->add_join("LEFT JOIN {enrol} {$enrol} ON {$enrol}.courseid = {$course}.id"));

        // Join the enrolments entity.
        $enrolmententity = (new enrolment())
            ->set_table_alias('enrol', $enrol);
        $userenrolment = $enrolmententity->get_table_alias('user_enrolments');
        $this->add_entity($enrolmententity
            ->add_joins($enrolentity->get_joins())
            ->add_join("LEFT JOIN {user_enrolments} {$userenrolment} ON {$userenrolment}.enrolid = {$enrol}.id AND {$userenrolment}.userid = {$user}.id"));

        // Join the role entity.
        $roleentity = (new role())
            ->set_table_alias('context', $context);
        $role = $roleentity->get_table_alias('role');
        $this->add_entity($roleentity
            ->add_joins($userentity->get_joins())
            ->add_join($courseentity->get_context_join())
            ->add_join("LEFT JOIN {role_assignments} ras ON ras.contextid = {$context}.id AND ras.userid = {$user}.id")
            ->add_join("LEFT JOIN {role} {$role} ON {$role}.id = ras.roleid")
        );

        // Join group entity.
        $groupentity = (new group())
            ->set_table_alias('context', $context);
        $groups = $groupentity->get_table_alias('groups');

        // Sub-select for all course group members.
        $groupsinnerselect = "
            SELECT grs.*, grms.userid
              FROM {groups} grs
              JOIN {groups_members} grms ON grms.groupid = grs.id";

        $this->add_entity($groupentity
            ->add_join($courseentity->get_context_join())
            ->add_joins($userentity->get_joins())
            ->add_join("
                LEFT JOIN ({$groupsinnerselect}) {$groups}
                       ON {$groups}.courseid = {$course}.id AND {$groups}.userid = {$user}.id")
        );

        // Join cohort entity.
        $cohortentity = new cohort();
        $cohortalias = $cohortentity->get_table_alias('cohort');
        $cohortmemberalias = database::generate_alias();
        $this->add_entity($cohortentity
            ->add_joins($userentity->get_joins())
            ->add_joins([
                "LEFT JOIN {cohort_members} {$cohortmemberalias} ON {$cohortmemberalias}.userid = {$user}.id",
                "LEFT JOIN {cohort} {$cohortalias} ON {$cohortalias}.id = {$cohortmemberalias}.cohortid",
            ])
        );

        // Join completion entity.
        $completionentity = (new completion())
            ->set_table_aliases([
                'course' => $course,
                'user' => $user,
            ]);
        $completion = $completionentity->get_table_alias('course_completion');
        $this->add_entity($completionentity
            ->add_joins($userentity->get_joins())
            ->add_join("
                LEFT JOIN {course_completions} {$completion}
                       ON {$completion}.course = {$course}.id AND {$completion}.userid = {$user}.id")
        );

        // Join course access entity.
        $accessentity = (new access())
            ->set_table_alias('user', $user);
        $lastaccess = $accessentity->get_table_alias('user_lastaccess');
        $this->add_entity($accessentity
            ->add_joins($userentity->get_joins())
            ->add_join("
                LEFT JOIN {user_lastaccess} {$lastaccess}
                       ON {$lastaccess}.userid = {$user}.id AND {$lastaccess}.courseid = {$course}.id"));

        $this->add_all_from_entities();
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
