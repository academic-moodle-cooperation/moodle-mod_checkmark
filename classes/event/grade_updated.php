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
 * The mod_checkmark_grade_updated event.
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_checkmark\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Grades have been updated in this event.
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grade_updated extends \core\event\base {
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
        $this->data['objecttable'] = 'checkmark_feedbacks';
    }

    /**
     * Convenience method to create event object and return it if grade has been manually updated.
     *
     * @param \stdClass $cm course module object
     * @param array $data event data
     * @return \mod_checkmark\event\grade_updated
     */
    public static function manual(\stdClass $cm, array $data) {
        $data['type'] = 'manual';
        $data['instance'] = $cm->instance;
        $event = self::create(array(
            'objectid'    => $data['feedbackid'],
            'context'     => \context_module::instance($cm->id),
            'relateduserid' => $data['userid'],
            'other'       => $data,
        ));
        return $event;
    }

    /**
     * Convenience method to create event object and return it if grade has been automatically updated.
     *
     * @param \stdClass $cm course module object
     * @param array $data event data
     * @return \mod_checkmark\event\grade_updated
     */
    public static function automatic(\stdClass $cm, array $data) {
        $data['type'] = 'automatic';
        $data['instance'] = $cm->instance;
        $event = self::create(array(
            'objectid'    => $data['feedbackid'],
            'context'     => \context_module::instance($cm->id),
            'relateduserid' => $data['userid'],
            'other'       => $data,
        ));
        return $event;
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        switch($this->data['other']['type']) {
            case 'manual':
                return "The user with id '".$this->userid."' updated the grade for user with id '".$this->data['relateduserid'].
                       "' in checkmark module with course module id '$this->contextinstanceid'.";
            break;
            case 'automatic':
                return "The user with id '".$this->userid."' updated the grade for user with id '".$this->data['relateduserid']."'".
                       " using autograding in checkmark module with course module id '$this->contextinstanceid'.";
            break;
        }
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventgradeupdated', 'checkmark');
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url("/mod/checkmark/submissions.php", array('id'  => $this->contextinstanceid,
                                                                       'tab' => 'submissions'));
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
            throw new \coding_exception('The registration_created event must define objectid and object table.');
        }
        // Make sure the context level is set to module.
        if ($this->contextlevel != CONTEXT_MODULE) {
            throw new \coding_exception('Context level must be CONTEXT_MODULE.');
        }

        if ($this->data['other']['type'] != 'manual' && $this->data['other']['type'] != 'automatic') {
            throw new \coding_exception('Grading action had to take place automatic or manual!');
        }

        if (empty($this->data['relateduserid'])) {
            throw new \coding_exception('Related user has to be set!');
        }
    }
}
