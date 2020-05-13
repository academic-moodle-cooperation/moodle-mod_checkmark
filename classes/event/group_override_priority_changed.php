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
 * The mod_checkmark_group_override_updated event.
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
 * Event for when a group date override has been updated by a teacher.
 *
 * @property-read array $other {
 *  Extra information about event.
 *
 *      - int checkmarkid: the id of the checkmark.
 *      - int groupid: the id of the group.
 *      - int groupidswap: the id of the group that swapped priorities with groupid.
 *      - int objectidswap: the id of the object that swapped priorities with objectid.
 * }
 *
 * @package   mod_checkmark
 * @author    Daniel Binder
 * @copyright 2020 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class group_override_priority_changed extends group_override {
    /**
     * Init method.
     */
    protected function init() {
        $this->data['objecttable'] = 'checkmark_overrides';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    /**
     * Get other mapping
     */
    public static function get_other_mapping() {
        $othermapped = array();
        $othermapped['checkmarkid'] = array('db' => 'checkmark', 'restore' => 'checkmark');
        $othermapped['groupid'] = array('db' => 'groups', 'restore' => 'group');
        $othermapped['groupidswap'] = array('db' => 'groups', 'restore' => 'group');
        $othermapped['objectidswap'] = array('db' => 'groups', 'restore' => 'group');

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

        if (!isset($this->other['groupidswap'])) {
            throw new \coding_exception('The \'groupidswap\' value must be set in other.');
        }

        if (!isset($this->other['objectidswap'])) {
            throw new \coding_exception('The \'objectidswap\' value must be set in other.');
        }
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventoverrideprioritychanged', 'checkmark');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' swapped the priorities of overrides for the checkmark with
                course module id '$this->contextinstanceid'. The override with the id '$this->objectid' for group
                 '{$this->other['groupid']}' has swapped priority with the override with the id '{$this->other['objectidswap']}'
                  for  group '{$this->other['groupidswap']}'.";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/checkmark/overrides.php', array('id' => $this->contextinstanceid,
                'mode' => overrideform::GROUP));
    }
}

