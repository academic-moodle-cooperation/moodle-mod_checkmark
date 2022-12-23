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
 * lib.php - This file contains the basic checkmark functions
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/** GRADE ITEM */
define('CHECKMARK_GRADE_ITEM', 0);
/** ATTENDANCE ITEM */
define('CHECKMARK_ATTENDANCE_ITEM', 1);
/** PRESENTATIONGRADE ITEM */
define('CHECKMARK_PRESENTATION_ITEM', 2);

/** EVENT TYPE DUE - deadline for student's submissions */
define('CHECKMARK_EVENT_TYPE_DUE', 'due'); // Is backwards compatible to former events!

/** EVENT TYPE GRADINGDUE - reminder for teachers to grade */
define('CHECKMARK_EVENT_TYPE_GRADINGDUE', 'gradingdue');

define('CHECKMARK_INTROATTACHMENT_FILEAREA', 'introattachment');

/**
 * Deletes a checkmark instance
 *
 * This is done by calling the delete_instance() method
 *
 * @param int $id ID of checkmark-instance to delete
 * @return bool true if OK, else false
 */
function checkmark_delete_instance($id) {
    global $DB, $OUTPUT;

    // Bad practice, but we had some issues deleting corrupt checkmark instances with >200k examples!
    core_php_time_limit::raise(600);
    raise_memory_limit(MEMORY_UNLIMITED);

    if (!$checkmark = $DB->get_record('checkmark', array('id' => $id))) {
        return false;
    }

    if (!$cm = get_coursemodule_from_instance('checkmark', $checkmark->id)) {
        echo $OUTPUT->notification('invalidinstance(CMID=' . $cm->id . ' CheckmarkID=' . $checkmark->id . ')', 'notifyproblem');
    }

    $result = true;

    // Now get rid of all files!
    $fs = get_file_storage();
    if (!empty($cm)) {
        $context = context_module::instance($cm->id);
        $fs->delete_area_files($context->id);
    }

    if (!$DB->delete_records('checkmark_feedbacks', array('checkmarkid' => $checkmark->id))) {
        $result = false;
    }

    $submissions = $DB->get_fieldset_select('checkmark_submissions', 'id',
            'checkmarkid = :checkmarkid',
            array('checkmarkid' => $checkmark->id));
    if (!empty($submissions)) {
        list($ssql, $sparams) = $DB->get_in_or_equal($submissions, SQL_PARAMS_NAMED);
    } else {
        // No dataset should have submissionid = NULL so we can use this for our OR to select whom do delete!
        $ssql = ' = NULL';
        $sparams = array();
    }
    if (!$DB->delete_records('checkmark_submissions', array('checkmarkid' => $checkmark->id))) {
        $result = false;
    }

    $examples = $DB->get_fieldset_select('checkmark_examples', 'id',
            'checkmarkid = :checkmarkid',
            array('checkmarkid' => $checkmark->id));
    if (!empty($examples)) {
        list($esql, $eparams) = $DB->get_in_or_equal($examples, SQL_PARAMS_NAMED);
    } else {
        // No dataset should have exampleid = NULL so we can use this for our OR to select whom do delete!
        $esql = ' = NULL';
        $eparams = array();
    }
    if (!$DB->delete_records('checkmark_examples', array('checkmarkid' => $checkmark->id))) {
        $result = false;
    }

    if (!empty($examples) || !empty($submissions)) {
        $DB->delete_records_select('checkmark_checks', 'submissionid ' . $ssql . ' OR exampleid ' . $esql,
                array_merge($sparams, $eparams));
    }

    if (!$DB->delete_records('event', array('modulename' => 'checkmark',
            'instance' => $checkmark->id))) {
        $result = false;
    }

    if (!$DB->delete_records('checkmark', array('id' => $checkmark->id))) {
        $result = false;
    }

    checkmark_grade_item_delete($checkmark);
    checkmark_attendance_item_delete($checkmark);
    checkmark_presentation_item_delete($checkmark);

    return $result;
}

/**
 * Updates a checkmark instance
 *
 * @param object $checkmark Checkmark-data from form
 * @return bool true if OK, else false
 */
function checkmark_update_instance($checkmark) {
    global $CFG, $OUTPUT, $DB;

    $checkmark->timemodified = time();

    // Clean examplenames and examplegrades!
    $checkmark->examplenames = preg_replace('#^,*|,*$#', '',
            $checkmark->examplenames, -1);
    $checkmark->examplenames = preg_replace('#,{2,}#', ',', $checkmark->examplenames, -1);
    $checkmark->examplegrades = preg_replace('#^,*|,*$#', '',
            $checkmark->examplegrades, -1);
    $checkmark->examplegrades = preg_replace('#,{2,}#', ',', $checkmark->examplegrades, -1);

    $checkmark->id = $checkmark->instance;

    if (!empty($checkmark->presentationfeedbackpresent)) {
        /* If there are presentation feedbacks present we won't change these settings,
         * so get presentationgrade, everything else hasn't changed! */
        $checkmark->presentationgrade = $DB->get_field('checkmark', 'presentationgrade', array('id' => $checkmark->instance));
    } else if (empty($checkmark->presentationgrading)) {
        $checkmark->presentationgrade = 0;
        $checkmark->presentationgradebook = 0;
        checkmark_presentation_item_delete($checkmark);
    }

    if (!empty($checkmark->flexiblenaming)) {
        $checkmark->flexiblenaming = 1;
    }

    $DB->update_record('checkmark', $checkmark);

    save_intro_draft_files($checkmark, $checkmark->coursemodule);

    checkmark_update_examples($checkmark);

    checkmark_refresh_events($checkmark->course, $checkmark);

    if (!empty($examples)) {
        if (empty($checkmark->flexiblenaming)) {
            $examplecount = $checkmark->examplecount;
        } else {
            $examplecount = count(explode(checkmark::DELIMITER, $checkmark->examplenames));
        }

        if (!$DB->record_exists('checkmark_submissions', ['checkmarkid' => $checkmark->instance])
                || count($examples) == $examplecount) {
            /* We won't change the grades after someone submitted already - otherwise he/she would
             * have submitted with other informations than displayed
             *
             * Get existing grade item!
             */
            checkmark_grade_item_update($checkmark);
        }
    } else {
        checkmark_grade_item_update($checkmark);
    }

    if ($checkmark->trackattendance && $checkmark->attendancegradebook) {
        checkmark_attendance_item_update($checkmark);
        checkmark_update_attendances($checkmark);
    } else {
        checkmark_attendance_item_delete($checkmark);
    }

    if (empty($checkmark->presentationfeedbackpresent)) {
        if (!empty($checkmark->presentationgrading) && !empty($checkmark->presentationgradebook)) {
            checkmark_presentation_item_update($checkmark);
            checkmark_update_presentation_grades($checkmark);
        } else {
            checkmark_presentation_item_delete($checkmark);
        }
    } else if (empty($checkmark->presentationgradebook)) {
        // We have all the data save in our own table, so we can restore it anytime!
        checkmark_presentation_item_delete($checkmark);
    } else {
        checkmark_presentation_item_update($checkmark);
        checkmark_update_presentation_grades($checkmark);
    }

    if (!$cm = get_coursemodule_from_instance('checkmark', $checkmark->id)) {
        echo $OUTPUT->notification('invalidinstance(' . $checkmark->id . ')', 'notifyproblem');
    }

    return true;
}

/**
 * Adds a checkmark instance
 *
 * @param object $checkmark Checkmark-data from form
 * @return int new checkmark id
 */
function checkmark_add_instance($checkmark) {
    global $DB;
    $checkmark->timemodified = time();

    if (!empty($checkmark->flexiblenaming)) {
        $checkmark->flexiblenaming = 1;
    }

    // Clean examplenames and examplegrades!
    $checkmark->examplenames = preg_replace('#^,*|,*$#', '', $checkmark->examplenames, -1);
    $checkmark->examplenames = preg_replace('#,{2,}#', ',', $checkmark->examplenames, -1);
    $checkmark->examplegrades = preg_replace('#^,*|,*$#', '', $checkmark->examplegrades, -1);
    $checkmark->examplegrades = preg_replace('#,{2,}#', ',', $checkmark->examplegrades, -1);

    $returnid = $DB->insert_record('checkmark', $checkmark);
    $checkmark->instance = $returnid;

    save_intro_draft_files($checkmark, $checkmark->coursemodule);

    checkmark_update_examples($checkmark, $checkmark->coursemodule);

    checkmark_refresh_events($checkmark->course, $returnid);

    checkmark_grade_item_update($checkmark);
    if ($checkmark->trackattendance && $checkmark->attendancegradebook) {
        checkmark_attendance_item_update($checkmark);
    }

    if (!empty($checkmark->presentationgrading) && !empty($checkmark->presentationgradebook)) {
        checkmark_presentation_item_update($checkmark);
    }
    checkmark_grade_item_category_update($checkmark);

    return $returnid;
}

/**
 * Save the attachments in the draft areas.
 *
 * @param stdClass $formdata Formdata containing the introattachments file manager
 * @param int $cmid Course module id of the checkmark
 */
function save_intro_draft_files($formdata, $cmid) {
    if (isset($formdata->introattachments)) {
        $context = context_module::instance($cmid);
        file_save_draft_area_files($formdata->introattachments, $context->id,
                'mod_checkmark', CHECKMARK_INTROATTACHMENT_FILEAREA, 0);
    }
}

