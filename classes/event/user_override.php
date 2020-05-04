<?php
// This file is part of mod_checkmark for Moodle - http://moodle.org/
//
// It is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// It is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The parent class for mod_checkmark_user_override events.
 *
 * @package   mod_checkmark
 * @author    Daniel Binder
 * @copyright 2020 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_checkmark\event;
use mod_checkmark\overrideform;

defined('MOODLE_INTERNAL') || die();

/**
 * The parent class for mod_checkmark_group_override events.
 *
 * @property-read array $other {
 *  Extra information about event.
 *
 *      - int checkmarkid: the id of the checkmark.
 * }
 *
 * @package   mod_checkmark
 * @author    Daniel Binder
 * @copyright 2020 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class user_override extends \core\event\base {

    /**
     * Get objectid mapping
     */
    public static function get_objectid_mapping() {
        return array('db' => 'checkmark_overrides', 'restore' => 'checkmark_override');
    }

    /**
     * Get other mapping
     */
    public static function get_other_mapping() {
        $othermapped = array();
        $othermapped['checkmarkid'] = array('db' => 'checkmark', 'restore' => 'checkmark');

        return $othermapped;
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->relateduserid)) {
            throw new \coding_exception('The \'relateduserid\' must be set.');
        }

        if (!isset($this->other['checkmarkid'])) {
            throw new \coding_exception('The \'checkmarkid\' value must be set in other.');
        }
    }
}