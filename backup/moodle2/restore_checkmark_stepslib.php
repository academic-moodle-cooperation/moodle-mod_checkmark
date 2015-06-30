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
 * backup/moodle2/restore_checkmark_stepslib.php
 *
 * @package       mod_checkmark
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Define all the restore steps that will be used by the restore_checkmark_activity_task
 */

/**
 * Structure step to restore one checkmark activity
 */
class restore_checkmark_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $checkmark = new restore_path_element('checkmark', '/activity/checkmark');
        $paths[] = $checkmark;
        $example = new restore_path_element('checkmark_example',
                                            '/activity/checkmark/examples/example');
        $paths[] = $example;

        if ($userinfo) {
            $submission = new restore_path_element('checkmark_submission',
                                                   '/activity/checkmark/submissions/submission');
            $paths[] = $submission;
            $check = new restore_path_element('checkmark_check',
                                              '/activity/checkmark/submissions/submission/checks/check');
            $paths[] = $check;
        }

        // Return the paths wrapped into standard activity structure!
        return $this->prepare_activity_structure($paths);
    }

    protected function process_checkmark($data) {
        global $DB;

        $addexamples = false;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timedue = $this->apply_date_offset($data->timedue);
        $data->timeavailable = $this->apply_date_offset($data->timeavailable);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        if (!isset($data->cutoffdate)) {
            $data->cutoffdate = 0;
        }

        if (!empty($data->preventlate)) {
            $data->cutoffdate = $data->timedue;
        } else {
            $data->cutoffdate = $this->apply_date_offset($data->cutoffdate);
        }

        if ($data->grade < 0) { // Scale found, get mapping!
            $data->grade = -($this->get_mappingid('scale', abs($data->grade)));
        }

        if (isset($data->flexiblenaming) && isset($data->examplenames) && isset($data->examplegrades)) {
            // Prepare processing of old flexiblenaming backup!
            $addexamples = true;
            $examplenames = explode(',', $data->examplenames);
            $examplegrades = explode(',', $data->examplegrades);
            unset($data->examplenames);
            unset($data->examplegrades);
            unset($data->examplenames);
            unset($data->examplegrades);
            unset($data->flexiblenaming);
        }
        if (isset($data->flexiblenaming) && !$data->flexiblenaming
            && isset($data->examplestart) && isset($data->examplecount)) {
            // Prepare process old standard-naming backup!
            $addexamples = true;
            $examplenames = array();
            $examplegrades = array();
            $points = $data->grade / $data->examplecount;
            for ($i = $data->examplestart; $i < $data->examplestart + $data->examplecount; $i++) {
                $examplenames[] = $i;
                $examplegrades[] = $points;
            }
            unset($data->examplestart);
            unset($data->examplecount);
            unset($data->examplenames);
            unset($data->examplegrades);
            unset($data->flexiblenaming);
        }

        // Insert the checkmark record!
        $newitemid = $DB->insert_record('checkmark', $data);
        // Immediately after inserting 'activity' record, call this!
        $this->apply_activity_instance($newitemid);

        // Insert examples if it was an old backup!
        if (!empty($addexamples)) {
            foreach ($examplenames as $key => $examplename) {
                $DB->insert_record('checkmark_examples', array('checkmarkid' => $newitemid,
                                                               'name'        => $examplenames[$key],
                                                               'grade'       => $examplegrades[$key]));
            }
        }
    }

    protected function process_checkmark_submission($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        // Convert old foreign key fields to new naming scheme!
        if (isset($data->checkmark_id)) {
            $data->checkmarkid = $data->checkmark_id;
            unset($data->checkmark_id);
        }
        if (isset($data->user_id)) {
            $data->userid = $data->user_id;
            unset($data->user_id);
        }
        if (isset($data->teacher_id)) {
            $data->teacherid = $data->teacher_id;
            unset($data->teacher_id);
        }

        $data->checkmarkid = $this->get_new_parentid('checkmark');
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->timemarked = $this->apply_date_offset($data->timemarked);

        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->teacherid = $this->get_mappingid('user', $data->teacherid);

        // Prepare conversion of old db structure to new one if needed!
        if (isset($data->checked)) {
            $examples = explode(',', $data->checked);
            unset($data->checked);
        } else {
            $examples = false;
        }

        $newitemid = $DB->insert_record('checkmark_submissions', $data);
        $this->set_mapping('checkmark_submission', $oldid, $newitemid, true); // Going to have files?

        // Convert old db structure to new one if needed!
        if ($examples !== false) {
            $examplecount = $DB->count_records('checkmark_examples', array('checkmarkid' => $data->checkmarkid));
            $ids = $DB->get_fieldset_select('checkmark_examples', 'id', 'checkmarkid = ?', array($data->checkmarkid));
            for ($k = 1; $k <= $examplecount; $k++) {
                $data = new stdClass();
                $data->exampleid = $ids[$k - 1];
                $data->submissionid = $newitemid;
                if (is_array($examples) && in_array($k, $examples)) {
                    $data->state = 1;
                } else {
                    $data->state = 0;
                }
                $DB->insert_record('checkmark_checks', $data);
            }
        }
    }

    protected function process_checkmark_example($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->checkmarkid = $this->get_new_parentid('checkmark');

        $newitemid = $DB->insert_record('checkmark_examples', $data);
        $this->set_mapping('checkmark_examples', $oldid, $newitemid, true);
    }

    protected function process_checkmark_check($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->submissionid = $this->get_new_parentid('checkmark_submission');
        $data->exampleid = $this->get_mappingid('checkmark_examples', $data->exampleid);

        $newitemid = $DB->insert_record('checkmark_checks', $data);
        $this->set_mapping('checkmark_checks', $oldid, $newitemid, true);
    }

    protected function after_execute() {
        // Add checkmark related files, no need to match by itemname (jst intern handled context)!
        $this->add_related_files('mod_checkmark', 'intro', null);
    }
}
