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

namespace mod_checkmark;

/**
 * Test helper exposing the protected restore processor with deterministic mappings.
 *
 * @package     mod_checkmark
 * @copyright   2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testable_restore_checkmark_activity_structure_step extends \restore_checkmark_activity_structure_step {
    /** @var int Checkmark instance id. */
    private $checkmarkid;

    /**
     * Constructor.
     *
     * @param int $checkmarkid Checkmark instance id.
     */
    public function __construct(int $checkmarkid) {
        parent::__construct('checkmark_structure', 'checkmark.xml');
        $this->checkmarkid = $checkmarkid;
    }

    /**
     * Expose the protected feedback processor for testing.
     *
     * @param object $data Restore feedback data.
     */
    public function restore_feedback(object $data): void {
        $this->process_checkmark_feedback($data);
    }

    /**
     * Return the current checkmark as parent.
     *
     * @param string $itemname Mapping item name.
     * @return int|null New parent id.
     */
    public function get_new_parentid($itemname) {
        if ($itemname === 'checkmark') {
            return $this->checkmarkid;
        }

        return null;
    }

    /**
     * Keep test user ids stable.
     *
     * @param string $itemname Mapping item name.
     * @param int $oldid Old item id.
     * @param mixed $ifnotfound Value to return if not found.
     * @return int New item id.
     */
    public function get_mappingid($itemname, $oldid, $ifnotfound = false) {
        return $oldid;
    }

    /**
     * Keep test dates stable.
     *
     * @param int $value Date value.
     * @return int Date value.
     */
    public function apply_date_offset($value) {
        return $value;
    }

    /**
     * Avoid writing backup id mappings in this narrow unit test.
     *
     * @param string $itemname Mapping item name.
     * @param int $oldid Old item id.
     * @param int $newid New item id.
     * @param bool $restorefiles Whether files are restored.
     * @param int|null $filesctxid Files context id.
     * @param int|null $parentid Parent id.
     */
    public function set_mapping($itemname, $oldid, $newid, $restorefiles = false, $filesctxid = null, $parentid = null) {
    }
}
