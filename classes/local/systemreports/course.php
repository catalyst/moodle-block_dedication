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

use block_dedication\local\entities\{dedication, enrolment, groups};
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\local\report\action;
use core_reportbuilder\system_report;
use moodle_url;
use pix_icon;

/**
 * Dedication system level report.
 *
 * @package    block_dedication
 * @copyright  2022 Canterbury University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course extends system_report {

    /**
     * Initialise report, we need to set the main table, load our entities and set columns/filters
     */
    protected function initialise(): void {
        global $DB, $PAGE, $USER;

        $courseentity = new \core_reportbuilder\local\entities\course();
        $course = $courseentity->get_table_alias('course');
        $this->add_entity($courseentity);

        $this->set_main_table('course', $course);

        // We need to ensure page context is always set, as required by output and string formatting.
        $courserecord = get_course($this->get_context()->instanceid);
        $PAGE->set_context($this->get_context());

        $enrolmententity = new enrolment();
        $userenrolment = $enrolmententity->get_table_alias('user_enrolments');
        $enrol = $enrolmententity->get_table_alias('enrol');
        $enroljoin = "LEFT JOIN {enrol} {$enrol} ON {$enrol}.courseid = {$course}.id";
        $userenrolmentjoin = " LEFT JOIN {user_enrolments} {$userenrolment} ON {$userenrolment}.enrolid = {$enrol}.id";
        $enrolmententity->add_joins([$enroljoin, $userenrolmentjoin]);
        $this->add_entity($enrolmententity);

        // Join user entity.
        $userentity = new user();
        $user = $userentity->get_table_alias('user');
        $userentity->add_joins([$enroljoin, $userenrolmentjoin]);
        $userentity->add_join("LEFT JOIN {user} {$user} ON {$userenrolment}.userid = {$user}.id AND {$user}.deleted = 0");
        $this->add_entity($userentity);

        $this->add_base_fields("{$user}.id as userid");

        $dedicationentity = new dedication();
        $dedicationalias = $dedicationentity->get_table_alias('block_dedication');
        // Note: rather than joining normally, we have to do a subselect so we can get the SUM() aggregation.
        // In future once MDL-76392 lands, we should be able to do this better.
        $dedicationentity->add_join("JOIN (
                                   SELECT SUM(timespent) as timespent, userid, courseid
                                     FROM {block_dedication} GROUP BY userid, courseid) {$dedicationalias} ON
                                          {$dedicationalias}.userid = {$user}.id and {$dedicationalias}.courseid = {$course}.id");
        $this->add_entity($dedicationentity);

        $groupnamesssql = $DB->sql_group_concat('gr.name', ', ');

        $groupidssql = $DB->sql_group_concat('gm.groupid', ',');
        $groupidssql = $DB->sql_concat("','", $groupidssql, "','");

        $groupsentity = new groups();
        $groupsalias = $groupsentity->get_table_alias('groups');

        $groupjointype = "LEFT JOIN";
        if ($PAGE->course->groupmode == VISIBLEGROUPS || has_capability('moodle/site:accessallgroups', $PAGE->context)) {
            $visiblegroups = groups_get_all_groups($PAGE->course->id, 0, $PAGE->course->defaultgroupingid, 'g.id');
        } else {
            $visiblegroups = groups_get_all_groups($PAGE->course->id, $USER->id, $PAGE->course->defaultgroupingid, 'g.id');
            $groupjointype = "JOIN";
        }

        if (!empty($visiblegroups)) {
            $vglikesql = '(';
            foreach ($visiblegroups as $vg) {
                $vglikesql .= $vg->id . ',';
            }
            $vglikesql = substr($vglikesql, 0, -1) . ')';
            $vglikesql = "AND gm.groupid IN $vglikesql";
        } else {
            $vglikesql = '';
        }

        $groupsentity->add_join("$groupjointype (
                            SELECT gm.userid, gr.courseid, $groupidssql groupids, $groupnamesssql groupnames
                            FROM {groups_members} gm
                            JOIN {groups} gr ON gr.id = gm.groupid
                            $vglikesql
                            GROUP BY gm.userid, gr.courseid
                        ) $groupsalias
                        ON $groupsalias.userid = {$user}.id AND $groupsalias.courseid = {$course}.id");

        $this->add_entity($groupsentity);

        $param1 = database::generate_param_name();
        $wheresql = "$course.id = :$param1";

        $this->add_base_condition_sql($wheresql,
            [$param1 => $courserecord->id]);

        // Now we can call our helper methods to add the content we want to include in the report.
        $this->add_columns();
        $this->add_filters();

        // Action to download individual task log.
        $this->add_action((new action(
            new moodle_url('/blocks/dedication/user.php', ['id' => $courserecord->id, 'userid' => ":userid"]),
            new pix_icon('i/search', get_string('viewsessiondurationreport', 'block_dedication')))));

        if (has_capability('report/log:view', \context_course::instance($courserecord->id))) {
            $this->add_action((new action(
                new moodle_url('/report/log/user.php', ['id' => ":userid", 'course' => $courserecord->id, 'mode' => 'all']),
                new pix_icon('i/search', get_string('alllogs')))));
        }

        // Set if report can be downloaded.
        $this->set_downloadable(true);
    }

    /**
     * Validates access to view this report
     *
     * @return bool
     */
    protected function can_view(): bool {
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
            'user:fullnamewithpicturelink',
            'groups:groupnames',
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
            'user:fullname',
            'dedication:timespent',
            'groups:group',
        ];

        $this->add_filters_from_entities($filters);
    }
}
