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
 * Tasks used in plugin.
 * @package block_dedication
 * @copyright 2022 University of Canterbury
 * @author Pramith Dayananda <pramithd@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$tasks = [
    [
        'classname' => 'block_dedication\task\dedication_collector',
        'blocking' => 0,
        'minute' => '10',
        'hour' => '01',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
        'disabled' => false
    ],
    [
        'classname' => 'block_dedication\task\cleanup',
        'blocking' => 0,
        'minute' => 'R',
        'hour' => '01',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
        'disabled' => false
    ],
];
