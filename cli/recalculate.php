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
 * Recalculate historical session durations.
 *
 * @package    block_dedication
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

use block_dedication\lib\utils;

require('../../../config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->libdir.'/adminlib.php');

$help =
    "Recalculate session durations.

Options:
--start=timestamp       Start from this date (unix timestamp)
--end=timestamp         End at this date (unix timestamp)
--truncate              Truncate table before running. (dangerous - deletes all existing data.)
-h, --help            Print out this help.

Example:
\$ sudo -u www-data /usr/bin/php blocks/dedication/cli/recalculate.php --start=1637478193 --end=1653116593
";

list($options, $unrecognized) = cli_get_params(
    array(
        'start'  => null,
        'end' => null,
        'truncate' => false,
        'help'    => false,
    ),
    array(
        'h' => 'help',
    )
);

if ($options['help'] || $options['start'] === null || $options['end'] === null) {
    echo $help;
    exit(0);
}


try {
    $start = validate_param($options['start'], PARAM_INT);
    $end = validate_param($options['end'], PARAM_INT);
} catch (invalid_parameter_exception $e) {
    cli_error(get_string('invalidcharacter', 'tool_replace'));
}

if ($options['truncate']) {
    $DB->delete_records('block_dedication');
    echo "Truncated block_dedication table";
}

// Sanity check to make sure data doesn't exist between the values.
$sqlwhere = "timestart > ? AND timestart < ?";
if ($DB->record_exists_select('block_dedication', $sqlwhere, [$start, $end])) {
    echo "Data already exists within the timeframe specified, you cannot import this as it may generate duplicate data ".
         "- try with truncate if you want to delete existing data";
    cli_heading(get_string('error'));
    exit(1);
}
utils::generate_stats($start, $end);
cli_heading(get_string('success'));
exit(0);
