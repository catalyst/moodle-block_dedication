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

declare(strict_types=1);

namespace block_dedication\local\filters;

use core_reportbuilder\local\helpers\database;
use core_reportbuilder\local\filters\select as core_select;

/**
 * Select report filter
 *
 * System level reports do not support aggregation (MDL-76392) so we have to manually aggregate the data,
 * This filter has been adapted to match within a string, as opposed to core_select which matches the whole string.
 * block_dedication needs this for its group filter. Students can belong to multiple groups in a course, and the groups column
 * values look generally like ",groupid1,groupid2,groupid3,".
 * When MDL-76392 is fixed, we can probably revisit and delete this.
 *
 * @package     block_dedication
 * @copyright   2022 Michael Kotlyar <michael.kotlyar@catalyst.net.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class select extends core_select {

    /**
     * Return filter SQL
     *
     * Modified to filter with SQL LIKE in string arrays.
     *
     * @param array $values Must be delimited with a comma and also must start and end with a comma. E.g. [",val1,val2,val3,", ...]
     * @return array array of two elements - SQL query and named parameters
     */
    public function get_sql_filter(array $values): array {
        global $DB;
        $name = database::generate_param_name();

        $operator = $values["{$this->name}_operator"] ?? self::ANY_VALUE;
        $value = $values["{$this->name}_value"] ?? 0;

        $fieldsql = $this->filter->get_field_sql();
        $params = $this->filter->get_field_params();

        // Validate filter form values.
        if (!$this->validate_filter_values((int) $operator, $value)) {
            // Filter configuration is invalid. Ignore the filter.
            return ['', []];
        }

        $value = "%,$value,%";

        switch ($operator) {
            case self::EQUAL_TO:
                $fieldsql = $DB->sql_like($fieldsql, ":$name");
                $params[$name] = $value;
                break;
            case self::NOT_EQUAL_TO:
                $fieldsql = $DB->sql_like($fieldsql, ":$name", true, true, true);
                $params[$name] = $value;
                break;
            default:
                return ['', []];
        }
        return [$fieldsql, $params];
    }

    /**
     * Validate filter form values
     *
     * @param int|null $operator
     * @param mixed|null $value
     * @return bool
     */
    private function validate_filter_values(?int $operator, $value): bool {
        return !($operator === null || $value === '');
    }
}
