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

/**
 * @package moodlecore
 * @subpackage backup-moodle2
 * @copyright 2011 Philipp Hager (e0803285@gmail.com) based upon mod/checkmark's backup
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the backup steps that will be used by the backup_checkmark_activity_task
 */

/**
 * Define the complete checkmark structure for backup, with file and id annotations
 */
class backup_checkmark_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');
        // Define each element separated
        $checkmark = new backup_nested_element('checkmark', array('id'), array(
            'name', 'intro', 'introformat', 'resubmit', 'preventlate', 'emailteachers',
            'examplecount', 'examplestart', 'timedue', 'timeavailable', 'grade',
            'timemodified', 'examplenames', 'examplegrades', 'flexiblenaming'));

        $submissions = new backup_nested_element('submissions');

        $submission = new backup_nested_element('submission', array('id'), array(
            'user_id', 'timecreated', 'timemodified', 'checked', 'grade', 'submissioncomment',
            'format', 'teacher_id', 'timemarked', 'mailed'));

        // Build the tree

        $checkmark->add_child($submissions);
        $submissions->add_child($submission);

        // Define sources
        $checkmark->set_source_table('checkmark', array('id' => backup::VAR_ACTIVITYID));

        // All the rest of elements only happen if we are including user info
        if ($userinfo) {
            $submission->set_source_table('checkmark_submissions',
                                          array('checkmark_id' => backup::VAR_PARENTID));
        }

        // Define id annotations
        $checkmark->annotate_ids('scale', 'grade');
        $submission->annotate_ids('user', 'user_id');
        $submission->annotate_ids('user', 'teacher_id');

        // Define file annotations
        $checkmark->annotate_files('mod_checkmark', 'intro', null); // This file area hasn't itemid

        // Return the root element (checkmark), wrapped into standard activity structure
        return $this->prepare_activity_structure($checkmark);
    }
}
