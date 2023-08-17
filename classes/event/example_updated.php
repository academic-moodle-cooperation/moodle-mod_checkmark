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
 * The mod_checkmark_example_updated event.
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2018 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_checkmark\event;

/**
 * An example has been updated in this event.
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2018 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class example_updated extends \core\event\base {
    /**
     * Init method.
     *
     * Please override this in extending class and specify objecttable.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'checkmark_examples';
    }

    /**
     * Convenience method to create event object and return it if example has been updated.
     *
     * @param int $cmid course module ID
     * @param \stdClass $old
     * @param \stdClass $new
     * @return \core\event\base
     */
    public static function get($cmid, \stdClass $old, \stdClass $new) {
        $event = self::create([
            'objectid' => ''.$old->id,
            'context' => \context_module::instance($cmid),
            'other' => [
                'old' => ['id' => ''.$old->id, 'name' => ''.$old->name, 'grade' => ''.$old->grade],
                'new' => ['id' => ''.$new->id, 'name' => ''.$new->name, 'grade' => ''.$new->grade]
            ]
        ]);

        return $event;
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "Updated example ".$this->data['other']['old']['id']." in checkmark module with course module id '".
                $this->contextinstanceid."'. Name: '".$this->data['other']['old']['name']."'->'".
                $this->data['other']['new']['name']."' Grade: '".$this->data['other']['old']['grade']."'->'".
                $this->data['other']['new']['grade']."'";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventexampleupdated', 'checkmark');
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url("/mod/checkmark/view.php", [
            'id' => $this->contextinstanceid
        ]);
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        // Make sure this class is never used without proper object details.
        if (empty($this->objectid) || empty($this->objecttable)) {
            throw new \coding_exception('The example_updated event must define objectid and object table.');
        }

        // Make sure the context level is set to module.
        if ($this->contextlevel != CONTEXT_MODULE) {
            throw new \coding_exception('Context level must be CONTEXT_MODULE.');
        }
    }
}
