<?PHP
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
defined('MOODLE_INTERNAL') || die;

/**
 * This file contains the major pieces of function checkmark uses
 *
 * @package       mod_checkmark
 * @author        Philipp Hager
 * @copyright     2012 Philipp Hager
 * @since         Moodle 2.2
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/*
 ***************************** OTHER STANDARD FUNCTIONS *********************************
 */

/**
 * Deletes a checkmark instance
 *
 * This is done by calling the delete_instance() method
 */
function checkmark_delete_instance($id) {
    global $CFG, $DB, $OUTPUT, $COURSE;

    if (! $checkmark = $DB->get_record('checkmark', array('id'=>$id))) {
        return false;
    }

    if (! $cm = get_coursemodule_from_instance('checkmark', $checkmark->id)) {
        echo $OUTPUT->notification('invalidinstance('.$returnid.')', 'notifyproblem');
        $id = null;
    } else {
        $id = $cm->id;
    }

    $result = true;

    // Now get rid of all files!
    $fs = get_file_storage();
    if (!empty($cm)) {
        $context = context_module::instance($cm->id);
        $fs->delete_area_files($context->id);
    }

    if (! $DB->delete_records('checkmark_submissions', array('checkmark_id'=>$checkmark->id))) {
        $result = false;
    }

    if (! $DB->delete_records('event', array('modulename' => 'checkmark',
                                             'instance'   => $checkmark->id))) {
        $result = false;
    }

    if (! $DB->delete_records('checkmark', array('id' => $checkmark->id))) {
        $result = false;
    }
    $mod = $DB->get_field('modules', 'id', array('name' => 'checkmark'));

    checkmark_grade_item_delete($checkmark);

    add_to_log($COURSE->id, 'checkmark', 'delete',
               $CFG->wwwroot.'/course/view.php?id='.$COURSE->id, $id . ' - ' . $checkmark->name,
               $id);

    return $result;
}

/**
 * Updates a checkmark instance
 *
 * This is done by calling the update_instance() method
 */
function checkmark_update_instance($checkmark) {
    global $COURSE, $CFG, $OUTPUT, $DB;

    $checkmark->timemodified = time();

    if (($checkmark->allready_submit != 'yes') && !isset($checkmark->flexiblenaming)) {
        $checkmark->flexiblenaming = 0;
    } else {
        unset($checkmark->flexiblenaming);
    }

    // Clean examplenames and examplegrades!
    $checkmark->examplenames = preg_replace('#^,*|,*$#', '',
                                            $checkmark->examplenames, -1);
    $checkmark->examplenames = preg_replace('#,{2,}#', ',', $checkmark->examplenames, -1);
    $checkmark->examplegrades = preg_replace('#^,*|,*$#', '',
                                             $checkmark->examplegrades, -1);
    $checkmark->examplegrades = preg_replace('#,{2,}#', ',', $checkmark->examplegrades, -1);

    $checkmark->id = $checkmark->instance;

    $DB->update_record('checkmark', $checkmark);

    if ($checkmark->timedue) {
        $event = new stdClass();

        if ($event->id = $DB->get_field('event', 'id', array('modulename' => 'checkmark',
                                                             'instance'   => $checkmark->id))) {

            $event->name        = $checkmark->name;
            $event->description = format_module_intro('checkmark', $checkmark,
                                                      $checkmark->coursemodule);
            $event->timestart   = $checkmark->timedue;

            $calendarevent = calendar_event::load($event->id);
            $calendarevent->update($event);
        } else {
            $event = new stdClass();
            $event->name        = $checkmark->name;
            $event->description = format_module_intro('checkmark', $checkmark,
                                                      $checkmark->coursemodule);
            $event->courseid    = $checkmark->course;
            $event->groupid     = 0;
            $event->userid      = 0;
            $event->modulename  = 'checkmark';
            $event->instance    = $checkmark->id;
            $event->eventtype   = 'course';
            $event->timestart   = $checkmark->timedue;
            $event->timeduration = 0;

            calendar_event::create($event);
        }
    } else {
        $DB->delete_records('event', array('modulename' => 'checkmark',
                                           'instance'   => $checkmark->id));
    }

    // Get existing grade item!
    checkmark_grade_item_update($checkmark);

    if (! $cm = get_coursemodule_from_instance('checkmark', $checkmark->id)) {
        echo $OUTPUT->notification('invalidinstance('.$checkmark->id.')', 'notifyproblem');
        $link = '';
        $id = null;
        $name = $checkmark->name;
    } else {
        $link = $CFG->wwwroot . '/mod/checkmark/view.php?id='.$cm->id;
        $id = $cm->id;
        $name = $id . ' - ' . $checkmark->name;
    }
    add_to_log($COURSE->id, 'checkmark', 'update', $link, $name, $id);
    return true;
}

/**
 * Adds a checkmark instance
 *
 * This is done by calling the add_instance() method
 */
function checkmark_add_instance($checkmark) {
    global $COURSE, $CFG, $OUTPUT, $DB;
    $checkmark->timemodified = time();

    if (!isset($checkmark->flexiblenaming)) {
        $checkmark->flexiblenaming = 0;
    }
    $returnid = $DB->insert_record("checkmark", $checkmark);
    $checkmark->id = $returnid;

    if ($checkmark->timedue) {
        $event = new stdClass();
        $event->name        = $checkmark->name;
        $event->description = format_module_intro('checkmark', $checkmark,
                                                  $checkmark->coursemodule);
        $event->courseid    = $checkmark->course;
        $event->groupid     = 0;
        $event->userid      = 0;
        $event->modulename  = 'checkmark';
        $event->instance    = $returnid;
        $event->eventtype = 'course';
        $event->timestart   = $checkmark->timedue;
        $event->timeduration = 0;

        calendar_event::create($event);
    }

    checkmark_grade_item_update($checkmark);

    $link = $CFG->wwwroot.'/mod/checkmark/view.php?c='.$returnid;
    $name = $checkmark->name;

    add_to_log($COURSE->id, 'checkmark', 'add', $link, $name);

    return $returnid;
}

/**
 * Returns an outline of a user interaction with an checkmark
 *
 * This is done by calling the user_outline() method
 */
function checkmark_user_outline($course, $user, $mod, $checkmark) {
    global $CFG;
    require_once($CFG->dirroot.'/mod/checkmark/locallib.php');
    require_once("$CFG->libdir/gradelib.php");
    $instance = new checkmark($mod->id, $checkmark, $mod, $course);
    $grades = grade_get_grades($course->id, 'mod', 'checkmark', $checkmark->id, $user->id);
    if (!empty($grades->items[0]->grades)) {
        return $instance->user_outline(reset($grades->items[0]->grades));
    } else {
        return null;
    }
}

/**
 * Prints the complete info about a user's interaction with an checkmark
 *
 * This is done by calling the user_complete() method
 */
function checkmark_user_complete($course, $user, $mod, $checkmark) {
    global $CFG;
    require_once($CFG->dirroot.'/mod/checkmark/locallib.php');
    require_once("$CFG->libdir/gradelib.php");

    $instance = new checkmark($mod->id, $checkmark, $mod, $course);
    $grades = grade_get_grades($course->id, 'mod', 'checkmark', $checkmark->id, $user->id);
    if (empty($grades->items[0]->grades)) {
        $grade = false;
    } else {
        $grade = reset($grades->items[0]->grades);
    }
    return $instance->user_complete($user, $grade);
}

/**
 * Function to be run periodically according to the moodle cron
 *
 * Finds all checkmark notifications that have yet to be mailed out, and mails them
 */
