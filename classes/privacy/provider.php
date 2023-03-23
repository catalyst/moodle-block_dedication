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
 * Privacy api class.
 * @package block_dedication
 * @copyright 2022 University of Canterbury
 * @author Pramith Dayananda <pramithd@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dedication\privacy;

use context_block;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\{approved_contextlist, approved_userlist, contextlist, userlist};

/**
 * Class provider
 * @package block_dedication
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Returns metadata.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'block_dedication',
            [
                'userid' => 'privacy:metadata:block_dedication:userid',
                'courseid' => 'privacy:metadata:block_dedication:courseid',
                'timespent' => 'privacy:metadata:block_dedication:timespent',
                'timestart' => 'privacy:metadata:block_dedication:timestart',
            ],
            'privacy:metadata'
        );
        return $collection;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof context_block) {
            return;
        }
        $sql = "SELECT DISTINCT(userid) FROM {block_dedication}";
        $userlist->add_from_sql('userid', $sql, []);
    }

    /**
     * Delete users' dedication items.
     *
     * @param approved_userlist $userlist
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $userids = $userlist->get_userids();
        list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('block_dedication', "userid $insql", $inparams);
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * In the case of attendance, that is any attendance where a student has had their
     * attendance taken or has taken attendance for someone else.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        return (new contextlist)->add_from_sql(
            "SELECT cx.id
            FROM {block_dedication} bd
            JOIN {context} cx ON cx.contextlevel = :blocklevel AND cx.instanceid = bd.id
            WHERE bd.userid = :userid",
            [
                'blocklevel' => CONTEXT_BLOCK,
                'userid' => $userid,
            ]
        );
    }

    /**
     * Delete all personal data for all users in the specified context.
     *
     * @param context $context Context to delete data from.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if ($context instanceof \context_course) {
            $DB->delete_records('block_dedication', ['courseid' => $context->instanceid]);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contextlist to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist as $context) {
            if ($context instanceof \context_user) {
                $DB->delete_records('block_dedication', ['userid' => $userid]);
            } else if ($context instanceof \context_course) {
                $DB->delete_records('block_dedication', ['userid' => $userid, 'courseid' => $context->instanceid]);
            }
        }
    }

    /**
     * Export all user data for the specified user, in the specified contexts, using the supplied exporter instance.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $data = [];

        $userid = (int) $contextlist->get_user()->id;
        $results = $DB->get_records('block_dedication', array('userid' => $userid));
        foreach ($results as $result) {
            $data[] = (object) [
                'courseid' => $result->courseid,
                'timespent' => $result->timespent,
                'timestart' => $result->timestart,
            ];
        }
        if (!empty($data)) {
            $data = (object) [
                'block_dedication' => $data,
            ];
            \core_privacy\local\request\writer::with_context($contextlist->current())->export_data(
                [get_string('pluginname', 'local_ace')],
                $data
            );
        }
    }

    /**
     * Get the block instance record for the specified context.
     *
     * @param   context_block $context The context to fetch
     * @return  stdClass
     */
    protected static function get_instance_from_context(context_block $context) {
        global $DB;
        return $DB->get_record('block_instances', ['id' => $context->instanceid, 'blockname' => 'dedication']);
    }
}