/**
 * Serves intro checkmark files.
 *
 * @param mixed $course course or id of the course
 * @param mixed $cm course module or id of the course module
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function mod_checkmark_pluginfile($course,
        $cm,
        context $context,
        $filearea,
        $args,
        $forcedownload,
        array $options=array()) {
    global $CFG;

    require_login($course, false, $cm);
    if (!has_capability('mod/checkmark:view', $context)) {
        return false;
    }

    require_once($CFG->dirroot . '/mod/checkmark/locallib.php');
    $checkmark = new checkmark($cm->id);

    if ($filearea !== CHECKMARK_INTROATTACHMENT_FILEAREA) {
        return false;
    }
    $itemid = (int)array_shift($args);
    if ($itemid != 0) {
        return false;
    }

    $relativepath = implode('/', $args);
    $fullpath = "/{$context->id}/mod_checkmark/$filearea/$itemid/$relativepath";

    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Updates the examples in the DB for this checkmark
 *
 * @param object $checkmark containing data from checkmarks mod_form
 * @param int $cmid (optional, if not set, we get it via get_coursemodule_from_instance())
 * @since MOODLE 2.4
 */
function checkmark_update_examples($checkmark, $cmid = false) {
    global $DB;

    if (!is_object($checkmark)) {
        // Something wrong happened, but this should never happen!
        throw new coding_exception('The checkmark param to checkmark_update_examples() must be an' .
                ' object containing data from the mod_form.');
    }

    if (!$cmid) {
        $cm = get_coursemodule_from_instance('checkmark', $checkmark->instance);
        $cmid = $cm->id;
    }
    $examples = $DB->get_records('checkmark_examples', ['checkmarkid' => $checkmark->instance], 'id ASC');

    if (!empty($examples)) {
        if (empty($checkmark->flexiblenaming)) {
            $examplecount = $checkmark->examplecount;
        } else {
            $examplecount = count(explode(checkmark::DELIMITER, $checkmark->examplenames));
        }

        if ($DB->record_exists('checkmark_submissions', ['checkmarkid' => $checkmark->instance])
                && count($examples) !== $examplecount) {
            return;
        }

        if (checkmark::get_autograded_feedbacks($checkmark->instance)) {
            \core\notification::info(get_string('remembertoupdategrades', 'checkmark'));
        }
    }

    reset($examples);
    if (empty($checkmark->flexiblenaming)) {
        // Standard-naming.
        $i = $checkmark->examplestart;
        if ($checkmark->grade >= 0 && !empty($checkmark->examplecount)) {
            $grade = $checkmark->grade / $checkmark->examplecount;
        } else {
            $grade = 0;
        }
        // First we go through the old examples.
        while ($example = current($examples)) {
            if ($i < $checkmark->examplestart + $checkmark->examplecount) {
                // If there are more new examples replace the old ones with the new ones!
                if (($i != $example->name) || ($grade != $example->grade)) {
                    $old = clone $example;
                    $example->name = $i;
                    $example->grade = $grade;
                    $DB->update_record('checkmark_examples', $example);
                    \mod_checkmark\event\example_updated::get($cmid, $old, $example)->trigger();
                }
            } else {
                // If there are enough examples delete the rest of the old ones!
                $DB->delete_records('checkmark_examples', ['id' => $example->id]);
                \mod_checkmark\event\example_deleted::get($cmid, $example);
            }
            $i++;
            next($examples);
        }
        // Add all the new examples if there haven't been enough old ones to update!
        while ($i < $checkmark->examplestart + $checkmark->examplecount) {
            $example = new stdClass();
            $example->name = $i;
            $example->grade = $grade;
            $example->checkmarkid = $checkmark->instance;
            $example->id = $DB->insert_record('checkmark_examples', $example);
            \mod_checkmark\event\example_created::get($cmid, $example)->trigger();
            $i++;
        }
    } else {
        // Flexiblenaming!
        $names = explode(checkmark::DELIMITER, $checkmark->examplenames);
        $grades = explode(checkmark::DELIMITER, $checkmark->examplegrades);
        reset($examples);
        foreach (array_keys($names) as $key) {
            if ($next = current($examples)) {
                if (($next->name !== $names[$key]) || (empty($next->grade) && empty($grades[$key]))
                        || ($next->grade !== $grades[$key])) {
                    $old = clone $next;
                    // If there's an old example to update, we reuse them!
                    $next->name = html_entity_decode($names[$key]);
                    $next->grade = null;
                    if (!empty($grades[$key])) {
                        $next->grade = $grades[$key];
                    }
                    if (empty($next->grade)) {
                        $next->grade = 0;
                    }
                    $DB->update_record('checkmark_examples', $next);
                    \mod_checkmark\event\example_updated::get($cmid, $old, $next)->trigger();
                }
            } else {
                // Or we create new ones if there aren't any old ones left!
                $example = new stdClass();
                $example->checkmarkid = $checkmark->instance;
                $example->name = html_entity_decode($names[$key]);
                $example->grade = null;
                if (!empty($grades[$key])) {
                    $example->grade = $grades[$key];
                }
                if (empty($example->grade)) {
                    $example->grade = 0;
                }
                $example->id = $DB->insert_record('checkmark_examples', $example);
                \mod_checkmark\event\example_created::get($cmid, $example)->trigger();
            }
            next($examples);
        }
        while ($next = current($examples)) { // We delete the rest if there are any old left!
            $DB->delete_records('checkmark_examples', ['id' => $next->id]);
            \mod_checkmark\event\example_deleted::get($cmid, $next);
            next($examples);
        }
    }
}

/**
 * Returns an outline of a user interaction with an checkmark
 *
 * This is done by calling the user_outline() method
 *
 * @param object $course Course object
 * @param object $user User object
 * @param object $mod Course module object
 * @param object $checkmark Checkmark object
 * @return object
 */
function checkmark_user_outline($course, $user, $mod, $checkmark) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/checkmark/locallib.php');
    require_once($CFG->libdir . '/gradelib.php');
    $instance = new checkmark($mod->id, $checkmark, $mod, $course);
    $grades = grade_get_grades($course->id, 'mod', 'checkmark', $checkmark->id, $user->id);
    if (!empty($grades->items[CHECKMARK_GRADE_ITEM]->grades)) {
        return $instance->user_outline(reset($grades->items[CHECKMARK_GRADE_ITEM]->grades));
    } else {
        return null;
    }
}

/**
 * Prints the complete info about a user's interaction with an checkmark
 *
 * This is done by calling the user_complete() method
 *
 * @param object $course Course object
 * @param object $user User object
 * @param object $mod course module object
 * @param object $checkmark checkmark object
 * @return void
 */
function checkmark_user_complete($course, $user, $mod, $checkmark) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/checkmark/locallib.php');
    require_once($CFG->libdir . '/gradelib.php');

    $instance = new checkmark($mod->id, $checkmark, $mod, $course);
    $grades = grade_get_grades($course->id, 'mod', 'checkmark', $checkmark->id, $user->id);
    if (empty($grades->items[CHECKMARK_GRADE_ITEM]->grades)) {
        $grade = false;
    } else {
        $grade = reset($grades->items[CHECKMARK_GRADE_ITEM]->grades);
    }
    return $instance->user_complete($user, $grade);
}

/**
 * Add a get_coursemodule_info function in case any checkmark type wants to add 'extra' information
 * for the course (see resource).
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses
 *                        will know about (most noticeably, an icon).
 */
function checkmark_get_coursemodule_info($coursemodule) {
    global $DB, $USER;

    $dbparams = array('id' => $coursemodule->instance);
    $fields = 'id, name, alwaysshowdescription, timeavailable, timedue, intro, introformat, completionsubmit';
    if (!$checkmark = $DB->get_record('checkmark', $dbparams, $fields)) {
        return false;
    }

    if ($overridden = checkmark_get_overridden_dates($checkmark->id, $USER->id, $coursemodule->course)) {
        if ($overridden->timeavailable !== null) {
            $checkmark->timeavailable = $overridden->timeavailable;
        }
    }

    $result = new cached_cm_info();
    $result->name = $checkmark->name;
    if ($coursemodule->showdescription) {
        if ($checkmark->alwaysshowdescription || (time() > $checkmark->timeavailable)) {
            // Convert intro to html. Do not filter cached version, filters run at display time.
            $result->content = format_module_intro('checkmark', $checkmark, $coursemodule->id, false);
        } else {
            unset($result->content);
        }
    }

    // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $result->customdata['customcompletionrules']['completionsubmit'] = $checkmark->completionsubmit;
    }

    // Populate some other values that can be used in calendar or on dashboard.
    if ($checkmark->timeavailable) {
        $result->customdata['timeavailable'] = $checkmark->timeavailable;
    }
    if ($checkmark->timedue) {
        $result->customdata['timedue'] = $checkmark->timedue;
    }

    return $result;
}

/**
 * This function returns the overridden values for timeavailable, timedue and cutoffdate or false!
 * It checks for user and group overrides. A user override has priority over a group override.
 * Otherwise, the override of the group with the highest priority is considered.
 * It uses a static variable to cache the results and possibly lessen DB queries! TODO: examine if we need some other cache for it!
 *
 * @param int $checkmarkid The checkmark-ID to get the overridden dates for.
 * @param int $userid (optional) 0 to get all user's overrides or a specific user's ID
 * @param int $courseid If of the course the checkmark activity is located in. Required when group overrides should be considered
 * @return stdClass|bool
 * @throws coding_exception
 * @throws dml_exception
 */
