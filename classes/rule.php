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

use graderule_dummy\helper;
use core\grade\rule\rule_helper;
use core\grade\rule\rule_interface;

defined('MOODLE_INTERNAL') || die('');

class rule implements rule_interface {

    /** @var integer */
    public $dummyid;

    /** @var \grade_item */
    private $gradeitem;

    /** @var integer */
    private $gradingruleid;

    /** @var boolean */
    private $enabled;

    /** @var boolean */
    private $needsupdate;

    /**
     * Dummy constructor.
     *
     * @param bool $enabled Is this grading rule enabled?
     * @param \grade_item|null $gradeitem The grade_item object, null if it has not been created yet
     * @param int|null $gradingruleid The id in the grading_rules table, null if it has not been created yet
     * @param int $id
     */
    public function __construct(bool $enabled, ?\grade_item $gradeitem, ?int $gradingruleid) {
        $this->enabled = $enabled;
        $this->gradeitem = $gradeitem;
        $this->gradingruleid = $gradingruleid;
    }

    /**
     * Returns an instance of this rule.
     *
     * @param \grade_item|null $gradeitem The grade_item object, null if it has not been created yet
     * @param int|null $gradingruleid The id in the grading_rules table, null if it has not been created yet
     * @return rule_interface
     */
    public static function create(?\grade_item $gradeitem, ?int $gradingruleid): rule_interface {
        global $DB;

        $enabled = false;
        if (!is_null($gradeitem) && !is_null($gradingruleid)) {
            if ($graderuleisdummy = $DB->get_field('graderule_dummy', 'isdummy', ['gradingruleid' => $gradingruleid])) {
                $enabled = (bool) $graderuleisdummy;
            }
        }

        return new self($enabled, $gradeitem, $gradingruleid);
    }

    /**
     * @return string
     */
    public function get_name(): string {
        return 'dummy';
    }

    /**
     * Returns whether or not a grade item is an dummy grade item
     *
     * @return bool
     */
    public function is_enabled(): bool {
        return $this->enabled;
    }

    /**
     * We do not need to modify the final grade so just return the current value.
     *
     * @param \grade_item $item
     * @param int $userid
     * @param float $currentvalue
     * @return float
     */
    public function final_grade_modifier(int $userid, float $currentvalue): float {
        return $currentvalue;
    }

    /**
     * We do not have to modify the symbol either so just return current symbol.
     *
     * @param \grade_item  $item
     * @param int $userid
     * @param string $currentsymbol
     * @return string
     */
    public function letter_modifier($value, int $userid, string $currentsymbol): string {
        return $currentsymbol;
    }

    /**
     * Inject settings into the edit grade item form.
     *
     * @param \MoodleQuickForm $mform
     * @return void
     */
    public function edit_form_hook(\MoodleQuickForm &$mform): void {
        $element = $mform->createElement(
            'advcheckbox',
            'dummy_enabled',
            get_string('enabled', 'graderule_dummy', get_config('graderule_dummy', 'graderulename')),
            '',
            [],
            [0, 1]
        );

        // Only enable dummy for grade items that have a passing grade.
        if ($mform->elementExists('gradepass')) {
            $mform->insertElementBefore($element, 'gradepass');
        } else if ($mform->elementExists('grade_item_gradepass')) {
            $mform->insertElementBefore($element, 'grade_item_gradepass');
        }

        $mform->setDefault('dummy_enabled', $this->enabled ? 1 : 0);
    }

    /**
     * Process the form.
     *
     * @param \grade_item $gradeitem
     * @param \stdClass $data
     * @return void
     */
    public function process_form(\grade_item $gradeitem, \stdClass &$data): void {
        // This might be a new grade item that hasn't had this rule processed yet.
        $this->gradeitem = $gradeitem;
        if (property_exists($data, 'dummy_enabled') && $data->dummy_enabled == 1) {
            $this->enabled = true;
        } else {
            $this->enabled = false;
        }
    }

    /**
     * We do not have to bother with recursing for dummy.
     *
     * @return void
     */
    public function recurse(): void {

    }

    /**
     * Save the dummy status state for this grade item.
     *
     * @return void
     */
    public function save(): void {
        global $DB;

        // New grade item.
        if (is_null($this->gradingruleid)) {
            // Save to the grading_rules table.
            $this->gradingruleid = rule_helper::save_rule_association($this->gradeitem->id, $this->get_name());

            $record = new \stdClass();
            $record->gradingruleid = $this->gradingruleid;
            $record->isdummy = $this->enabled;

            // Save this dummy instance to the database.
            $this->dummyid = $DB->insert_record('graderule_dummy', $record);
        } else {
            // Get the dummy record.
            $this->dummyid = $DB->get_field('graderule_dummy', 'id', ['gradingruleid' => $this->gradingruleid]);

            $record = new \stdClass();
            $record->id = $this->dummyid;
            $record->isdummy = $this->enabled;

            // Save this dummy instance to the database.
            $DB->update_record('graderule_dummy', $record);
        }

        $this->needsupdate = true;
    }

    /**
     * Delete the dummy status state for this grade item.
     *
     * @return void
     */
    public function delete(): void {
        rule_helper::delete_rule_association($this->gradingruleid);
        helper::delete_instance($this->gradingruleid);
    }

    /**
     *
     * Whether this rule needs updating.
     *
     * @return boolean
     */
    public function needs_update(): bool {
        return $this->needsupdate;
    }
}
