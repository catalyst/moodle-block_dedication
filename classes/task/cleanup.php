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
 * Data cleanup. Supports cli and restore type.
 * @package     block_dedication
 * @copyright   2022 University of Canterbury
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dedication\task;

/**
 * Scheduled task to delete logs with origin cli and restore.
 */
class cleanup extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).     *
     * @return string
     */
    public function get_name() {
        return get_string('cleanuptask', 'block_dedication');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $DB;
        $loglifetime = (int)get_config('block_dedication', 'allloglifetime');

        if (empty($loglifetime) || $loglifetime < 0) {
            return;
        }

        $loglifetime = time() - ($loglifetime * 3600 * 24); // Value in days.
        $lifetimep = array($loglifetime);
        $start = time();

        while ($min = $DB->get_field_select("block_dedication", "MIN(timestart)",
        "timestart < ?", $lifetimep)) {
            $params = array(min($min + 3600 * 24, $loglifetime));
            // Delete cli and restore logs.
            $DB->delete_records_select("block_dedication", "timestart < ? ", $params);
            if (time() > $start + 1200) {
                // Do not churn on log deletion for too long each run.
                break;
            }
        }

        mtrace(" Deleted old records from block_dedication table.");
    }
}

