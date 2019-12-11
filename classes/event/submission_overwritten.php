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
 * The mod_checkmark_submission_overwrutten event.
 *
 * @package   mod_checkmark
 * @author    Daniel Binder
 * @copyright 2019 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_checkmark\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Event for when the submission has been overwritten by the teacher.
 *
 * @package   mod_checkmark
 * @author    Daniel Binder
 * @copyright 2019 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submission_overwritten extends \core\event\base {
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
        $this->data['objecttable'] = 'checkmark_submissions';
    }

    /**
     * Convenience method to create event from object.
     *
     * @param \stdClass $cm course module object
     * @param \mod_checkmark\submission $submission submission object
     * @return object event object
     */
    public static function create_from_object(\stdClass $cm, \mod_checkmark\submission $submission) {
        // Trigger overview event.
        $event = self::create(array(
                'objectid'      => $submission->get_id(),
                'context'       => \context_module::instance($cm->id),
                'relateduserid' => $submission->get_userid(),
        ));
        $event->add_record_snapshot('checkmark_submissions', $submission->export_for_snapshot());
        return $event;
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '".$this->userid."' has overwritten the submission for user with id '".$this->relateduserid.
                "' in ".$this->objecttable." with course module id '$this->contextinstanceid'.";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventsubmissionoverwritten', 'checkmark');
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
     * Return the legacy event log data.
     *
     * @return array|null
     */
    protected function get_legacy_logdata() {
        $submission = $this->get_record_snapshot('checkmark_submissions', $this->objectid);
        return array($this->courseid, 'checkmark', 'update submission', $this->get_url(),
                $submission->checkmarkid, $this->contextinstanceid);
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

        if (empty($this->data['relateduserid'])) {
            throw new \coding_exception('Related user has to be set!');
        }
    }
}