function checkmark_get_overridden_dates($checkmarkid, $userid = 0, $courseid = 0) {
    global $USER, $DB;

    static $cached = [];

    if ($userid === 0) {
        $userid = $USER->id;
    }

    if (key_exists($userid, $cached) && key_exists($checkmarkid, $cached[$userid])) {
        return $cached[$userid][$checkmarkid];
    }

    // Retrieves all groupings and groups a user is part of.
    $groups = groups_get_user_groups($courseid, $userid);
    // Flattens groupings/groups array to one dimension.
    $groups = call_user_func_array('array_merge', $groups);

    $records = array();
    if (!empty($groups) && is_array($groups)) {
        list($insql, $params) = $DB->get_in_or_equal($groups);
        array_push($params, $checkmarkid);
        $sql = "SELECT id, timeavailable, timedue, cutoffdate, groupid FROM {checkmark_overrides}
            WHERE groupid $insql AND checkmarkid = ? ORDER BY grouppriority DESC";
        $records = $DB->get_records_sql($sql, $params, 0, 1);
    }

    $userrecords = $DB->get_records('checkmark_overrides', ['checkmarkid' => $checkmarkid, 'userid' => $userid], "timecreated DESC",
            "id, timeavailable, timedue, cutoffdate, userid", 0, 1);

    if (count($userrecords)) {
        $records = $userrecords;
    }

    if (!key_exists($userid, $cached)) {
        $cached[$userid] = [];
    }

    if (count($records)) {
        $cached[$userid][$checkmarkid] = reset($records);
    } else {
        $cached[$userid][$checkmarkid] = false;
    }

    return $cached[$userid][$checkmarkid];
}

/**
 * Return overridden dates for a given group in a given checkmark activity
 *
 * @param int $checkmarkid Id of the checkmark activity to be checked
 * @param int $groupid Id of the group to be checked for
 * @return stdClass|void Overridden dates if there are any. Empty object otherwise
 * @throws dml_exception
 */
function checkmark_get_override_dates_for_group($checkmarkid, $groupid) {
    global $DB;
    if (empty($groupid)) {
        return;
    }
    $records = $DB->get_records('checkmark_overrides', ['checkmarkid' => $checkmarkid, 'groupid' => $groupid],
            'grouppriority DESC', 'id, timeavailable, timedue, cutoffdate', 0, 1);
    return array_values($records)[0];
}

/**
 * Return grade for given user or all users.
 *
 * @param object $checkmark checkmark object
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function checkmark_get_user_grades($checkmark, $userid = 0) {
    global $DB;

    if ($userid) {
        $user = 'AND u.id = :userid';
        $params = array('userid' => $userid);
    } else {
        $user = '';
    }
    $params['aid'] = $checkmark->id;

    $sql = 'SELECT u.id, u.id AS userid, f.grade AS rawgrade, f.feedback AS feedback,
                   f.format AS feedbackformat, f.graderid AS usermodified,
                   f.timemodified AS dategraded
              FROM {user} u, {checkmark_feedbacks} f
             WHERE u.id = f.userid AND f.checkmarkid = :aid' .
            $user;
    return $DB->get_records_sql($sql, $params);
}

/**
 * returns symbol for the attendance value (1, 0, other)
 *
 * @param bool|mixed $attendance 1, 0 or other value (incl. null)
 * @return string HTML snippet with attendance symbol
 */
function checkmark_get_attendance_symbol($attendance = null) {
    global $OUTPUT;

    if ($attendance == 1) {
        $attendantstr = strtolower(get_string('attendant', 'checkmark'));
        $symbol = $OUTPUT->pix_icon('i/valid', $attendantstr, 'moodle', array('title' => $attendantstr));
    } else if (($attendance == 0) && ($attendance != null)) {
        $absentstr = strtolower(get_string('absent', 'checkmark'));
        $symbol = $OUTPUT->pix_icon('i/invalid', $absentstr, 'moodle', array('title' => $absentstr));
    } else {
        $unknownstr = strtolower(get_string('unknown', 'checkmark'));
        $symbol = $OUTPUT->pix_icon('questionmark', $unknownstr, 'checkmark', array('title' => $unknownstr));
    }

    return $symbol;
}

/**
 * Return attendance for given user or all users.
 *
 * @param object $checkmark checkmark object
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function checkmark_get_user_attendances($checkmark, $userid = 0) {
    global $DB;

    if ($userid) {
        $user = 'AND u.id = :userid';
        $params = array('userid' => $userid);
    } else {
        $user = '';
    }
    $params['aid'] = $checkmark->id;

    $sql = 'SELECT u.id, u.id AS userid, f.attendance AS rawgrade, f.graderid AS usermodified,
                   f.timemodified AS dategraded
              FROM {user} u, {checkmark_feedbacks} f
             WHERE u.id = f.userid AND f.checkmarkid = :aid' .
            $user;
    return $DB->get_records_sql($sql, $params);
}

/**
 * Return presentation grade for given user or all users.
 *
 * @param object $checkmark checkmark object
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function checkmark_get_user_presentation_grades($checkmark, $userid = 0) {
    global $DB;

    if ($userid) {
        $user = 'AND u.id = :userid';
        $params = array('userid' => $userid);
    } else {
        $user = '';
    }
    $params['aid'] = $checkmark->id;

    $sql = 'SELECT u.id, u.id AS userid, f.presentationgrade AS rawgrade, f.presentationfeedback AS feedback,
                   f.presentationformat AS format, f.graderid AS usermodified, f.timemodified AS dategraded
              FROM {user} u, {checkmark_feedbacks} f
             WHERE u.id = f.userid AND f.checkmarkid = :aid' .
            $user;
    return $DB->get_records_sql($sql, $params);
}

/**
 * Update activity grades
 *
 * @param object $checkmark
 * @param int $userid specific user only, 0 means all
 * usual param bool $nullifnone (optional) not used here!
 */
function checkmark_update_grades($checkmark, $userid = 0) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $grades = null;
    if ($grades = checkmark_get_user_grades($checkmark, $userid)) {
        foreach ($grades as $k => $v) {
            if ($v->rawgrade == -1) {
                $grades[$k]->rawgrade = null;
            }
        }
    }
    checkmark_grade_item_update($checkmark, $grades);
}

/**
 * Update activity attendances
 *
 * @param object $checkmark
 * @param int $userid specific user only, 0 means all
 * usual param bool $nullifnone (optional) not used here!
 */
function checkmark_update_attendances($checkmark, $userid = 0) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    if (!$checkmark->trackattendance || !$checkmark->attendancegradebook) {
        // If there's no gradeitem, we won't do anything!
        return;
    }

    $attendances = null;
    if ($attendances = checkmark_get_user_attendances($checkmark, $userid)) {
        foreach ($attendances as $k => $v) {
            if ($v->rawgrade == -1) {
                $attendances[$k]->rawgrade = null;
            }
        }
    }

    checkmark_attendance_item_update($checkmark, $attendances);
}

/**
 * Update activity grades
 *
 * @param object $checkmark
 * @param int $userid specific user only, 0 means all
 * usual param bool $nullifnone (optional) not used here!
 */
function checkmark_update_presentation_grades($checkmark, $userid = 0) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    if (!$checkmark->presentationgrading || !$checkmark->presentationgradebook) {
        // If there's no gradeitem, we won't do anything!
        return;
    }

    $grades = null;
    if ($grades = checkmark_get_user_presentation_grades($checkmark, $userid)) {
        foreach ($grades as $k => $v) {
            if ($v->rawgrade == -1) {
                $grades[$k]->rawgrade = null;
            }
        }
    }
    checkmark_presentation_item_update($checkmark, $grades);
}

/**
 * Update all grades (including attendances and presentationgrades) in gradebook.
 *
 * Is this function still used anywhere? TODO: check that!
 */
function checkmark_upgrade_grades() {
    global $DB;

    $sql = 'SELECT COUNT(\'x\')
              FROM {checkmark} a, {course_modules} cm, {modules} m
             WHERE m.name=\'checkmark\' AND m.id=cm.module AND cm.instance=a.id';
    $count = $DB->count_records_sql($sql);

    $sql = 'SELECT a.*, cm.idnumber AS cmidnumber, a.course AS course
              FROM {checkmark} a, {course_modules} cm, {modules} m
             WHERE m.name=\'checkmark\' AND m.id=cm.module AND cm.instance=a.id';
    $rs = $DB->get_recordset_sql($sql);
    if ($rs->valid()) {
        // Too much debug output!
        $pbar = new progress_bar('checkmarkupgradegrades', 500, true);
        $i = 0;
        foreach ($rs as $checkmark) {
            $i++;
            upgrade_set_timeout(60 * 5); // Set up timeout, may also abort execution!
            checkmark_update_grades($checkmark);
            if ($checkmark->trackattendance && $checkmark->attendancegradebook) {
                checkmark_update_attendances($checkmark);
            }
            if ($checkmark->presentationgrading && $checkmark->presentationgradebook) {
                checkmark_update_presentation_grades($checkmark);
            }
            $pbar->update($i, $count, 'Updating checkmark grades (' . $i . '/' . $count . ')');
        }
        upgrade_set_timeout(); // Reset to default timeout!
    }
    $rs->close();
}

