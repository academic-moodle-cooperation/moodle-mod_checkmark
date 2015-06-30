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
 * backup/moodle2/restore_checkmark_activity_task.class.php
 *
 * @package       mod_checkmark
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Because it exists (must)!
require_once($CFG->dirroot . '/mod/checkmark/backup/moodle2/restore_checkmark_stepslib.php');

/**
 * checkmark restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
class restore_checkmark_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity!
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Checkmark only has one structure step!
        $this->add_step(new restore_checkmark_activity_structure_step('checkmark_structure',
                                                                      'checkmark.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('checkmark', array('intro'), 'checkmark');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('CHECKMARKVIEWBYID', '/mod/checkmark/view.php?id=$1',
                                           'course_module');
        $rules[] = new restore_decode_rule('CHECKMARKINDEX', '/mod/checkmark/index.php?id=$1',
                                           'course');

        return $rules;

    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * checkmark logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    static public function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('checkmark', 'add', 'view.php?id={course_module}',
                                        '{checkmark}');
        $rules[] = new restore_log_rule('checkmark', 'update', 'view.php?id={course_module}',
                                        '{checkmark}');
        $rules[] = new restore_log_rule('checkmark', 'view', 'view.php?id={course_module}',
                                        '{checkmark}');
        $rules[] = new restore_log_rule('checkmark', 'upload', 'view.php?a={checkmark}',
                                        '{checkmark}');
        $rules[] = new restore_log_rule('checkmark', 'view submission',
                                        'submissions.php.php?id={course_module}',
                                        '{checkmark}');
        $rules[] = new restore_log_rule('checkmark', 'update grades',
                                        'submissions.php.php?id={course_module}&user={user}',
                                        '{user}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    static public function define_restore_log_rules_for_course() {
        $rules = array();

        $rules[] = new restore_log_rule('checkmark', 'view all', 'index.php?id={course}', null);

        return $rules;
    }

    public function after_restore() {
        global $DB, $OUTPUT;

        // Here we try to restore corrupt calendar entries due to old checkmark events being course events!
        $courseid = $this->get_courseid();
        if ($checkmarkid = $this->get_activityid()) { // ...always set, but just to be sure to not break any course-restore!
            $checkmark = $DB->get_record('checkmark', array('id' => $checkmarkid));

            $params = array('eventtype'  => 'course',
                            'modulename' => 'checkmark',
                            'courseid'   => $courseid,
                            'timestart'  => $checkmark->timedue,
                            'name'       => '%'.$checkmark->name.'%');
            $where = $DB->sql_like('eventtype', ':eventtype')."
                     AND ".$DB->sql_like('modulename', ':modulename')."
                     AND courseid = :courseid
                     AND timestart = :timestart
                     AND ".$DB->sql_like('name', ':name');

            $events = $DB->get_records_select('event', $where, $params);
            if (count($events) == 1) {
                $event = current($events);
                // We can fix this event!
                $event->instance = $checkmarkid;
                $event->eventtype = 'due';
                $calendarevent = calendar_event::load($event->id);
                $calendarevent->update($event);
                $this->get_logger()->process(get_string('couldfixevent', 'checkmark', $event),
                                             backup::LOG_INFO);
                echo $OUTPUT->notification(get_string('couldfixevent', 'checkmark', $event),
                                           'notifysuccess');
            } else {
                foreach ($events as $event) {
                    echo $OUTPUT->notification(get_string('cantfixevent', 'checkmark', $event),
                                   'notifyproblem');
                    $this->get_logger()->process(get_string('cantfixevent', 'checkmark', $event),
                                                 backup::LOG_ERROR);
                }
            }
        }
    }
}
