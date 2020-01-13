<?php
// This file is part of mod_checkmark for Moodle - http://moodle.org/
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
 * Privacy class for requesting user data.
 *
 * @package    mod_checkmark
 * @author     Philipp Hager <philipp.hager@tuwien.ac.at>
 * @copyright  2018 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_checkmark\privacy;

defined('MOODLE_INTERNAL') || die();

use \core_privacy\local\metadata\collection;
use \core_privacy\local\metadata\provider as metadataprovider;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\plugin\provider as pluginprovider;
use \core_privacy\local\request\user_preference_provider as user_preference_provider;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\transform;
use \core_privacy\local\request\helper;
use \core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;

if (isset($CFG)) {
    require_once($CFG->dirroot . '/mod/checkmark/locallib.php');
}

/**
 * Privacy class for requesting user data.
 *
 * @package    mod_checkmark
 * @author     Philipp Hager <philipp.hager@tuwien.ac.at>
 * @copyright  2018 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements metadataprovider, pluginprovider, user_preference_provider, core_userlist_provider {
    /**
     * Provides meta data that is stored about a user with mod_checkmark
     *
     * @param  collection $collection A collection of meta data items to be added to.
     * @return  collection Returns the collection of metadata.
     */
    public static function get_metadata(collection $collection): collection {
        $submissions = [
                'timecreated' => 'privacy:metadata:submission:timecreated',
                'timemodified' => 'privacy:metadata:submission:timemodified',
        ];
        $checks = [
                'state' => 'privacy:metadata:state',
        ];
        $feedbacks = [
                'grade' => 'privacy:metadata:grade',
                'feedback' => 'privacy:metadata:feedback',
                'format' => 'privacy:metadata:format',
                'attendance' => 'privacy:metadata:attendance',
                'presentationgrade' => 'privacy:metadata:presentationgrade',
                'presentationfeedback' => 'privacy:metadata:presentationfeedback',
                'presentationformat' => 'privacy:metadata:presentationformat',
                'graderid' => 'privacy:metadata:graderid',
                'mailed' => 'privacy:metadata:mailed',
                'timecreated' => 'privacy:metadata:feedback:timecreated',
                'timemodified' => 'privacy:metadata:feedback:timemodified'
        ];
        $overrides = [
                'timeavailable' => 'privacy:metadata:timeavailable',
                'timedue' => 'privacy:metadata:timedue',
                'cutoffdate' => 'privacy:metadata:cutoffdate',
                'timecreated' => 'privacy:metadata:override:timecreated',
                'timemodified' => 'privacy:metadata:override:timemodified',
        ];

        $collection->add_database_table('checkmark_submissions', $submissions, 'privacy:metadata:submissions');
        $collection->add_database_table('checkmark_checks', $checks, 'privacy:metadata:checks');
        $collection->add_database_table('checkmark_feedbacks', $feedbacks, 'privacy:metadata:feedbacks');
        $collection->add_database_table('checkmark_overrides', $overrides, 'privacy:metadata:overrides');

        $collection->add_user_preference('checkmark_filter', 'privacy:metadata:pref:filter');
        $collection->add_user_preference('checkmark_forcesinglelinenames', 'privacy:metadata:pref:forcesinglelinenames');
        $collection->add_user_preference('checkmark_format', 'privacy:metadata:pref:format');
        $collection->add_user_preference('checkmark_mailinfo', 'privacy:metadata:pref:mailinfo');
        $collection->add_user_preference('checkmark_pageorientation', 'privacy:metadata:pref:pageorientation');
        $collection->add_user_preference('checkmark_pdfprintperpage', 'privacy:metadata:pref:pdfprintperpage');
        $collection->add_user_preference('checkmark_perpage', 'privacy:metadata:pref:perpage');
        $collection->add_user_preference('checkmark_printheader', 'privacy:metadata:pref:printheader');
        $collection->add_user_preference('checkmark_quickgrade', 'privacy:metadata:pref:quickgrade');
        $collection->add_user_preference('checkmark_sumabs', 'privacy:metadata:pref:sumabs');
        $collection->add_user_preference('checkmark_sumrel', 'privacy:metadata:pref:sumrel');
        $collection->add_user_preference('checkmark_textsize', 'privacy:metadata:pref:textsize');
        $collection->add_user_preference('checkmark_zipped', 'privacy:metadata:pref:zipped');

        // Link to subplugins.
        $collection->add_subsystem_link('core_message', [], 'privacy:metadata:subsys:message');

        return $collection;
    }

    /**
     * Returns all of the contexts that has information relating to the userid.
     *
     * @param  int $userid The user ID.
     * @return contextlist an object with the contexts related to a userid.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $params = [
                'modulename' => 'checkmark',
                'contextlevel' => CONTEXT_MODULE,
                'suserid' => $userid,
                'fuserid' => $userid,
                'fgraderid' => $userid,
                'ouserid' => $userid,
                'omodifierid' => $userid
        ];

        $sql = "
   SELECT ctx.id
     FROM {course_modules} cm
     JOIN {modules} m ON cm.module = m.id AND m.name = :modulename
     JOIN {checkmark} c ON cm.instance = c.id
     JOIN {context} ctx ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
LEFT JOIN {checkmark_submissions} s ON c.id = s.checkmarkid
LEFT JOIN {checkmark_feedbacks} f ON c.id = f.checkmarkid
LEFT JOIN {checkmark_overrides} o ON c.id = o.checkmarkid
    WHERE s.userid = :suserid OR f.userid = :fuserid OR f.graderid = :fgraderid
          OR o.userid = :ouserid OR o.modifierid = :omodifierid";
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $params = [
                'modulename' => 'checkmark',
                'contextid' => $context->id,
                'contextlevel' => CONTEXT_MODULE
        ];

        // Get all who submitted!
        $sql = "SELECT s.userid
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {checkmark} c ON c.id = cm.instance
                  JOIN {checkmark_submissions} s ON c.id = s.checkmarkid
                 WHERE ctx.id = :contextid AND ctx.contextlevel = :contextlevel";
        $userlist->add_from_sql('userid', $sql, $params);

        // Get all whom anybody has given feedback or who gave feedback themselves!
        $sql = "SELECT f.userid, f.graderid
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {checkmark} c ON c.id = cm.instance
                  JOIN {checkmark_feedbacks} f ON c.id = f.checkmarkid
                 WHERE ctx.id = :contextid AND ctx.contextlevel = :contextlevel";
        $userlist->add_from_sql('userid', $sql, $params);
        $userlist->add_from_sql('graderid', $sql, $params);

        // Get all overrides or people who overridden!
        $sql = "SELECT o.userid, o.modifierid
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {checkmark} c ON c.id = cm.instance
                  JOIN {checkmark_overrides} o ON c.id = o.checkmarkid
                 WHERE ctx.id = :contextid AND ctx.contextlevel = :contextlevel";
        $userlist->add_from_sql('userid', $sql, $params);
        $userlist->add_from_sql('graderid', $sql, $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if ($context->contextlevel == CONTEXT_MODULE) {
            // Apparently we can't trust anything that comes via the context.
            // Go go mega query to find out it we have an checkmark context that matches an existing checkmark.
            $sql = "SELECT c.id
                    FROM {checkmark} c
                    JOIN {course_modules} cm ON c.id = cm.instance AND c.course = cm.course
                    JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                    JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextmodule
                    WHERE ctx.id = :contextid";
            $params = ['modulename' => 'checkmark', 'contextmodule' => CONTEXT_MODULE, 'contextid' => $context->id];
            $id = $DB->get_field_sql($sql, $params);
            // If we have an id over zero then we can proceed.
            if ($id > 0) {
                $userids = $userlist->get_userids();
                if (count($userids) <= 0) {
                    return;
                }

                list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'usr');
                list($usersql2, $userparams2) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'usr2_');
                // Get all checkmark submissions, feedbacks and extensions to delete them!
                if ($submissions = $DB->get_records_select('checkmark_submissions', "checkmark = :id AND userid ".$usersql,
                        ['id' => $id] + $userparams)) {
                    $DB->delete_records_list('checkmark_checks', 'submissionid', array_keys($submissions));
                }

                $DB->delete_records_select('checkmark_submissions', "checkmarkid = :id AND userid ".$usersql,
                        ['id' => $id] + $userparams);
                $DB->delete_records_select('checkmark_feedbacks', "checkmarkid = :id AND (userid ".$usersql." OR graderid "
                        .$usersql2.")",
                        ['id' => $id] + $userparams + $userparams2);
                $DB->delete_records('checkmark_overrides',
                        "checkmarkid = :id AND (userid ".$usersql." OR modifierid ".$usersql2.")",
                        ['id' => $id] + $userparams + $userparams2);
            }
        }
    }

    /**
     * Write out the user data filtered by contexts.
     *
     *
     * @param approved_contextlist $contextlist contexts that we are writing data out from.
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $contexts = $contextlist->get_contexts();

        if (empty($contexts)) {
            return;
        }

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT
                    ctx.id AS contextid,
                    c.*,
                    cm.id AS cmid
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid
                  JOIN {checkmark} c ON c.id = cm.instance
                 WHERE ctx.id {$contextsql}";

        // Keep a mapping of checkmarkid to contextid.
        $mappings = [];

        $checkmarks = $DB->get_records_sql($sql, $contextparams);

        $user = $contextlist->get_user();

        foreach ($checkmarks as $checkmark) {
            $context = \context_module::instance($checkmark->cmid);
            $mappings[$checkmark->id] = $checkmark->contextid;

            // Check that the context is a module context.
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }

            $checkmarkdata = helper::get_context_data($context, $user);
            helper::export_context_files($context, $user);

            $cm = get_coursemodule_from_instance('checkmark', $checkmark->id);

            $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
            $checkmark = new \checkmark($cm->id, $checkmark, $cm, $course);

            writer::with_context($context)->export_data([], $checkmarkdata);

            static::export_user_preferences($user->id);
            if ($submission = $checkmark->get_submission($user->id)) {
                static::export_submission($context, $submission->export_for_snapshot());
            }
            if ($feedback = $checkmark->get_feedback($user->id)) {
                static::export_feedback($context, $checkmark, $feedback);
            }
            static::export_extensions($context, $checkmark, $user);

        }
    }

    /**
     * Stores the user preferences related to mod_checkmark.
     *
     * @param  int $userid The user ID that we want the preferences for.
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public static function export_user_preferences(int $userid) {
        $context = \context_system::instance();
        $prefs = [
            'filter' => 'privacy:metadata:pref:filter',
            'forcesinglelinenames' => 'privacy:metadata:pref:forcesinglelinenames',
            'format' => 'privacy:metadata:pref:format',
            'mailinfo' => 'privacy:metadata:pref:mailinfo',
            'pageorientation' => 'privacy:metadata:pref:pageorientation',
            'pdfprintperpage' => 'privacy:metadata:pref:pdfprintperpage',
            'perpage' => 'privacy:metadata:pref:perpage',
            'printheader' => 'privacy:metadata:pref:printheader',
            'quickgrade' => 'privacy:metadata:pref:quickgrade',
            'sumabs' => 'privacy:metadata:pref:sumabs',
            'sumrel' => 'privacy:metadata:pref:sumrel',
            'textsize' => 'privacy:metadata:pref:textsize'
        ];
        foreach ($prefs as $key => $text) {
            $value = get_user_preferences('checkmark_' . $key, null, $userid);
            if ($value !== null) {
                writer::with_context($context)->export_user_preference('mod_checkmark', 'checkmark_'.$key, $value,
                        get_string($text, 'mod_checkmark'));
            }
        }
    }

    /**
     * Export user's submission for this assignment.
     *
     * @param  \context $context Context
     * @param  \stdClass $submission The submission to export
     * @throws \coding_exception
     */
    public static function export_submission(\context $context, \stdClass $submission) {
        $data = new \stdClass();

        $data->timesubmitted = transform::datetime($submission->timecreated);
        $data->lastmodification = transform::datetime($submission->timemodified);
        $data->examples = [];
        foreach ($submission->examples as $example) {
            $data->examples[] = (object)[
                'name' => $example->name,
                'checked' => transform::yesno($example->state),
                'grade' => $example->grade
            ];
        }

        writer::with_context($context)->export_data([get_string('privacy:path:submission', 'checkmark')], $data);
    }

    /**
     * Export user's feedback for this assignment.
     *
     * @param  \context $context Context
     * @param  \checkmark $c The checkmark object.
     * @param  \stdClass $feedback The feedback object.
     * @throws \coding_exception
     */
    public static function export_feedback(\context $context, \checkmark $c, \stdClass $feedback) {
        $data = new \stdClass();

        if (!empty($c->checkmark->grade)) {
            $data->grade = $c->display_grade($feedback->grade, CHECKMARK_GRADE_ITEM);
        }
        if ($feedback->feedback !== "") {
            $data->feedback = format_text($feedback->feedback, $feedback->format, ['context' => $context]);
        }
        if ($c->checkmark->trackattendance) {
            switch ($feedback->attendance) {
                case 1:
                    $data->attendance = strtolower(get_string('attendant', 'checkmark'));
                    break;
                case 0:
                    $data->attendance = strtolower(get_string('absent', 'checkmark'));
                    break;
                default:
                    $data->attendance = strtolower(get_string('unknown', 'checkmark'));
                    break;
            }
        }
        if (!empty($c->checkmark->presentationgrade)) {
            $data->presentationgrade = $c->display_grade($feedback->presentationgrade, CHECKMARK_PRESENTATION_ITEM);
        }
        if ($feedback->presentationfeedback !== "") {
            $data->presentationfeedback = format_text($feedback->presentationfeedback, $feedback->presentationformat,
                    ['context' => $context]);
        }
        $data->timegraded = transform::datetime($feedback->timecreated);
        $data->timemodified = transform::datetime($feedback->timemodified);
        $data->grader = transform::user($feedback->graderid);
        $data->notified = transform::yesno($feedback->mailed);

        writer::with_context($context)->export_data([get_string('privacy:path:feedback', 'checkmark')], $data);
    }

    /**
     * Export overrides for this assignment.
     *
     * @param  \context $context Context
     * @param  \checkmark $c The checkmark object.
     * @param  \stdClass $user The user object.
     * @throws \coding_exception
     */
    public static function export_extensions(\context $context, \checkmark $c, \stdClass $user) {
        $ext = checkmark_get_overridden_dates($c->checkmark->id, $user->id);

        if ($ext !== false) {
            $data = [];
            if ($ext->timeavailable !== false) {
                $data[get_string('availabledate', 'checkmark')] = transform::datetime($ext->timeavailable);
            }
            if ($ext->timedue !== false) {
                $data[get_string('duedate', 'checkmark')] = transform::datetime($ext->timedue);
            }
            if ($ext->cutoffdate !== false) {
                $data[get_string('cutoffdate', 'checkmark')] = transform::datetime($ext->cutoffdate);
            }
            writer::with_context($context)->export_data([], (object)$data);
        }
    }

    /**
     * Delete all use data which matches the specified context.
     *
     * @param \context $context The module context.
     * @throws \dml_exception
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel == CONTEXT_MODULE) {
            // Apparently we can't trust anything that comes via the context.
            // Go go mega query to find out it we have an checkmark context that matches an existing checkmark.
            $sql = "SELECT c.id
                    FROM {checkmark} c
                    JOIN {course_modules} cm ON c.id = cm.instance AND c.course = cm.course
                    JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                    JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextmodule
                    WHERE ctx.id = :contextid";
            $params = ['modulename' => 'checkmark', 'contextmodule' => CONTEXT_MODULE, 'contextid' => $context->id];
            $id = $DB->get_field_sql($sql, $params);
            // If we have an id over zero then we can proceed.
            if ($id > 0) {
                // Get all checkmark submissions, feedbacks and extensions to delete them!
                if ($submissions = $DB->get_records('checkmark_submissions', ['checkmark' => $id])) {
                    $DB->delete_records_list('checkmark_checks', 'submissionid', array_keys($submissions));
                }

                $DB->delete_records('checkmark_submissions', ['checkmarkid' => $id]);
                $DB->delete_records('checkmark_feedbacks', ['checkmarkid' => $id]);
                $DB->delete_records('checkmark_overrides', ['checkmarkid' => $id]);
            }
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();
        $contextids = $contextlist->get_contextids();

        if (empty($contextids) || $contextids === []) {
            return;
        }

        list($ctxsql, $ctxparams) = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED, 'ctx');

        // Apparently we can't trust anything that comes via the context.
        // Go go mega query to find out it we have an checkmark context that matches an existing checkmark.
        $sql = "SELECT ctx.id AS ctxid, c.*
                    FROM {checkmark} c
                    JOIN {course_modules} cm ON c.id = cm.instance AND c.course = cm.course
                    JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                    JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextmodule
                    WHERE ctx.id ".$ctxsql;
        $params = ['modulename' => 'checkmark', 'contextmodule' => CONTEXT_MODULE];

        if (!$records = $DB->get_records_sql($sql, $params + $ctxparams)) {
            return;
        }

        $cids = [];

        foreach ($contextlist as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }

            $cids[] = $records[$context->id]->id;
        }

        if (count($cids) > 0) {
            list($csql, $cparams) = $DB->get_in_or_equal($cids, SQL_PARAMS_NAMED, 'checkmark');
        } else {
            $csql = ' = :checkmark';
            $cparams = ['checkmark' => -1];
        }
        $params = ['userid' => $user->id] + $cparams;

        if ($subids = $DB->get_fieldset_select('checkmark_submissions', 'id', 'userid = :userid AND checkmarkid '.$csql, $params)) {
            $DB->delete_records_list('checkmark_checks', 'submissionid', $subids);
        }

        $DB->delete_records_select('checkmark_submissions', 'userid = :userid AND checkmarkid '.$csql, $params);
        $DB->delete_records_select('checkmark_feedbacks', 'userid = :userid AND checkmarkid '.$csql, $params);
        $DB->delete_records_select('checkmark_overrides', 'userid = :userid AND checkmarkid '.$csql, $params);
    }
}
