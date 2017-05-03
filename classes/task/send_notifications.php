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
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_checkmark\task;
defined('MOODLE_INTERNAL') || die();

/**
 * Class send_notifications handles sending of messages to students if they got new unmailed feedback (grades, comments).
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_notifications extends \core\task\scheduled_task {
    /**
     * Get the tasks name.
     *
     * @return string Tasks name
     */
    public function get_name() {
        // Shown in admin screens!
        return get_string('modulename', 'checkmark').' | '.get_string('sendnotifications', 'mod_checkmark');
    }

    /**
     * Executes the task.
     *
     * @return bool true if everythings OK.
     */
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
        if ($feedbacks = \checkmark_get_unmailed_feedbacks($starttime, $endtime)) {

            $timenow = time();

            foreach ($feedbacks as $feedback) {

                echo 'Processing checkmark feedback '.$feedback->id."\n";

                if (!$user = $DB->get_record('user', array('id' => $feedback->userid))) {
                    echo 'Could not find user '.$user->id."\n";
                    continue;
                }

                if (!$course = $DB->get_record('course', array('id' => $feedback->course))) {
                    echo 'Could not find course '.$feedback->course."\n";
                    continue;
                }

                /*
                 * Override the language and timezone of the 'current' user, so that
                 * mail is customised for the receiver.
                 */
                \cron_setup_user($user, $course);

                if (!\is_enrolled(\context_course::instance($feedback->course), $user->id)) {
                    echo fullname($user).' isn\'t an active participant in ' .
                         format_string($course->shortname) . "\n";
                    continue;
                }

                if (!$grader = $DB->get_record('user', array('id' => $feedback->graderid))) {
                    echo 'Could not find teacher '.$feedback->graderid."\n";
                    continue;
                }

                if (!$mod = \get_coursemodule_from_instance('checkmark', $feedback->checkmarkid,
                                                            $course->id)) {
                    echo 'Could not find course module for checkmark id '.$feedback->checkmarkid."\n";
                    continue;
                }

                if (!$mod->visible) {    // Hold mail notification for hidden checkmarks until later!
                    continue;
                }

                $strcheckmarks = \get_string('modulenameplural', 'checkmark');

                $checkmarkinfo = new \stdClass();
                $checkmarkinfo->grader = \fullname($grader);
                $checkmarkinfo->checkmark = \format_string($feedback->name, true);
                $checkmarkinfo->url = $CFG->wwwroot.'/mod/checkmark/view.php?id='.$mod->id;

                $postsubject = $course->shortname.': '.$strcheckmarks.': '.
                               \format_string($feedback->name, true);
                $posttext  = $course->shortname.' -> '.$strcheckmarks.' -> '.
                             \format_string($feedback->name, true)."\n".
                             "---------------------------------------------------------------------\n".
                             \get_string('checkmarkmail', 'checkmark', $checkmarkinfo)."\n".
                             "---------------------------------------------------------------------\n";

                if ($user->mailformat == 1) {  // HTML!
                    $posthtml = '<p><font face="sans-serif">'.
                    '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">'.$course->shortname.'</a> '.
                    '-><a href="'.$CFG->wwwroot.'/mod/checkmark/index.php?id='.$course->id.'">'.$strcheckmarks.'</a> '.
                    '-><a href="'.$CFG->wwwroot.'/mod/checkmark/view.php?id='.$mod->id.'">'.
                    \format_string($feedback->name, true).'</a></font></p>'.
                    '<hr /><font face="sans-serif">'.
                    '<p>'.\get_string('checkmarkmailhtml', 'checkmark', $checkmarkinfo).'</p>'.
                    '</font><hr />';
                } else {
                    // We don't need HTML-Text if mailformat is plain text. (Plain text is in stdClass::fullmessage)!
                    $posthtml = '';
                }

                $message = new \core\message\message();
                $message->component         = 'mod_checkmark';
                $message->name              = 'checkmark_updates';
                $message->courseid          = $course->id;
                $message->userfrom          = $grader;
                $message->userto            = $user;
                $message->subject           = $postsubject;
                $message->fullmessage       = $posttext;
                $message->fullmessageformat = FORMAT_HTML;
                $message->fullmessagehtml   = $posthtml;
                $message->smallmessage      = \get_string('checkmarkmailsmall', 'checkmark', $checkmarkinfo);
                $message->notification      = 1;
                $message->contexturl        = $checkmarkinfo->url;
                $message->contexturlname    = $checkmarkinfo->checkmark;

                \message_send($message);
                $DB->set_field('checkmark_feedbacks', 'mailed', '1', array('id' => $feedback->id));
            }

            \cron_setup_user();
        } else {
            echo "\nNo unmailed Submissions!\n";
        }

        return true;
    }
}
