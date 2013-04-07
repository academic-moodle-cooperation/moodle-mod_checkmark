<?php
// This file is made for Moodle - http://moodle.org/
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
defined('MOODLE_INTERNAL') || die;

/**
 * @package       mod_checkmark
 * @author        Philipp Hager (e0803285@gmail.com)
 * @copyright     2011 TSC TU Vienna
 * @since         Moodle 2.1
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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

        if ($userinfo) {
            $submission = new restore_path_element('checkmark_submission',
                                                   '/activity/checkmark/submissions/submission');
            $paths[] = $submission;
        }

        // Return the paths wrapped into standard activity structure!
        return $this->prepare_activity_structure($paths);
    }

    protected function process_checkmark($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timedue = $this->apply_date_offset($data->timedue);
        $data->timeavailable = $this->apply_date_offset($data->timeavailable);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        if ($data->grade < 0) { // Scale found, get mapping!
            $data->grade = -($this->get_mappingid('scale', abs($data->grade)));
        }

        // Insert the checkmark record!
        $newitemid = $DB->insert_record('checkmark', $data);
        // Immediately after inserting "activity" record, call this!
        $this->apply_activity_instance($newitemid);
    }

    protected function process_checkmark_submission($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->checkmark_id = $this->get_new_parentid('checkmark');
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->timemarked = $this->apply_date_offset($data->timemarked);

        $data->user_id = $this->get_mappingid('user', $data->user_id);
        $data->teacher_id = $this->get_mappingid('user', $data->teacher_id);

        $newitemid = $DB->insert_record('checkmark_submissions', $data);
        $this->set_mapping('checkmark_submission', $oldid, $newitemid, true); // Going to have files?
    }

    protected function after_execute() {
        // Add checkmark related files, no need to match by itemname (jst intern handled context)!
        $this->add_related_files('mod_checkmark', 'intro', null);
    }
}
