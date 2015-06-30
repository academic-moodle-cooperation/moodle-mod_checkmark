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
 * Converts old Assignmenttype Checkmarks into new checkmarks (but old DB-Style)
 * db/upgradeoldcheckmarks.php
 *
 * @package       mod_checkmark
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->dirroot.'/mod/checkmark/locallib.php');

require_login();

if (!is_siteadmin()) {
    print_error('cannotuseadmin');
    die;
}

$starttime = microtime(1);
$instancecount = 0;
$submissioncount = 0;

$assignmentmodid = $DB->get_field_sql('SELECT id FROM {modules} WHERE name = \'assignment\'',
                                        null, IGNORE_MISSING);
$newmodid = $DB->get_field_sql('SELECT id FROM {modules} WHERE name = \'checkmark\'', null,
                                 MUST_EXIST);

if (!$assignmentmodid) {
    // No assignment module installed!
    echo $OUTPUT->notification('No Assignment-Module installed!', 'notifyproblem');
    echo $OUTPUT->notification('Conversion success!', 'notifysuccess');
    exit;
}

$assignmentinstancesold = $DB->get_fieldset_sql('SELECT id
                                                   FROM {assignment}
                                                   WHERE assignmenttype = \'checkmark\'');

if (empty($assignmentinstancesold)) {
    // No assignment-checkmark instances found!
    echo $OUTPUT->notification('No old Assignment-Checkmark-Instances found!', 'notifyproblem');
    echo $OUTPUT->notification('Conversion success!', 'notifysuccess');
    exit;
}
echo $OUTPUT->notification('Starting conversion of assignment_checkmarks to mod_checkmarks!',
                           'notifysuccess');
echo $OUTPUT->box_start('generalbox', 'statusoutput');
$refreshcourses = array();

