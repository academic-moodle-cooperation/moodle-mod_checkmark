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
 * backup/moodle2/backup_checkmark_stepslib.php Define all the backup steps that will be used by the backup_checkmark_activity_task
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Define the complete checkmark structure for backup, with file and id annotations
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_checkmark_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define backup structure
     *
     * @return object standard activity structure
     */
    protected function define_structure() {

        // Are we including userinfo?
        $userinfo = $this->get_setting_value('userinfo');
        // Define each element separated!
        $checkmark = new backup_nested_element('checkmark', array('id'), array(
            'name', 'intro', 'introformat', 'alwaysshowdescription', 'resubmit', 'timeavailable', 'timedue', 'cutoffdate',
            'gradingdue', 'emailteachers', 'exampleprefix', 'grade', 'trackattendance', 'attendancegradelink',
            'attendancegradebook', 'presentationgrading', 'presentationgrade', 'presentationgradebook', 'timemodified', 'flexiblenaming'));

        $overrides = new backup_nested_element('overrides');

        $override = new backup_nested_element('override', ['id'], ['checkmarkid', 'groupid', 'userid', 'timeavailable', 'timedue',
            'cutoffdate', 'timecreated', 'modifierid', 'grouppriority']);

        $submissions = new backup_nested_element('submissions');

        $submission = new backup_nested_element('submission', array('id'), array(
            'userid', 'timecreated', 'timemodified'));

        $feedbacks = new backup_nested_element('feedbacks');

        $feedback = new backup_nested_element('feedback', array('id'), array(
            'userid', 'grade', 'feedback', 'format', 'attendance', 'presentationgrade', 'presentationfeedback',
            'presentationformat', 'graderid', 'mailed', 'timecreated', 'timemodified'));

        $examples = new backup_nested_element('examples');

        $example = new backup_nested_element('example', array('id'), array('checkmarkid', 'name', 'grade'));

        $checks = new backup_nested_element('checks');

        $check = new backup_nested_element('check', array('id'), array('checkmarkid', 'submissionid', 'exampleid', 'state'));

        // Now build the tree!
        $checkmark->add_child($examples);
        $examples->add_child($example);
        $checkmark->add_child($overrides);
        $overrides->add_child($override);
        $checkmark->add_child($submissions);
        $submissions->add_child($submission);
        $checkmark->add_child($feedbacks);
        $feedbacks->add_child($feedback);
        // Second level.
        $submission->add_child($checks);
        $checks->add_child($check);

        // Define sources!
        $checkmark->set_source_table('checkmark', array('id' => backup::VAR_ACTIVITYID));

        $example->set_source_table('checkmark_examples',
                                    array('checkmarkid' => backup::VAR_PARENTID));

        // All the rest of elements only happen if we are including user info!
        if ($userinfo) {
            $override->set_source_table('checkmark_overrides', ['checkmarkid' => backup::VAR_PARENTID]);
            $submission->set_source_table('checkmark_submissions',
                                          array('checkmarkid' => backup::VAR_PARENTID));
            $feedback->set_source_table('checkmark_feedbacks',
                                          array('checkmarkid' => backup::VAR_PARENTID));
            $check->set_source_table('checkmark_checks',
                                      array('submissionid' => backup::VAR_PARENTID));
        }

        // Define id annotations!
        $checkmark->annotate_ids('scale', 'grade');
        $checkmark->annotate_ids('scale', 'presentationgrade');
        $override->annotate_ids('user', 'userid');
        $override->annotate_ids('user', 'modifierid');
        $submission->annotate_ids('user', 'userid');
        $feedback->annotate_ids('user', 'userid');
        $feedback->annotate_ids('user', 'graderid');
        $check->annotate_ids('checkmark_example', 'exampleid');

        // Define file annotations!
        $checkmark->annotate_files('mod_checkmark', 'intro', null); // This file area has no itemid!

        // Return the root element (checkmark), wrapped into standard activity structure!
        return $this->prepare_activity_structure($checkmark);
    }
}
