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
 * Task that generates session duration data for reports.
 * @package block_dedication
 * @copyright 2022 University of Canterbury
 * @author Pramith Dayananda <pramithd@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_dedication\task;

use block_dedication\lib\utils;

/**
 * Dedication data generator task.
 */
class dedication_collector extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('collect_dedication', 'block_dedication');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        $lastruntime = get_config('block_dedication', 'lastcalculated');
        if (empty($lastruntime)) {
            mtrace("This is the first time this task has been run, calculate data for last 12 weeks");
            // First time this task has been run - lets pull in last 12 weeks of time calculations.
            $lastruntime = time() - WEEKSECS * 12;
        } else if ($lastruntime > time() - (2 * HOURSECS)) {
            mtrace("This task can only be triggered every 2 hours");
            return;
        }
        utils::generate_stats($lastruntime, time());
    }
}
