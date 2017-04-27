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
 * fixmissinggradebookgrade.php
 *
 * Checks if there are any missing gradebook entries for checkmark
 * instances and updates them if necessary, this happened due to a bug in version 2.9.1
 * causing grades not being written to gradebook anymore, after a DB layout change in checkmark!
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../../config.php');
$PAGE->set_url($CFG->wwwroot.'/mod/checkmark/db/fixmissinggradebookgrade.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');

require_once($CFG->dirroot.'/mod/checkmark/locallib.php');

require_login();

echo $OUTPUT->header();

if (!is_siteadmin()) {
    print_error('cannotuseadmin');
    echo $OUTPUT->footer();
    die;
}

echo $OUTPUT->box($OUTPUT->notification(get_string('checkbrokengradebookgrades_desc', 'checkmark'), 'notifymessage'), 'generalbox');

$pbar = new progress_bar('checkmarkfixgradebook', 500, true);

$starttime = microtime(1);
$pbar->update(0, 100, 'fetching data...');

echo "Fetching affected records...";

// Get all affected submissions!
$records = $DB->get_records_sql("
     SELECT feedbacks.id, items.id AS itemid, items.courseid,
            grades.rawgrade, grades.id AS gradeid, grades.userid AS gradeduserid,
            feedbacks.checkmarkid, feedbacks.grade,
            feedbacks.graderid, feedbacks.userid AS feedbackuserid,
            course.id AS courseid, course.shortname AS shortname, course.fullname AS fullname,
            c.name AS checkmark
       FROM {grade_items} items
  LEFT JOIN {grade_grades} grades ON grades.itemid = items.id
  LEFT JOIN {checkmark_feedbacks} feedbacks ON feedbacks.checkmarkid = items.iteminstance
  LEFT JOIN {checkmark} c ON feedbacks.checkmarkid = c.id
  LEFT JOIN {course} course ON c.course = course.id
      WHERE items.itemtype LIKE 'mod'
            AND items.itemmodule LIKE 'checkmark'
            AND grades.userid = feedbacks.userid
            AND grades.locked = 0 AND grades.overridden = 0
            AND (grades.rawgrade <> feedbacks.grade OR grades.rawgrade IS NULL)
            AND NOT (grades.rawgrade IS NULL AND feedbacks.grade = -1)
   ORDER BY shortname ASC, checkmark ASC", array());

// Get all involved users!
$users = $DB->get_records_sql("
     SELECT DISTINCT u.*
       FROM {grade_items} items
  LEFT JOIN {grade_grades} grades ON grades.itemid = items.id
  LEFT JOIN {checkmark_feedbacks} feedbacks ON feedbacks.checkmarkid = items.iteminstance
  LEFT JOIN {user} u ON grades.userid = u.id OR feedbacks.userid = u.id OR feedbacks.graderid = u.id
      WHERE items.itemtype LIKE 'mod'
            AND items.itemmodule LIKE 'checkmark'
            AND grades.userid = feedbacks.userid
            AND grades.locked = 0 AND grades.overridden = 0
            AND (grades.rawgrade <> feedbacks.grade OR grades.rawgrade IS NULL)
            AND NOT (grades.rawgrade IS NULL AND feedbacks.grade = -1)", array());

$affectedrecords = count($records);

$pbar->update(0, 100, 'fetching data...done');

$fetchingfinished = microtime(1);

$needed = ($fetchingfinished - $starttime) * 1000;
echo "OK (needed ".$needed." ms)".html_writer::empty_tag('br')."\n";
echo $affectedrecords." records affected.".html_writer::empty_tag('br')."\n";

$selectedcourses = optional_param_array('fixcourses', null, PARAM_INT);
$fixall = optional_param('fixall', 0, PARAM_BOOL);

$checkmarks = array();

if ($affectedrecords && empty($selectedcourses)) {
    // Show table with affected entries, to select!
    $pbar->update(0, 100, 'building table...');

    echo "Building table...";

    $tabledata = array();
    $i = 0;
    foreach ($records as $feedbackid => $record) {
        if (!key_exists($record->courseid, $tabledata)) {
            $tabledata[$record->courseid] = new stdClass();
            $tabledata[$record->courseid]->records = array();
        }
        if (!key_exists($feedbackid, $tabledata[$record->courseid]->records)) {
            $tabledata[$record->courseid]->records[$feedbackid] = new stdClass();
        }

        $tabledata[$record->courseid]->records[$feedbackid] = $records[$feedbackid];
        $tabledata[$record->courseid]->records[$feedbackid]->grader = $users[$records[$feedbackid]->graderid];
        $tabledata[$record->courseid]->records[$feedbackid]->gradeduser = $users[$records[$feedbackid]->gradeduserid];
        $tabledata[$record->courseid]->records[$feedbackid]->feedbackuser = $users[$records[$feedbackid]->feedbackuserid];
        $i++;
        switch($i % 4) {
            case 3:
                $dots = '...';
                break;
            case 2:
                $dots = '.. ';
                break;
            case 1:
                $dots = '.  ';
                break;
            default:
                $dots = '  ';
            break;
        }
        $pbar->update($i, 2 * $affectedrecords, 'building table'.$dots);
    }

    // Output Form & Table!
    $headrow = html_writer::tag('th', 'Selection', array()).
               html_writer::tag('th', 'Course', array()).
               html_writer::tag('th', 'Checkmark', array()).
               html_writer::tag('th', 'User', array());
    echo html_writer::start_tag('form', array('method' => 'POST', 'class' => 'mform'));
    echo html_writer::start_tag('fieldset', array('class' => 'clearfix')).
         html_writer::start_tag('div', array('class' => 'fcontainer clearfix')).
         html_writer::start_tag('div', array('class' => 'fitem fitem_html fitem_custom')).
         html_writer::empty_tag('input', array('name'  => 'sesskey',
                                               'value' => sesskey(),
                                               'type'  => 'hidden'));
    echo html_writer::start_tag('table', array('id' => 'courseselection', 'class' => 'coloredrows')).
         html_writer::tag('thead',
                          html_writer::tag('tr', $headrow, array()),
                          array()).
         html_writer::tag('tfoot',
                          html_writer::tag('tr', $headrow, array()),
                          array());
    foreach ($tabledata as $courseid => $coursedata) {
        $first = true;
        $courseurl = new moodle_url('/course/view.php', array('id' => $courseid));
        $courserows = '';
        foreach ($coursedata->records as $feedbackid => $data) {
            // Cache the checkmark instance data!
            if (!key_exists($data->checkmarkid, $checkmarks)) {
                $checkmarks[$data->checkmarkid] = $DB->get_record('checkmark', array('id' => $data->checkmarkid));
                $cm = get_coursemodule_from_instance('checkmark',
                                                     $checkmarks[$data->checkmarkid]->id,
                                                     $checkmarks[$data->checkmarkid]->course);
                $checkmarks[$data->checkmarkid]->cm = $cm;
                $checkmarks[$data->checkmarkid]->cmidnumber = $cm->idnumber;
            }

            $course = html_writer::tag('td', html_writer::link($courseurl, $data->shortname));

            $checkmarkurl = new moodle_url('/mod/checkmark/submissions.php',
                                           array('id' => $checkmarks[$data->checkmarkid]->cm->id));
            $checkmark = html_writer::tag('td', html_writer::link($checkmarkurl, $data->checkmark));
            $userurl = new moodle_url('/user/view.php', array('id' => $data->feedbackuserid));
            $user = html_writer::tag('td', html_writer::link($userurl, fullname($data->feedbackuser)));

            if ($first) {
                $first = false;
                $sel = html_writer::tag('td', html_writer::checkbox('fixcourses[]', $courseid, false),
                                        array('rowspan' => count($coursedata->records)));
            } else {
                $sel = '';
            }
            $courserows .= html_writer::tag('tr', $sel.$course.$checkmark.$user);

            $i++;
            switch($i % 4) {
                case 3:
                    $dots = '...';
                    break;
                case 2:
                    $dots = '.. ';
                    break;
                case 1:
                    $dots = '.  ';
                    break;
                default:
                    $dots = '  ';
                break;
            }
            $pbar->update($i, 2 * $affectedrecords, 'building table'.$dots);
        }

        echo html_writer::tag('tbody', $courserows, array('id' => 'coursedata'.$courseid));
    }
    echo html_writer::end_tag('table');
    echo html_writer::end_tag('div').
         html_writer::end_tag('div').
         html_writer::end_tag('fieldset');
    echo html_writer::start_tag('fieldset', array('class' => 'clearfix')).
         html_writer::start_tag('div', array('class' => 'fcontainer clearfix')).
         html_writer::start_tag('div', array('class' => 'fitem fitem_actionbuttons fitem_fsubmit')).
         html_writer::start_tag('div', array('class' => 'felement fsubmit'));
    echo html_writer::tag('button', get_string('go'), array('type' => 'submit', 'class' => 'btn btn-primary'));
    echo html_writer::end_tag('div').
         html_writer::end_tag('div').
         html_writer::end_tag('div').
         html_writer::end_tag('fieldset');
    echo html_writer::end_tag('form');

    $tablebuilt = microtime(1);

} else if ($affectedrecords && !empty($selectedcourses)) {
    // Building data structure for digests!

    $pbar->update(0, 100, 'building digests...');

    echo "Building digests...";

    $digests = array();
    $i = 0;

    foreach ($records as $feedbackid => $record) {
        if (!$fixall && !in_array($record->courseid, $selectedcourses)) {
            $i++;
            switch($i % 4) {
                case 3:
                    $dots = '...';
                    break;
                case 2:
                    $dots = '.. ';
                    break;
                case 1:
                    $dots = '.  ';
                    break;
                default:
                    $dots = '  ';
                break;
            }
            $pbar->update($i, $affectedrecords, 'building digests'.$dots.' (skipped record)');
            continue;
        }
        if (!key_exists($record->graderid, $digests)) {
            $digests[$record->graderid] = new stdClass();
            $digests[$record->graderid]->records = array();
        }
        if (!key_exists($feedbackid, $digests[$record->graderid]->records)) {
            $digests[$record->graderid]->records[$feedbackid] = new stdClass();
        }

        $digests[$record->graderid]->records[$feedbackid] = $records[$feedbackid];
        $digests[$record->graderid]->records[$feedbackid]->grader = $users[$records[$feedbackid]->graderid];
        $digests[$record->graderid]->records[$feedbackid]->gradeduser = $users[$records[$feedbackid]->gradeduserid];
        $digests[$record->graderid]->records[$feedbackid]->feedbackuser = $users[$records[$feedbackid]->feedbackuserid];
        $i++;
        switch($i % 4) {
            case 3:
                $dots = '...';
                break;
            case 2:
                $dots = '.. ';
                break;
            case 1:
                $dots = '.  ';
                break;
            default:
                $dots = '  ';
            break;
        }
        $pbar->update($i, $affectedrecords, 'building digests'.$dots);
    }

    $digestsbuilt = microtime(1);

    $needed = ($digestsbuilt - $fetchingfinished) * 1000;
    echo "OK (needed ".$needed." ms)".html_writer::empty_tag('br')."\n";

    // Correct gradebook entries!

    echo "Correcting gradebook grades...";

    $pbar->update(0, 100, 'correcting gradebook...');

    $corrected = array();
    $i = 0;

    foreach ($records as $feedbackid => $record) {
        if (!$fixall && !in_array($record->courseid, $selectedcourses)) {
            $i++;
            switch($i % 4) {
                case 3:
                    $dots = '...';
                    break;
                case 2:
                    $dots = '.. ';
                    break;
                case 1:
                    $dots = '.  ';
                    break;
                default:
                    $dots = '  ';
                break;
            }
            $pbar->update($i, $affectedrecords, 'correcting records'.$dots.' (skipped record)');
            continue;
        } else {

            // Cache the checkmark instance data!
            if (!key_exists($record->checkmarkid, $checkmarks)) {
                $checkmarks[$record->checkmarkid] = $DB->get_record('checkmark', array('id' => $record->checkmarkid));
                $cm = get_coursemodule_from_instance('checkmark', $checkmarks[$record->checkmarkid]->id, $record->courseid);
                $checkmarks[$record->checkmarkid]->cm = $cm;
                $checkmarks[$record->checkmarkid]->cmidnumber = $cm->idnumber;
            }

            // Get current grades and write them to gradebook!
            checkmark_update_grades($checkmarks[$record->checkmarkid], $record->feedbackuserid);
            $corrected[] = $feedbackid;

            $i++;
            switch($i % 4) {
                case 3:
                    $dots = '...';
                    break;
                case 2:
                    $dots = '.. ';
                    break;
                case 1:
                    $dots = '.  ';
                    break;
                default:
                    $dots = '  ';
                break;
            }
            $pbar->update($i, $affectedrecords, 'correcting records'.$dots);
        }
    }

    $recordscorrected = microtime(1);

    $needed = ($recordscorrected - $digestsbuilt) * 1000;
    echo "OK (needed ".$needed." ms)".html_writer::empty_tag('br')."\n";

    // Inform graders!

    $i = 0;
    $digestscount = count($digests);

    echo "Informing graders...";

    $pbar->update($i, $digestscount, 'sending messages...');
    $userfrom = core_user::get_noreply_user();
    $messageids = array();
    $messagetxtstd = get_string('checkbrokengradebookgrades_mail', 'checkmark');
    $msgtxts = array();
    $stringman = get_string_manager();
    foreach ($digests as $graderid => $digest) {
        // Try to send the mail in a language the user prefers...
        if (!empty($users[$graderid]->lang)) {
            if (!key_exists($users[$graderid]->lang, $msgtxts)) {
                $msgtxts[$users[$graderid]->lang] = $stringman->get_string('checkbrokengradebookgrades_mail', 'checkmark', null,
                                                                           $users[$graderid]->lang);
            }
            $messagetxt = $msgtxts[$users[$graderid]->lang];
        } else {
            $messagetxt = $messagetxtstd;
        }

        $message = new \core\message\message();
        $message->component = 'mod_checkmark';
        $message->name = 'checkmark_updates';
        $message->userfrom = $userfrom;
        $message->userto = $graderid;

        $message->subject = 'Corrected gradebook grades for Checkmark';
        $message->fullmessage = $messagetxt;
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->fullmessagehtml = str_replace("\n", "<br />\n", $messagetxt);
        $message->smallmessage = $messagetxt;
        foreach ($digest->records as $feedbackid => $record) {
            $message->courseid = $record->courseid;
            // Add individual data to fullmessage, fullmessagehtml and smallmessage!
            $courseurl = $CFG->wwwroot."/course/view.php?id=".$record->courseid;
            $checkmarkurl = $CFG->wwwroot."/mod/checkmark/submissions.php?id=".$checkmarks[$record->checkmarkid]->cm->id;
            $userurl = $CFG->wwwroot."/user/view.php?id=".$record->feedbackuserid;
            $message->fullmessage .= "\n".
                                     "Course (shortname): ".$record->shortname." (".$courseurl.")\n".
                                     "Checkmark: ".$record->checkmark." (".$checkmarkurl.")\n".
                                     "User: ".fullname($record->feedbackuser)." (".$userurl.")\n";
            $message->smallmessage .= "\n".
                                      "Course (shortname): ".$record->shortname."\n".
                                      "Checkmark: ".$record->checkmark."\n".
                                      "User: ".fullname($record->feedbackuser)."\n";
            $message->fullmessagehtml .= "<br />\n".
                                         "Course (shortname): <a href=\"".$courseurl."\">".$record->shortname."</a><br />\n".
                                         "Checkmark: <a href=\"".$checkmarkurl."\">".$record->checkmark."</a><br />\n".
                                         "User: <a href=\"".$userurl."\">".fullname($record->feedbackuser)."</a><br />\n";
        }
        $message->notification = '1';

        $messageids[] = message_send($message);

        $i++;
        switch($i % 4) {
            case 3:
                $dots = '...';
                break;
            case 2:
                $dots = '.. ';
                break;
            case 1:
                $dots = '.  ';
                break;
            default:
                $dots = '  ';
            break;
        }
        $pbar->update($i, $digestscount, 'sending messages'.$dots);
    }

    $messagessent = microtime(1);

    $needed = ($messagessent - $recordscorrected) * 1000;

    echo "OK (needed ".$needed." ms)".html_writer::empty_tag('br')."\n";
}

$pbar->update(1, 1, 'completed!');

echo $OUTPUT->footer();