function checkmark_cron () {
    global $CFG, $USER, $DB;
    require_once($CFG->dirroot.'/mod/checkmark/locallib.php');

    $ass = new checkmark();
    $ass->cron();

    /*
     * Notices older than 2 days will not be mailed.  This is to avoid the problem where
     * cron has not been running for a long time, and then suddenly people are flooded
     * with mail from the past few weeks or months
     */

    $timenow   = time();
    $endtime   = $timenow - $CFG->maxeditingtime;
    if (isset($CFG->checkmark_validmsgtime)) {
        $starttime = $endtime - $CFG->checkmark_validmsgtime * 24 * 3600;   // Two days earlier?
    } else {
            $starttime = $endtime - 2 * 24 * 3600;   // Two days earlier?
    }
    if ($submissions = checkmark_get_unmailed_submissions($starttime, $endtime)) {
        //tscpr:
            //not used anywhere(in this function :))
        $realuser = clone($USER);

        foreach ($submissions as $key => $submission) {
            $DB->set_field("checkmark_submissions", "mailed", "1", array("id"=>$submission->id));
        }

        $timenow = time();

        foreach ($submissions as $submission) {

            echo "Processing checkmark submission $submission->id\n";

            if (! $user = $DB->get_record("user", array("id"=>$submission->user_id))) {
                echo "Could not find user $user->id\n";
                continue;
            }

            if (! $course = $DB->get_record("course", array("id"=>$submission->course))) {
                echo "Could not find course $submission->course\n";
                continue;
            }

            /*
             * Override the language and timezone of the "current" user, so that
             * mail is customised for the receiver.
             */
            cron_setup_user($user, $course);

            if (!is_enrolled(context_course::instance($submission->course), $user->id)) {
                echo fullname($user)." not an active participant in " .
                     format_string($course->shortname) . "\n";
                continue;
            }

            if (! $teacher = $DB->get_record("user", array("id"=>$submission->teacher_id))) {
                echo "Could not find teacher $submission->teacher_id\n";
                continue;
            }

            if (! $mod = get_coursemodule_from_instance("checkmark", $submission->checkmark_id,
                                                        $course->id)) {
                echo "Could not find course module for checkmark id $submission->checkmark_id\n";
                continue;
            }

            if (! $mod->visible) {    // Hold mail notification for hidden checkmarks until later!
                continue;
            }

            $strcheckmarks = get_string("modulenameplural", "checkmark");
            $strcheckmark  = get_string("modulename", "checkmark");

            $checkmarkinfo = new stdClass();
            $checkmarkinfo->teacher = fullname($teacher);
            $checkmarkinfo->checkmark = format_string($submission->name, true);
            $checkmarkinfo->url = "$CFG->wwwroot/mod/checkmark/view.php?id=$mod->id";

            $postsubject = "$course->shortname: $strcheckmarks: ".
                           format_string($submission->name, true);
            $posttext  = "$course->shortname -> $strcheckmarks -> ".
                         format_string($submission->name, true)."\n";
            $posttext .= "---------------------------------------------------------------------\n";
            $posttext .= get_string("checkmarkmail", "checkmark", $checkmarkinfo)."\n";
            $posttext .= "---------------------------------------------------------------------\n";

            if ($user->mailformat == 1) {  // HTML!
                $posthtml = "<p><font face=\"sans-serif\">".
                "<a href=\"$CFG->wwwroot/course/view.php?id=$course->id\">$course->shortname</a> ".
                "-><a href=\"$CFG->wwwroot/mod/checkmark/index.php?id=$course->id\">$strcheckmarks".
                "</a> -><a href=\"$CFG->wwwroot/mod/checkmark/view.php?id=$mod->id\">".
                format_string($submission->name, true)."</a></font></p>";
                $posthtml .= "<hr /><font face=\"sans-serif\">";
                $posthtml .= "<p>".get_string("checkmarkmailhtml", "checkmark", $checkmarkinfo).
                             "</p>";
                $posthtml .= "</font><hr />";
            } else {
                //tscpr:
                    //does this mean that if message format is something different from HTML, then an empty email is sent?
                $posthtml = "";
            }

            $eventdata = new stdClass();
            $eventdata->modulename       = 'checkmark';
            $eventdata->userfrom         = $teacher;
            $eventdata->userto           = $user;
            $eventdata->subject          = $postsubject;
            $eventdata->fullmessage      = $posttext;
            $eventdata->fullmessageformat = FORMAT_PLAIN;
            $eventdata->fullmessagehtml  = $posthtml;
            $eventdata->smallmessage     = get_string('checkmarkmailsmall', 'checkmark',
                                                      $checkmarkinfo);
            $eventdata->name            = 'checkmark_updates';
            $eventdata->component       = 'mod_checkmark';
            $eventdata->notification    = 1;
            $eventdata->contexturl      = $checkmarkinfo->url;
            $eventdata->contexturlname  = $checkmarkinfo->checkmark;

            message_send($eventdata);
        }

        cron_setup_user();
    }

    return true;
}

/**
 * Return grade for given user or all users.
 *
 * @param int $checkmarkid id of checkmark
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function checkmark_get_user_grades($checkmark, $userid=0) {
    global $CFG, $DB;

    if ($userid) {
        $user = "AND u.id = :userid";
        $params = array('userid'=>$userid);
    } else {
        $user = "";
    }
    $params['aid'] = $checkmark->id;

    $sql = "SELECT u.id, u.id AS userid, s.grade AS rawgrade, s.submissioncomment AS feedback,
                   s.format AS feedbackformat, s.teacher_id AS usermodified,
                   s.timemarked AS dategraded, s.timemodified AS datesubmitted
            FROM {user} u, {checkmark_submissions} s
            WHERE u.id = s.user_id AND s.checkmark_id = :aid
            $user";
    //tscpr:
        //again according to guidelines, better use single quotes and append variables with .
             return $DB->get_records_sql($sql, $params);
}

/**
 * Update activity grades
 *
 * @param object $checkmark
 * @param int $userid specific user only, 0 means all
 */
function checkmark_update_grades($checkmark, $userid=0, $nullifnone=true) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');
    
    //tscpr:
        //this whole thing can be a bit simplified - checkmark_grade_item_update is used with the same parameters
        /*
        e.g.
        $grades = null;
        if ($checkmark->grade != 0 && $grades = checkmark_get_user_grades($checkmark, $userid)) {
            foreach ($grades as $k => $v) {
                if ($v->rawgrade == -1) {
                    $grades[$k]->rawgrade = null;
                }
            }
        }
        checkmark_grade_item_update($checkmark, $grades);
        
        */
    if ($checkmark->grade == 0) {
        checkmark_grade_item_update($checkmark);

    } else if ($grades = checkmark_get_user_grades($checkmark, $userid)) {
        foreach ($grades as $k => $v) {
            if ($v->rawgrade == -1) {
                $grades[$k]->rawgrade = null;
            }
        }
        checkmark_grade_item_update($checkmark, $grades);

    } else {
        checkmark_grade_item_update($checkmark);
    }
}

/**
 * Update all grades in gradebook.
 */
