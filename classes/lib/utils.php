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

class utils {

    public static $logstores = array('logstore_standard');

    /**
     * Return formatted events from logstores.
     * @param string $selectwhere
     * @param array $params
     * @return array
     */
    public static function get_events_select($selectwhere, array $params) {
        $return = array();

        static $allreaders = null;

        if (is_null($allreaders)) {
            $allreaders = get_log_manager()->get_readers();
        }

        $processedreaders = 0;

        foreach (self::$logstores as $name) {
            if (isset($allreaders[$name])) {
                $reader = $allreaders[$name];
                $events = $reader->get_events_select($selectwhere, $params, 'timecreated ASC', 0, 0);
                foreach ($events as $event) {
                    // Note: see \core\event\base to view base class of event.
                    $obj = new \stdClass();
                    $obj->time = $event->timecreated;
                    $obj->ip = $event->get_logextra()['ip'];
                    $return[] = $obj;
                }
                if (!empty($events)) {
                    $processedreaders++;
                }
            }
        }

        // Sort mixed array by time ascending again only when more of a reader has added events to return array.
        if ($processedreaders > 1) {
            usort($return, function($a, $b) {
                return $a->time > $b->time;
            });
        }

        return $return;
    }

    /**
     * Formats time based in Moodle function format_time($totalsecs).
     * @param int $totalsecs
     * @return string
     */
    public static function format_dedication($totalsecs) {
        $totalsecs = abs($totalsecs);

        $str = new \stdClass();
        $str->hour = get_string('hour');
        $str->hours = get_string('hours');
        $str->min = get_string('min');
        $str->mins = get_string('mins');
        $str->sec = get_string('sec');
        $str->secs = get_string('secs');

        $hours = floor($totalsecs / HOURSECS);
        $remainder = $totalsecs - ($hours * HOURSECS);
        $mins = floor($remainder / MINSECS);
        $secs = round($remainder - ($mins * MINSECS), 2);

        $ss = ($secs == 1) ? $str->sec : $str->secs;
        $sm = ($mins == 1) ? $str->min : $str->mins;
        $sh = ($hours == 1) ? $str->hour : $str->hours;

        $ohours = '';
        $omins = '';
        $osecs = '';

        if ($hours) {
            $ohours = $hours . ' ' . $sh;
        }
        if ($mins) {
            $omins = $mins . ' ' . $sm;
        }
        if ($secs) {
            $osecs = $secs . ' ' . $ss;
        }

        if ($hours) {
            return trim($ohours . ' ' . $omins);
        }
        if ($mins) {
            return trim($omins . ' ' . $osecs);
        }
        if ($secs) {
            return $osecs;
        }
        return get_string('none');
    }

    /**
     * @param string[] $ips
     * @return string
     */
    public static function format_ips($ips) {
        return implode(', ', array_map('block_dedication_utils::link_ip', $ips));
    }

    /**
     * Generates a linkable ip.
     * @param string $ip
     * @return string
     */
    public static function link_ip($ip) {
        return \html_writer::link("http://en.utrace.de/?query=$ip", $ip, array('target' => '_blank'));
    }

    /**
     * Return table styles based on current theme.
     * @return array
     */
    public static function get_table_styles() {
        global $PAGE;

        // Twitter Bootstrap styling.
        $is_bootstrap_theme = ($PAGE->theme->name === 'boost') || count(array_intersect(array('boost', 'bootstrapbase'), $PAGE->theme->parents)) > 0;
        if ($is_bootstrap_theme) {
            $styles = array(
                'table_class' => 'table table-bordered table-hover table-sm table-condensed table-dedication',
                'header_style' => 'background-color: #333; color: #fff;'
            );
        } else {
            $styles = array(
                'table_class' => 'table-dedication',
                'header_style' => ''
            );
        }

        return $styles;
    }

    /**
     * Generates generic Excel file for download.
     * @param string $downloadname
     * @param array $rows
     * @return MoodleExcelWorkbook
     * @throws coding_exception
     */
    public static function generate_download($downloadname, $rows) {
        global $CFG;

        require_once($CFG->libdir . '/excellib.class.php');

        $workbook = new \MoodleExcelWorkbook(clean_filename($downloadname));

        $myxls = $workbook->add_worksheet(get_string('pluginname', 'block_dedication'));

        $rowcount = 0;
        foreach ($rows as $row) {
            foreach ($row as $index => $content) {
                $myxls->write($rowcount, $index, $content);
            }
            $rowcount++;
        }

        $workbook->close();

        return $workbook;
    }

    /** Generate stats and store in caching table
     *
     */
    public static function generate_stats($timestart, $timeend) {
        if ($timeend - $timestart > WEEKSECS) {
            // Break it down into bite sized weeks.
            while ($timeend - $timestart > WEEKSECS) {
                $timechunkend = $timestart + WEEKSECS;
                self::generate_stats($timestart, $timechunkend);
                $timestart = $timechunkend;
            }
        } else {
            self::calculate($timestart, $timeend);
        }

    }

    public static function calculate($timestart, $timeend) {
        global $DB;
        mtrace("calculating stats from: " . userdate($timestart) . " to :". userdate($timeend));
        // TODO: accessing logs data uses the log store reader classes - we should look at converting this to do something similar if possible.
        // Get list of courses and users we want to calculate for.
        $sql = "SELECT distinct ". $DB->sql_concat_join("':'", ['courseid', 'userid'])." as tmpid, courseid, userid
                  FROM {logstore_standard_log}
                 WHERE timecreated >= :timestart AND timecreated < :timeend AND userid > 0 AND courseid > 0";
        $records = $DB->get_recordset_sql($sql, ['timestart' => $timestart, 'timeend' => $timeend]);
        $courses = [];
        foreach ($records as $record) {
            if (!isset($courses[$record->courseid])) {
                $courses[$record->courseid] = [];
            }
            $courses[$record->courseid][] = $record->userid;
        }
        $records->close();

        foreach ($courses as $courseid => $users) {
            $course = $DB->get_record('course', ['id' => $courseid]);
            if (empty($course)) {
                mtrace("Course $courseid not found, it may have been deleted.");
                continue;
            }
            $logs = new manager($course, $timestart, $timeend);

            $events = $logs->get_students_dedication($users);

            $records = [];
            foreach ($events as $event) {
                $data = new \stdClass();
                if ($event->dedicationtime == 0) {
                    break;
                } else {
                    if (is_numeric($event->user)) {
                        // Sometimes full user object is passed, other times just the id.
                        $data->userid = $event->user;
                    } else {
                        $data->userid = $event->user->id;
                    }
                    $data->timespent = $event->dedicationtime;
                    $data->courseid = $course->id;
                    $data->timestart = $timestart;
                    $data->timeend = $timeend;
                }
                $records[] = $data;
            }
        }
        if (!empty($records)) {
            $DB->insert_records('block_dedication', $records);
            // Save the last time we saved some records if we haven't stored newer items yet.
            // Basically prevents cli process for old stuff from saving data.
            if (get_config('block_dedication', 'lastcalculated') < $timeend) {
                set_config('lastcalculated', $timeend, 'block_dedication');
            }
        }
    }
}
