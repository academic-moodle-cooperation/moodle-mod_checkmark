<?php
// This file is part of mod_checkmark for Moodle - http://moodle.org/
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
 * Generator file for mod_checkmark's PHPUnit tests
 *
 * @package   mod_checkmark
 * @category  test
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * checkmark module data generator class
 *
 * @package   mod_checkmark
 * @category  test
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_checkmark_generator extends testing_module_generator {

    /**
     * Generator method creating a mod_checkmark instance.
     *
     * @param array|stdClass|null $record (optional) Named array containing instance settings
     * @param array|null  $options (optional) general options for course module. Can be merged into $record
     * @return stdClass record from module-defined table with additional field cmid (corresponding id in course_modules table)
     */
    public function create_instance($record = null, array|null $options = null) {
        $record = (object)(array)$record;

        $timecreated = time();

        $defaultsettings = [
            'name' => 'Checkmark',
            'intro' => 'Introtext',
            'introformat' => 1,
            'alwaysshowdescription' => 1,
            'timecreated' => $timecreated,
            'timemodified' => $timecreated,
            'timedue' => $timecreated + 604800, // 1 week later!
            'gradingdue' => $timecreated + 1209600, // 2 weeks later!
            'timeavailable' => $timecreated,
            'cutoffdate' => 0,
            'resubmit' => 1,
            'emailteachers' => 1,
            'examplecount' => 10,
            'examplestart' => 1,
            'exampleprefix' => 'Example ',
            'flexiblenaming' => null,
            'examplenames' => '1,2,3,4,5,6,7,8,9,10',
            'examplegrades' => '10,10,10,10,10,10,10,10,10,10',
            'grade' => 100,
            'trackattendance' => 0,
            'attendancegradelink' => 0,
            'attendancegradebook' => 0,
            'presentationgrading' => 0,
            'presentationgrade' => 0,
            'presentationgradebook' => 0,
            'already_submit' => 0,
        ];

        foreach ($defaultsettings as $name => $value) {
            if (!isset($record->{$name})) {
                $record->{$name} = $value;
            }
        }

        return parent::create_instance($record, (array)$options);
    }

    /**
     * Create a submission in a given checkmark for a given student with given checks at the current time
     *
     * @param array $data Array containing all information
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function create_submission($data) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/checkmark/locallib.php');
        $checkmark = $this->get_test_checkmark($data);
        $userid = $data['userid'];

        $submission = $checkmark->get_submission($userid, true);
        $i = 1;
        foreach ($submission->get_examples() as $key => $example) {
            $name = $key;
            if (isset($data['example' . $i]) && ($data['example' . $i] != 0)) {
                $submission->get_example($key)->set_state(\mod_checkmark\example::CHECKED);
            } else {
                $submission->get_example($key)->set_state(\mod_checkmark\example::UNCHECKED);
            }
            $i++;
        }
        $checkmark->update_submission($submission);
    }

    /**
     * Grade a given checkmark for a given student and set attendance if activated.
     *
     * @param array $data Array containing all information
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function create_feedback($data) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/checkmark/locallib.php');
        $checkmark = $this->get_test_checkmark($data);
        $userid = $data['userid'];

        $feedback = $checkmark->prepare_new_feedback($userid);
        if (isset($data['grade'])) {
            $feedback->grade = $data['grade'];
        }
        if (isset($data['feedback'])) {
            $feedback->feedback = $data['feedback'];
        }
        if (isset($data['attendance']) && $checkmark->checkmark->trackattendance) {
            $feedback->attendance = $data['attendance'];
        } else if (isset($data['attendance'])) {
            throw new coding_exception('Attendance cannot be set because the current checkmark activity
            does not have attendace tracking enabled.');
        }
        if (isset($data['presentationgrade']) && $checkmark->checkmark->presentationgrading) {
            $feedback->presentationgrade = $data['presentationgrade'];
        } else if (isset($data['presentationgrade'])) {
            throw new coding_exception('Presentation grade cannot be set because the current checkmark activity
            does not have presentation grading enabled.');
        }
        if (isset($data['presentationfeedback']) && $checkmark->checkmark->presentationgrading) {
            $feedback->presentationfeedback = $data['presentationfeedback'];
        } else if (isset($data['presentationfeedback'])) {
            throw new coding_exception('Presentation feedback cannot be set because the current checkmark activity
            does not have presentation grading enabled.');
        }

        $feedback->timemodified = time();
        try {
            $DB->update_record('checkmark_feedbacks', $feedback);
        } catch (Exception $e) {
            var_dump($feedback);
            throw new coding_exception($e->getMessage());
        }
        // Trigger grade event!
        $checkmark->update_grade($feedback);
        // Trigger the event!
        \mod_checkmark\event\grade_updated::manual($checkmark->cm, ['userid' => $feedback->userid,
                'feedbackid' => $feedback->id, ])->trigger();
    }

    /**
     * Helper method to discover a checkmark matching the given data.
     *
     * @param array $data Array containing all information
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    private function get_test_checkmark($data) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/checkmark/locallib.php');
        if (!isset($data['userid'])) {
            throw new coding_exception('Must specify user (id) when creating a checkmark submission.');
        }
        if (!isset($data['checkmark'])) {
            throw new coding_exception('Must specify checkmark when creating a checkmark submission.');
        }
        if (!$cm = get_coursemodule_from_instance('checkmark', $data['checkmark'])) {
            throw new coding_exception('Invalid checkmark instance');
        }
        return new checkmark($cm->id);
    }

}