function checkmark_upgrade_grades() {
    global $DB;

    $sql = "SELECT COUNT('x')
              FROM {checkmark} a, {course_modules} cm, {modules} m
             WHERE m.name='checkmark' AND m.id=cm.module AND cm.instance=a.id";
    $count = $DB->count_records_sql($sql);

    $sql = "SELECT a.*, cm.idnumber AS cmidnumber, a.course AS course
              FROM {checkmark} a, {course_modules} cm, {modules} m
             WHERE m.name='checkmark' AND m.id=cm.module AND cm.instance=a.id";
    $rs = $DB->get_recordset_sql($sql);
    if ($rs->valid()) {
        // Too much debug output!
        $pbar = new progress_bar('checkmarkupgradegrades', 500, true);
        $i=0;
        foreach ($rs as $checkmark) {
            $i++;
            upgrade_set_timeout(60*5); // Set up timeout, may also abort execution!
            checkmark_update_grades($checkmark);
            $pbar->update($i, $count, "Updating checkmark grades ($i/$count).");
        }
        upgrade_set_timeout(); // Reset to default timeout!
    }
    $rs->close();
}

/**
 * Create grade item for given checkmark
 *
 * @param object $checkmark object with extra cmidnumber
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function checkmark_grade_item_update($checkmark, $grades=null) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $params = array('itemname'=>$checkmark->name, 'idnumber'=>$checkmark->cmidnumber);

    if ($checkmark->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $checkmark->grade;
        $params['grademin']  = 0;

    } else if ($checkmark->grade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$checkmark->grade;

    } else {
        $params['gradetype'] = GRADE_TYPE_TEXT; // Allow text comments only!
    }

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/checkmark', $checkmark->course, 'mod', 'checkmark', $checkmark->id, 0,
                        $grades, $params);
}

/**
 * Delete grade item for given checkmark
 *
 * @param object $checkmark object
 * @return object checkmark
 */
function checkmark_grade_item_delete($checkmark) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/checkmark', $checkmark->course, 'mod', 'checkmark', $checkmark->id, 0,
                        null, array('deleted'=>1));
}

/**
 * Returns the users with data in one checkmark (students and teachers)
 *
 * @todo: deprecated - to be deleted in 2.2
 //tscpr:
    //is there a reason to keep it?
 *
 * @param $checkmarkid int
 * @return array of user objects
 */
function checkmark_get_participants($checkmarkid) {
    global $CFG, $DB;

    // Get students!
    $students = $DB->get_records_sql("SELECT DISTINCT u.id, u.id
                                        FROM {user} u,
                                             {checkmark_submissions} a
                                       WHERE a.checkmark_id = ? and
                                             u.id = a.user_id", array($checkmarkid));
    // Get teachers!
    $teachers = $DB->get_records_sql("SELECT DISTINCT u.id, u.id
                                        FROM {user} u,
                                             {checkmark_submissions} a
                                       WHERE a.checkmark_id = ? and
                                             u.id = a.teacher_id", array($checkmarkid));

    // Add teachers to students!
    if ($teachers) {
        foreach ($teachers as $teacher) {
            $students[$teacher->id] = $teacher;
        }
    }
    // Return students array (it contains an array of unique users)!
    return ($students);
}

/**
 * Serves checkmark submissions and other files.
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - just send the file
 */
function checkmark_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/mod/checkmark/locallib.php');

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, false, $cm);

    if (!$checkmark = $DB->get_record('checkmark', array('id'=>$cm->instance))) {
        return false;
    }

    $checkmarkinstance = new checkmark($cm->id, $checkmark, $cm, $course);
    return $checkmarkinstance->send_file($filearea, $args);
}

/**
 * Checks if a scale is being used by an checkmark
 *
 * This is used by the backup code to decide whether to back up a scale
 * @param $checkmarkid int
 * @param $scaleid int
 * @return boolean True if the scale is used by the checkmark
 */
function checkmark_scale_used($checkmarkid, $scaleid) {
    global $DB;

    $return = false;

    $rec = $DB->get_record('checkmark', array('id' => $checkmarkid, 'grade' => -$scaleid));

    if (!empty($rec) && !empty($scaleid)) {
        $return = true;
    }

    return $return;
}

/**
 * Checks if scale is being used by any instance of checkmark
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any checkmark
 */