/**
 * Create grade item for given checkmark
 *
 * @param object $checkmark object with extra cmidnumber
 * @param object[]|object|string $grades (optional) array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function checkmark_grade_item_update($checkmark, $grades = null) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $params = array('itemname' => $checkmark->name, 'idnumber' => $checkmark->cmidnumber);

    if ($checkmark->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax'] = $checkmark->grade;
        $params['grademin'] = 0;

    } else if ($checkmark->grade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid'] = -$checkmark->grade;

    } else {
        $params['gradetype'] = GRADE_TYPE_TEXT; // Allow text comments only!
    }

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }
    if (!isset($checkmark->id)) {
        $checkmark->id = $checkmark->instance;
    }

    return grade_update('mod/checkmark', $checkmark->course, 'mod', 'checkmark', $checkmark->id, CHECKMARK_GRADE_ITEM, $grades,
            $params);
}

/**
 * Create attendance item for given checkmark
 *
 * @param object $checkmark object with extra cmidnumber
 * @param object[]|object|string $grades (optional) array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function checkmark_attendance_item_update($checkmark, $grades = null) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    if (!$checkmark->trackattendance && !$checkmark->attendancegradebook) {
        return;
    }

    if ($checkmark->cmidnumber) {
        $idnumber = 'A' . $checkmark->cmidnumber;
    } else {
        $idnumber = null;
    }
    $params = array('itemname' => get_string('attendance', 'checkmark') . ' ' . $checkmark->name,
            'idnumber' => 'A' . $idnumber);

    $params['gradetype'] = GRADE_TYPE_VALUE;
    $params['grademax'] = 1;
    $params['grademin'] = 0;

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }
    if (!isset($checkmark->id)) {
        $checkmark->id = $checkmark->instance;
    }

    // We can't update the category ID because Moodle forces us to stay in 1 grade category!

    if ($grades != null) {
        // Normalize attendance values here. We should only put 0 or 1 through to gradebook!
        foreach ($grades as $i => $grade) {
            if (!empty($grade->rawgrade)) {
                $grades[$i]->rawgrade = 1.00000;
            } else if ($grade->rawgrade === null) {
                $grades[$i]->rawgrade = null;
            } else if ($grade->rawgrade == 0) {
                $grades[$i]->rawgrade = 0.00000;
            } else {
                $grades[$i]->rawgrade = null;
            }
        }
    }

    $gradeupdate = grade_update('mod/checkmark', $checkmark->course, 'mod', 'checkmark', $checkmark->id, CHECKMARK_ATTENDANCE_ITEM,
            $grades, $params);
    // Move attendance item directly after grade item, if it exists in the same category!
    $params = array('courseid' => $checkmark->course,
            'itemtype' => 'mod',
            'itemmodule' => 'checkmark',
            'iteminstance' => $checkmark->id);
    if ($attendanceitem = grade_item::fetch($params + array('itemnumber' => CHECKMARK_ATTENDANCE_ITEM))) {
        if ($gradeitem = grade_item::fetch($params + array('itemnumber' => CHECKMARK_GRADE_ITEM))) {
            if ($gradeitem->categoryid == $attendanceitem->categoryid) {
                $attendanceitem->move_after_sortorder($gradeitem->get_sortorder());
            }
        }
    }

    return $gradeupdate;
}

/**
 * Create presentation grade item for given checkmark
 *
 * @param object $checkmark object with extra cmidnumber
 * @param object[]|object|string $grades (optional) array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function checkmark_presentation_item_update($checkmark, $grades = null) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $params = array('itemname' => get_string('presentationgrade_short', 'checkmark') . ' ' . $checkmark->name,
            'idnumber' => get_string('presentationgrade_short', 'checkmark') . $checkmark->cmidnumber);

    if ($checkmark->presentationgrade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax'] = $checkmark->presentationgrade;
        $params['grademin'] = 0;

    } else if ($checkmark->presentationgrade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid'] = -$checkmark->presentationgrade;

    } else {
        $params['gradetype'] = GRADE_TYPE_TEXT; // Allow text comments only!
    }

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }
    if (!isset($checkmark->id)) {
        $checkmark->id = $checkmark->instance;
    }

    $gradeupdate = grade_update('mod/checkmark', $checkmark->course, 'mod', 'checkmark', $checkmark->id,
            CHECKMARK_PRESENTATION_ITEM, $grades, $params);

    // Move presentation item attendance item directly after attendance or grade item, if one of them exists!
    $params = array('courseid' => $checkmark->course,
            'itemtype' => 'mod',
            'itemmodule' => 'checkmark',
            'iteminstance' => $checkmark->id);
    if ($presentationitem = grade_item::fetch($params + array('itemnumber' => CHECKMARK_PRESENTATION_ITEM))) {
        if ($attendanceitem = grade_item::fetch($params + array('itemnumber' => CHECKMARK_ATTENDANCE_ITEM))) {
            if ($attendanceitem->categoryid == $presentationitem->categoryid) {
                $presentationitem->move_after_sortorder($attendanceitem->get_sortorder());
            }
        } else if ($gradeitem = grade_item::fetch($params + array('itemnumber' => CHECKMARK_GRADE_ITEM))) {
            if ($presentationitem->categoryid == $gradeitem->categoryid) {
                $presentationitem->move_after_sortorder($gradeitem->get_sortorder());
            }
        }
    }

    return $gradeupdate;
}

/**
 * Update the grade items categories if they are changed via mod_form.php
 *
 * We must do it manually here in the checkmark module because modedit supports only
 * single grade item while we use one, two or three.
 *
 * @param stdClass $checkmark An object from the form in mod_form.php
 */
function checkmark_grade_item_category_update($checkmark) {

    $gradeitems = grade_item::fetch_all(array(
            'itemtype' => 'mod',
            'itemmodule' => 'checkmark',
            'iteminstance' => $checkmark->id,
            'courseid' => $checkmark->course));
    if (!empty($gradeitems)) {
        foreach ($gradeitems as $gradeitem) {
            if ($gradeitem->itemnumber == 0) {
                if (isset($checkmark->gradepass) &&
                        $gradeitem->gradepass != $checkmark->gradepass) {
                    $gradeitem->gradepass = $checkmark->gradepass;
                    $gradeitem->update();
                }
                if (empty($checkmark->gradecat)) {
                    $gradeitem->set_parent(null);
                } else if ($gradeitem->categoryid != $checkmark->gradecat) {
                    $gradeitem->set_parent($checkmark->gradecat);
                }
            } else if ($gradeitem->itemnumber == 1) {

                if ($gradeitem->categoryid != $checkmark->gradecat) {
                    $gradeitem->set_parent($checkmark->gradecat);
                }
            } else if ($gradeitem->itemnumber == 2) {

                if ($gradeitem->categoryid != $checkmark->gradecat) {
                    $gradeitem->set_parent($checkmark->gradecat);
                }
            }
        }
    }
}

/**
 * Delete grade item for given checkmark
 *
 * @param object $checkmark object
 * @return object checkmark
 */
function checkmark_grade_item_delete($checkmark) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    return grade_update('mod/checkmark', $checkmark->course, 'mod', 'checkmark', $checkmark->id, CHECKMARK_GRADE_ITEM, null,
            array('deleted' => 1));
}

/**
 * Delete attendance item for given checkmark
 *
 * @param object $checkmark object
 * @return object checkmark
 */
function checkmark_attendance_item_delete($checkmark) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    return grade_update('mod/checkmark', $checkmark->course, 'mod', 'checkmark', $checkmark->id, CHECKMARK_ATTENDANCE_ITEM, null,
            array('deleted' => 1));
}

/**
 * Delete presentation grade item for given checkmark
 *
 * @param object $checkmark object
 * @return object checkmark
 */
function checkmark_presentation_item_delete($checkmark) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    return grade_update('mod/checkmark', $checkmark->course, 'mod', 'checkmark', $checkmark->id, CHECKMARK_PRESENTATION_ITEM, null,
            array('deleted' => 1));
}

/**
 * Checks if a scale is being used by an checkmark
 *
 * This is used by the backup code to decide whether to back up a scale
 *
 * @param int $checkmarkid
 * @param int $scaleid
 * @return bool True if the scale is used by the checkmark
 */
function checkmark_scale_used($checkmarkid, $scaleid) {
    global $DB;

    if (empty($scaleid) || ($scaleid < 0)) {
        return false;
    }

    return $DB->record_exists_select('checkmark', "id = ? AND (grade = ? OR (presentationgrading = 1 AND presentationgrade = ?))",
            array($checkmarkid, -$scaleid, -$scaleid));
}

/**
 * Checks if scale is being used by any instance of checkmark
 *
 * This is used to find out if scale used anywhere
 *
 * @param int $scaleid
 * @return bool True if the scale is used by any checkmark
 */
