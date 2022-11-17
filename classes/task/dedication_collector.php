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
 *
 * @package block_dedication
 * @copyright 2022 University of Canterbury
 * @author Pramith Dayananda <pramithd@catalyst.net.nz>
 */
namespace block_dedication\task;

use block_dedication\lib\manager;

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
        global $DB;
        $table = 'block_dedication';
        $lastruntime = round($this->get_last_task_runtime(),0);
        $thisruntime = time();
        // Default session limit covert to seconds
        $defaultsession_limit = (get_config('block_dedication', 'default_session_limit')) * 60; 

        // Process Start - get all active courses
        // TODO: Filter the course list more, right now course end date used to filter the list
        $sql = 'SELECT 
                    c.id,
                    c.enddate
                FROM
                    {course} c
                WHERE c.id > 0 AND c.enddate >= ?
                    order by c.id
                ';
        $courses = $DB->get_records_sql($sql, array($thisruntime), $limitfrom=0, $limitnum=0);

        
        // Go through all courses and find enrolled users
        foreach ($courses as $course) {
            // find min time
            $logs = new manager($course, $lastruntime, $thisruntime, $defaultsession_limit);

            $students = get_enrolled_users(\context_course::instance($course->id));
            $events = $logs->get_students_dedication($students);

            foreach($events as $event) {
                $data = new \stdClass();
                if($event->dedicationtime == 0) {
                    break;
                } else {
                    $data->userid = $event->user->id;
                    $data->timespent = $event->dedicationtime;
                    $data->courseid = $course->id;
                    $data->timecollected = $thisruntime;
                }

                $DB->insert_record($table, $data, $returnid=true, $bulk=false);
            }
        }

    }

    /*
     return last runtime for the cron
    */
    public function get_last_task_runtime() {
        global $DB;
        $sql = "SELECT
                    timestart
                FROM {task_log}
                    WHERE classname like '%dedication_collector%'  order by id desc limit 1";
        $log = $DB->get_record_sql($sql);

        if ($log->timestart) {
            return $log->timestart;
        }

        // return the start of the day if no rog of previous run
        return strtotime("today", time());
    }
}