function checkmark_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('checkmark', array('grade'=>-$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Make sure up-to-date events are created for all checkmark instances
 *
 * This standard function will check all instances of this module
 * and make sure there are up-to-date events created for each of them.
 * If course = 0, then every checkmark event in the site is checked, else
 * only checkmark events belonging to the course specified are checked.
 * This function is used, in its new format, by restore_refresh_events()
 *
 * @param $course int optional If zero then all checkmarks for all courses are covered
 * @return boolean Always returns true
 */
function checkmark_refresh_events($course = 0) {
    global $DB;

    if ($course == 0) {
        if (! $checkmarks = $DB->get_records("checkmark")) {
            return true;
        }
    } else {
        if (! $checkmarks = $DB->get_records("checkmark", array("course"=>$course))) {
            return true;
        }
    }
    $moduleid = $DB->get_field('modules', 'id', array('name'=>'checkmark'));

    foreach ($checkmarks as $checkmark) {
        $cm = get_coursemodule_from_id('checkmark', $checkmark->id);
        $event = new stdClass();
        $event->name        = $checkmark->name;
        $event->description = format_module_intro('checkmark', $checkmark, $cm->id);
        $event->timestart   = $checkmark->timedue;

        if ($event->id = $DB->get_field('event', 'id', array('modulename' => 'checkmark',
                                                             'instance'   => $checkmark->id))) {
            update_event($event);

        } else {
            $event->courseid    = $checkmark->course;
            $event->groupid     = 0;
            $event->userid      = 0;
            $event->modulename  = 'checkmark';
            $event->instance    = $checkmark->id;
            $event->eventtype   = 'course';
            $event->timeduration = 0;
            $event->visible     = $DB->get_field('course_modules', 'visible',
                                                 array('module'   => $moduleid,
                                                       'instance' => $checkmark->id));
            add_event($event);
        }

    }
    return true;
}

/**
 * Print recent activity from all checkmarks in a given course
 *
 * This is used by the recent activity block
 */
function checkmark_print_recent_activity($course, $viewfullnames, $timestart) {
    global $CFG, $USER, $DB, $OUTPUT;

    // Do not use log table if possible, it may be huge!

    if (!$submissions = $DB->get_records_sql("
            SELECT asb.id, asb.timemodified, cm.id AS cmid, asb.user_id,
                 u.firstname, u.lastname, u.email, u.picture
            FROM {checkmark_submissions} asb
                JOIN {checkmark} a      ON a.id = asb.checkmark_id
                JOIN {course_modules} cm ON cm.instance = a.id
                JOIN {modules} md        ON md.id = cm.module
                JOIN {user} u            ON u.id = asb.user_id
            WHERE asb.timemodified > ? AND
                  a.course = ? AND
                  md.name = 'checkmark'
            ORDER BY asb.timemodified ASC", array($timestart, $course->id))) {
        return false;
    }

    $modinfo =& get_fast_modinfo($course); // Reference needed because we might load the groups!
    $show    = array();
    $grader  = array();

    foreach ($submissions as $submission) {
        if (!array_key_exists($submission->cmid, $modinfo->cms)) {
            continue;
        }
        $cm = $modinfo->cms[$submission->cmid];
        if (!$cm->uservisible) {
            continue;
        }
        if ($submission->userid == $USER->id) {
            $show[] = $submission;
            continue;
        }

        /*
         * The act of sumbitting of checkmark may be considered private
         * only graders will see it if specified!
         */
        if (empty($CFG->checkmark_showrecentsubmissions)) {
            if (!array_key_exists($cm->id, $grader)) {
                $grader[$cm->id] = has_capability('moodle/grade:viewall',
                                                  context_module::instance($cm->id));
            }
            if (!$grader[$cm->id]) {
                continue;
            }
        }

        $groupmode = groups_get_activity_groupmode($cm, $course);

        if ($groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups',
                                                             context_module::instance($cm->id))) {
            if (isguestuser()) {
                // Shortcut - guest user does not belong into any group.
                continue;
            }

            if (is_null($modinfo->groups)) {
                // Load all my groups and cache it in modinfo!
                $modinfo->groups = groups_get_user_groups($course->id);
            }

            // This will be slow - show only users that share group with me in this cm!
            if (empty($modinfo->groups[$cm->id])) {
                continue;
            }
            $usersgroups =  groups_get_all_groups($course->id, $submission->userid,
                                                  $cm->groupingid);
            if (is_array($usersgroups)) {
                $usersgroups = array_keys($usersgroups);
                $intersect = array_intersect($usersgroups, $modinfo->groups[$cm->id]);
                if (empty($intersect)) {
                    continue;
                }
            }
        }
        $show[] = $submission;
    }

    if (empty($show)) {
        return false;
    }

    echo $OUTPUT->heading(get_string('newsubmissions', 'checkmark').':', 3);

    foreach ($show as $submission) {
        $cm = $modinfo->cms[$submission->cmid];
        $link = $CFG->wwwroot.'/mod/checkmark/view.php?id='.$cm->id;
        print_recent_activity_note($submission->timemodified, $submission, $cm->name, $link, false,
                                   $viewfullnames);
    }

    return true;
}


/**
 * Returns all checkmarks since a given time in specified forum.
 */
function checkmark_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid,
                                           $userid=0, $groupid=0) {
    global $CFG, $COURSE, $USER, $DB;

    if ($COURSE->id == $courseid) {
        $course = $COURSE;
    } else {
        $course = $DB->get_record('course', array('id'=>$courseid));
    }

    $modinfo =& get_fast_modinfo($course);

    $cm = $modinfo->cms[$cmid];

    $params = array();
    if ($userid) {
        $userselect = "AND u.id = :userid";
        $params['userid'] = $userid;
    } else {
        $userselect = "";
    }

    if ($groupid) {
        $groupselect = "AND gm.groupid = :groupid";
        $groupjoin   = "JOIN {groups_members} gm ON  gm.userid=u.id";
        $params['groupid'] = $groupid;
    } else {
        $groupselect = "";
        $groupjoin   = "";
    }

    $params['cminstance'] = $cm->instance;
    $params['timestart'] = $timestart;

    $userfields = user_picture::fields('u', null, 'userid');

    if (!$submissions = $DB->get_records_sql("SELECT asb.id, asb.timemodified,
                                                $userfields
                                                FROM {checkmark_submissions} asb
                                                JOIN {checkmark} a      ON a.id = asb.checkmark_id
                                                JOIN {user} u            ON u.id = asb.user_id
                                                $groupjoin
                                                WHERE asb.timemodified > :timestart
                                                   AND a.id = :cminstance
                                                $userselect $groupselect
                                                ORDER BY asb.timemodified ASC", $params)) {
        return;
    }

    $groupmode       = groups_get_activity_groupmode($cm, $course);
    $cm_context      = context_module::instance($cm->id);
    $grader          = has_capability('moodle/grade:viewall', $cm_context);
    $accessallgroups = has_capability('moodle/site:accessallgroups', $cm_context);
    $viewfullnames   = has_capability('moodle/site:viewfullnames', $cm_context);

    if (is_null($modinfo->groups)) {
        // Load all my groups and cache it in modinfo!
        $modinfo->groups = groups_get_user_groups($course->id);
    }

    $show = array();

    foreach ($submissions as $submission) {
        if ($submission->userid == $USER->id) {
            $show[] = $submission;
            continue;
        }
        /*
         * The act of submitting of checkmark may be considered private
         * only graders will see it if specified!
         */
        if (empty($CFG->checkmark_showrecentsubmissions)) {
            if (!$grader) {
                continue;
            }
        }

        if ($groupmode == SEPARATEGROUPS and !$accessallgroups) {
            if (isguestuser()) {
                // Shortcut - guest user does not belong into any group.
                continue;
            }

            // This will be slow - show only users that share group with me in this cm!
            if (empty($modinfo->groups[$cm->id])) {
                continue;
            }
            $usersgroups = groups_get_all_groups($course->id, $cm->userid, $cm->groupingid);
            if (is_array($usersgroups)) {
                $usersgroups = array_keys($usersgroups);
                $intersect = array_intersect($usersgroups, $modinfo->groups[$cm->id]);
                if (empty($intersect)) {
                    continue;
                }
            }
        }
        $show[] = $submission;
    }

    if (empty($show)) {
        return;
    }

    if ($grader) {
        require_once($CFG->libdir.'/gradelib.php');
        $userids = array();
        foreach ($show as $id => $submission) {
            $userids[] = $submission->userid;

        }
        $grades = grade_get_grades($courseid, 'mod', 'checkmark', $cm->instance, $userids);
    }

    $aname = format_string($cm->name, true);
    foreach ($show as $submission) {
        $tmpactivity = new stdClass();

        $tmpactivity->cmid         = $cm->id;
        $tmpactivity->name         = $aname;
        $tmpactivity->sectionnum   = $cm->sectionnum;
        $tmpactivity->timestamp    = $submission->timemodified;

        if ($grader) {
            $tmpactivity->grade = $grades->items[0]->grades[$submission->userid]->str_long_grade;
        }

        $userfields = explode(',', user_picture::fields());
        foreach ($userfields as $userfield) {
            if ($userfield == 'id') {
                $tmpactivity->user->{$userfield} = $submission->userid; // Aliased in SQL above!
            } else {
                $tmpactivity->user->{$userfield} = $submission->{$userfield};
            }
        }
        $tmpactivity->user->fullname = fullname($submission, $viewfullnames);

        $activities[$index++] = $tmpactivity;
    }

    return;
}

/**
 * Print recent activity from all checkmarks in a given course
 *
 * This is used by course/recent.php
 */
function checkmark_print_recent_mod_activity($activity, $courseid, $detail, $modnames) {
    global $CFG, $OUTPUT;

    echo '<table border="0" cellpadding="3" cellspacing="0" class="checkmark-recent">';

    echo "<tr><td class=\"userpicture\" valign=\"top\">";
    echo $OUTPUT->user_picture($activity->user);
    echo "</td><td>";

    if ($detail) {
        $modname = get_string('modulename', 'checkmark');
        echo '<div class="title">';
        echo "<img src=\"" . $OUTPUT->pix_url('icon', 'checkmark') . "\" ".
             "class=\"icon\" alt=\"$modname\">";
        echo "<a href=\"$CFG->wwwroot/mod/checkmark/view.php?id={$activity->cmid}\"".
             ">{$activity->name}</a>";
        echo '</div>';
    }

    if (isset($activity->grade)) {
        echo '<div class="grade">';
        echo get_string('grade').': ';
        echo $activity->grade;
        echo '</div>';
    }

    echo '<div class="user">';
    echo "<a href=\"$CFG->wwwroot/user/view.php?id={$activity->user->id}&amp;course=$courseid\">"
    ."{$activity->user->fullname}</a>  - ".userdate($activity->timestamp);
    echo '</div>';

    echo "</td></tr></table>";
}

/**
 * Fetch info from logs
 *
 * @param $log object with properties ->info (the checkmark id) and ->userid
 * @return array with checkmark name and user firstname and lastname
 */
function checkmark_log_info($log) {
    global $CFG, $DB;

    return $DB->get_record_sql("SELECT a.name, u.firstname, u.lastname
                                  FROM {checkmark} a, {user} u
                                 WHERE a.id = ? AND u.id = ?", array($log->info, $log->userid));
}

/**
 * Return list of marked submissions that have not been mailed out for currently enrolled students
 *
 * @return array
 */
function checkmark_get_unmailed_submissions($starttime, $endtime) {
    global $CFG, $DB;

    return $DB->get_records_sql("SELECT s.*, a.course, a.name
                                   FROM {checkmark_submissions} s,
                                        {checkmark} a
                                  WHERE s.mailed = 0
                                        AND s.timemarked <= ?
                                        AND s.timemarked >= ?
                                        AND s.checkmark_id = a.id", array($endtime, $starttime));
}

/**
 * Counts all real checkmark submissions by ENROLLED students (not empty ones)
 *
 * @param $groupid int optional If nonzero then count is restricted to this group
 * @return int The number of submissions
 */
function checkmark_count_real_submissions($cm, $groupid=0) {
    global $CFG, $DB;

    $context = context_module::instance($cm->id);

    // This is all the users with this capability set, in this context or higher!
    if ($users = get_enrolled_users($context, 'mod/checkmark:submit', $groupid, 'u.id')) {
        $users = array_keys($users);
    }

    // If groupmembersonly used, remove users who are not in any group!
    if ($users and !empty($CFG->enablegroupmembersonly) and $cm->groupmembersonly) {
        if ($groupingusers = groups_get_grouping_members($cm->groupingid, 'u.id', 'u.id')) {
            $users = array_intersect($users, array_keys($groupingusers));
        }
    }

    if (empty($users)) {
        return 0;
    }

    list($sqluserlist, $userlistparams) = $DB->get_in_or_equal($users);
    $params = array_merge(array($cm->instance), $userlistparams);

    return $DB->count_records_sql("SELECT COUNT('x')
                                     FROM {checkmark_submissions}
                                    WHERE checkmark_id = ? AND
                                          timemodified > 0 AND
                                          user_id ".$sqluserlist, $params);
}


/**
 * Return all checkmark submissions by ENROLLED students (even empty)
 *
 * @param $sort string optional field names for the ORDER BY in the sql query
 * @param $dir string optional specifying the sort direction, defaults to DESC
 * @return array The submission objects indexed by id
 */
function checkmark_get_all_submissions($checkmark, $sort="", $dir="DESC") {
    // Return all checkmark submissions by ENROLLED students (even empty)!
    global $CFG, $DB;

    if ($sort == "lastname" or $sort == "firstname") {
        $sort = "u.$sort $dir";
    } else if (empty($sort)) {
        $sort = "a.timemodified DESC";
    } else {
        $sort = "a.$sort $dir";
    }

    return $DB->get_records_sql("SELECT a.*
                                   FROM {checkmark_submissions} a, {user} u
                                  WHERE u.id = a.user_id
                                        AND a.checkmark_id = ?
                               ORDER BY $sort", array($checkmark->id));

}

/**
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities()
 * in course/lib.php.
 *
 * @param $coursemodule object The coursemodule object (record).
 * @return object An object on information that the courses will know about
 *                (most noticeably, an icon).
 *
 */
function checkmark_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/mod/checkmark/locallib.php');

    if (! $checkmark = $DB->get_record('checkmark', array('id'=>$coursemodule->instance),
                                       'id, name')) {
        return false;
    }

    $instance = new checkmark('staticonly');
    if ($result = $instance->get_coursemodule_info($coursemodule)) {
        return $result;
    } else {
        $info = new stdClass();
        $info->name = $checkmark->name;
        return $info;
    }
}

/*
 ******************** OTHER GENERAL FUNCTIONS FOR checkmarks  ***********************
 */

/*
 * checkmark_get_summarystring() returns a short statistic over the actual checked examples
 * in this checkmark
 * You've checked out X from a maximum of Y examples. (A out of B points)
 *
 * @return string short summary
 */
function checkmark_getsummarystring($submission, $checkmark) {
    GLOBAL $USER, $CFG, $DB;
    require_once($CFG->dirroot.'/mod/checkmark/locallib.php');

    $checked_examples = 0;
    $checked_grades = 0;
    $max_checked_examples = 0;
    $max_checked_grades = 0;
    $course     = $DB->get_record('course', array('id' => $checkmark->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('checkmark', $checkmark->id, $course->id, false,
                                                 MUST_EXIST);
    $instance = new checkmark($cm->instance, $checkmark, $cm);
    if (!isset($submission)) {
        $submission = $instance->get_submission($USER->id, false); // Get the submission!
    }

    if ($submission) {
        $examplestates = explode(checkmark::DELIMITER, $submission->checked);

        if ($instance->checkmark->flexiblenaming) {
            $examplenames = explode(checkmark::DELIMITER, $instance->checkmark->examplenames);
            $examplegrades = explode(checkmark::DELIMITER, $instance->checkmark->examplegrades);
            for ($i=0; $i<count($examplenames); $i++) {
                $examplenumber = $i+1;
                $state = 0;
                foreach ($examplestates as $singlestate) {
                    if (intval($singlestate) == $examplenumber) {
                        $state = 1;
                        break;
                    }
                }

                if ($state) { // Is it checked?
                    $checked_examples++;
                    $checked_grades += intval($examplegrades[$i]);
                }
                $max_checked_examples++;
                $max_checked_grades += intval($examplegrades[$i]);
            }
        } else {
            $i = 0;
            $points = $instance->checkmark->grade/$instance->checkmark->examplecount;
            do {
                $state = 0;
                $examplenumber = strval($i+$instance->checkmark->examplestart);
                foreach ($examplestates as $singlestate) {
                    if (intval($singlestate) == ($i+1)) {
                        $state = 1;
                        break;
                    }
                }
                if ($state) { // Is it checked?
                    $checked_examples++;
                    $checked_grades += $points;
                }
                $max_checked_examples++;
                $max_checked_grades += $points;
                $i++;
            } while ($i<$instance->checkmark->examplecount);
        }
    } else {
        if ($instance->checkmark->flexiblenaming) {
            $examplegrades = explode(checkmark::DELIMITER, $instance->checkmark->examplegrades);
            $max_checked_examples = count($examplegrades);
            $max_checked_grades = 0;
            for ($i=0; $i<count($examplegrades); $i++) {
                $max_checked_grades += intval($examplegrades[$i]);
            }
        } else {
            $max_checked_examples = $instance->checkmark->examplecount;
            $max_checked_grades = $instance->checkmark->grade;
        }
        $checked_examples = 0;
        $checked_grades = 0;
    }
    $a = new stdClass();
    $a->checked_examples = $checked_examples;
    $a->max_checked_examples = $max_checked_examples;
    $a->checked_grades = $checked_grades;
    $a->max_checked_grades = $max_checked_grades;
    if (!empty($submission->teacher_id) && ($submission->grade != -1)) {
        global $DB;

        /*
         * Cache scales for each checkmark
         * they might have different scales!
         */
        static $scalegrades = array();

        if ($instance->checkmark->grade >= 0) {    // Normal number?
            if ($submission->grade == -1) {
                $a->grade = get_string('notgradedyet', 'checkmark');
            } else {
                $a->grade = get_string('graded', 'checkmark').': '.$submission->grade.
                            ' / '.$instance->checkmark->grade;
            }

        } else {                                // Scale?
            if (empty($scalegrades[$instance->checkmark->id])) {
                if ($scale = $DB->get_record('scale', array('id'=>-($instance->checkmark->grade)))) {
                    $scalegrades[$instance->checkmark->id] = make_menu_from_list($scale->scale);
                } else {
                    $a->grade = get_string('notgradedyet', 'checkmark');
                }
            }
            if (isset($scalegrades[$instance->checkmark->id][$grade])) {
                $a->grade = get_string('graded', 'checkmark').': '.
                            $scalegrades[$instance->checkmark->id][$submission->grade];
            }
        }
    } else {
        $a->grade = get_string('notgradedyet', 'checkmark');
    }
    $output = get_string('checkmark_overviewsummary', 'checkmark', $a);

    return $output;
}

/*
 * checkmark_get_summarystring() returns a short statistic over the actual
 * checked examples in this checkmark
 * You've checked out X from a maximum of Y examples. (A out of B points)
 *
 * @return string short summary
 */
function checkmark_getsubmissionstats($submission, $checkmark) {

    GLOBAL $USER, $CFG, $DB;

    $checked_examples = 0;
    $checked_grades = 0;
    $max_checked_examples = 0;
    $max_checked_grades = 0;

    if ($submission) {
        require_once($CFG->dirroot.'/mod/checkmark/locallib.php');
        $examplestates = explode(checkmark::DELIMITER, $submission->checked);

        if ($checkmark->flexiblenaming) {
            $examplenames = explode(checkmark::DELIMITER, $checkmark->examplenames);
            $examplegrades = explode(checkmark::DELIMITER, $checkmark->examplegrades);
            for ($i=0; $i<count($examplenames); $i++) {
                $examplenumber = $i+1;
                $state = 0;
                foreach ($examplestates as $singlestate) {
                    if (intval($singlestate) == $examplenumber) {
                        $state = 1;
                        break;
                    }
                }

                if ($state) { // Is it checked?
                    $checked_examples++;
                    $checked_grades += intval($examplegrades[$i]);
                }
                $max_checked_examples++;
                $max_checked_grades += intval($examplegrades[$i]);
            }
        } else {
            $i = 0;
            $points = $checkmark->grade/$checkmark->examplecount;
            do {
                $state = 0;
                $examplenumber = strval($i+$checkmark->examplestart);
                foreach ($examplestates as $singlestate) {
                    if (intval($singlestate) == ($i+1)) {
                        $state = 1;
                        break;
                    }
                }
                if ($state) { // Is it checked?
                    $checked_examples++;
                    $checked_grades += $points;
                }
                $max_checked_examples++;
                $max_checked_grades += $points;
                $i++;
            } while ($i<$checkmark->examplecount);
        }
    } else {
        if ($checkmark->flexiblenaming) {
            require_once($CFG->dirroot.'/mod/checkmark/locallib.php');
            $examplegrades = explode(checkmark::DELIMITER, $checkmark->examplegrades);
            $max_checked_examples = count($examplegrades);
            $max_checked_grades = 0;
            for ($i=0; $i<count($examplegrades); $i++) {
                $max_checked_grades += intval($examplegrades[$i]);
            }
        } else {
            $max_checked_examples = $checkmark->examplecount;
            $max_checked_grades = $checkmark->grade;
        }
        $checked_examples = 0;
        $checked_grades = 0;
    }
    $a = new stdClass();
    $a->checked_examples = $checked_examples;
    $a->total_examples = $max_checked_examples;
    $a->checked_grade = $checked_grades;
    $a->total_grade = $max_checked_grades;
    $a->name = $checkmark->name;

    if (!empty($submission->teacher_id) && ($submission->grade != -1)) {
        global $DB;

        /*
         * Cache scales for each checkmark
         * they might have different scales!
         */
        static $scalegrades = array();

        if ($checkmark->grade >= 0) {    // Normal number?
            if ($submission->grade == -1) {
                $a->grade = get_string('notgradedyet', 'checkmark');
            } else {
                $a->grade = get_string('graded', 'checkmark').': '.$submission->grade.
                            ' / '.$checkmark->grade;
            }

        } else {                                // Scale?
            if (empty($scalegrades[$checkmark->id])) {
                if ($scale = $DB->get_record('scale', array('id'=>-($checkmark->grade)))) {
                    $scalegrades[$checkmark->id] = make_menu_from_list($scale->scale);
                } else {
                    $a->grade = get_string('notgradedyet', 'checkmark');
                }
            }
            if (isset($scalegrades[$checkmark->id][$grade])) {
                $a->grade = get_string('graded', 'checkmark').': '.
                            $scalegrades[$checkmark->id][$submission->grade];
            }
        }
    } else {
        $a->grade = get_string('notgradedyet', 'checkmark');
    }
    return $a;
}

/**
 * prepares text for mymoodle-Page to be displayed
 * @param $courses
 * @param $htmlarray
 */
function checkmark_print_overview($courses, &$htmlarray) {
    global $USER, $CFG, $DB, $OUTPUT;

    if (empty($courses) || !is_array($courses) || count($courses) == 0) {
        return;
    }

    if (!$checkmarks = get_all_instances_in_courses('checkmark', $courses)) {
        return;
    }

    $checkmarkids = array();
    $closedids = array();

    // Do checkmark_base::isopen() here without loading the whole thing for speed!
    foreach ($checkmarks as $key => $checkmark) {
        $time = time();
        if ($checkmark->timedue) {
            if ($checkmark->preventlate) {
                $isopen = ($checkmark->timeavailable <= $time && $time <= $checkmark->timedue);
            } else {
                $isopen = ($checkmark->timeavailable <= $time);
            }
        }
        if (empty($isopen) || empty($checkmark->timedue)) { // Is it not open or is it never closed?
            $closedids[] = $checkmark->id;
        } else {
            $checkmarkids[] = $checkmark->id;
        }
    }

    if (empty($checkmarkids) || (count($checkmarkids) == 0)) {
        // No checkmarks to look at - we're done!
        return;
    }

    $strduedate = get_string('duedate', 'checkmark');
    $strduedateno = get_string('duedateno', 'checkmark');
    $strgraded = get_string('graded', 'checkmark');
    $strnotgradedyet = get_string('notgradedyet', 'checkmark');
    $strnotsubmittedyet = get_string('notsubmittedyet', 'checkmark');
    $strsubmitted = get_string('submitted', 'checkmark');
    $strcheckmark = get_string('modulename', 'checkmark');
    $strreviewed = get_string('reviewed', 'checkmark');

    // NOTE: we do all possible database work here *outside* of the loop to ensure this scales!
    list($sqlcheckmarkids, $checkmarkidparams) = $DB->get_in_or_equal($checkmarkids);

    /*
     * Build up and array of unmarked submissions indexed by checkmark id/userid
     * for use where the user has grading rights on checkmark!
     */
    $rs = $DB->get_recordset_sql("SELECT id, checkmark_id, user_id
                            FROM {checkmark_submissions}
                            WHERE teacher_id = 0 AND timemarked = 0
                            AND checkmark_id $sqlcheckmarkids", $checkmarkidparams);

    $unmarkedsubmissions = array();
    foreach ($rs as $rd) {
        $unmarkedsubmissions[$rd->checkmark_id][$rd->user_id] = $rd->id;
    }
    $rs->close();

    $rs = $DB->get_recordset_sql("SELECT checkmark_id, count(DISTINCT user_id) as amount
                                 FROM {checkmark_submissions}
                                 WHERE checkmark_id $sqlcheckmarkids GROUP BY checkmark_id",
                                 $checkmarkidparams);
    $submissioncounts = array();
    foreach ($rs as $rd) {
        $submissioncounts[$rd->checkmark_id] = $rd->amount;
    }
    $rs->close();

    // Get all user submissions, indexed by checkmark id!
    $mysubmissions = $DB->get_records_sql("SELECT checkmark_id, timemarked, teacher_id, grade,
                                                  checked
                                      FROM {checkmark_submissions}
                                      WHERE user_id = ? AND
                                      checkmark_id $sqlcheckmarkids",
                                          array_merge(array($USER->id), $checkmarkidparams));

    // Get all users who submitted something, indexed by checkmark_id!
    foreach ($checkmarkids as $cur_id) {
        $userids = $DB->get_fieldset_select('checkmark_submissions',
                                                      'user_id',
                                                      'checkmark_id = ?', array($cur_id));
        foreach ($userids as $usrid) {
            $usersubmissions[$cur_id][$usrid] = true;
        }
    }

    $statistics = array();
    foreach ($checkmarks as $checkmark) {
        if (!isset($statistics[$checkmark->course])) {
            $statistics[$checkmark->course] = array();
            $statistics[$checkmark->course][0] = new stdClass();
            $statistics[$checkmark->course][0]->total_examples = 0;
            $statistics[$checkmark->course][0]->total_grade = 0;
            $statistics[$checkmark->course][0]->checked_examples = 0;
            $statistics[$checkmark->course][0]->checked_grade = 0;
        }
        $str = '<div class="checkmark overview"><div class="name">'.$strcheckmark. ': '.
               '<a '.($checkmark->visible ? '':' class="dimmed"').
               'title="'.$strcheckmark.'" href="'.$CFG->wwwroot.
               '/mod/checkmark/view.php?id='.$checkmark->coursemodule.'">'.
        $checkmark->name.'</a></div>';
        if ($checkmark->timedue) {
            $str .= '<div class="info">'.$strduedate.': '.userdate($checkmark->timedue).'</div>';
        } else {
            $str .= '<div class="info">'.$strduedateno.'</div>';
        }
        $context = context_module::instance($checkmark->coursemodule);

        if (has_capability('mod/checkmark:grade', $context)) {
            // Teachers view with information about submitted checkmarks and required gradings!

            $teachers = get_users_by_capability($context, 'mod/checkmark:grade');
            $teacher_submissions = 0;
            $teacher_submissions_graded = 0;
            $subs  = $DB->get_records("checkmark_submissions",
                                      array("checkmark_id" => $checkmark->id));
            foreach ($subs as $cur) {
                if (array_key_exists($cur->user_id, $teachers)) {
                    // Teacher did a submission!
                    $teacher_submissions++;

                    if ($cur->teacher_id != 0 || $cur->timemarked !=0) {
                        $teacher_submissions_graded++;
                    }
                }
            }
            // Count how many people can submit!
            $submissions = new stdClass();
            $amount = new stdClass();
            $submissions->reqgrading = 0; // Init!

            $totalstudents = 0;
            $studentsubmissions = 0;
            if ($students = get_enrolled_users($context, 'mod/checkmark:view', 0, 'u.id')) {
                foreach ($students as $student) {
                    if (!has_capability('mod/checkmark:grade', $context, $student->id)) {
                        $totalstudents++;
                        if (isset($unmarkedsubmissions[$checkmark->id][$student->id])) {
                            $submissions->reqgrading++;
                        }
                        if (isset($usersubmissions[$checkmark->id][$student->id])) {
                            $studentsubmissions++;
                        }
                    }
                }
            }

            $amount->total = $totalstudents;
            $amount->submitted = $studentsubmissions;
            $submissions->all = $studentsubmissions;
            $submissions->graded = $submissions->all - $submissions->reqgrading;
            if ($amount->total == $amount->submitted) { // Everyone has submitted!
                $submittedclass = 'allsubmitted';
            } else {
                $submittedclass = 'submissionsmissing';
            }
            if ($submissions->reqgrading > 0) {
                $reqgradingclass = 'tobegraded';
            } else {
                $reqgradingclass = 'allgraded';
            }
            $link = new moodle_url('/mod/checkmark/submissions.php',
                                   array('id'=>$checkmark->coursemodule));
            $str .= '<div class="details">';
            $str .= '<a href="'.$link.'"><span class="'.$submittedclass.'">'.
                    get_string('submissionsamount', 'checkmark', $amount).'</span><br />';
            if ($submissions->all != 0) {
                $str .= '(<span class="'.$reqgradingclass.'">'.
                        get_string('submissionsgraded', 'checkmark', $submissions).'</span>)';
            }
            $str .= '</a>';
            $str .= '</div>';

        } else if (has_capability('mod/checkmark:submit', $context)) {
            $str .= '<div class="details">';
            if (isset($mysubmissions[$checkmark->id])) {

                $submission = $mysubmissions[$checkmark->id];
                // Is grade in gradebook more actual?
                $grading_info = grade_get_grades($checkmark->course, 'mod', 'checkmark',
                                                 $checkmark->id, $USER->id);
                $item = $grading_info->items[0];
                $grade = $item->grades[$USER->id];
                if ($grading_info->items[0]->grades[$USER->id]->overridden) {
                    $submission->grade = round($grade->grade, 2);
                }
                if ($submission->teacher_id == 0 && $submission->timemarked == 0) {
                    $str .= checkmark_getsummarystring($submission, $checkmark);
                } else if ($submission->grade <= 0) {
                    $str .= checkmark_getsummarystring($submission, $checkmark);
                } else {
                    $str .= checkmark_getsummarystring($submission, $checkmark);
                }
                $statistics[$checkmark->course][] = checkmark_getsubmissionstats($submission, $checkmark);
                $idx = count($statistics[$checkmark->course])-1;

                if (!isset($statistics[$checkmark->course][0]->name)) {
                    $statistics[$checkmark->course][0]->name = get_string('strsum', 'checkmark');
                }
                $statistics[$checkmark->course][0]->checked_examples += $statistics[$checkmark->course][$idx]->checked_examples;
                $statistics[$checkmark->course][0]->total_examples += $statistics[$checkmark->course][$idx]->total_examples;
                $statistics[$checkmark->course][0]->checked_grade += $statistics[$checkmark->course][$idx]->checked_grade;
                $statistics[$checkmark->course][0]->total_grade += $statistics[$checkmark->course][$idx]->total_grade;
            } else {
                $str .= $strnotsubmittedyet . ' ' . checkmark_display_lateness(time(), $checkmark->timedue);
                $statistics[$checkmark->course][] = checkmark_getsubmissionstats(null, $checkmark);

                $idx = count($statistics[$checkmark->course])-1;

                if (!isset($statistics[$checkmark->course][0]->name)) {
                    $statistics[$checkmark->course][0]->name = get_string('strsum', 'checkmark');
                }
                $statistics[$checkmark->course][0]->checked_examples += $statistics[$checkmark->course][$idx]->checked_examples;
                $statistics[$checkmark->course][0]->total_examples += $statistics[$checkmark->course][$idx]->total_examples;
                $statistics[$checkmark->course][0]->checked_grade += $statistics[$checkmark->course][$idx]->checked_grade;
                $statistics[$checkmark->course][0]->total_grade += $statistics[$checkmark->course][$idx]->total_grade;
            }
            $str .= '</div>';
        }
        $str .= '</div>';
        if (empty($htmlarray[$checkmark->course]['checkmark'])
                 && !in_array($checkmark->id, $closedids)) {
            $htmlarray[$checkmark->course]['checkmark'] = $str;
        } else if (!in_array($checkmark->id, $closedids)) {
            $htmlarray[$checkmark->course]['checkmark'] .= $str;
        }
    }

    // Append statistics!
    // First get courses with checkmarks!

    $sql = "SELECT DISTINCT course FROM {checkmark}";
    $courses = $DB->get_fieldset_sql($sql);
    if (!$courses) {
        return;
    }
    foreach ($courses as $currentcourse) {
        $str = '';
        $context = context_course::instance(intval($currentcourse));
        if (has_capability('mod/checkmark:grade', $context)) {
            continue; // Skip for teachers view!
        }
        $str .= html_writer::start_tag('div', array('class'=>'checkmark overview statistics')).
                html_writer::tag('div', get_string('checkmarkstatstitle', 'checkmark'),
                                 array('class'=>'name'));
        if (!key_exists($currentcourse, $statistics)) {
            continue;
        }
        $strname = html_writer::start_tag('div', array('class'=>'name'));
        $strexamples = html_writer::start_tag('div', array('class'=>'examples'));
        $strgrades = html_writer::start_tag('div', array('class'=>'grades'));
        $strgrade = html_writer::start_tag('div', array('class'=>'grade'));
        $str .= html_writer::start_tag('div', array('class'=>'details'));

        foreach ($statistics[$currentcourse] as $key => $statistic) {
            if ($key != 0) {

                $strname .= html_writer::tag('div', $statistic->name, array('class'=>'element'));
                $strexamples .= html_writer::tag('div', $statistic->checked_examples.' / '.
                                                        $statistic->total_examples,
                                                 array('class'=>'element'));
                $strgrades .= html_writer::tag('div', $statistic->checked_grade.' / '.
                                                      $statistic->total_grade,
                                               array('class'=>'element'));
                $strgrade .= html_writer::tag('div', $statistic->grade, array('class'=>'element'));
            }
        }

        $statistic = $statistics[$currentcourse][0];
        $strname .= html_writer::tag('div', $statistic->name, array('class'=>'element total'));
        $strexamples .= html_writer::tag('div', $statistic->checked_examples.' / '.
                                                $statistic->total_examples,
                                         array('class'=>'element total'));
        $strgrades .= html_writer::tag('div', $statistic->checked_grade.' / '.
                                              $statistic->total_grade,
                                       array('class'=>'element total'));

        $strname .= html_writer::end_tag('div');
        $strexamples .= html_writer::end_tag('div');
        $strgrades .= html_writer::end_tag('div');
        $strgrade .= html_writer::end_tag('div');

        $str .= $strname . $strexamples . $strgrades . $strgrade;
        $str .= html_writer::end_tag('div');
        $str .= html_writer::end_tag('div');

        if (empty($htmlarray[strval($currentcourse)]['checkmark'])) {
            $htmlarray[strval($currentcourse)]['checkmark'] = $str;
        } else {
            $htmlarray[strval($currentcourse)]['checkmark'] .= $str;
        }
    }
}

function checkmark_display_lateness($timesubmitted, $timedue) {
    if (!$timedue) {
        return '';
    }
    $time = $timedue - $timesubmitted;

    if ($time >= 7*24*60*60) { // More than 7 days?
        $timetext = get_string('early', 'checkmark', format_time($time));
        return ' (<span class="early">'.$timetext.'</span>)';
    } else if ($time >= 24*60*60) { // More than 1 day but less than 7 days?
        $timetext = get_string('early', 'checkmark', format_time($time));
        return ' (<span class="soon">'.$timetext.'</span>)';
    } else if ($time >= 0) { // In the future but less than 1 day?
        $timetext = get_string('early', 'checkmark', format_time($time));
        return ' (<span class="today">'.$timetext.'</span>)';
    } else {
        $timetext = get_string('late', 'checkmark', format_time($time));
        return ' (<span class="late">'.$timetext.'</span>)';
    }
}

function checkmark_get_view_actions() {
    return array('view', 'view submission', 'view submission', 'view print-preview');
}

function checkmark_get_post_actions() {
    return array('upload');
}

/**
 * Removes all grades from gradebook
 * @param int $courseid
 */
function checkmark_reset_gradebook($courseid) {
    global $CFG, $DB;

    $params = array('courseid'=>$courseid);

    $sql = "SELECT a.*, cm.idnumber as cmidnumber, a.course as courseid
            FROM {checkmark} a, {course_modules} cm, {modules} m
            WHERE m.name='checkmark' AND m.id=cm.module
                                     AND cm.instance=a.id
                                     AND a.course=:courseid";

    if ($checkmarks = $DB->get_records_sql($sql, $params)) {
        foreach ($checkmarks as $checkmark) {
            checkmark_grade_item_update($checkmark, 'reset');
        }
    }
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * This function will remove all posts from the specified checkmark
 * and clean up any related data.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function checkmark_reset_userdata($data) {
    global $CFG;

    $status = array();
    require_once($CFG->dirroot.'/mod/checkmark/locallib.php');
    $checkmark = new checkmark();
    $status = array_merge($status, $checkmark->reset_userdata($data));

    return $status;
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the checkmark.
 * @param $mform form passed by reference
 */
function checkmark_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'checkmarkheader', get_string('modulenameplural', 'checkmark'));
    $mform->addElement('advcheckbox', 'reset_checkmark_submissions',
                       get_string('deleteallsubmissions', 'checkmark'));
}

/**
 * Course reset form defaults.
 */
function checkmark_reset_course_form_defaults($course) {
    return array('reset_checkmark_submissions'=>1);
}

/**
 * Returns all other caps used in module
 */
function checkmark_get_extra_capabilities() {
    return array('moodle/site:accessallgroups', 'moodle/site:viewfullnames');
}

/**
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function checkmark_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_GRADE_OUTCOMES:          return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_BACKUP_MOODLE2:          return true;

        default: return null;
    }
}

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $checkmarknode The node to add module settings to
 */
function checkmark_extend_settings_navigation(settings_navigation $settings,
                                              navigation_node $checkmarknode) {
    global $PAGE, $DB, $USER, $CFG;

    $checkmarkrow = $DB->get_record("checkmark", array("id" => $PAGE->cm->instance));
    require_once("$CFG->dirroot/mod/checkmark/locallib.php");

    $checkmarkinstance = new checkmark($PAGE->cm->id, $checkmarkrow, $PAGE->cm, $PAGE->course);

    $allgroups = false;

    // Add checkmark submission information!
    if (has_capability('mod/checkmark:grade', $PAGE->cm->context)) {
        if ($allgroups && has_capability('moodle/site:accessallgroups', $PAGE->cm->context)) {
            $group = 0;
        } else {
            $group = groups_get_activity_group($PAGE->cm);
        }
        $link = new moodle_url('/mod/checkmark/submissions.php', array('id'=>$PAGE->cm->id));
        if ($count = $checkmarkinstance->count_real_submissions($group)) {
            $string = get_string('viewsubmissions', 'checkmark', $count);
        } else {
            $string = get_string('noattempts', 'checkmark');
        }
        $checkmarknode->add($string, $link, navigation_node::TYPE_SETTING);
    }

    if (is_object($checkmarkinstance)
             && method_exists($checkmarkinstance, 'extend_settings_navigation')) {
        $checkmarkinstance->extend_settings_navigation($checkmarknode);
    }
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function checkmark_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array(
        'mod-checkmark-*'=>get_string('page-mod-checkmark-x', 'checkmark'),
        'mod-checkmark-view'=>get_string('page-mod-checkmark-view', 'checkmark'),
        'mod-checkmark-submissions'=>get_string('page-mod-checkmark-submissions', 'checkmark')
    );
    return $module_pagetype;
}
