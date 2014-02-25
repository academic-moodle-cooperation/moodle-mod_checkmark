<?php
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

$assignment_mod_id = $DB->get_field_sql('SELECT id FROM {modules} WHERE name = \'assignment\'',
                                        null, IGNORE_MISSING);
$new_mod_id = $DB->get_field_sql('SELECT id FROM {modules} WHERE name = \'checkmark\'', null,
                                 MUST_EXIST);

if (!$assignment_mod_id) {
    // No assignment module installed!
    echo $OUTPUT->notification('No Assignment-Module installed!', 'notifyproblem');
    echo $OUTPUT->notification('Conversion success!', 'notifysuccess');
    exit;
}

$assignment_instances_old = $DB->get_fieldset_sql('SELECT id
                                                   FROM {assignment}
                                                   WHERE assignmenttype = \'checkmark\'');

if (empty($assignment_instances_old)) {
    // No assignment-checkmark instances found!
    echo $OUTPUT->notification('No old Assignment-Checkmark-Instances found!', 'notifyproblem');
    echo $OUTPUT->notification('Conversion success!', 'notifysuccess');
    exit;
}
echo $OUTPUT->notification('Starting conversion of assignment_checkmarks to mod_checkmarks!',
                           'notifysuccess');
echo $OUTPUT->box_start('generalbox', 'statusoutput');
$refreshcourses = array();

foreach ($assignment_instances_old as $instanceid) {
    $instancecount++;
    // Get data from old assignment-entry!
    echo html_writer::start_tag('div').'Get Instance-Data';

    $instance_data = $DB->get_record_sql('SELECT * FROM {assignment} WHERE id = ?',
                                         array($instanceid));

    if ($instance_data) {
        try {
            $transaction = $DB->start_delegated_transaction();


            echo ' from '.$instance_data->name.'....OK'.html_writer::end_tag('div');

            /*
             * Course needs refresh - otherwise links to updated activities would still refer
             * to mod/assignment!
             */
            if (!in_array($instance_data->course, $refreshcourses)) {
                $refreshcourses[] = $instance_data->course;
            }

            // Convert to new field names!
            if (!empty($instance_data->var1)) {
                $instance_data->examplecount = $instance_data->var1;
                unset($instance_data->var1);
            } else {
                $instance_data->examplecount = 10;
            }
            if (!empty($instance_data->var2)) {
                $instance_data->examplestart = $instance_data->var2;
                unset($instance_data->var2);
            } else {
                $instance_data->examplestart = 1;
            }

            unset($instance_data->var3);
            unset($instance_data->var4);
            unset($instance_data->var5);
            unset($instance_data->id);

            // New entry in checkmark with data from old assignment-entry!
            echo html_writer::start_tag('div').'inserted new record in checkmark';
            $new_instanceid = $DB->insert_record('checkmark', $instance_data, 1);
            echo '....OK (old='.$instanceid.' / new='.$new_instanceid.')'.html_writer::end_tag('div');

            // Event-update!
            $event = new stdClass();
            if ($event = $DB->get_record('event', array('modulename'  => 'assignment',
                                                        'instance'    => $instanceid))) {
                $event->modulename        = 'checkmark';
                $event->instance = $new_instanceid;

                $calendarevent = calendar_event::load($event->id);
                $calendarevent->update($event);
            }
            // Gradeitem-update!
            $grades = $DB->get_records('grade_items', array('itemmodule'    => 'assignment',
                                                            'iteminstance'  => $instanceid));
            foreach ($grades as $grade) {
                $grade->itemmodule = 'checkmark';
                $grade->iteminstance = $new_instanceid;
                $DB->update_record('grade_items', $grade);
                $grade->oldid = $grade->id;
                unset($grade->id);
                $grade->action = 1; // Is this the correct value for the action?
                $grade->source = 'mod/checkmark';
                $DB->insert_record('grade_items_history', $grade);
            }

            // Copy submissions!
            echo html_writer::start_tag('div').'Get submissions for '.$instance_data->name;
            $submissions = $DB->get_records_sql('SELECT *
                                                 FROM {assignment_submissions}
                                                 WHERE assignment = ?', array($instanceid));
            echo '....OK'.html_writer::end_tag('div');

            if (is_array($submissions)) {
                echo html_writer::start_tag('div').
                     'start to insert submissions for '.$instance_data->name;
                $submissioncount += count($submissions);
                foreach ($submissions as $currentsubmission) {
                    /*
                     * Convert standard-assignment-fields to checkmark fields
                     * and set right references!
                     */
                    $currentsubmission->checkmarkid = $new_instanceid;
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
                 'update course_module entry for '.$instance_data->name;
            $DB->set_field('course_modules', 'instance', '0',
                           array('instance'=>$instanceid, 'module'  =>$assignment_mod_id));
            $DB->set_field('course_modules', 'module', $new_mod_id,
                           array('instance'=>'0', 'module'=>$assignment_mod_id));
            $DB->set_field('course_modules', 'instance', $new_instanceid,
                           array('instance'=>'0', 'module'=>$new_mod_id));
            echo '....OK'.html_writer::end_tag('div');

            // Delete old submissions!
            echo html_writer::start_tag('div').
                 'delete old submissions for '.$instance_data->name;
            $DB->delete_records('assignment_submissions', array('assignment' => $instanceid));
            echo '....OK'.html_writer::end_tag('div');

            // Delete old assignment-instance!
            echo html_writer::start_tag('div').
                 'delete old assignment-instance for '.$instance_data->name;
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
$conversetime = $midtime-$starttime;
$updatetime = $endtime-$midtime;
$totaltime = $endtime-$starttime;
echo 'Timing-Info:';
echo ' converted '.$instancecount.' instances with a total of '.$submissioncount.' submissions'.
        ' in about '.$conversetime.' seconds<br />';
echo ' courseupdates needed additional '.$updatetime.
     ' secondsd which leads to a toal execution time of '.$totaltime.' seconds';
echo $OUTPUT->box_end();
echo $OUTPUT->notification('Conversion success!', 'notifysuccess');
