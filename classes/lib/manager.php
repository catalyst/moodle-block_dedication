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
namespace block_dedication\lib;

use block_dedication\lib\utils;

/**
 * Generate dedication reports based in passed params.
 */
class manager {

    protected $course;
    protected $mintime;
    protected $maxtime;
    protected $limit;

    public function __construct($course, $mintime = null, $maxtime = null) {
        $this->course = $course;

        $startdate = empty($course->startdate) ? time() - (90 * DAYSECS) : $course->startdate;
        $this->mintime = !empty($mintime) ? $mintime : $startdate;
        $this->maxtime = empty($maxtime) ? time() : $maxtime;
        $limit = get_config('block_dedication', 'session_limit');
        $this->limit = empty($limit) ? HOURSECS : $limit;
    }

    public function get_user_dedication($user, $simple = false) {
        $config = get_config('block_dedication');
        if (is_numeric($user)) {
            $userid = $user;
        } else {
            $userid = $user->id;
        }
        $where = 'courseid = :courseid AND userid = :userid AND timecreated >= :mintime AND timecreated <= :maxtime';
        $params = array(
            'courseid' => $this->course->id,
            'userid' => $userid,
            'mintime' => $this->mintime,
            'maxtime' => $this->maxtime
        );
        $logs = utils::get_events_select($where, $params);

        if ($simple) {
            // Return total dedication time in seconds.
            $total = 0;

            if ($logs) {
                $previouslog = array_shift($logs);
                $previouslogtime = $previouslog->time;
                $sessionstart = $previouslogtime;

                foreach ($logs as $log) {
                    if (($log->time - $previouslogtime) > $this->limit) {
                        $dedication = $previouslogtime - $sessionstart;
                        $total += $dedication;
                        $sessionstart = $log->time;
                    }
                    $previouslogtime = $log->time;
                }
                $dedication = $previouslogtime - $sessionstart;
                $total += $dedication;
            }

            return $total;

        } else {
            // Return user sessions with details.
            $rows = array();

            if ($logs) {
                $previouslog = array_shift($logs);
                $previouslogtime = $previouslog->time;
                $sessionstart = $previouslogtime;
                $ips = array($previouslog->ip => true);

                foreach ($logs as $log) {
                    if (($log->time - $previouslogtime) > $this->limit) {
                        $dedication = $previouslogtime - $sessionstart;

                        // Ignore sessions with a really short duration.
                        if ($dedication > $config->ignore_sessions_limit) {
                            $rows[] = (object) array('start_date' => $sessionstart, 'dedicationtime' => $dedication);
                            $ips = array();
                        }
                        $sessionstart = $log->time;
                    }
                    $previouslogtime = $log->time;
                    $ips[$log->ip] = true;
                }

                $dedication = $previouslogtime - $sessionstart;

                // Ignore sessions with a really short duration.
                if ($dedication > $config->ignore_sessions_limit) {
                    $rows[] = (object) array('start_date' => $sessionstart, 'dedicationtime' => $dedication);
                }
            }

            return $rows;
        }
    }
}
