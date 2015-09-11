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
 * classes/task/send_notifications.php
 *
 * @package       mod_checkmark
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_checkmark\task;
defined('MOODLE_INTERNAL') || die();

class send_notifications extends \core\task\scheduled_task {
    public function get_name() {
        // Shown in admin screens!
        return get_string('modulename', 'checkmark').' | '.get_string('sendnotifications', 'mod_checkmark');
    }

    public function execute() {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/mod/checkmark/lib.php');

        /*
         * Notices older than 2 days will not be mailed.  This is to avoid the problem where
         * cron has not been running for a long time, and then suddenly people are flooded
         * with mail from the past few weeks or months
         */

        $timenow   = time();
        $endtime   = $timenow - $CFG->maxeditingtime;
        $validmsgtime = get_config('checkmark', 'validmsgtime');
        if (false !== $validmsgtime) {
            $starttime = $endtime - $validmsgtime * 24 * 3600;   // Two days earlier?
        } else {
                $starttime = $endtime - 2 * 24 * 3600;   // Two days earlier?
        }
        if ($submissions = \checkmark_get_unmailed_submissions($starttime, $endtime)) {

            $timenow = time();

            foreach ($submissions as $submission) {

                echo 'Processing checkmark submission '.$submission->id."\n";

                if (!$user = $DB->get_record('user', array('id' => $submission->userid))) {
                    echo 'Could not find user '.$user->id."\n";
                    continue;
                }

                if (!$course = $DB->get_record('course', array('id' => $submission->course))) {
                    echo 'Could not find course '.$submission->course."\n";
                    continue;
                }

                /*
                 * Override the language and timezone of the 'current' user, so that
                 * mail is customised for the receiver.
                 */
                \cron_setup_user($user, $course);

                if (!\is_enrolled(\context_course::instance($submission->course), $user->id)) {
                    echo fullname($user).' isn\'t an active participant in ' .
                         format_string($course->shortname) . "\n";
                    continue;
                }

                if (!$teacher = $DB->get_record('user', array('id' => $submission->teacherid))) {
                    echo 'Could not find teacher '.$submission->teacherid."\n";
                    continue;
                }

                if (!$mod = \get_coursemodule_from_instance('checkmark', $submission->checkmarkid,
                                                           $course->id)) {
                    echo 'Could not find course module for checkmark id '.$submission->checkmarkid."\n";
                    continue;
                }

                if (!$mod->visible) {    // Hold mail notification for hidden checkmarks until later!
                    continue;
                }

                $strcheckmarks = \get_string('modulenameplural', 'checkmark');
                $strcheckmark  = \get_string('modulename', 'checkmark');

                $checkmarkinfo = new \stdClass();
                $checkmarkinfo->teacher = \fullname($teacher);
                $checkmarkinfo->checkmark = \format_string($submission->name, true);
                $checkmarkinfo->url = $CFG->wwwroot.'/mod/checkmark/view.php?id='.$mod->id;

                $postsubject = $course->shortname.': '.$strcheckmarks.': '.
                               \format_string($submission->name, true);
                $posttext  = $course->shortname.' -> '.$strcheckmarks.' -> '.
                             \format_string($submission->name, true)."\n".
                             "---------------------------------------------------------------------\n".
                             \get_string('checkmarkmail', 'checkmark', $checkmarkinfo)."\n".
                             "---------------------------------------------------------------------\n";

                if ($user->mailformat == 1) {  // HTML!
                    $posthtml = '<p><font face="sans-serif">'.
                    '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">'.$course->shortname.'</a> '.
                    '-><a href="'.$CFG->wwwroot.'/mod/checkmark/index.php?id='.$course->id.'">'.$strcheckmarks.'</a> '.
                    '-><a href="'.$CFG->wwwroot.'/mod/checkmark/view.php?id='.$mod->id.'">'.
                    \format_string($submission->name, true).'</a></font></p>'.
                    '<hr /><font face="sans-serif">'.
                    '<p>'.\get_string('checkmarkmailhtml', 'checkmark', $checkmarkinfo).'</p>'.
                    '</font><hr />';
                } else {
                    // We don't need HTML-Text if mailformat is plain text. (Plain text is in stdClass::fullmessage)!
                    $posthtml = '';
                }

                $eventdata = new \stdClass();
                $eventdata->modulename       = 'checkmark';
                $eventdata->userfrom         = $teacher;
                $eventdata->userto           = $user;
                $eventdata->subject          = $postsubject;
                $eventdata->fullmessage      = $posttext;
                $eventdata->fullmessageformat = FORMAT_PLAIN;
                $eventdata->fullmessagehtml  = $posthtml;
                $eventdata->smallmessage     = \get_string('checkmarkmailsmall', 'checkmark',
                                                           $checkmarkinfo);
                $eventdata->name            = 'checkmark_updates';
                $eventdata->component       = 'mod_checkmark';
                $eventdata->notification    = 1;
                $eventdata->contexturl      = $checkmarkinfo->url;
                $eventdata->contexturlname  = $checkmarkinfo->checkmark;

                \message_send($eventdata);
                $DB->set_field('checkmark_submissions', 'mailed', '1', array('id' => $submission->id));
            }

            \cron_setup_user();
        } else {
            echo "\nNo unmailed Submissions!\n";
        }

        return true;
    }
}