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
defined('MOODLE_INTERNAL') || die();

/**
 * Generate dedication reports based in passed params.
 */
use block_dedication\lib\utils;

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

    public function get_students_dedication($students) {
        global $DB;

        $rows = array();

        $where = 'courseid = :courseid AND userid = :userid AND timecreated >= :mintime AND timecreated <= :maxtime';
        $params = array(
            'courseid' => $this->course->id,
            'userid' => 0,
            'mintime' => $this->mintime,
            'maxtime' => $this->maxtime
        );

        $perioddays = ($this->maxtime - $this->mintime) / DAYSECS;
        foreach ($students as $user) {
            $daysconnected = array();
            if (is_numeric($user)) { // Only userids passed in array.
                $userid = $user;
            } else {
                $userid = $user->id;
            }
            $params['userid'] = $userid;
            $logs = utils::get_events_select($where, $params);

            if ($logs) {
                $previouslog = array_shift($logs);
                $previouslogtime = $previouslog->time;
                $sessionstart = $previouslog->time;
                $dedication = 0;
                $daysconnected[date('Y-m-d', $previouslog->time)] = 1;

                foreach ($logs as $log) {
                    if (($log->time - $previouslogtime) > $this->limit) {
                        $dedication += $previouslogtime - $sessionstart;
                        $sessionstart = $log->time;
                    }
                    $previouslogtime = $log->time;
                    $daysconnected[date('Y-m-d', $log->time)] = 1;
                }
                $dedication += $previouslogtime - $sessionstart;
            } else {
                $dedication = 0;
            }
            $rows[] = (object) array(
                'user' => $user,
                'dedicationtime' => $dedication,
                'connectionratio' => round(count($daysconnected) / $perioddays, 2),
            );
        }

        return $rows;
    }

    public function download_students_dedication($rows) {
        $groups = groups_get_all_groups($this->course->id);

        $headers = array(
            array(
                get_string('sincerow', 'block_dedication'),
                userdate($this->mintime),
                get_string('torow', 'block_dedication'),
                userdate($this->maxtime),
                get_string('perioddiffrow', 'block_dedication'),
                format_time($this->maxtime - $this->mintime),
            ),
            array(''),
            array(
                get_string('firstname'),
                get_string('lastname'),
                get_string('group'),
                get_string('dedicationrow', 'block_dedication') . ' (' . get_string('mins') . ')',
                get_string('dedicationrow', 'block_dedication'),
                get_string('connectionratiorow', 'block_dedication'),
            ),
        );

        foreach ($rows as $index => $row) {
            $rows[$index] = array(
                $row->user->firstname,
                $row->user->lastname,
                isset($groups[$row->groupid]) ? $groups[$row->groupid]->name : '',
                round($row->dedicationtime / MINSECS),
                \utils::format_dedication($row->dedicationtime),
                $row->connectionratio,
            );
        }

        $rows = array_merge($headers, $rows);

        return \block_dedication\lib\utils::generate_download("{$this->course->shortname}_dedication", $rows);
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
                            $rows[] = (object) array('start_date' => $sessionstart, 'dedicationtime' => $dedication, 'ips' => array_keys($ips));
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
                    $rows[] = (object) array('start_date' => $sessionstart, 'dedicationtime' => $dedication, 'ips' => array_keys($ips));
                }
            }

            return $rows;
        }
    }

    /**
     * Downloads user dedication with passed data.
     * @param $user
     * @return MoodleExcelWorkbook
     */
    public function download_user_dedication($user) {
        $headers = array(
            array(
                get_string('sincerow', 'block_dedication'),
                userdate($this->mintime),
                get_string('torow', 'block_dedication'),
                userdate($this->maxtime),
                get_string('perioddiffrow', 'block_dedication'),
                format_time($this->maxtime - $this->mintime),
            ),
            array(''),
            array(
                get_string('firstname'),
                get_string('lastname'),
                get_string('sessionstart', 'block_dedication'),
                get_string('dedicationrow', 'block_dedication') . ' ' . get_string('secs'),
                get_string('sessionduration', 'block_dedication'),
                'IP',
            )
        );

        $rows = $this->get_user_dedication($user);
        foreach ($rows as $index => $row) {
            $rows[$index] = array(
                $user->firstname,
                $user->lastname,
                userdate($row->start_date),
                $row->dedicationtime,
                \utils::format_dedication($row->dedicationtime),
                implode(', ', $row->ips),
            );
        }

        $rows = array_merge($headers, $rows);

        return \utils::generate_download("{$this->course->shortname}_dedication", $rows);
    }

}