foreach ($assignmentinstancesold as $instanceid) {
    $instancecount++;
    // Get data from old assignment-entry!
    echo html_writer::start_tag('div').'Get Instance-Data';

    $instancedata = $DB->get_record_sql('SELECT * FROM {assignment} WHERE id = ?',
                                         array($instanceid));

    if ($instancedata) {
        try {
            $transaction = $DB->start_delegated_transaction();


            echo ' from '.$instancedata->name.'....OK'.html_writer::end_tag('div');

            /*
             * Course needs refresh - otherwise links to updated activities would still refer
             * to mod/assignment!
             */
            if (!in_array($instancedata->course, $refreshcourses)) {
                $refreshcourses[] = $instancedata->course;
            }

            // Convert to new field names!
            if (!empty($instancedata->var1)) {
                $instancedata->examplecount = $instancedata->var1;
                unset($instancedata->var1);
            } else {
                $instancedata->examplecount = 10;
            }
            if (!empty($instancedata->var2)) {
                $instancedata->examplestart = $instancedata->var2;
                unset($instancedata->var2);
            } else {
                $instancedata->examplestart = 1;
            }

            unset($instancedata->var3);
            unset($instancedata->var4);
            unset($instancedata->var5);
            unset($instancedata->id);

            // New entry in checkmark with data from old assignment-entry!
            echo html_writer::start_tag('div').'inserted new record in checkmark';
            $newinstanceid = $DB->insert_record('checkmark', $instancedata, 1);
            echo '....OK (old='.$instanceid.' / new='.$newinstanceid.')'.html_writer::end_tag('div');

            // Event-update!
            $event = new stdClass();
            if ($event = $DB->get_record('event', array('modulename'  => 'assignment',
                                                        'instance'    => $instanceid))) {
                $event->modulename        = 'checkmark';
                $event->instance = $newinstanceid;

                $calendarevent = calendar_event::load($event->id);
                $calendarevent->update($event);
            }
            // Gradeitem-update!
            $grades = $DB->get_records('grade_items', array('itemmodule'    => 'assignment',
                                                            'iteminstance'  => $instanceid));
            foreach ($grades as $grade) {
                $grade->itemmodule = 'checkmark';
                $grade->iteminstance = $newinstanceid;
                $DB->update_record('grade_items', $grade);
                $grade->oldid = $grade->id;
                unset($grade->id);
                $grade->action = 1; // Is this the correct value for the action?
                $grade->source = 'mod/checkmark';
                $DB->insert_record('grade_items_history', $grade);
            }

            // Copy submissions!
            echo html_writer::start_tag('div').'Get submissions for '.$instancedata->name;
            $submissions = $DB->get_records_sql('SELECT *
                                                 FROM {assignment_submissions}
                                                 WHERE assignment = ?', array($instanceid));
            echo '....OK'.html_writer::end_tag('div');

            if (is_array($submissions)) {
                echo html_writer::start_tag('div').
                     'start to insert submissions for '.$instancedata->name;
                $submissioncount += count($submissions);
                foreach ($submissions as $currentsubmission) {
                    /*
                     * Convert standard-assignment-fields to checkmark fields
                     * and set right references!
                     */
                    $currentsubmission->checkmarkid = $newinstanceid;
                    unset($currentsubmission->assignment);
                    $currentsubmission->userid = $currentsubmission->userid;
                    unset($currentsubmission->user);
                    $currentsubmission->checked = $currentsubmission->data1;
                    unset($currentsubmission->data1);
                    $currentsubmission->teacherid = $currentsubmission->teacher;
                    unset($currentsubmission->teacher);
                    unset($currentsubmission->id);
                    $DB->insert_record('checkmark_submissions', $currentsubmission);
                }
                echo '....OK'.html_writer::end_tag('div');
            }
            // Set new module and instance-values in course_modules!
            echo html_writer::start_tag('div').
                 'update course_module entry for '.$instancedata->name;
            $DB->set_field('course_modules', 'instance', '0',
                           array('instance' => $instanceid, 'module'  => $assignmentmodid));
            $DB->set_field('course_modules', 'module', $newmodid,
                           array('instance' => '0', 'module' => $assignmentmodid));
            $DB->set_field('course_modules', 'instance', $newinstanceid,
                           array('instance' => '0', 'module' => $newmodid));
            echo '....OK'.html_writer::end_tag('div');

            // Delete old submissions!
            echo html_writer::start_tag('div').
                 'delete old submissions for '.$instancedata->name;
            $DB->delete_records('assignment_submissions', array('assignment' => $instanceid));
            echo '....OK'.html_writer::end_tag('div');

            // Delete old assignment-instance!
            echo html_writer::start_tag('div').
                 'delete old assignment-instance for '.$instancedata->name;
            $DB->delete_records('assignment', array('id' => $instanceid));
            echo '....OK'.html_writer::end_tag('div');

            // Assuming the both inserts work, we get to the following line.
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
            if (empty($refreshcourses)) {
                echo html_writer::start_tag('div').'rebuilding course cache for all courses';
                rebuild_course_cache();
                echo '....OK'.html_writer::end_tag('div');
            } else {
                foreach ($refreshcourses as $key => $currentcourse) {
                    echo html_writer::start_tag('div').
                         'rebuilding course cache for course with id = '.$currentcourse;
                    rebuild_course_cache($currentcourse);       // Update course cache!
                    unset($refreshcourses[$key]);
                    echo '....OK'.html_writer::end_tag('div');
                }
            }
        }
    } else {
        echo ' ....SKIPPED (instance-id '.$instanceid.' is no checkmark-assignment)'.
             html_writer::end_tag('div');
    }

}
$midtime = microtime(1);
if (empty($refreshcourses)) {
    echo html_writer::start_tag('div').'rebuilding course cache for all courses';
    rebuild_course_cache();
    echo '....OK'.html_writer::end_tag('div');
} else {
    foreach ($refreshcourses as $currentcourse) {
        echo html_writer::start_tag('div').
             'rebuilding course cache for course with id = '.$currentcourse;
        rebuild_course_cache($currentcourse);       // Update course cache!

        echo '....OK'.html_writer::end_tag('div');
    }
}
$endtime = microtime(1);
$conversetime = $midtime - $starttime;
$updatetime = $endtime - $midtime;
$totaltime = $endtime - $starttime;
echo 'Timing-Info:';
echo ' converted '.$instancecount.' instances with a total of '.$submissioncount.' submissions'.
        ' in about '.$conversetime.' seconds<br />';
echo ' courseupdates needed additional '.$updatetime.
     ' secondsd which leads to a toal execution time of '.$totaltime.' seconds';
echo $OUTPUT->box_end();
echo $OUTPUT->notification('Conversion success!', 'notifysuccess');