function checkmark_scale_used_anywhere($scaleid) {
    global $DB;

    if (($scaleid > 0) && $DB->record_exists_select('checkmark', "grade = ? OR (presentationgrading = 1 AND presentationgrade = ?)",
                    array(-$scaleid, -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * This function updates the events associated to the checkmark.
 * If $override is non-zero, then it updates only the events
 * associated with the specified override.
 *
 * @param checkmark $checkmark the checkmark object.
 * @param object $override (optional) limit to a specific override
 */
function checkmark_refresh_override_events($checkmark, $override = null) {
    global $CFG, $DB;

    require_once($CFG->dirroot . '/calendar/lib.php');

    $checkmarkinstance = $checkmark->checkmark;

    // Load the old events relating to this checkmark.
    $conds = array('modulename' => 'checkmark', 'instance' => $checkmarkinstance->id);
    if (!empty($override)) {
        // Only load events for this override.
        if (isset($override->userid)) {
            $conds['userid'] = $override->userid;
        } else if (isset($override->groupid)) {
            $conds['groupid'] = $override->groupid;
        } else {
            // This is not a valid override, it may have been left from a bad import or restore.
            $conds['groupid'] = $conds['userid'] = 0;
        }
    }
    $oldevents = $DB->get_records('event', $conds, 'id ASC');

    // Now make a to-do list of all that needs to be updated.
    if (empty($override)) {
        // We are updating the primary settings for the assignment, so we need to add all the overrides.
        $overrides = $DB->get_records('checkmark_overrides', array('checkmarkid' => $checkmarkinstance->id), 'id ASC');
        // It is necessary to add an empty stdClass to the beginning of the array as the $oldevents
        // list contains the original (non-override) event for the module. If this is not included
        // the logic below will end up updating the wrong row when we try to reconcile this $overrides
        // list against the $oldevents list.
        array_unshift($overrides, new stdClass());
    } else {
        // Just do the one override.
        $overrides = array($override);
    }

    if (!empty($checkmark->cm)) {
        $cmid = $checkmark->cm->id;
    } else {
        $cmid = get_coursemodule_from_instance('checkmark', $checkmarkinstance->id, $checkmarkinstance->course)->id;
    }

    foreach ($overrides as $current) {
        $groupid   = isset($current->groupid) ? $current->groupid : 0;
        $userid    = isset($current->userid) ? $current->userid : 0;
        $duedate = $current->timedue !== null ? $current->timedue : $checkmarkinstance->timedue;

        // Only add 'due' events for an override if they differ from the checkmark default.
        $addclose = empty($current->id) || isset($current->timedue);

        $event = new stdClass();
        $event->type = CALENDAR_EVENT_TYPE_ACTION;
        $event->format = FORMAT_HTML;
        $event->description = format_module_intro('checkmark', $checkmarkinstance, $cmid);
        // Events module won't show user events when the courseid is nonzero.
        $event->courseid    = ($userid) ? 0 : $checkmarkinstance->course;
        $event->groupid     = $groupid;
        $event->userid      = $userid;
        $event->modulename  = 'checkmark';
        $event->instance    = $checkmarkinstance->id;
        $event->timestart   = $duedate;
        $event->timeduration = 0;
        $event->timesort    = $event->timestart + $event->timeduration;
        $event->visible     = instance_is_visible('checkmark', $checkmarkinstance);
        $event->eventtype   = CHECKMARK_EVENT_TYPE_DUE;
        $event->priority    = null;

        // Determine the event name and priority.
        if ($groupid) {
            // Group override event.
            $params = new stdClass();
            $params->assign = $checkmarkinstance->name;
            $params->group = groups_get_group_name($groupid);
            if ($params->group === false) {
                // Group doesn't exist, just skip it.
                continue;
            }
            // Flip checkmark grouppriority and set group override priority.
            if (isset($current->grouppriority)) {
                $event->priority = 10000 - $current->grouppriority;
            }
        } else if ($userid) {
            // User override event.
            $params = new stdClass();
            $params->assign = $checkmarkinstance->name;
            // Set user override priority.
            $event->priority = CALENDAR_EVENT_USER_OVERRIDE_PRIORITY;
        }

        if ((isset($duedate) && $addclose)) {
            if ($oldevent = array_shift($oldevents)) {
                $event->id = $oldevent->id;
            } else {
                unset($event->id);
            }
            $event->name = get_string('calendardue', 'checkmark', $checkmarkinstance->name);
            calendar_event::create($event, false);
        }
    }

    // Delete any leftover events.
    foreach ($oldevents as $badevent) {
        $badevent = calendar_event::load($badevent);
        $badevent->delete();
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
 * @param int $courseid
 * @param int|stdClass $instance Assign module instance or ID.
 * @param int|stdClass $cm Course module object or ID (not used in this module).
 * @return bool
 */
function checkmark_refresh_events($courseid = 0, $instance = null, $cm = null) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/calendar/lib.php');

    // If we have instance information then we can just update the one event instead of updating all events.
    if (isset($instance)) {
        if (!is_object($instance)) {
            $instance = $DB->get_record('checkmark', array('id' => $instance), '*', MUST_EXIST);
        }
        $checkmarks = [$instance->id => $instance];
    } else {
        $cond = [];

        if ($courseid) {
            // Make sure that the course id is numeric.
            if (!is_numeric($courseid)) {
                return false;
            }
            $cond['course'] = $courseid;
        }

        if (!$checkmarks = $DB->get_records('checkmark', $cond)) {
            return true;
        }
    }

    if ($checkmarks) {
        foreach ($checkmarks as $checkmark) {
            if (count($checkmarks) > 1 || !isset($cm) || !is_object($cm)) {
                $cm = get_coursemodule_from_instance('checkmark', $checkmark->id);
            }

            // Start with creating the event.
            $event = new stdClass();
            $event->modulename = 'checkmark';
            $event->courseid = $checkmark->course;
            $event->groupid = 0;
            $event->userid = 0;
            $event->instance = $checkmark->id;
            $event->name = $checkmark->name;
            $event->type = CALENDAR_EVENT_TYPE_ACTION;

            if (!empty($checkmark->intro)) {
                if (!$cm) {
                    // Convert the links to pluginfile. It is a bit hacky but at this stage the files
                    // might not have been saved in the module area yet.
                    $intro = $checkmark->intro;
                    if ($draftid = file_get_submitted_draft_itemid('introeditor')) {
                        $intro = file_rewrite_urls_to_pluginfile($intro, $draftid);
                    }

                    // We need to remove the links to files as the calendar is not ready
                    // to support module events with file areas.
                    $intro = strip_pluginfile_content($intro);
                    $event->description = array('text' => $intro,
                            'format' => $checkmark->introformat);
                } else {
                    $event->description = format_module_intro('checkmark', $checkmark, $cm->id);
                }
            }

            $eventtype = CHECKMARK_EVENT_TYPE_DUE;
            if ($checkmark->timedue) {
                $event->eventtype = $eventtype;
                $event->name = get_string('calendardue', 'checkmark', $checkmark->name);

                $event->timestart = $checkmark->timedue;
                $event->timesort = $checkmark->timedue;
                $select = "modulename = :modulename
                           AND instance = :instance
                           AND eventtype = :eventtype
                           AND groupid = 0
                           AND courseid <> 0";
                $params = array('modulename' => 'checkmark', 'instance' => $checkmark->id, 'eventtype' => $eventtype);
                $event->id = $DB->get_field_select('event', 'id', $select, $params);

                // Now process the event.
                if ($event->id) {
                    $calendarevent = calendar_event::load($event->id);
                    $calendarevent->update($event, false);
                } else {
                    calendar_event::create($event, false);
                }
            } else {
                $DB->delete_records('event', array('modulename' => 'checkmark', 'instance' => $checkmark->id,
                        'eventtype' => $eventtype, 'priority' => null));
            }

            $eventtype = CHECKMARK_EVENT_TYPE_GRADINGDUE;
            if ($checkmark->gradingdue) {
                $event->eventtype = $eventtype;
                $event->name = get_string('calendargradingdue', 'checkmark', $checkmark->name);

                $event->timestart = $checkmark->gradingdue;
                $event->timesort = $checkmark->gradingdue;
                $select = "modulename = :modulename
                           AND instance = :instance
                           AND eventtype = :eventtype
                           AND groupid = 0
                           AND courseid <> 0";
                $params = array('modulename' => 'checkmark', 'instance' => $checkmark->id, 'eventtype' => $eventtype);
                $event->id = $DB->get_field_select('event', 'id', $select, $params);

                // Now process the event.
                if ($event->id) {
                    $calendarevent = calendar_event::load($event->id);
                    $calendarevent->update($event, false);
                } else {
                    calendar_event::create($event, false);
                }
            } else {
                $DB->delete_records('event', array('modulename' => 'checkmark', 'instance' => $checkmark->id,
                        'eventtype' => $eventtype));
            }
        }
    }
    return true;
}

/**
 * List the file areas that can be browsed.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array
 */
function checkmark_get_file_areas($course, $cm, $context) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/assign/locallib.php');

    $areas = array(
        CHECKMARK_INTROATTACHMENT_FILEAREA => get_string('introattachments', 'mod_checkmark'),
    );
    return $areas;
}

/**
 * Print recent activity from all checkmarks in a given course
 *
 * This is used by the recent activity block
 *
 * @param object $course Course object
 * @param bool $viewfullnames Wether to print fullnames or not
 * @param int $timestart Earliest timestamp to print activities for
 * @return bool true if something has been printed!
 */
function checkmark_print_recent_activity($course, $viewfullnames, $timestart) {
    global $CFG, $USER, $DB, $OUTPUT;

    $userfields = core_user\fields::for_name()->get_sql('u')->selects;
    if (!$submissions = $DB->get_records_sql('
            SELECT asb.id, asb.timemodified, cm.id AS cmid, asb.userid
                   ' . $userfields . ', u.email, u.picture
              FROM {checkmark_submissions} asb
              JOIN {checkmark} a       ON a.id = asb.checkmarkid
              JOIN {course_modules} cm ON cm.instance = a.id
              JOIN {modules} md        ON md.id = cm.module
              JOIN {user} u            ON u.id = asb.userid
             WHERE asb.timemodified > ? AND
                   a.course = ? AND
                   md.name = \'checkmark\'
          ORDER BY asb.timemodified ASC', array($timestart, $course->id))) {
        return false;
    }

    $modinfo = get_fast_modinfo($course);
    $show = array();
    $grader = array();

    $showrecentsubs = get_config('checkmark', 'showrecentsubmissions');

    $owngroups = groups_get_user_groups($course->id);

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
        if (empty($showrecentsubs)) {
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

            // This will be slow - show only users that share group with me in this cm!
            if (empty($owngroups[$cm->id])) {
                continue;
            }
            $usersgroups = groups_get_all_groups($course->id, $submission->userid,
                    $cm->groupingid);
            if (is_array($usersgroups)) {
                $usersgroups = array_keys($usersgroups);
                $intersect = array_intersect($usersgroups, $owngroups[$cm->id]);
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

    echo $OUTPUT->heading(get_string('newsubmissions', 'checkmark') . ':', 3);

    foreach ($show as $submission) {
        $cm = $modinfo->cms[$submission->cmid];
        $link = $CFG->wwwroot . '/mod/checkmark/view.php?id=' . $cm->id;
        print_recent_activity_note($submission->timemodified, $submission, $cm->name, $link, false,
                $viewfullnames);
    }

    return true;
}

/**
 * Returns all checkmarks since a given time in specified forum.
 *
 * @param object[] $activities Where to put the data in general
 * @param object[] $index Where to place the data in $activities
 * @param int $timestart Timestamp for the earliest activities to get
 * @param int $courseid the course id
 * @param int $cmid the course module id
 * @param int $userid (optional) to filter for a user
 * @param int $groupid (optional) to filter for a group
 */
function checkmark_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid,
        $userid = 0, $groupid = 0) {
    global $CFG, $COURSE, $USER, $DB;

    if ($COURSE->id == $courseid) {
        $course = $COURSE;
    } else {
        $course = $DB->get_record('course', array('id' => $courseid));
    }

    $modinfo =& get_fast_modinfo($course);

    $cm = $modinfo->cms[$cmid];

    $params = array();
    if ($userid) {
        $userselect = 'AND u.id = :userid';
        $params['userid'] = $userid;
    } else {
        $userselect = '';
    }

    if ($groupid) {
        $groupselect = 'AND gm.groupid = :groupid';
        $groupjoin = 'JOIN {groups_members} gm ON  gm.userid=u.id';
        $params['groupid'] = $groupid;
    } else {
        $groupselect = '';
        $groupjoin = '';
    }

    $params['cminstance'] = $cm->instance;
    $params['timestart'] = $timestart;

    $userfields = user_picture::fields('u', null, 'userid');

    if (!$submissions = $DB->get_records_sql('SELECT asb.id, asb.timemodified,
                                                ' . $userfields . '
                                                FROM {checkmark_submissions} asb
                                                JOIN {checkmark} a      ON a.id = asb.checkmarkid
                                                JOIN {user} u            ON u.id = asb.userid ' .
            $groupjoin .
            ' WHERE asb.timemodified > :timestart
                                                   AND a.id = :cminstance' .
            $userselect . ' ' . $groupselect .
            'ORDER BY asb.timemodified ASC', $params)) {
        return;
    }

    $groupmode = groups_get_activity_groupmode($cm, $course);
    $cmcontext = context_module::instance($cm->id);
    $grader = has_capability('moodle/grade:viewall', $cmcontext);
    $accessallgroups = has_capability('moodle/site:accessallgroups', $cmcontext);
    $viewfullnames = has_capability('moodle/site:viewfullnames', $cmcontext);

    // Load all my groups!
    $owngroups = groups_get_user_groups($course->id);

    $show = array();

    $showrecentsubs = get_config('checkmark', 'showrecentsubmissions');

    foreach ($submissions as $submission) {
        if ($submission->userid == $USER->id) {
            $show[] = $submission;
            continue;
        }
        /*
         * The act of submitting of checkmark may be considered private
         * only graders will see it if specified!
         */
        if (empty($showrecentsubs)) {
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
            if (empty($owngroups[$cm->id])) {
                continue;
            }
            $usersgroups = groups_get_all_groups($course->id, $cm->userid, $cm->groupingid);
            if (is_array($usersgroups)) {
                $usersgroups = array_keys($usersgroups);
                $intersect = array_intersect($usersgroups, $owngroups[$cm->id]);
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
        require_once($CFG->libdir . '/gradelib.php');
        $userids = array();
        foreach ($show as $submission) {
            $userids[] = $submission->userid;

        }
        $grades = grade_get_grades($courseid, 'mod', 'checkmark', $cm->instance, $userids);
    }

    $aname = format_string($cm->name, true);
    foreach ($show as $submission) {
        $tmpactivity = new stdClass();

        $tmpactivity->cmid = $cm->id;
        $tmpactivity->name = $aname;
        $tmpactivity->sectionnum = $cm->sectionnum;
        $tmpactivity->timestamp = $submission->timemodified;

        if ($grader) {
            $tmpactivity->grade = $grades->items[CHECKMARK_GRADE_ITEM]->grades[$submission->userid]->str_long_grade;
        }

        $userfields = core_user\fields::for_userpic()->get_required_fields();
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
 *
 * @param object $activity various data to use.
 * @param int $courseid Courses id
 * @param bool $detail Wether to display details or just a summary
 * usual param string $modnames not used here!
 */
function checkmark_print_recent_mod_activity($activity, $courseid, $detail) {
    global $CFG, $OUTPUT;

    echo '<table border="0" cellpadding="3" cellspacing="0" class="checkmark-recent">';

    echo '<tr><td class="userpicture" valign="top">' .
            $OUTPUT->user_picture($activity->user) .
            '</td><td>';

    if ($detail) {
        $modname = get_string('modulename', 'checkmark');
        echo '<div class="title">';
        echo $OUTPUT->image_icon('icon', $modname, 'checkmark', array('class' => 'icon'));
        echo '<a href="' . $CFG->wwwroot . '/mod/checkmark/view.php?id=' . $activity->cmid . '">' . $activity->name . '</a>';
        echo '</div>';
    }

    if (isset($activity->grade)) {
        echo '<div class="grade">';
        echo get_string('grade', 'grades') . ': ';
        echo $activity->grade;
        echo '</div>';
    }

    echo '<div class="user">';
    echo '<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $activity->user->id . '&amp;course=' . $courseid . '">' .
            $activity->user->fullname . '</a>  - ' . userdate($activity->timestamp);
    echo '</div>';

    echo '</td></tr></table>';
}

/**
 * Fetch info from logs
 *
 * @param object $log with properties ->info (the checkmark id) and ->userid
 * @return array with checkmark name and user firstname and lastname
 */
function checkmark_log_info($log) {
    global $DB;

    return $DB->get_record_sql('SELECT a.name, u.firstname, u.lastname
                                FROM {checkmark} a, {user} u
                                WHERE a.id = ?
                                    AND u.id = ?', array($log->info, $log->userid));
}

/**
 * Return list of marked submissions that have not been mailed out for currently enrolled students
 *
 * @param int $starttime earliest timetamp
 * @param int $endtime latest timestamp
 * @return array
 */
function checkmark_get_unmailed_feedbacks($starttime, $endtime) {
    global $DB;

    return $DB->get_records_sql('SELECT s.*, a.course, a.name
                                   FROM {checkmark_feedbacks} s
                              LEFT JOIN {checkmark} a ON s.checkmarkid = a.id
                                  WHERE s.mailed = 0
                                        AND s.timemodified <= ?
                                        AND s.timemodified >= ?', array($endtime, $starttime));
}

/**
 * Counts all real checkmark submissions by ENROLLED students (not empty ones)
 *
 * @param object $cm The course module object
 * @param int $groupid optional If nonzero then count is restricted to this group
 * @return int The number of submissions
 */
function checkmark_count_real_submissions($cm, $groupid = 0) {
    global $DB;

    $context = context_module::instance($cm->id);

    // This is all the users with this capability set, in this context or higher!
    if ($users = get_enrolled_users($context, 'mod/checkmark:submit', $groupid, 'u.id')) {
        $users = array_keys($users);
    }

    if (empty($users)) {
        return 0;
    }

    list($sqluserlist, $userlistparams) = $DB->get_in_or_equal($users);
    $params = array_merge(array($cm->instance), $userlistparams);

    return $DB->count_records_sql('SELECT COUNT(\'x\')
                                     FROM {checkmark_submissions}
                                    WHERE checkmarkid = ? AND
                                          timemodified > 0 AND
                                          userid ' . $sqluserlist, $params);
}

/**
 * Counts all ungrades submissions by ENROLLED students (not empty ones)
 *
 * @param object $cm The course module object
 * @return int The number of submissions to be graded!
 */
function checkmark_count_real_ungraded_submissions($cm) {
    global $DB;

    return $DB->count_records_sql('SELECT COUNT(1)
                                    FROM {checkmark_submissions} s
                               LEFT JOIN {checkmark_feedbacks} f ON s.userid = f.userid AND s.checkmarkid = f.checkmarkid
                                   WHERE graderid IS NULL
                                     AND f.timemodified IS NULL
                                     AND s.checkmarkid = ?', array($cm->instance));
}

/**
 * Return all checkmark submissions by ENROLLED students (even empty)
 *
 * @param object $checkmark Checkmark to get submissions for
 * @param string $sort optional field names for the ORDER BY in the sql query
 * @param string $dir optional specifying the sort direction, defaults to DESC
 * @return array The submission objects indexed by id
 */
function checkmark_get_all_submissions($checkmark, $sort = '', $dir = 'DESC') {
    // Return all checkmark submissions by ENROLLED students (even empty)!
    global $DB;

    if ($sort == 'lastname' or $sort == 'firstname') {
        $sort = 'u.' . $sort . ' ' . $dir;
    } else if (empty($sort)) {
        $sort = 'a.timemodified DESC';
    } else {
        $sort = 'a.' . $sort . ' ' . $dir;
    }

    $records = $DB->get_records_sql('SELECT a.*
                                     FROM {checkmark_submissions} a, {user} u
                                     WHERE u.id = a.userid
                                     AND a.checkmarkid = ?
                                     ORDER BY ' . $sort, array($checkmark->id));
    foreach ($records as $record) {
        $records->checked = $DB->get_records('checkmark_checks', array('submissionid' => $record->id));
    }

    return $records;

}

/*
 ******************** OTHER GENERAL FUNCTIONS FOR checkmarks  ***********************
 */

/**
 * checkmark_get_summarystring() returns a short statistic over the actual checked examples
 * in this checkmark
 * You've checked out X from a maximum of Y examples. (A out of B points)
 *
 * @param object $submission Submisson-object
 * @param object $checkmark Checkmark-instance-object
 * @return string short summary
 */
function checkmark_getsummarystring($submission, $checkmark) {
    global $USER, $CFG, $DB;
    require_once($CFG->dirroot . '/mod/checkmark/locallib.php');

    $course = $DB->get_record('course', array('id' => $checkmark->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('checkmark', $checkmark->id, $course->id, false,
            MUST_EXIST);
    $instance = new checkmark($cm->instance, $checkmark, $cm);
    if (!isset($submission)) {
        $submission = $instance->get_submission($USER->id, false); // Get the submission!
    }

    $a = checkmark_getsubmissionstats($submission, $checkmark);

    if ($checkmark->grade == 0) {
        $output = get_string('checkmark_overviewsummary_nograde', 'checkmark', $a);
    } else {
        $output = get_string('checkmark_overviewsummary', 'checkmark', $a);
    }

    return $output;
}

/**
 * checkmark_getsubmissionstats() returns a short statistic over the actual
 * checked examples in this checkmarksubmission
 * checked out X of Y examples (A of B points) graded/not graded
 *
 * @param \mod_checkmark\submission $submission Submission object
 * @param object $checkmark Checkmark-Instance object
 * @return object submissions statistics data
 * @throws dml_exception
 * @throws coding_exception
 */
function checkmark_getsubmissionstats($submission, $checkmark) {
    global $DB, $USER;

    $checkedexamples = 0;
    $checkedgrades = 0;
    $maxcheckedexamples = 0;
    $maxcheckedgrades = 0;

    if ($submission) {
        $maxcheckedexamples = count($submission->get_examples());
        foreach ($submission->get_examples() as $example) {
            $checkedgrades += \mod_checkmark\example::static_is_checked($example->state) ? $example->grade : 0;
            $checkedexamples += \mod_checkmark\example::static_is_checked($example->state) ? 1 : 0;
            $maxcheckedgrades += $example->grade;
        }
    } else {
        $examples = \checkmark::get_examples_static($checkmark->id);
        $maxcheckedexamples = count($examples);
        foreach ($examples as $example) {
            $maxcheckedgrades += $example->grade;
        }
        $checkedexamples = 0;
        $checkedgrades = 0;
    }

    $a = new stdClass();
    $a->checked_examples = $checkedexamples;
    $a->total_examples = $maxcheckedexamples;
    $a->checked_grade = $checkedgrades;
    $a->total_grade = $maxcheckedgrades;
    $a->name = $checkmark->name;

    if (gettype($submission) === 'boolean' || $submission->get_userid()) {
        $feedback = false;
        $userid = $USER->id;
    } else {
        $feedback = $DB->get_record('checkmark_feedbacks', array('checkmarkid' => $checkmark->id,
                'userid' => $submission->get_userid()));
        $userid = $submission->get_userid();
    }

    $gradinginfo = grade_get_grades($checkmark->course, 'mod', 'checkmark', $checkmark->id, $userid);
    $item = $gradinginfo->items[CHECKMARK_GRADE_ITEM];
    $grade = $item->grades[$userid];
    if ($feedback == false) {
        $feedback = new stdClass();
        $feedback->grade = $grade->grade;
        $feedback->feedback = $grade->feedback;
    } else if ($grade->overridden || $grade->locked) {
        $feedback->grade = $grade->grade;
        $feedback->feedback = $grade->feedback;
    }

    if (!empty($userid) && $feedback) {
        /*
         * Cache scales for each checkmark
         * they might have different scales!
         */
        static $scalegrades = array();

        if ($checkmark->grade > 0) {    // Normal number?
            if ($feedback->grade === null || $feedback->grade == -1) {
                $a->grade = get_string('notgradedyet', 'checkmark');
            } else {
                $a->grade = get_string('graded', 'checkmark') . ': ' . (int) $feedback->grade .
                        ' / ' . $checkmark->grade;
            }
        } else if ($checkmark->grade < 0) {                                // Scale?
            if (empty($scalegrades[$checkmark->id])) {
                if (!$scale = grade_scale::fetch(array('id' => -$checkmark->grade))) {
                    $a->grade = get_string('notgradedyet', 'checkmark');
                } else {
                    $scale->load_items();
                    // This is to ensure compatibility with make_grades_menu(), because every scale is a 1-indexed-array!
                    $scalegrades[$checkmark->id] = array();
                    foreach ($scale->scale_items as $key => $item) {
                        $scalegrades[$checkmark->id][$key + 1] = $item;
                    }
                }
            }
            if (key_exists((int) $feedback->grade, $scalegrades[$checkmark->id])) {
                $a->grade = get_string('graded', 'checkmark') . ': ' .
                        $scalegrades[$checkmark->id][(int) $feedback->grade];
            } else {
                $a->grade = get_string('notgradedyet', 'checkmark');
            }
        } else {
            // Just comments!
            if ($feedback->feedback != null) {
                $a->grade = get_string('graded', 'checkmark');
            } else {
                $a->grade = get_string('notgradedyet', 'checkmark');
            }
        }
    } else {
        $a->grade = get_string('notgradedyet', 'checkmark');
    }

    return $a;
}

/**
 * Return a string indicating how late a submission is
 *
 * @param int $timesubmitted Submissions timestamp to compare
 * @param int $timedue Instances due date
 * @return string HTML snippet containing info about submission time
 */
function checkmark_display_lateness($timesubmitted, $timedue) {
    if (!$timedue) {
        return '';
    }
    $time = $timedue - $timesubmitted;

    if ($time >= 7 * 24 * 60 * 60) { // More than 7 days?
        $timetext = get_string('early', 'checkmark', format_time($time));
        return ' (<span class="text-success">' . $timetext . '</span>)';
    } else if ($time >= 24 * 60 * 60) { // More than 1 day but less than 7 days?
        $timetext = get_string('early', 'checkmark', format_time($time));
        return ' (<span class="text-info">' . $timetext . '</span>)';
    } else if ($time >= 0) { // In the future but less than 1 day?
        $timetext = get_string('early', 'checkmark', format_time($time));
        return ' (<span class="text-warning">' . $timetext . '</span>)';
    } else {
        $timetext = get_string('late', 'checkmark', format_time($time));
        return ' (<span class="text-error">' . $timetext . '</span>)';
    }
}

/**
 * Removes all grades from gradebook
 *
 * @param int $courseid
 */
function checkmark_reset_gradebook($courseid) {
    global $DB;

    $params = array('courseid' => $courseid);

    $sql = 'SELECT a.*, cm.idnumber AS cmidnumber, a.course AS courseid
              FROM {checkmark} a, {course_modules} cm, {modules} m
             WHERE m.name=\'checkmark\' AND m.id=cm.module
                                        AND cm.instance=a.id
                                        AND a.course=:courseid';

    if ($checkmarks = $DB->get_records_sql($sql, $params)) {
        foreach ($checkmarks as $checkmark) {
            checkmark_grade_item_update($checkmark, 'reset');
            if ($checkmark->trackattendance && $checkmark->attendancegradebook) {
                checkmark_attendance_item_update($checkmark, 'reset');
            }
            if ($checkmark->presentationgrading && $checkmark->presentationgradebook) {
                checkmark_presentation_item_update($checkmark, 'reset');
            }
        }
    }
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * This function will remove all posts from the specified checkmark
 * and clean up any related data.
 *
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function checkmark_reset_userdata($data) {
    global $CFG;

    $status = array();
    require_once($CFG->dirroot . '/mod/checkmark/locallib.php');
    $checkmark = new checkmark();
    $status = array_merge($status, $checkmark->reset_userdata($data));

    return $status;
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the checkmark.
 *
 * @param object $mform form passed by reference
 */
function checkmark_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'checkmarkheader', get_string('modulenameplural', 'checkmark'));
    $mform->addElement('advcheckbox', 'reset_checkmark_submissions', get_string('deleteallsubmissions', 'checkmark'));
    $mform->addElement('advcheckbox', 'reset_checkmark_overrides', get_string('deletealloverrides', 'checkmark'));
}

/**
 * Course reset form defaults.
 *
 * usual param mixed $course The course, not used here anyway!
 *
 * @return array Associative array defining defaults for the form
 */
function checkmark_reset_course_form_defaults() {
    return [
            'reset_checkmark_submissions' => 1,
            'reset_checkmark_overrides' => 1
    ];
}

/**
 * Returns all other caps used in module
 */
function checkmark_get_extra_capabilities() {
    return array('moodle/site:accessallgroups', 'moodle/site:viewfullnames');
}

/**
 * Defines what is supported by checkmark plugin.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return bool|null True if module supports feature, null if doesn't know
 */
function checkmark_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_IDNUMBER:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_OTHER;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ASSESSMENT;

        default:
            return false;
    }
}

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $checkmarknode The node to add module settings to
 */
function checkmark_extend_settings_navigation(settings_navigation $settings, navigation_node $checkmarknode) {
    global $PAGE, $DB, $CFG;

    // We want to add these new nodes after the Edit settings node, and before the
    // Locally assigned roles node. Of course, both of those are controlled by capabilities.
    $keys = $checkmarknode->get_children_key_list();
    $beforekey = null;
    $i = array_search('modedit', $keys);
    if ($i === false and array_key_exists(0, $keys)) {
        $beforekey = $keys[0];
    } else if (array_key_exists($i + 1, $keys)) {
        $beforekey = $keys[$i + 1];
    }

    $checkmarkrow = $DB->get_record('checkmark', array('id' => $PAGE->cm->instance));
    require_once($CFG->dirroot . '/mod/checkmark/locallib.php');

    $checkmarkinstance = new checkmark($PAGE->cm->id, $checkmarkrow, $PAGE->cm, $PAGE->course);

    $allgroups = false;

    // Add checkmark submission information!
    if (has_capability('mod/checkmark:grade', $PAGE->cm->context)) {
        $keys = $checkmarknode->get_children_key_list();
        if ($allgroups && has_capability('moodle/site:accessallgroups', $PAGE->cm->context)) {
            $group = 0;
        } else {
            $group = groups_get_activity_group($PAGE->cm);
        }
        $link = new moodle_url('/mod/checkmark/submissions.php', array('id' => $PAGE->cm->id));
        $string = get_string('viewsubmissions', 'checkmark');
        $key = 'viewsubmissions';
        $submissionnode = \navigation_node::create($string, $link, navigation_node::TYPE_SETTING,
            $string, $key, null);
        $checkmarknode->add_node($submissionnode, $beforekey);
    }

    // Add nodes to override dates for users/groups!
    if (has_capability('mod/checkmark:manageoverrides', $PAGE->cm->context)) {
        $keys = $checkmarknode->get_children_key_list();
        // Insert nodes on position 2 and 3 in the list!
        $url = new moodle_url('/mod/checkmark/extend.php', [
                'id' => $PAGE->cm->id,
                'return' => urlencode($PAGE->url->out())
        ]);
        $type = \navigation_node::TYPE_CUSTOM;
        $shorttext = get_string('groupoverrides', 'checkmark');
        $key = 'extendgroups';
        $icon = null;
        $groupnode = \navigation_node::create($shorttext, new moodle_url('/mod/checkmark/overrides.php',
                array('id' => $PAGE->cm->id, 'mode' => 'group')), $type, $shorttext, $key, $icon);
        $checkmarknode->add_node($groupnode, $beforekey);

        $shorttext = get_string('useroverrides', 'checkmark');
        $key = 'extendusers';
        $icon = null;
        $usernode = \navigation_node::create($shorttext, new moodle_url('/mod/checkmark/overrides.php',
                array('id' => $PAGE->cm->id, 'mode' => 'user')), $type, $shorttext, $key, $icon);
        $checkmarknode->add_node($usernode, 'extendgroups');
    }
}

/**
 * Return a list of page types they don't depend on pagetype, parentcontext or currentcontext!
 * Usual: param string $pagetype current page type
 * Usual: param stdClass $parentcontext Block's parent context
 * Usual: param stdClass $currentcontext Current context of block
 * return string[] array with modules pagetypes!
 */
function checkmark_page_type_list() {
    $modulepagetype = array(
            'mod-checkmark-*' => get_string('page-mod-checkmark-x', 'checkmark'),
            'mod-checkmark-view' => get_string('page-mod-checkmark-view', 'checkmark'),
            'mod-checkmark-submissions' => get_string('page-mod-checkmark-submissions', 'checkmark')
    );
    return $modulepagetype;
}

/*
 ******************** CALENDAR API AND SIMILAR FUNCTIONS FOR checkmarks  ***********************
 */

/**
 * Is the event visible?
 *
 * This is used to determine global visibility of an event in all places throughout Moodle. For example,
 * the ASSIGN_EVENT_TYPE_GRADINGDUE event will not be shown to students on their calendar, and
 * ASSIGN_EVENT_TYPE_DUE events will not be shown to teachers.
 *
 * @param calendar_event $event
 * @return bool Returns true if the event is visible to the current user, false otherwise.
 */
function mod_checkmark_core_calendar_is_event_visible(calendar_event $event) {
    global $CFG, $USER;
    require_once($CFG->dirroot . '/mod/checkmark/locallib.php');

    $cm = get_fast_modinfo($event->courseid)->instances['checkmark'][$event->instance];
    $context = context_module::instance($cm->id);

    $checkmark = new checkmark($cm->id, null, $cm, null);

    if ($event->eventtype == CHECKMARK_EVENT_TYPE_GRADINGDUE) {
        return has_capability('mod/checkmark:grade', $context, $USER->id);
    } else if ($event->eventtype == CHECKMARK_EVENT_TYPE_DUE) {
        return !has_capability('mod/checkmark:grade', $context, $USER->id) || $checkmark->checkmark->calendarteachers;
    } else {
        return true;
    }
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @param int $userid User the calender events should be retorned for. If 0 the events for the current user are returned
 * @return \core_calendar\local\event\entities\action_interface|null
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function mod_checkmark_core_calendar_provide_event_action(calendar_event $event,
        \core_calendar\action_factory $factory, int $userid = 0) {
    global $CFG, $USER;

    require_once($CFG->dirroot . '/mod/checkmark/locallib.php');
    if (empty($userid)) {
        $userid = $USER->id;
    }

    $cm = get_fast_modinfo($event->courseid)->instances['checkmark'][$event->instance];
    $context = context_module::instance($cm->id);

    $checkmark = new checkmark($cm->id, null, $cm, null);

    $notoverridden = (!$checkmark->overrides || $checkmark->overrides->timeavailable === null);
    $cmptime = $notoverridden ? $checkmark->checkmark->timeavailable : $checkmark->overrides->timeavailable;
    $started = time() >= $cmptime;

    if ($event->eventtype == CHECKMARK_EVENT_TYPE_GRADINGDUE) {
        $name = get_string('grade', 'grades');
        $url = new \moodle_url('/mod/checkmark/submissions.php', [
                'id' => $cm->id
        ]);
        $itemcount = checkmark_count_real_ungraded_submissions($cm);
        $actionable = has_capability('mod/checkmark:grade', $context) && $started;
    } else {
        $usersubmission = $checkmark->get_submission($USER->id, false);
        $feedback = $checkmark->get_feedback($USER->id);
        if ((!$checkmark->isopen(true) && $started) || ($feedback && !$checkmark->checkmark->resubmit)) {
            // The user has already been graded, nothing more to do here!
            return null;
        }

        $url = new \moodle_url('/mod/checkmark/view.php', [
                'id' => $cm->id,
                'edit' => 1
        ]);
        $itemcount = 1;
        if (has_capability('mod/checkmark:grade', $context)) {
            $name = get_string('gotoactivity', 'checkmark');
        } else if (!$usersubmission) {
            // The user has not yet submitted anything. Show the addsubmission link.
            $name = get_string('addsubmission', 'checkmark');
        } else {
            // The user has not yet submitted anything. Show the editmysubmission link (if he's allowed to resubmit).
            $name = get_string('editmysubmission', 'checkmark');
        }
        $actionable = true;
    }
    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false, $userid);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
            $name,
            $url,
            $itemcount,
            $actionable
    );
}

/**
 * Callback function that determines whether an action event should be showing its item count
 * based on the event type and the item count.
 *
 * @param calendar_event $event The calendar event.
 * @param int $itemcount The item count associated with the action event.
 * @return bool
 */
function mod_checkmark_core_calendar_event_action_shows_item_count(calendar_event $event, $itemcount = 0) {
    // List of event types where the action event's item count should be shown.
    $showitemcountfor = [
            CHECKMARK_EVENT_TYPE_GRADINGDUE
    ];
    // For mod_checkmark, item count should be shown if the event type is 'gradingdue' and there is one or more item count.
    return in_array($event->eventtype, $showitemcountfor) && $itemcount > 0;
}

/**
 * Map icons for font-awesome themes.
 */
function mod_checkmark_get_fontawesome_icon_map() {
    return [
            'mod_checkmark:questionmark' => 'fa-question text-warning',
            'mod_checkmark:overwrittendates' => 'fa-clock-o text-info'
    ];
}

/**
 * Callback which returns human-readable strings describing the active completion custom rules for the module instance.
 *
 * @param cm_info|stdClass $cm object with fields ->completion and ->customdata['customcompletionrules']
 * @return array $descriptions the array of descriptions for the custom rules.
 */
function mod_checkmark_get_completion_active_rule_descriptions($cm) {
    // Values will be present in cm_info, and we assume these are up to date.
    if (empty($cm->customdata['customcompletionrules'])
            || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return [];
    }

    $descriptions = [];
    foreach ($cm->customdata['customcompletionrules'] as $key => $val) {
        switch ($key) {
            case 'completionsubmit':
                if (!empty($val)) {
                    $descriptions[] = get_string('completionsubmit', 'checkmark');
                }
                break;
            default:
                break;
        }
    }
    return $descriptions;
}
