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
 * Grade rule for 'Dummy Item' status
 *
 * @package   graderule_dummy
 * @author    Marcus Boon <marcus@catalyst-au.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace graderule_dummy;

defined('MOODLE_INTERNAL') || die('');

class helper {

    /**
     * Delete the record from the 'graderule_dummy' table.
     *
     * @param int $gradingruleid
     * @throws \dml_exception
     */
    public static function delete_instance(int $gradingruleid) {
        global $DB;

        $DB->delete_records('graderule_dummy', ['gradingruleid' => $gradingruleid]);
    }
}