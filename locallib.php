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
 * This file contains checkmark-class with all logic-methods used by checkmark
 *
 * @package   mod_checkmark
 * @author    Philipp Hager, extended and maintained by Daniel Binder
 * @copyright 2019 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_checkmark\submission;
use mod_checkmark\submissionstable;
use mod_checkmark\output\checkmark_header;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/checkmark/lib.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/mod/checkmark/submission_form.php');
require_once($CFG->dirroot . '/mod/checkmark/grading_form.php');

/**
 * This class provides all the basic functionality for a checkmark-module
 *
 * @package   mod_checkmark
 * @author    Philipp Hager, extended and maintained by Daniel Binder
 * @copyright 2019 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class checkmark {

    /** FILTER_ALL */
    const FILTER_ALL = 1;
    /** FILTER_SUBMITTED */
    const FILTER_SUBMITTED = 2;
    /** FILTER_REQUIRE_GRADING */
    const FILTER_REQUIRE_GRADING = 3;
    /** FILTER_SELECTED */
    const FILTER_SELECTED = 4;
    /** FILTER ATTENDANT USERS */
    const FILTER_ATTENDANT = 5;
    /** FILTER ABSENT USERS */
    const FILTER_ABSENT = 6;
    /** FILTER UNKNOWN */
    const FILTER_UNKNOWN = 7;
    /** FILTER EXTENSION */
    const FILTER_EXTENSION = 8;
    /** FILER NOT SUBMITTED */
    const FILTER_NOT_SUBMITTED = 9;
    /** FILER PRESENTATIONGRADING */
    const FILTER_PRESENTATIONGRADING = 10;
    /** FILER NO PRESENTATIONGRADING */
    const FILTER_NO_PRESENTATIONGRADING = 11;
    /** FILER NO PRESENTATIONGRADING */
    const FILTER_GRADED = 12;
    /** FILTER_SELECTED_GRADED */
    const FILTER_SELECTED_GRADED = 13;


    /** DELIMITER Used to connect example-names, example-grades, submission-examplenumbers! */
    const DELIMITER = ',';

    /** EMPTYBOX UTF-8 empty box = &#x2610; = '☐'! */
    const EMPTYBOX = '';
    /** CHECKEDBOX UTF-8 box with x-mark = &#x2612; = '☒'! */
    const CHECKEDBOX = 'X';
    /** FORCED_EMPTYBOX UTF-8 empty box surrounded by parenthesis = &#x0028;&#x2610;&#x0029; = '(☐)'! */
    const FORCED_EMPTYBOX = '()';
    /** FORCED_EMPTYBOX UTF-8 box with x-mark surrounded by parenthesis = &#x0028;&#x2612;&#x0029; = '(☒)'! */
    const FORCED_CHECKEDBOX = '(X)';

    /** @var object */
    public $cm;
    /** @var object */
    public $course;
    /** @var object */
    public $checkmark;
    /** @var bool */
    public $usehtmleditor;
    /** @var int */
    public $defaultformat;
    /** @var object */
    public $context;
    /** @var object[] cached examples for this instance */
    public $examples;
    /** @var object contains overridden dates (for current user only!) */
    public $overrides = false;
    /** @var object */
    public $instance;
    /** @var checkmark_renderer the custom renderer for this module */
    private $output;
    /** @var array $var array an array containing per-user checkmark records, each having calculated properties (e.g. dates) */
    private $userinstances = [];

    /**
     * Constructor for the checkmark class
     *
     * Constructor for the checkmark class.
     * If cmid is set create the cm, course, checkmark objects.
     * If the checkmark is hidden and the user is not a teacher then
     * this prints a page header and notice.
     *
     * @param string|int $cmid the current course module id - not set for new checkmarks
     * @param object $checkmark usually null, but if we have it we pass it to save db access
     * @param object $cm usually null, but if we have it we pass it to save db access
     * @param object $course usually null, but if we have it we pass it to save db access
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function __construct($cmid = 'staticonly', $checkmark = null, $cm = null, $course = null) {
        global $COURSE, $DB, $USER;

        if ($cmid == 'staticonly') {
            // Use static functions only!
            return;
        }

        if ($cm) {
            $this->cm = $cm;
        } else if (!$this->cm = get_coursemodule_from_id('checkmark', $cmid)) {
            throw new moodle_exception('invalidcoursemodule');
        }

        $this->context = context_module::instance($this->cm->id);

        if ($course) {
            $this->course = $course;
        } else if ($this->cm->course == $COURSE->id) {
            $this->course = $COURSE;
        } else if (!$this->course = $DB->get_record('course', ['id' => $this->cm->course])) {
            throw new moodle_exception('invalidid', 'checkmark');
        }

        if ($checkmark) {
            $this->checkmark = $checkmark;
        } else if (!$this->checkmark = $DB->get_record('checkmark', ['id' => $this->cm->instance])) {
            throw new moodle_exception('invalidid', 'checkmark');
        }

        // Check for overridden dates!
        if ($overridden = checkmark_get_overridden_dates($this->checkmark->id, $USER->id, $this->checkmark->course)) {
            $this->overrides = $overridden;
        }

        // Ensure compatibility with modedit checkmark obj!
        $this->checkmark->cmidnumber = $this->cm->idnumber;
        $this->checkmark->course = $this->course->id;

        /*
         * Visibility handled by require_login() with $cm parameter
         * get current group only when really needed!
         */

        // Set up things for a HTML editor if it's needed!
        $this->defaultformat = editors_get_preferred_format();

        // We cache examples now...
        $this->get_examples();
    }

    /**
     * Lazy load the page renderer and expose the renderer to plugins.
     *
     * @return checkmark_renderer
     */
    public function get_renderer() {
        global $PAGE;
        if ($this->output) {
            return $this->output;
        }
        $this->output = $PAGE->get_renderer('mod_checkmark', null, RENDERER_TARGET_GENERAL);
        return $this->output;
    }

    /**
     * Standardizes course module, checkmark and course data objects and checks for login state!
     *
     * @param int $id course module id or 0 (either $id or $c have to be set!)
     * @param int $c checkmark instance id or 0 (either $id or $c have to be set!)
     * @param moodle_url $url current url of the viewed page
     * @return object[] Returns array with coursemodule, checkmark and course objects
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     * @throws require_login_exception
     */
    public static function init_checks($id, $c, $url) {
        global $PAGE, $DB;

        if ($id) {
            if (!$cm = get_coursemodule_from_id('checkmark', $id)) {
                throw new moodle_exception('invalidcoursemodule');
            }
            if (!$checkmark = $DB->get_record('checkmark', ['id' => $cm->instance])) {
                throw new moodle_exception('invalidid', 'checkmark');
            }
            if (!$course = $DB->get_record('course', ['id' => $checkmark->course])) {
                throw new moodle_exception('coursemisconf', 'checkmark');
            }
            $url->param('id', $id);
        } else {
            if (!$checkmark = $DB->get_record('checkmark', ['id' => $c])) {
                throw new moodle_exception('invalidcoursemodule');
            }
            if (!$course = $DB->get_record('course', ['id' => $checkmark->course])) {
                throw new moodle_exception('coursemisconf', 'checkmark');
            }
            if (!$cm = get_coursemodule_from_instance('checkmark', $checkmark->id, $course->id)) {
                throw new moodle_exception('invalidcoursemodule');
            }
            $url->param('id', $cm->id);
        }

        $PAGE->set_url($url);
        require_login($course->id, false, $cm);

        return [$cm, $checkmark, $course];
    }

    /**
     * Get the examples for this checkmark from the DB
     *
     * Adds the prefix if set and flexible naming is used
     *
     * @return \mod_checkmark\example[] checkmark's examples from the DB (raw records)
     * @throws dml_exception
     */
    public function get_examples() {
        if (!isset($this->checkmark->examples)) {
            $exampleprefix = $this->checkmark->exampleprefix;
            $this->checkmark->examples = self::get_examples_static($this->checkmark->id, $exampleprefix);
        }

        return $this->checkmark->examples;
    }

    /**
     * Get the examples for this checkmark from the DB
     *
     * Adds the prefix if set and flexible naming is used
     *
     * @param object|int $checkmarkid the checkmark object containing ID or the ID itself
     * @param string|false $exampleprefix If you already have it, you can save 1 query by setting it!
     * @return \mod_checkmark\example[] checkmark's examples from the DB (raw records)
     * @throws dml_exception
     */
    public static function get_examples_static($checkmarkid, $exampleprefix = false) {
        global $DB;

        $records = $DB->get_records('checkmark_examples', ['checkmarkid' => $checkmarkid], 'id ASC');

        if ($exampleprefix === false) {
            $exampleprefix = $DB->get_field('checkmark', 'exampleprefix', ['id' => $checkmarkid]);
        }
        $examples = [];
        foreach ($records as $key => $cur) {
            $examples[$key] = new \mod_checkmark\example($key, $cur->name, $cur->grade, $exampleprefix);
        }

        return $examples;
    }

    /**
     * print_summary() returns a short statistic over the actual checked examples in this checkmark
     * You've checked out X from a maximum of Y examples. (A out of B points)
     *
     * @return string short summary
     * @throws coding_exception
     * @throws dml_exception
     */
    public function print_summary() {
        global $USER;

        $submission = $this->get_submission($USER->id, false); // Get the submission!

        $a = checkmark_getsubmissionstats($submission, $this->checkmark);

        $output =
                html_writer::tag('div', get_string('checkmark_summary', 'checkmark', $a), ['class' => 'chkmrksubmissionsummary']) .
                html_writer::empty_tag('br');

        return $output;
    }

    /**
     * print_student_answer($userid) returns a short HTML-coded string
     * with the checked examples in black an unchecked ones lined through and in a light grey.
     *
     * @param int $userid The user-ID to print the student anwer for.
     * @return string checked examples
     * @throws coding_exception
     * @throws dml_exception
     */
    public function print_student_answer($userid) {
        $output = '';

        if (!$submission = $this->get_submission($userid)) {
            return get_string('nosubmission', 'checkmark');
        }

        foreach ($submission->get_examples() as $example) {
            if ($output != '') {
                $output .= ', ';
            } else {
                $output .= get_string('strexamples', 'checkmark') . ': ';
            }
            if ($example->is_checked()) { // Is it checked?
                $class = 'checked';
            } else {
                $class = 'unchecked';
            }
            $output .= html_writer::tag('span', $example->shortname, ['class' => $class]);
        }

        // Wrapper!
        return html_writer::tag('div', $output, ['class' => 'examplelist']);
    }

    /**
     * Echo the print preview tab including a optional message!
     *
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function preview() {
        global $CFG, $OUTPUT, $PAGE, $USER;

        $context = context_module::instance($this->cm->id);
        require_capability('mod/checkmark:view', $context);

        $PAGE->set_title(format_string($this->checkmark->name, true));
        $PAGE->set_heading($this->course->fullname);
        $PAGE->activityheader->disable();

        $submission = $this->get_submission($USER->id, false);
        $feedback = $this->get_feedback($USER->id);

        // Guest can not submit nor edit an checkmark (bug: 4604)!
        if (!is_enrolled($this->context, $USER, 'mod/checkmark:submit')) {
            $editable = false;
        } else {
            $editable = $this->isopen();
        }

        echo html_writer::start_div('header-maxwidth');
        $urlbase = $CFG->wwwroot . '/mod/checkmark/';
        echo html_writer::tag('a', get_string('back'), [
            'class' => 'btn btn-secondary mb-2',
            'href' => $urlbase . 'view.php?id=' . $this->cm->id,
            'id' => 'view',
        ]);
        echo html_writer::end_div();

        // Studentview Container start.
        echo $OUTPUT->container_start('studentview');
        $previewform = new MoodleQuickForm('optionspref', 'post', '#', '');

        $content = '';
        $content .= $this->get_attendancehint();
        $content .= "\n";

        $content .= $this->get_checkmarks_overview_html($context);
        if (has_capability('mod/checkmark:grade', $this->context)) {
            $previewform->addElement('html', $content);
            $previewform->display();
        } else {
            echo $content;
        }

        echo $this->view_student_summary($USER, true);
        echo $OUTPUT->container_end();
        echo $OUTPUT->footer();
    }

    /**
     * This function returns the HTML with the checkmarks to be displayed. This is used to preview and to display
     * the checkmarks after submission is over, and the checkmarks are not editable anymore.
     *
     * @param object $context Context instance to check capabilities
     * @return string HTML with the checkmarks overview
     */
    public function get_checkmarks_overview_html($context) {
        global $USER, $OUTPUT;

        $o = '';
        // Display overview!
        if (has_capability('mod/checkmark:view_preview', $context) ||
        has_capability('mod/checkmark:submit', $context, $USER, false)) {
            $o .= $OUTPUT->box_start('generalbox boxaligncenter header-maxwidth', 'checkmark');
            $o .= html_writer::start_tag('div', ['class' => 'mform']);
            $o .= html_writer::start_tag('div', ['class' => 'clearfix']);
            $o .= $this->print_user_submission($USER->id, true);
            $o .= html_writer::end_tag('div');
            $o .= html_writer::end_tag('div');
            $o .= $OUTPUT->box_end();
            $o .= "\n";
        }
        return $o;
    }

    /**
     * Every view for checkmark (teacher/student/etc.)
     *
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     * @throws required_capability_exception
     */
    public function view() {
        global $OUTPUT, $USER, $PAGE, $CFG;

        $edit = optional_param('edit', 0, PARAM_BOOL);
        $saved = optional_param('saved', 0, PARAM_BOOL);
        $late = optional_param('late', 0, PARAM_BOOL);

        $context = context_module::instance($this->cm->id);
        require_capability('mod/checkmark:view', $context);

        $PAGE->set_title(format_string($this->checkmark->name, true));
        $PAGE->set_heading($this->course->fullname);
        $PAGE->add_body_class('limitedwidth');

        /* TRIGGER THE VIEW EVENT */
        $event = \mod_checkmark\event\course_module_viewed::create([
                'objectid' => $this->cm->instance,
                'context' => context_module::instance($this->cm->id),
                'other' => [
                        'name' => $this->checkmark->name,
                ],
        ]);
        $event->add_record_snapshot('course', $this->course);
        // In the next line you can use $PAGE->activityrecord if you have set it, or skip this line if you don't have a record.
        $event->add_record_snapshot($PAGE->cm->modname, $this->checkmark);
        $event->trigger();
        /* END OF VIEW EVENT */

        $submission = $this->get_submission($USER->id, false);
        $feedback = $this->get_feedback($USER->id);

        // Guest can not submit nor edit an checkmark (bug: 4604)!
        if (!is_enrolled($this->context, $USER, 'mod/checkmark:submit')) {
            $editable = false;
        } else {
            $editable = $this->isopen();
        }
        $editmode = ($editable && $edit);

        $data = new stdClass();
        $data->id = $this->cm->id;
        $data->checkmarkid = $this->checkmark->id;
        $data->edit = $editmode;
        $data->examples = $this->get_examples();
        $data->editable = $editable;
        if ($submission) {
            $data->sid = $submission->get_id();
            if (!empty($submission->get_examples())) {
                foreach ($submission->get_examples() as $key => $example) {
                    $name = 'example' . $key;
                    $data->$name = empty($example->is_checked()) ? 0 : 1;
                }
            }
        } else {
            $data->sid = null;
        }

        $mform = new checkmark_submission_form(null, $data);

        // Prepare form and process submitted data!
        if ($mform->is_cancelled()) {
            $url = new moodle_url('/course/view.php', ['id' => $PAGE->course->id], "section-" . $PAGE->cm->sectionnum);
            redirect($url);
        }

        if (($formdata = $mform->get_data()) && $editable) {

            // Create the submission if needed & return its id!
            $submission = $this->get_submission($USER->id, true);
            $formarray = json_decode(json_encode($formdata), true);

            foreach ($submission->get_examples() as $key => $example) {
                $name = $key;

                if (isset($formarray[$name]) && ($formarray[$name] != 0)) {
                    $submission->get_example($key)->set_state(\mod_checkmark\example::CHECKED);
                } else {
                    $submission->get_example($key)->set_state(\mod_checkmark\example::UNCHECKED);
                }
            }

            $this->update_submission($submission);
            $this->email_teachers($submission);

            // Redirect to get updated submission date!
            redirect(new moodle_url($PAGE->url, ['id' => $this->cm->id, 'saved' => 1]));
        } else if ($formdata && !$editable) {
            // Redirect to get error message!
            redirect(new moodle_url($PAGE->url, ['id' => $this->cm->id, 'late' => 1]));
        }
        $this->view_header();

        if ($saved) {
            \core\notification::success(get_string('submissionsaved', 'checkmark'));
        }

        if ($late) {
            \core\notification::error(get_string('latesubmissionwarning', 'checkmark'));
        }

        $gradingsummery = $this->create_grading_summary();
        echo html_writer::tag('div', $this->buttongroup($gradingsummery), ['class' => 'tertiary-navigation pl-0 header-maxwidth']);

        // Print override info.
        if (has_capability('mod/checkmark:manageoverrides', $context)) {
            echo html_writer::div($this->get_renderer()->checkmark_override_summary_links($this->get_instance(), $this->cm),
                'header-maxwidth mb-3');
        }
        // Print grading summary only when user has mod/checkmark:grade capability.
        if (has_capability('mod/checkmark:grade', $this->context)) {
            echo html_writer::div($this->get_renderer()->render_checkmark_grading_summary(
                $gradingsummery, $this->cm));
        }

        // Studentview Container start.
        echo $OUTPUT->container_start('studentview');

        $content = '';
        $content .= $this->get_attendancehint();
        $content .= "\n";

        if ($editable && has_capability('mod/checkmark:submit', $context, $USER, false) && !empty($mform)) {
            $content .= $OUTPUT->box_start('generalbox boxaligncenter header-maxwidth', 'checkmarkform');
            $content .= $mform->render();
            $content .= $OUTPUT->box_end();
            $content .= "\n";
        } else {
            $content .= $this->get_checkmarks_overview_html($context);
        }

        // Display the checkmarks only for the students, not for the teachers.
        if (!has_capability('mod/checkmark:grade', $this->context)) {
            echo $content;
            echo $this->view_student_summary($USER, true);
        }

        echo $OUTPUT->container_end();
        echo "\n";
        $this->view_footer();
        echo "\n";
    }

    /**
     * Utility function to add a row of data to a table with 2 columns where the first column is the table's header.
     * Modified the table param and does not return a value.
     *
     * @param html_table $table The table to append the row of data to
     * @param string $first The first column text
     * @param string $second The second column text
     * @param array $firstattributes The first column attributes (optional)
     * @param array $secondattributes The second column attributes (optional)
     * @return void
     */
    private function add_table_row_tuple(html_table $table, $first, $second, $firstattributes = [],
            $secondattributes = []) {
        $row = new html_table_row();
        $cell1 = new html_table_cell($first);
        $cell1->header = true;
        if (!empty($firstattributes)) {
            $cell1->attributes = $firstattributes;
        }
        $cell2 = new html_table_cell($second);
        if (!empty($secondattributes)) {
            $cell2->attributes = $secondattributes;
        }
        $row->cells = [$cell1, $cell2];
        $table->data[] = $row;
    }

    /**
     * Display the header and top of a page
     *
     * This is used by the view() method to print the header of view.php but
     * it can be used on other pages in which case the string to denote the
     * page in the navigation trail should be passed as an argument
     *
     * @param string $subpage Description of subpage to be used in navigation trail
     * @throws coding_exception
     */
    public function view_header($subpage = '') {
        global $CFG, $PAGE, $OUTPUT;

        if ($subpage) {
            $PAGE->navbar->add($subpage);
        }

        $pagetitle = strip_tags($this->course->shortname . ': ' . get_string('modulename', 'checkmark') .
                ': ' . format_string($this->checkmark->name, true));
        $PAGE->set_heading($this->course->fullname);

        // Postfix are the additional files here.
        $o = $this->get_renderer()->render(new checkmark_header(
            $this->get_instance(),
            $this->show_intro(),
            $this->get_course_module()->id,
            $this->get_introattachments())
        );

        echo $o;
    }

    /**
     * Print 2 tables of information with no action links -
     * the submission summary and the grading summary.
     *
     * @param stdClass $user the user to print the report for
     * @param bool $showlinks - Return plain text or links to the profile
     * @return string - the html summary
     */
    public function view_student_summary($user, $showlinks) {
        $o = '';

        if ($this->can_view_submission($user->id)) {
            if (has_capability('mod/checkmark:view', $this->context, $user, false)) {
                // The user can view the submission summary.
                list($submissionstatus, $feedbackstatus) = $this->get_checkmark_submission_status_renderable($user->id, $showlinks);
                $o .= $this->get_renderer()->render_checkmark_submission_status($submissionstatus);
                if (array_key_exists('grade', $feedbackstatus)) {
                    $o .= $this->get_renderer()->render_checkmark_feedback_status($feedbackstatus);
                }
            }
        }
        return $o;
    }

    /**
     * Perform an access check to see if the current $USER can view this users submission.
     *
     * @param int $userid
     * @return bool
     */
    public function can_view_submission($userid) {
        global $USER;

        if (!is_enrolled($this->context, $userid)) {
            return false;
        }
        if (has_any_capability(['mod/checkmark:view_preview', 'mod/checkmark:grade'], $this->context)) {
            return true;
        }

        if ($userid == $USER->id) {
            return true;
        }
        return false;
    }

    /**
     * Perform an access check to see if the current $USER can view this users submission.
     *
     * @param int $userid
     * @return array
     */
    public function get_checkmark_submission_status_renderable($userid) {
        global $CFG, $USER;
        require_once($CFG->libdir . '/gradelib.php');

        $status = [];
        $submission = $this->get_submission($USER->id, false); // Get the submission!
        $forfeedback = [];

        list($avail, $due) = $this->get_avail_due_times();

        $gradinginfo = grade_get_grades($this->course->id, 'mod', 'checkmark',
            $this->checkmark->id, $userid);

        $item = $gradinginfo->items[CHECKMARK_GRADE_ITEM];
        $grade = $item->grades[$userid];

        $status['gradingstatus'] = 'notgraded';
        $status['feedback'] = null;
        if ($grade->grade) {
            $status['gradingstatus'] = 'graded';
            $forfeedback = (array)$grade;
            $forfeedback['gradefordisplay'] = $this->display_grade($grade->grade);
        }

        $status['timedue'] = $due;

        $status['submissionstatus'] = 'notsubmitted';
        $status['timecreated'] = 0;
        $status['timemodified'] = 0;
        if ($submission) {
            $status['submissionstatus'] = 'submitted';
            $status['timecreated'] = $submission->timecreated;
            if ($status['timecreated']) {
                $status['timemodified'] = $submission->get_timemodified();
            }
        }

        $status['checkmarkinfo'] = checkmark_getsubmissionstats($submission, $this->checkmark);

        return [$status, $forfeedback];
    }

    /**
     * Creates a gradingsummary object for use in the gradingsummary table
     *
     * @return \mod_checkmark\gradingsummary
     * @throws coding_exception
     */
    public function create_grading_summary() {
        $currentgroup = groups_get_activity_group($this->cm, true);
        $participantcount = submissionstable::count_userids($this->context, $this->checkmark->id,
                $currentgroup, self::FILTER_ALL);
        $submittedcount = submissionstable::count_userids($this->context, $this->checkmark->id,
                $currentgroup, self::FILTER_SUBMITTED);
        $needsgrading = submissionstable::count_userids($this->context, $this->checkmark->id,
                $currentgroup, self::FILTER_REQUIRE_GRADING);
        $cangrade = has_capability('mod/checkmark:grade', $this->context);
        $attendantcount = -1;
        $absencecount = -1;
        $needattendanceentrycount = -1;
        $presentationgradingcount = -1;
        if ($this->checkmark->trackattendance) {
            $attendantcount = submissionstable::count_userids($this->context, $this->checkmark->id,
                    $currentgroup, self::FILTER_ATTENDANT);
            $absencecount = submissionstable::count_userids($this->context, $this->checkmark->id,
                    $currentgroup, self::FILTER_ABSENT);
            $needattendanceentrycount = submissionstable::count_userids($this->context, $this->checkmark->id,
                    $currentgroup, self::FILTER_UNKNOWN);
        }
        if ($this->checkmark->presentationgrading) {
            $presentationgradingcount = submissionstable::count_userids($this->context, $this->checkmark->id,
                    $currentgroup, self::FILTER_PRESENTATIONGRADING);
        }

        $summary = new \mod_checkmark\gradingsummary($participantcount, $this->checkmark->timeavailable, $submittedcount,
                $needsgrading, $this->checkmark->timedue, $this->checkmark->cutoffdate, $this->cm->id,
                $this->course->startdate, $cangrade, $this->cm->visible, $attendantcount,
                $absencecount, $needattendanceentrycount, $presentationgradingcount);
        return $summary;
    }

    /**
     * Display the checkmark intro
     *
     * The default implementation prints the checkmark description in a box
     */
    public function get_intro() {
        global $OUTPUT;
        $content = '';
        $content .= $OUTPUT->container_start('description');
        $content .= $OUTPUT->heading($this->checkmark->name, 3);
        $notoverridden = (!$this->overrides || $this->overrides->timeavailable === null);
        $cmptime = $notoverridden ? $this->checkmark->timeavailable : $this->overrides->timeavailable;
        if ($this->checkmark->alwaysshowdescription || (time() > $cmptime)) {
            $introattachments = $this->get_introattachments();
            if (!empty($this->checkmark->intro || !empty($introattachments))) {
                $content .= $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
                $content .= format_module_intro('checkmark', $this->checkmark, $this->cm->id);
                $content .= $introattachments;
                $content .= $OUTPUT->box_end();
            }
        }
        $content .= $OUTPUT->container_end();
        return $content;
    }

    /**
     * Print intro attachment files if there are any
     */
    public function get_introattachments() {
        if ($this->should_provide_intro_attachments()) {
            if ($files = $this->get_renderer()->checkmark_files($this->context, 0,
                CHECKMARK_INTROATTACHMENT_FILEAREA, 'mod_checkmark')) {
                return $files;
            }
        }
        return '';
    }

    /**
     * Check if the intro attachments should be provided. Based on the database entry for the checkmark setting.
     *
     * @return bool
     */
    public function should_provide_intro_attachments() {
        global $DB;

        $submissionattachments = $DB->get_field('checkmark', 'submissionattachments', ['id' => $this->checkmark->id]);
        // If attachments should only be shown when the submission is open, check if the submission is open.
        if ($submissionattachments) {
            return $this->isopen();
        }
        return true;
    }

    /**
     * Based on the current checkmark settings should we display the intro.
     *
     * @return bool showintro
     */
    public function show_intro() {
        return $this->checkmark->alwaysshowdescription || $this->isopen();
    }

    /**
     * Display the checkmark dates
     *
     * Prints the checkmark start and end dates in a box.
     *
     * @throws coding_exception
     */
    public function get_avail_due_times() {
        if (!$this->checkmark->timeavailable && !$this->checkmark->timedue && (!$this->overrides ||
            ($this->overrides && !$this->overrides->timeavailable && !$this->overrides->timedue))) {
            return;
        }

        // Variables have to be initialized to avoid Behat undefined variable errors.
        $timeavailable = 0;
        $due = 0;

        if (($this->checkmark->timeavailable || ($this->overrides && $this->overrides->timeavailable &&
            ($this->overrides->timeavailable !== $this->checkmark->timeavailable))) &&
            (!$this->overrides || $this->overrides->timeavailable !== 0)) {
            if ($this->checkmark->timeavailable) {
                $timeavailable = $this->checkmark->timeavailable;
                if ($this->overrides && $this->overrides->timeavailable) {
                    $timeavailable = $this->overrides->timeavailable;
                }
            } else {
                $timeavailable = $this->overrides->timeavailable;
            }
        }
        if (($this->checkmark->timedue || ($this->overrides && $this->overrides->timedue &&
            ($this->overrides->timedue !== $this->checkmark->timedue))) &&
            (!$this->overrides || $this->overrides->timedue !== 0)) {
            if ($this->checkmark->timedue) {
                $due = $this->checkmark->timedue;
                if ($this->overrides && $this->overrides->timedue) {
                    $due = $this->overrides->timedue;
                }
            } else {
                $due = $this->overrides->timedue;
            }
        }

        $overridetimes = [$timeavailable, $due];
        return $overridetimes;
    }

    /**
     * Display the checkmark dates
     *
     * Prints the checkmark start and end dates in a box.
     *
     * @throws coding_exception
     */
    public function get_dates() {
        global $OUTPUT;
        $content = '';
        if (!$this->checkmark->timeavailable && !$this->checkmark->timedue && (!$this->overrides ||
            ($this->overrides && !$this->overrides->timeavailable && !$this->overrides->timedue))) {
            return;
        }

        $content .= $OUTPUT->box_start('generalbox boxaligncenter header-maxwidth activity-header', 'dates');
        $now = time();

        if (($this->checkmark->timeavailable || ($this->overrides && $this->overrides->timeavailable &&
            ($this->overrides->timeavailable !== $this->checkmark->timeavailable))) &&
            (!$this->overrides || $this->overrides->timeavailable !== 0)) {
            if ($this->checkmark->timeavailable) {
                $timeavailable = userdate($this->checkmark->timeavailable);
                if ($this->overrides && $this->overrides->timeavailable) {
                    $timeavailable = userdate($this->overrides->timeavailable);
                }
            } else {
                $timeavailable = userdate($this->overrides->timeavailable);
            }
            $openlabelid = $timeavailable > $now ? 'activitydate:opens' : 'activitydate:opened';
            $starttime = html_writer::tag('strong', get_string($openlabelid, 'checkmark')) . ' ' . $timeavailable;
            $content .= html_writer::div($starttime);
        }

        if (($this->checkmark->timedue || ($this->overrides && $this->overrides->timedue &&
            ($this->overrides->timedue !== $this->checkmark->timedue))) &&
            (!$this->overrides || $this->overrides->timedue !== 0)) {
            if ($this->checkmark->timedue) {
                $due = userdate($this->checkmark->timedue);
                if ($this->overrides && $this->overrides->timedue) {
                    $due = userdate($this->overrides->timedue);
                }
            } else {
                $due = userdate($this->overrides->timedue);
            }
            $endtime = html_writer::tag('strong', get_string('activitydate:due', 'checkmark')) . ' ' . $due;
            $content .= html_writer::div($endtime);
        }

        $content .= $OUTPUT->box_end();
        return $content;
    }

    /**
     * Display the hint if attendance is tracked and linked to grades
     *
     * @throws coding_exception
     */
    public function get_attendancehint() {
        global $OUTPUT;
        if (!$this->checkmark->trackattendance || !$this->checkmark->attendancegradelink) {
            return;
        }

        return $OUTPUT->box(get_string('attendancegradelink_hint', 'checkmark'), 'generalbox', 'attendancehint');
    }

    /**
     * Display the bottom and footer of a page
     *
     * This default method just prints the footer.
     */
    public function view_footer() {
        global $OUTPUT;
        echo $OUTPUT->footer();
    }

    /**
     * Display the feedback to the student
     *
     * This default method prints the teacher picture and name, date when marked,
     * grade and teacher submissioncomment.
     *
     * @param object $feedback The feedback object or null in which case it will be loaded
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function view_feedback($feedback = null) {
        global $USER, $CFG, $DB, $OUTPUT;
        require_once($CFG->libdir . '/gradelib.php');

        if (!is_enrolled($this->context, $USER, 'mod/checkmark:view')) {
            // Can not submit checkmarks -> no feedback!
            return;
        }

        if (!$feedback) { // Get feedback for this checkmark!
            $userid = $USER->id;
            $feedback = $this->get_feedback($USER->id);
        } else {
            $userid = $feedback->userid;
        }

        $gradinginfo = grade_get_grades($this->course->id, 'mod', 'checkmark',
                $this->checkmark->id, $userid);
        $item = $gradinginfo->items[CHECKMARK_GRADE_ITEM];
        $grade = $item->grades[$userid];

        $attendanceitem = false;
        $attendancegrade = false;
        if ($this->checkmark->trackattendance && $this->checkmark->attendancegradebook) {
            $attendanceitem = $gradinginfo->items[CHECKMARK_ATTENDANCE_ITEM];
            $attendancegrade = $attendanceitem->grades[$userid];
        }
        $presentationitem = false;
        $presentationgrade = false;
        if ($this->checkmark->presentationgrading && $this->checkmark->presentationgradebook) {
            $presentationitem = $gradinginfo->items[CHECKMARK_PRESENTATION_ITEM];
            $presentationgrade = $presentationitem->grades[$userid];
        }

        if (($feedback == false)
                && (!$item || !$grade || (($grade->grade == null) && ($grade->feedback == null)))
                && (!$attendanceitem || !$attendancegrade || ($attendancegrade->grade == null))
                && (empty($presentationitem) || empty($presentationgrade)
                        || (($presentationgrade->grade == null) && ($presentationgrade->feedback == null)))) {
            return;
        } else if ($feedback == false) {
            $feedback = new stdClass();
            $feedback->timemodified = 0;
            if ($item && $grade) {
                $feedback->graderid = $grade->usermodified;
                $feedback->timemodified = $grade->dategraded;
                $feedback->grade = $grade->grade;
                $feedback->feedback = $grade->feedback;
                $feedback->format = $grade->feedbackformat;
            } else {
                $feedback->grade = null;
                $feedback->format = 1;
            }
            if (!empty($attendanceitem) && $attendancegrade) {
                if ($attendancegrade->dategraded > $feedback->timemodified) {
                    $feedback->graderid = $attendancegrade->usermodified;
                    $feedback->timemodified = $attendancegrade->dategraded;
                }
                $feedback->attendance = $attendancegrade->grade;
            } else {
                $feedback->attendance = null;
            }
            if (!empty($presentationitem) && $presentationgrade) {
                if ($presentationgrade->dategraded > $feedback->timemodified) {
                    $feedback->graderid = $presentationgrade->usermodified;
                    $feedback->timemodified = $presentationgrade->dategraded;
                }
                $feedback->presentationgrade = $presentationgrade->grade;
                $feedback->presentationfeedback = $presentationgrade->feedback;
                $feedback->presentationformat = $presentationgrade->feedbackformat;
            } else {
                $feedback->presentationgrade = null;
                $feedback->presentationfeedback = null;
                $feedback->presentationformat = 1;
            }
        }

        // Check if the user can submit?
        $cansubmit = has_capability('mod/checkmark:submit', $this->context, $userid, false);
        // If not then check if the user still has the view cap and has a previous submission?
        $cansubmit = $cansubmit || (($feedback !== false) && has_capability('mod/checkmark:view', $this->context, $userid, false));

        if (!$cansubmit) {
            // Can not submit checkmarks -> no feedback!
            return;
        }

        if (($grade->hidden || $grade->grade === false)
                && (!$this->checkmark->trackattendance || ($this->checkmark->attendancegradebook && $attendanceitem->hidden))
                && (!$this->checkmark->presentationgrading || ($this->checkmark->presentationgradebook
                                && $presentationitem->hidden))) { // Hidden or error!
            return;
        }

        if ($grade->grade === null && empty($grade->str_feedback)
                && (!$this->checkmark->trackattendance || $feedback->attendance === null)
                && (!$this->checkmark->presentationgrading
                        || (!$this->checkmark->presentationgrade && $feedback->presentationfeedback === null)
                        || ($this->checkmark->presentationgrade && $feedback->presentationgrade === null
                                && $feedback->presentationfeedback === null))) {   // Nothing to show yet!
            return;
        }

        $dategraded = $grade->dategraded;
        $gradedby = $grade->usermodified;
        $showfeedback = false;
        if (empty($gradedby)) {
            // Only show attendance or presentationgrade!
            $gradedby = $feedback->graderid;
            $dategraded = $feedback->timemodified;
            if (!$grader = $DB->get_record('user', ['id' => $gradedby])) {
                throw new moodle_exception('cannotfindteacher');
            }
        } else {
            // We need the teacher info!
            if (!$grader = $DB->get_record('user', ['id' => $gradedby])) {
                throw new moodle_exception('cannotfindteacher');
            }
            $showfeedback = true;
        }

        // Print the feedback!
        echo $OUTPUT->heading(get_string('feedbackfromteacher', 'checkmark', fullname($grader)));
        if ($grader) {
            $userpicture = $OUTPUT->user_picture($grader);
            $from = html_writer::tag('div', html_writer::tag('strong', fullname($grader)), ['class' => 'fullname']);
        } else {
            $userpicture = '';
            $from = '';
        }
        $from .= html_writer::tag('div', html_writer::tag('strong', userdate($dategraded)), ['class' => 'time']);
        $topic = html_writer::tag('div', $from, ['class' => 'from']);
        $row = html_writer::tag('td', $userpicture, ['class' => 'left picture']);
        $row .= html_writer::tag('td', $topic, ['class' => 'topic']);
        $tablecontent = html_writer::tag('tr', $row);
        // Second row!
        if ($showfeedback) {
            if ($this->checkmark->grade) {
                $content =
                        html_writer::tag('div', html_writer::tag('strong',
                                        get_string('modgrade', 'grades') . ': ') . $grade->str_long_grade,
                        ['class' => 'grade']);
            } else {
                $content = '';
            }
            $content .= html_writer::tag('div', '', ['class' => 'clearer']) .
                    html_writer::tag('div', $grade->str_feedback, ['class' => 'comment']);
            $row = html_writer::tag('td', $content, ['class' => 'content', 'colspan' => 2]);
            $tablecontent .= html_writer::tag('tr', $row);
        }

        if ($this->checkmark->trackattendance) {
            if ($attendanceitem && ($attendancegrade->locked || $attendancegrade->overridden)) {
                $feedback->attendance = $attendancegrade->grade;
            }
            if ($feedback->attendance == 1) {
                $attendancestr = strtolower(get_string('attendant', 'checkmark'));
            } else if ($feedback->attendance == 0 && $feedback->attendance !== null) {
                $attendancestr = strtolower(get_string('absent', 'checkmark'));
            } else {
                $attendancestr = strtolower(get_string('unknown', 'checkmark'));
            }
            $attendance = checkmark_get_attendance_symbol($feedback->attendance) . $attendancestr;
            // Third row --> attendance info!
            $row = html_writer::tag('td', html_writer::tag('strong', get_string('attendance', 'checkmark') . ': ') . $attendance,
                    ['class' => 'content', 'colspan' => 2]);
            $tablecontent .= html_writer::tag('tr', $row);
        }

        if ($this->checkmark->presentationgrading) {
            if ($this->checkmark->presentationgrade && $this->checkmark->presentationgradebook) {
                $presgrade = $presentationgrade->str_long_grade;
            } else if (!empty($this->checkmark->presentationgrade)) {
                $presgrade = $this->display_grade($feedback->presentationgrade, CHECKMARK_PRESENTATION_ITEM);
            } else {
                $presgrade = "";
            }
            if ($this->checkmark->presentationgradebook) {
                $presfeedback = $presentationgrade->str_feedback;
            } else {
                $presfeedback = $feedback->presentationfeedback;
            }
            if ($presgrade != "" || $presfeedback != "") {
                $content = html_writer::tag('div', html_writer::tag('strong', get_string('presentationgrade', 'checkmark') . ': ') .
                                $presgrade, ['class' => 'grade']) .
                        html_writer::tag('div', '', ['class' => 'clearer']) .
                        html_writer::tag('div', $presfeedback, ['class' => 'comment']);
                $row = html_writer::tag('td', $content, ['class' => 'content', 'colspan' => 2]);
                $tablecontent .= html_writer::tag('tr', $row);
            }
        }

        echo html_writer::tag('table', $tablecontent, ['cellspacing' => 0, 'class' => 'feedback']);
    }

    /**
     * Returns two buttons
     *
     * This is used by view_header to put this link at the top right of the page.
     * For teachers it gives the number of submitted checkmarks with a link
     * For students it gives the time of their submission.
     *
     * @param object $summary
     * @param bool $allgroups print all groups info if user can access all groups, suitable for index.php
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     */
    public function buttongroup($summary, $allgroups = false) {
        global $USER, $CFG;

        $submitted = '';
        $urlbase = $CFG->wwwroot . '/mod/checkmark/';

        $gradebtntype = 'btn-secondary';
        if ($summary->submissionssubmittedcount > 0) {
            $gradebtntype = 'btn-primary';
        }
        $context = context_module::instance($this->cm->id);
        if (has_capability('mod/checkmark:grade', $context)) {
            $submitted = html_writer::tag('a', get_string('gradebutton', 'checkmark'), [
                'class' => 'btn ' . $gradebtntype . ' mr-1 ml-0',
                'href' => $urlbase . 'submissions.php?id=' . $this->cm->id,
                'id' => 'submissions',
            ]);
            $submitted .= html_writer::tag('a', get_string('viewpreview', 'checkmark'), [
                'class' => 'btn btn-secondary',
                'href' => $urlbase . 'preview.php?id=' . $this->cm->id,
                'id' => 'preview',
            ]);
        }

        return $submitted;
    }
    /**
     * Returns a link with info about the state of the checkmark submissions
     *
     * This is used by view_header to put this link at the top right of the page.
     * For teachers it gives the number of submitted checkmarks with a link
     * For students it gives the time of their submission.
     *
     * @param bool $allgroups print all groups info if user can access all groups, suitable for index.php
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     */
    public function submittedlink($allgroups = false) {
        global $USER, $CFG;

        $submitted = '';
        $urlbase = $CFG->wwwroot . '/mod/checkmark/';

        $context = context_module::instance($this->cm->id);
        if (has_capability('mod/checkmark:grade', $context)) {
            $submitted = html_writer::tag('a', get_string('viewallsubmissions', 'checkmark'), [
                    'class' => 'btn btn-secondary',
                    'href' => $urlbase . 'submissions.php?id=' . $this->cm->id,
                    'id' => 'submissions',
            ]);
        } else {
            if (isloggedin()) {
                if ($submission = $this->get_submission($USER->id)) {
                    if ($submission->get_timemodified()) {
                        $date = userdate($submission->get_timemodified());
                        if ($this->overrides && $this->overrides->timedue !== null) {
                            $timedue = $this->overrides->timedue;
                        } else {
                            $timedue = $this->checkmark->timedue;
                        }
                        if ($submission->get_timemodified() <= $timedue || empty($timedue)) {
                            $submitted = html_writer::tag('span', $date, ['class' => 'text-success']);
                        } else {
                            $submitted = html_writer::tag('span', $date, ['class' => 'text-error']);
                        }
                    }
                }
            }
        }

        return $submitted;
    }

    /**
     * Override available from date, due date or cut off date for certain users or groups!
     *
     * @param int[] $entities Array of userids or groupids to override dates for
     * @param int $timeavailable
     * @param int $timedue
     * @param int $cutoffdate
     * @param string $mode \overrideform::USER for using userids or \overrideform::GROUP for using group ids
     * @throws dml_exception
     */
    public function override_dates(array $entities, int $timeavailable, int $timedue, int $cutoffdate,
            string $mode = \mod_checkmark\overrideform::USER) {
        global $DB, $USER;
        require_capability('mod/checkmark:manageoverrides', $this->context);

        $cmgroupmode = groups_get_activity_groupmode($this->cm);
        // Checks if current user is allowed to access all groups of the course.
        $accessallgroups = ($cmgroupmode == NOGROUPS) ||
                has_capability('moodle/site:accessallgroups', $this->context);
        // Groups the current user is part of for checking valid requests if !$accessallgroups.
        $usergroups = groups_get_all_groups($this->cm->course, $USER->id);

        if (empty($entities) || !is_array($entities)) {
            return;
        }

        $entities = array_unique($entities);

        $record = new stdClass();
        if ($timeavailable == $this->checkmark->timeavailable) {
            $record->timeavailable = null;
        } else if (empty($timeavailable)) {
            $record->timeavailable = 0;
        } else {
            $record->timeavailable = $timeavailable;
        }

        if ($timedue == $this->checkmark->timedue) {
            $record->timedue = null;
        } else if (empty($timedue)) {
            $record->timedue = 0;
        } else {
            $record->timedue = $timedue;
        }

        if ($cutoffdate == $this->checkmark->cutoffdate) {
            $record->cutoffdate = null;
        } else if (empty($cutoffdate)) {
            $record->cutoffdate = 0;
        } else {
            $record->cutoffdate = $cutoffdate;
        }

        $record->modifierid = $USER->id;
        $record->checkmarkid = $this->cm->instance;
        $record->timecreated = time();
        foreach ($entities as $cur) {
            $existingrecord = null;
            $cond = ['userid' => $cur, 'checkmarkid' => $this->cm->instance];

            // Init eventparams and add general event params.
            $eventparams = [
                'context' => context_module::instance($this->cm->id),
                'other' => [
                    'checkmarkid' => $this->cm->instance,
                ],
            ];
            if ($mode == \mod_checkmark\overrideform::GROUP) {
                if (!$accessallgroups && !property_exists($usergroups, $cur)) {
                    // Will always throw an exception once we get here.
                    require_capability('moodle/site:accessallgroups', $this->context);
                }
                $record->groupid = $cur;
                $existingrecord = $DB->get_record('checkmark_overrides',
                        ['groupid' => $cur, 'checkmarkid' => $this->cm->instance]);
            } else {
                if (!$accessallgroups) {
                    $curgroups = groups_get_all_groups($this->cm->course, $cur);
                    // Checks if user to override has at least one group in common with the user performing the action.
                    if (empty(array_intersect(array_keys($usergroups), array_keys($curgroups)))) {
                        // Will always throw an exception once we get here.
                        require_capability('moodle/site:accessallgroups', $this->context);
                    }
                }
                $record->userid = $cur;
                $existingrecord = $DB->get_record('checkmark_overrides', ['userid' => $cur,
                        'checkmarkid' => $this->cm->instance, ]);
            }
            if ($existingrecord) {
                $record->id = $existingrecord->id;
                $record->grouppriority = $existingrecord->grouppriority;
                // Delete Override if all values are reset to the course dates.
                if ($record->timeavailable === null && $record->timedue === null && $record->cutoffdate === null) {
                    $this->delete_override($entities, $mode);
                }
                $DB->update_record('checkmark_overrides', $record);
                // Null values are ignored by update_record so they need to be updated manually.
                if ($record->timeavailable === null && $record->timeavailable != $existingrecord->timeavailable) {
                    $DB->set_field('checkmark_overrides', 'timeavailable', null, $cond);
                }
                if ($record->timedue === null && $record->timedue != $existingrecord->timedue) {
                    $DB->set_field('checkmark_overrides', 'timedue', null, $cond);
                }
                if ($record->cutoffdate === null && $record->cutoffdate != $existingrecord->cutoffdate) {
                    $DB->set_field('checkmark_overrides', 'cutoffdate', null, $cond);
                }

                // Add event params for override_updated events and fire them.
                $eventparams['objectid'] = $record->id;
                if ($mode == \mod_checkmark\overrideform::GROUP) {
                    $eventparams['other']['groupid'] = $record->groupid;
                    $event = \mod_checkmark\event\group_override_updated::create($eventparams);
                } else {
                    $eventparams['relateduserid'] = $record->userid;
                    $event = \mod_checkmark\event\user_override_updated::create($eventparams);
                }
                $event->trigger();

            } else {
                // Don't insert override if all values are identical with the course dates.
                if ($record->timeavailable === null && $record->timedue === null && $record->cutoffdate === null) {
                    return;
                }
                if ($mode == \mod_checkmark\overrideform::GROUP) {
                    $sql = "SELECT MAX(grouppriority) AS max
                              FROM {checkmark_overrides}
                             WHERE checkmarkid = ? AND groupid IS NOT NULL";
                    $params = [$this->cm->instance];
                    $highestpriority = $DB->get_record_sql($sql, $params);
                    $record->grouppriority = $highestpriority->max + 1;
                }
                $record->id = $DB->insert_record('checkmark_overrides', $record);

                // Add event params for override_created events and fire them.
                $eventparams['objectid'] = $record->id;
                if ($mode == \mod_checkmark\overrideform::GROUP) {
                    $eventparams['other']['groupid'] = $record->groupid;
                    $event = \mod_checkmark\event\group_override_created::create($eventparams);
                } else {
                    $eventparams['relateduserid'] = $record->userid;
                    $event = \mod_checkmark\event\user_override_created::create($eventparams);
                }
                $event->trigger();
            }
            checkmark_refresh_override_events($this, $record);
        }
    }

    /**
     * Delete override for given userids or groupids
     *
     * @param int[] $entities Array of userids or groupids for which override dates are deleted
     * @param int $mode \overrideform::USER for using userids or \overrideform::GROUP for using group ids
     * @throws dml_exception
     */
    public function delete_override($entities, $mode = \mod_checkmark\overrideform::USER) {
        global $DB, $USER;
        if (empty($entities)) {
            return;
        }

        require_capability('mod/checkmark:manageoverrides', $this->context);

        // Checks if current user is allowed to access all groups of the course.
        $cmgroupmode = groups_get_activity_groupmode($this->cm);
        $accessallgroups = ($cmgroupmode == NOGROUPS) ||
                has_capability('moodle/site:accessallgroups', $this->context);
        // Groups the current user is part of for checking valid requests if !$accessallgroups.
        $usergroups = groups_get_all_groups($this->cm->course, $USER->id);

        if (!is_array($entities)) {
            $entities = [$entities];
        }
        $entities = array_unique($entities);
        // Init eventparams and add general event params.
        $eventparams = [
            'context' => context_module::instance($this->cm->id),
            'other' => [
                'checkmarkid' => $this->cm->instance,
            ],
        ];

        foreach ($entities as $cur) {
            $existingrecord = null;
            if ($mode == \mod_checkmark\overrideform::GROUP) {
                if (!$accessallgroups && !property_exists($usergroups, $cur)) {
                    // Will always throw an exception once we get here.
                    require_capability('moodle/site:accessallgroups', $this->context);
                }
                $existingrecord = $DB->get_record('checkmark_overrides',
                        ['groupid' => $cur, 'checkmarkid' => $this->cm->instance]);
                $eventparams['objectid'] = $existingrecord->id;
                $eventparams['other']['groupid'] = $cur;
                $DB->delete_records('checkmark_overrides', ['groupid' => $cur]);
                $event = \mod_checkmark\event\group_override_deleted::create($eventparams);

            } else {
                $curgroups = groups_get_all_groups($this->cm->course, $cur);
                // Checks if user to delete the override from has at least one group in common with the user performing the action.
                if (empty(array_intersect(array_keys($usergroups), array_keys($curgroups)))) {
                    // Will always throw an exception once we get here.
                    require_capability('moodle/site:accessallgroups', $this->context);
                }
                $existingrecord = $DB->get_record('checkmark_overrides', ['userid' => $cur,
                        'checkmarkid' => $this->cm->instance, ]);
                $eventparams['objectid'] = $existingrecord->id;
                $eventparams['relateduserid'] = $cur;
                $DB->delete_records('checkmark_overrides', ['userid' => $cur]);
                $event = \mod_checkmark\event\user_override_deleted::create($eventparams);
            }
            $event->trigger();

            $existingrecord->timedue = null;
            checkmark_refresh_override_events($this, $existingrecord);
        }
    }

    /**
     * Changes the priority of a group override with the one above or below it
     *
     * @param int $groupidfrom Id of the group override that should be changed
     * @param bool $decrease True if priortiy should be lowered, false if it should be raised
     * @throws dml_exception
     */
    public function reorder_group_overrides(int $groupidfrom, bool $decrease = false) {
        global $DB, $USER;

        require_capability('mod/checkmark:manageoverrides', $this->context);

        // Checks if current user is allowed to access all groups of the course.
        $cmgroupmode = groups_get_activity_groupmode($this->cm);
        $accessallgroups = ($cmgroupmode == NOGROUPS) ||
                has_capability('moodle/site:accessallgroups', $this->context);

        $sign = '<';
        $minmax = 'MIN';
        if ($decrease) {
            $sign = '>';
            $minmax = 'MAX';
        }
        $joinstring = "";
        $joinwhere = "";
        $params = ['groupid' => $groupidfrom, 'checkmarkid' => $this->cm->instance,
                'checkmarkid2' => $this->cm->instance, 'checkmarkid3' => $this->cm->instance, ];

        if (!$accessallgroups) {
            $joinstring = "JOIN {groups_members} gm ON (gm.groupid = ov.groupid)";
            $joinwhere = "AND gm.userid = :userid ";
            $params['userid'] = $USER->id;
        }
        $sql = "SELECT groupid AS groupidto, grouppriority
                  FROM {checkmark_overrides} o
                  JOIN (
                        SELECT $minmax(grouppriority) priority
                          FROM {checkmark_overrides} ov
                          $joinstring
                         WHERE (
                                SELECT grouppriority
                                  FROM {checkmark_overrides}
                                 WHERE groupid = :groupid AND checkmarkid = :checkmarkid
                            ) $sign grouppriority AND checkmarkid = :checkmarkid2 AND ov.groupid IS NOT NULL $joinwhere
                        ) o1 ON o1.priority = o.grouppriority
                WHERE checkmarkid = :checkmarkid3;";

        $groupto = $DB->get_record_sql($sql, $params, MUST_EXIST);
            $this->swap_group_overrides($groupidfrom, $groupto->groupidto);
    }

    /**
     * Exchange the priorities of two group overrides
     *
     * @param int $groupidfrom
     * @param int $groupidto
     * @throws dml_exception
     */
    private function swap_group_overrides(int $groupidfrom, int $groupidto) {
        global $DB;
        $from = $DB->get_record('checkmark_overrides',
                ['checkmarkid' => $this->cm->instance, 'groupid' => $groupidfrom], '*', MUST_EXIST);
        $to = $DB->get_record('checkmark_overrides',
                ['checkmarkid' => $this->cm->instance, 'groupid' => $groupidto], '*', MUST_EXIST);

        if (!empty($from) && !empty($to) && $from->id != $to->id) {
            $oldfrompriority = $from->grouppriority;
            $from->grouppriority = $to->grouppriority;
            $to->grouppriority = $oldfrompriority;
            $DB->update_record('checkmark_overrides', $from);
            $DB->update_record('checkmark_overrides', $to);
            checkmark_refresh_override_events($this, $from);
            checkmark_refresh_override_events($this, $to);

            // Log the priority change.
            $eventparams = [
                'context' => context_module::instance($this->cm->id),
                'objectid' => $from->id,
                'other' => [
                    'checkmarkid' => $this->cm->instance,
                    'groupid' => $from->groupid,
                    'groupidswap' => $to->groupid,
                    'objectidswap' => $to->id,
                ],
            ];
            \mod_checkmark\event\group_override_priority_changed::create($eventparams)->trigger();
        }
    }


    /**
     * Calculate the grade corresponding to the users checks
     *
     * @param int $userid the user's ID
     * @return int the user's grade according to his checks
     * @throws dml_exception
     */
    public function calculate_grade($userid) {
        global $USER;
        $grade = 0;

        if (!isset($userid)) {
            $userid = $USER->id;
        }

        $submission = $this->get_submission($userid, false); // Get the submission!

        if ($submission) {
            foreach ($submission->get_examples() as $example) {
                if ($example->is_checked()) { // Is it checked?
                    $grade += $example->get_grade();
                }
            }
        } else {
            $grade = 0;
        }

        return $grade;
    }

    /**
     * grades submissions from this checkmark-instance (either all or those which require grading)
     *
     * @param int $filter (optional) which entrys to filter (self::FILTER_ALL, self::FILTER_REQUIRE_GRADING)
     * @param int[] $selected (optional) selected users, used if filter equals self::FILTER_SELECTED
     * @param bool $countonly (optional) defaults to false, should we only count the submissions or grade them?
     * @return int|array 0 if everything's ok, otherwise error code
     * @throws coding_exception
     * @throws dml_exception
     */
    public function autograde_submissions($filter = self::FILTER_ALL, $selected = [], $countonly = false) {
        global $DB, $USER;

        $result = [];
        $result['status'] = false;
        $result['updated'] = '0';

        $params = ['itemname' => $this->checkmark->name,
                'idnumber' => $this->checkmark->cmidnumber,
            ];

        if ($this->checkmark->grade > 0) {
            $params['gradetype'] = GRADE_TYPE_VALUE;
            $params['grademax'] = $this->checkmark->grade;
            $params['grademin'] = 0;
        } else {
            $result['status'] = GRADE_UPDATE_FAILED;
            if ($countonly) {
                return 0;
            } else {
                return $result;
            }
        }

        // Get all ppl that are allowed to submit checkmarks!
        $context = context_module::instance($this->cm->id);
        // Get groupmode and limit fetched users to current chosen group (or every)!
        if ($groupmode = groups_get_activity_groupmode($this->cm)) {
            $aag = has_capability('moodle/site:accessallgroups', $context);
            if ($groupmode == VISIBLEGROUPS || $aag) {
                // Is there any group in the grouping?
                $allowedgroups = groups_get_all_groups($this->cm->course, 0, $this->cm->groupingid);
            } else {
                // Only assigned groups!
                $allowedgroups = groups_get_all_groups($this->cm->course, $USER->id, $this->cm->groupingid);
            }
            $activegroup = groups_get_activity_group($this->cm, true, $allowedgroups);
        } else {
            $activegroup = 0;
        }

        list($esql, $params) = get_enrolled_sql($context, 'mod/checkmark:submit', $activegroup);
        switch ($filter) {
            case self::FILTER_SELECTED:
                // Prepare list with selected users!
                if (empty($selected)) {
                    $result['status'] = GRADE_UPDATE_FAILED;
                    return $result;
                }
                $usrlst = $selected;

                list($sqluserids, $userparams) = $DB->get_in_or_equal($usrlst, SQL_PARAMS_NAMED, 'user');
                $params = array_merge_recursive($params, $userparams);

                $sql = "SELECT u.id, f.attendance
                          FROM {user} u
                     LEFT JOIN {checkmark_feedbacks} f ON u.id = f.userid AND f.checkmarkid = :checkmarkid
                         WHERE u.deleted = 0 AND u.id " . $sqluserids;
                $params['checkmarkid'] = $this->checkmark->id;
                break;
            case self::FILTER_SELECTED_GRADED:
                // Prepare list with selected users!
                if (empty($selected)) {
                    return 0;
                }
                $usrlst = $selected;

                list($sqluserids, $userparams) = $DB->get_in_or_equal($usrlst, SQL_PARAMS_NAMED, 'user');
                $params = array_merge_recursive($params, $userparams);

                $sql = "SELECT u.id, f.attendance
                          FROM {user} u
                     LEFT JOIN {checkmark_feedbacks} f ON u.id = f.userid AND f.checkmarkid = :checkmarkid
                     LEFT JOIN {checkmark_submissions} s ON (u.id = s.userid) AND s.checkmarkid = f.checkmarkid
                         WHERE u.deleted = 0 AND u.id " . $sqluserids .
                        " AND COALESCE(f.timemodified,0) >= COALESCE(s.timemodified,0) AND f.timemodified IS NOT NULL";
                $params['checkmarkid'] = $this->checkmark->id;
                break;
            case self::FILTER_REQUIRE_GRADING:
                $wherefilter = ' AND (COALESCE(f.timemodified,0) < COALESCE(s.timemodified,0)) ';
                $sql = "  SELECT u.id, f.attendance
                            FROM {user} u
                       LEFT JOIN (" . $esql . ") eu ON eu.id=u.id
                       LEFT JOIN {checkmark_submissions} s ON (u.id = s.userid)
                       LEFT JOIN {checkmark_feedbacks} f ON u.id = f.userid AND s.checkmarkid = f.checkmarkid
                           WHERE u.deleted = 0
                                 AND s.checkmarkid = :checkmarkid" .
                        $wherefilter;
                $params = array_merge_recursive($params, ['checkmarkid' => $this->checkmark->id]);
                break;
            case self::FILTER_ALL:
            default:
                $sql = "  SELECT u.id, f.attendance
                            FROM {user} u
                       LEFT JOIN (" . $esql . ") eu ON eu.id=u.id
                       LEFT JOIN {checkmark_submissions} s ON (u.id = s.userid) AND s.checkmarkid = :checkmarkid
                       LEFT JOIN {checkmark_feedbacks} f ON u.id = f.userid AND f.checkmarkid = :checkmarkid
                           WHERE u.deleted = 0
                                 AND eu.id=u.id";
                $params['checkmarkid'] = $this->checkmark->id;
                break;
        }

        $users = $DB->get_records_sql($sql, $params);

        // If the attendance is linked to the grades!
        $attendancecoupled = $this->checkmark->trackattendance && $this->checkmark->attendancegradelink;
        $attendancegradebook = $this->checkmark->trackattendance && $this->checkmark->attendancegradebook;

        if ($attendancegradebook) {
            $gradinginfo = grade_get_grades($this->course->id, 'mod', 'checkmark', $this->checkmark->id, array_keys($users));
            $attendanceitem = $gradinginfo->items[CHECKMARK_ATTENDANCE_ITEM];
        } else {
            $attendanceitem = false;
        }

        if ($attendancecoupled) {
            // Filter all users with undefined attendance state!
            foreach ($users as $user) {
                if ($attendanceitem && key_exists($user->id, $attendanceitem->grades)
                        && ($attendanceitem->grades[$user->id]->locked || $attendanceitem->grades[$user->id]->overridden)) {
                    if ($attendanceitem->grades[$user->id]->grade === null || (($attendanceitem->grades[$user->id]->grade != 1)
                                    && ($attendanceitem->grades[$user->id]->grade != 0))) {
                        unset($users[$user->id]);
                    }
                } else {
                    if ($user->attendance === null || ($user->attendance != 1 && $user->attendance != 0)) {
                        unset($users[$user->id]);
                    }
                }
            }
        }

        if (empty($users) || count($users) == 0 || $users == null) {
            $result['status'] = GRADE_UPDATE_OK;
            if ($countonly) {
                return 0;
            } else {
                return $result;
            }
        } else {
            if ($countonly) {
                return count($users);
            } else {
                $mailinfo = get_user_preferences('checkmark_mailinfo', 0);
                // Do this for each user enrolled in course!
                if (empty($grades) || !is_array($grades)) {
                    $grades = [];
                }
                $timemarked = time();
                foreach ($users as $currentuser) {
                    $feedback = $this->get_feedback($currentuser->id); // Get feedback!
                    if ($feedback === false) { // Or make a new feedback!
                        $feedback = $this->prepare_new_feedback($currentuser->id);
                        $feedback->timecreated = $timemarked;
                    }

                    $feedback->timemodified = $timemarked;
                    if ($attendanceitem) {
                        $lockedoroverridden = $attendanceitem->grades[$currentuser->id]->locked
                                || $attendanceitem->grades[$currentuser->id]->overridden;
                    } else {
                        $lockedoroverridden = false;
                    }
                    if ($attendancecoupled && ((!$lockedoroverridden && $feedback->attendance == 0)
                                    || ($lockedoroverridden && $attendanceitem->grades[$currentuser->id]->grade == 0))) {
                        // We set grade to 0 if it's coupled with attendance and the user was absent!
                        $calculatedgrade = 0;
                    } else {
                        $calculatedgrade = $this->calculate_grade($currentuser->id);
                    }
                    $feedback->grade = $calculatedgrade;
                    if ($feedback->feedback == null) {
                        $feedback->feedback = get_string('strautograded', 'checkmark');
                    } else if (!strstr($feedback->feedback, get_string('strautograded', 'checkmark'))) {
                        $feedback->feedback .= get_string('strautograded', 'checkmark');
                    }
                    $feedback->graderid = $USER->id;
                    $feedback->timemodified = $timemarked;
                    if (!isset($grades[$currentuser->id])) { // Prevent strict standard warning!
                        $grades[$currentuser->id] = new stdClass();
                    }
                    $grades[$currentuser->id]->userid = $currentuser->id;
                    $grades[$currentuser->id]->rawgrade = $calculatedgrade;
                    $grades[$currentuser->id]->dategraded = $timemarked;
                    $grades[$currentuser->id]->feedback = $feedback->feedback;
                    $grades[$currentuser->id]->feedbackformat = $feedback->format;
                    if (!$mailinfo) {
                        $feedback->mailed = 1;       // Treat as already mailed!
                    } else {
                        $feedback->mailed = 0;       // Make sure mail goes out (again, even)!
                    }

                    $DB->update_record('checkmark_feedbacks', $feedback);
                    $result['updated']++;

                    // Trigger the event!
                    \mod_checkmark\event\grade_updated::automatic($this->cm, ['userid' => $currentuser->id,
                            'feedbackid' => $feedback->id, ])->trigger();
                }
            }

            if (!empty($grades)) {
                $result['status'] = grade_update('mod/checkmark', $this->checkmark->course, 'mod',
                        'checkmark', $this->checkmark->id, 0, $grades,
                        $params);
                return $result;
            } else {
                $result['status'] = GRADE_UPDATE_OK;
                return $result;
            }
        }
    }

    /**
     * Return if flexiblenaming is used/can be used with this examples
     *
     * @return bool flexible naming is used or not
     * @throws dml_exception
     */
    public function is_using_flexiblenaming() {
        // We try to cache the value while we're in object context...
        if (isset($this->checkmark->flexiblenaming)) {
            return $this->checkmark->flexiblenaming;
        }

        // Cache for later!
        $this->checkmark->flexiblenaming = self::is_using_flexiblenaming_static($this->checkmark->id);

        return $this->checkmark->flexiblenaming;
    }

    /**
     * Return if flexiblenaming is used/can be used with this examples
     *
     * @param int $instanceid ID of the current instance
     * @return bool flexible naming is used or not
     * @throws dml_exception
     */
    public static function is_using_flexiblenaming_static($instanceid) {
        global $DB;
        if ($instanceid == 0) {
            return false;
        }
        $instance = $DB->get_record('checkmark', ['id' => $instanceid]);
        if (!empty($instance->flexiblenaming)) {
            if ($instance->flexiblenaming == 1) {
                return true;
            } else if ($instance->flexiblenaming == -1) {
                return false;
            }
        }
        $examples = $DB->get_records('checkmark_examples', ['checkmarkid' => $instanceid], 'id ASC');

        $oldname = null;
        $oldgrade = null;
        foreach ($examples as $example) {
            if (($oldname != null) && ($oldgrade != null)) {
                if ((ord($oldname) + 1 != ord($example->name))
                        || ((int) $oldgrade != (int) $example->grade)) {
                    return true;
                }
            }
            $oldname = $example->name;
            $oldgrade = $example->grade;
        }
        return false;
    }

    /**
     * Any extra validation checks needed for the settings
     *
     * See lib/formslib.php, 'validation' function for details
     *
     * @param array $data Form data as submitted
     * @return array Array of strings with error messages
     * @throws coding_exception
     * @throws dml_exception
     */
    public function form_validation($data) {
        global $DB;
        $errors = [];

        if (!isset($data['flexiblenaming'])) {
            $data['flexiblenaming'] = 0;
        }

        $checkspresent = false;
        if (!empty($data['instance'])) {
            $checks = $DB->get_records_sql("
                            SELECT c.*
                              FROM {checkmark_checks} c
                              JOIN {checkmark_submissions} s ON c.submissionid = s.id
                              WHERE s.checkmarkid = :checkmarkid", ['checkmarkid' => $data['instance']]);
            if (is_array($checks) && count($checks) > 0) {
                $checkspresent = true;
            }
        }

        if ($data['flexiblenaming'] == 1 && (intval($data['grade']) > 0)) {
            // Check if amount of examplenames equals amount of examplegrades?
            $grades = explode(self::DELIMITER, $data['examplegrades']);
            $names = explode(self::DELIMITER, $data['examplenames']);
            if (count($grades) != count($names)) {
                $a = new stdClass();
                $a->gradecount = count($grades);
                $a->namecount = count($names);
                $errors['examplegrades'] = get_string('count_individuals_mismatch', 'checkmark', $a);
                $errors['examplenames'] = get_string('count_individuals_mismatch', 'checkmark', $a);
            }
            /*
             * If we use individual grades/names we also have to check
             * if the gradesum matches the sum of individual grades!
             */
            $gradesum = 0;
            for ($i = 0; $i < count($grades); $i++) {
                $gradesum += intval($grades[$i]);
            }
            if ($gradesum != intval($data['grade'])) {
                if (!isset($errors['examplegrades'])) {
                    $errors['examplegrades'] = '';
                } else {
                    $errors['examplegrades'] .= '<br />';
                }
                $a = new stdClass();
                $a->gradesum = $gradesum;
                $a->maxgrade = $data['grade'];
                $errors['grade'] = get_string('gradesum_mismatch', 'checkmark', $a);
                $errors['examplegrades'] .= get_string('gradesum_mismatch', 'checkmark', $a);
            }

            if ($checkspresent) {
                // Now we check if the examplecount has been changed!
                $oldexamples = $this->get_examples();
                $oldnames = [];
                foreach ($oldexamples as $cur) {
                    $oldnames[] = $cur->name;
                }
                if (count($oldexamples) !== count($names)) {
                    $errors['examplenames'] = get_string('examplecount_changed_after_submission', 'checkmark', (object) [
                            'old' => implode(', ', $oldnames),
                    ]);
                }
            }
        } else if (intval($data['grade']) > 0) {
            if (($data['examplecount'] <= 0) || !is_numeric($data['examplecount'])) {
                $errors['examplecount'] = get_string('posintrequired', 'checkmark');
            } else {
                if ($data['examplecount'] > 100) {
                    $errors['examplecount'] = get_string('posintst100required', 'checkmark');
                }
            }
            if ($data['examplestart'] < 0 || !is_numeric($data['examplestart'])) {
                $errors['examplestart'] = get_string('nonnegativeintrequired', 'checkmark');
            }
            // Grade has to be examplecount multiplied with an int!
            if (($data['examplecount'] != 0) && ($data['grade'] % $data['examplecount'])) {
                if (!isset($errors['examplecount'])) {
                    $errors['examplecount'] = get_string('grade_mismatch', 'checkmark');
                } else {
                    $errors['examplecount'] .= '<br />' . get_string('grade_mismatch', 'checkmark');
                }
                $errors['grade'] = get_string('grade_mismatch', 'checkmark');
            }

            if ($checkspresent) {
                // Now we check if the examplecount has been changed!
                $oldexamples = $this->get_examples();
                $oldnames = [];
                foreach ($oldexamples as $cur) {
                    $oldnames[] = $cur->name;
                }
                if (count($oldexamples) !== $data['examplecount']) {
                    $errors['examplecount'] = get_string('examplecount_changed_after_submission', 'checkmark', (object) [
                            'old' => implode(', ', $oldnames),
                    ]);
                }
            }
        }

        return $errors;
    }

    /**
     * Update grade item and attendance item for this feedback.
     *
     * @param object $feedback Feedback object
     */
    public function update_grade($feedback) {
        checkmark_update_grades($this->checkmark, $feedback->userid);
        if ($this->checkmark->trackattendance) {
            checkmark_update_attendances($this->checkmark, $feedback->userid);
        }
        if ($this->checkmark->presentationgrading && $this->checkmark->presentationgradebook) {
            checkmark_update_presentation_grades($this->checkmark, $feedback->userid);
        }
    }

    /**
     * Check for all autograded feedbacks and remind teacher to regrade them.
     *
     * @param string $id checkmark-id
     */
    public static function get_autograded_feedbacks($id) {
        global $DB;
        /*
         * Check for all autograded feedbacks and remind teacher to regrade them!
         * TODO: currently we don't mark autograded feedbacks as such with a flag in the DB, do it in the future,
         * so we don't have to rely on a certain string!
         */
        $strmanager = get_string_manager();
        $translations = $strmanager->get_list_of_translations(true);
        foreach (array_keys($translations) as $lang) {
            $select[] = $DB->sql_like('feedback', ':' . $lang);
            $params[$lang] = $strmanager->get_string('strautograded', 'checkmark', null, $lang);
        }

        return $DB->get_records_select('checkmark_feedbacks', "checkmarkid = :id AND (" . implode(" OR ", $select) . ")",
                ['id' => $id] + $params);
    }

    /**
     * Top-level function for handling of submissions called by submissions.php
     *
     * This is for handling the teacher interaction with the grading interface
     *
     * @param string $mode Specifies the kind of teacher interaction taking place
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function submissions($mode) {
        global $SESSION, $USER, $OUTPUT, $DB, $PAGE;
        /*
         * The main switch is changed to facilitate
         *     1) Batch fast grading
         *     2) Skip to the next one on the popup
         *     3) Save and Skip to the next one on the popup
         */

        $mailinfo = optional_param('mailinfo', null, PARAM_BOOL);

        if (optional_param('next', null, PARAM_BOOL)) {
            $mode = 'next';
        } else if (optional_param('previous', null, PARAM_BOOL)) {
            $mode = 'previous';
        } else if (optional_param('saveandnext', null, PARAM_BOOL)) {
            $mode = 'saveandnext';
        } else if (optional_param('saveandprevious', null, PARAM_BOOL)) {
            $mode = 'saveandprevious';
        } else if (optional_param('bulk', null, PARAM_BOOL)) {
            $mode = 'bulk';
        } else if (optional_param('overwritechecks', null, PARAM_BOOL)) {
            $mode = 'overwritechecks';
        }

        // This is no security check, this just tells us if there is posted data!
        if (data_submitted() && confirm_sesskey() && $mailinfo !== null) {
            set_user_preference('checkmark_mailinfo', $mailinfo);
        }

        switch ($mode) {
            case 'grade':                       // We are in a main window grading!
                $userid = required_param('userid', PARAM_INT);
                if ($this->process_feedback()) {
                    $this->display_submissions(get_string('changessaved'));
                } else {
                    $this->display_submissions();
                }
                break;

            case 'single':                      // We are in a main window displaying one submission!
                if ($this->process_feedback()) {
                    $this->display_submissions(get_string('changessaved'));
                } else {
                    $userid = required_param('userid', PARAM_INT);
                    $this->display_submission($userid);
                }
                break;

            case 'bulk':
                $message = '';
                $bulkaction = optional_param('bulkaction', null, PARAM_ALPHA);
                $selected = optional_param_array('selected', [], PARAM_INT);
                $confirm = optional_param('confirm', 0, PARAM_BOOL);
                $result = $this->autograde_submissions(self::FILTER_SELECTED, $selected, true);
                $resultgraded = $this->autograde_submissions(self::FILTER_SELECTED_GRADED,
                        $selected, true);
                if ($selected == [] && $confirm) {
                    $selected = $SESSION->checkmark->autograde->selected;
                }
                if (isset($selected) && (count($selected) == 0)) {
                    $message .= $OUTPUT->notification(get_string('bulk_no_users_selected', 'checkmark'), 'error');
                }
                if ($bulkaction &&
                    ($selected || (($confirm || $resultgraded == 0) && !empty($SESSION->checkmark->autograde->selected)))) {
                    // Process bulk action!
                    $confirmedaction = in_array($bulkaction, ['setattendantandgrade', 'setabsentandgrade'])
                            && (optional_param('confirm', 0, PARAM_BOOL) || $resultgraded == 0);
                    if ($confirmedaction || in_array($bulkaction, ['setattendant', 'setabsent', 'setunknown'])) {
                        // Check if some of the selected users don't have a feedback entry and create on if so!
                        foreach ($selected as $sel) {
                            $this->prepare_new_feedback($sel); // Make one if missing!
                        }

                        // First only the attendance changes!
                        switch ($bulkaction) {
                            case 'setattendantandgrade':
                            case 'setattendant':
                                $this->set_attendance($selected, 1, optional_param('mailinfo', 0, PARAM_BOOL));
                                break;
                            case 'setabsentandgrade':
                            case 'setabsent':
                                $this->set_attendance($selected, 0, optional_param('mailinfo', 0, PARAM_BOOL));
                                break;
                            case 'setunknown':
                                $this->set_attendance($selected, null, optional_param('mailinfo', 0, PARAM_BOOL));
                                break;
                        }
                    }

                    // Now all the grading stuff!
                    if (in_array($bulkaction, ['setattendantandgrade', 'setabsentandgrade', 'grade'])) {
                        if (!optional_param('confirm', 0, PARAM_BOOL) && $resultgraded != 0) {
                            $PAGE->set_title(format_string($this->checkmark->name, true));
                            $PAGE->set_heading($this->course->fullname);
                            if (!isset($SESSION->checkmark)) {
                                $SESSION->checkmark = new stdClass();
                            }
                            if (!isset($SESSION->checkmark->autograde)) {
                                $SESSION->checkmark->autograde = new stdClass();
                            }
                            $SESSION->checkmark->autograde->selected = $selected;
                            if ($result == 1) {
                                $amount = get_string('autograde_stronesubmission', 'checkmark');
                            } else {
                                $amount = get_string('autograde_strmultiplesubmissions', 'checkmark', $result);
                            }
                            $amountinfo = '';
                            if (($this->checkmark->grade <= 0)) {
                                // No autograde possible if no numeric grades are selected!
                                $message .= $OUTPUT->notification(get_string('autograde_non_numeric_grades', 'checkmark'), 'error');
                            } else {
                                if ($this->checkmark->trackattendance && $this->checkmark->attendancegradelink
                                        && (count($selected) != $result)
                                        && !in_array($bulkaction, ['setattendantandgrade', 'setabsentandgrade'])) {
                                    $amountinfo = get_string('autograde_users_with_unknown_attendance', 'checkmark',
                                            (count($selected) - $result));
                                } else if (in_array($bulkaction, ['setattendantandgrade', 'setabsentandgrade'])) {
                                    $amount = count($selected);
                                }
                                    echo $OUTPUT->header();
                                    $confirmboxcontent =
                                            $OUTPUT->notification(get_string('autograde_confirm', 'checkmark',
                                                    ['total' => $amount, 'graded' => $resultgraded]) .
                                                    html_writer::empty_tag('br') . $amountinfo, 'info') .
                                            $OUTPUT->confirm(get_string('autograde_confirm_continue', 'checkmark'),
                                                    'submissions.php?id=' . $this->cm->id . '&bulk=1&bulkaction=' .
                                                    $bulkaction . '&confirm=1',
                                                    'submissions.php?id=' . $this->cm->id);
                                    echo $OUTPUT->box($confirmboxcontent, 'generalbox');
                                    echo $OUTPUT->footer();
                                    die();
                            }
                        } else {
                            if (($this->checkmark->grade <= 0)) {
                                // No autograde possible if no numeric grades are selected!
                                $message .= $OUTPUT->notification(get_string('autograde_non_numeric_grades',
                                        'checkmark'),
                                        'notifyproblem');
                            } else if (has_capability('mod/checkmark:grade', context_module::instance($this->cm->id))) {
                                $result = $this->autograde_submissions(self::FILTER_SELECTED, $selected);
                                if (!isset($message)) {
                                    $message = '';
                                } else {
                                    $message .= html_writer::empty_tag('br');
                                }
                                if ($result['status'] == GRADE_UPDATE_OK) {
                                    if ($result['updated'] == 1) {
                                        $string = 'autograde_one_success';
                                    } else {
                                        $string = 'autograde_success';
                                    }
                                    $message .= $OUTPUT->notification(get_string($string, 'checkmark', $result['updated']),
                                            'notifysuccess');
                                } else {
                                    $message .= $OUTPUT->notification(get_string('autograde_failed', 'checkmark'),
                                            'notifyproblem');
                                }
                            } else {
                                throw new moodle_exception('autograde_error', 'checkmark');
                            }
                        }
                    }

                    // Bulk remove grades.
                    if (in_array($bulkaction, ['removegrade'])) {
                        if (has_capability('mod/checkmark:grade', context_module::instance($this->cm->id))) {
                            $result = $this->remove_grades(self::FILTER_SELECTED, $selected);
                            if (!isset($message)) {
                                $message = '';
                            } else {
                                $message .= html_writer::empty_tag('br');
                            }
                            if ($result['status'] == GRADE_UPDATE_OK) {
                                if ($result['updated'] == 1) {
                                    $string = 'remove_grade_one_success';
                                } else {
                                    $string = 'remove_grade_success';
                                }
                                $message .= $OUTPUT->notification(get_string($string, 'checkmark', $result['updated']),
                                        'notifysuccess');
                            } else {
                                $message .= $OUTPUT->notification(get_string('remove_grade_failed', 'checkmark'),
                                        'notifyproblem');
                            }
                        } else {
                            throw new moodle_exception('remove_grade_error', 'checkmark');
                        }
                    }

                    // Bulk remove grades.
                    if (in_array($bulkaction, ['removepresentationgrade'])) {
                        if (has_capability('mod/checkmark:grade', context_module::instance($this->cm->id))) {
                            $result = $this->remove_presentation_grades(self::FILTER_SELECTED, $selected);
                            if (!isset($message)) {
                                $message = '';
                            } else {
                                $message .= html_writer::empty_tag('br');
                            }
                            if ($result['status'] == GRADE_UPDATE_OK) {
                                if ($result['updated'] == 1) {
                                    $string = 'remove_presentation_grade_one_success';
                                } else {
                                    $string = 'remove_presentation_grade_success';
                                }
                                $message .= $OUTPUT->notification(get_string($string, 'checkmark', $result['updated']),
                                        'notifysuccess');
                            } else {
                                $message .= $OUTPUT->notification(get_string('remove_presentation_grade_failed', 'checkmark'),
                                        'notifyproblem');
                            }
                        } else {
                            throw new moodle_exception('remove_presentation_grade_error', 'checkmark');
                        }
                    }
                }

                $this->display_submissions($message);
                break;
            case 'all':                          // Main window, display everything!
                $this->display_submissions();
                break;

            case 'fastgrade':
                // Do the fast grading stuff  - this process should work for all 3 subclasses!
                $attendance = false;
                $presgrading = false;
                $prescommenting = false;
                $grading = false;
                $commenting = false;
                $col = false;

                $grades = optional_param_array('menu', [], PARAM_INT);
                $feedbacks = optional_param_array('feedback', [], PARAM_TEXT);
                $attendances = optional_param_array('attendance', [], PARAM_INT);
                $checks = optional_param_array('ex', [], PARAM_BOOL);
                $presgrades = optional_param_array('presentationgrade', [], PARAM_INT);
                $presfeedbacks = optional_param_array('presentationfeedback', [], PARAM_TEXT);
                $oldgrades = optional_param_array('oldgrade', [], PARAM_INT);
                $oldfeedbacks = optional_param_array('oldfeedback', [], PARAM_TEXT);
                $oldattendances = optional_param_array('oldattendance', [], PARAM_INT);
                $oldpresgrades = optional_param_array('oldpresentationgrade', [], PARAM_INT);
                $oldpresfeedbacks = optional_param_array('oldpresentationfeedback', [], PARAM_TEXT);
                $oldchecks = optional_param_array('oldex', [], PARAM_BOOL);

                $cantrackattendances = has_capability('mod/checkmark:trackattendance', $this->context);
                $cangradepres = has_capability('mod/checkmark:gradepresentation', $this->context);

                $ids = [];

                if (!empty($attendances) && $cantrackattendances) {
                    $col = 'attendance';
                    $attendance = true;
                    $ids = array_unique(array_merge($ids, array_keys($attendances)));
                }

                if (!empty($presgrades) && $this->checkmark->presentationgrading && !empty($this->checkmark->presentationgrade)
                        && $cangradepres) {
                    $col = 'presentationgrade';
                    $presgrading = true;
                    $ids = array_unique(array_merge($ids, array_keys($presgrades)));
                }

                if (!empty($presfeedbacks) && $this->checkmark->presentationgrading && $cangradepres) {
                    $col = 'presentationfeedback';
                    $prescommenting = true;
                    $ids = array_unique(array_merge($ids, array_keys($presfeedbacks)));
                }

                if (!empty($feedbacks)) {
                    $col = 'feedback';
                    $commenting = true;
                    $ids = array_unique(array_merge($ids, array_keys($feedbacks)));
                }
                if (!empty($grades)) {
                    $col = 'menu';
                    $grading = true;
                    $ids = array_unique(array_merge($ids, array_keys($grades)));
                }

                if (!(data_submitted() && confirm_sesskey())) {
                    $col = false;
                }
                if ($formdata = data_submitted() && confirm_sesskey() && $checks !== $oldchecks) {

                    $checksperuser = $this->split_by_user($checks);
                    $oldchecksperuser = $this->split_by_user($oldchecks);

                    foreach ($checksperuser as $userid => $userchecks) {
                        if ($userchecks !== $oldchecksperuser[$userid]) {
                            $submission = $this->get_submission($userid, true);
                            if ($submission) {
                                foreach ($submission->get_examples() as $key => $example) {
                                    $name = $key;
                                    if (isset($userchecks[$name]) && ($userchecks[$name] != 0)) {
                                        $submission->get_example($key)->overwrite_example(\mod_checkmark\example::CHECKED);
                                    } else {
                                        $submission->get_example($key)->overwrite_example(\mod_checkmark\example::UNCHECKED);
                                    }

                                }
                                $this->update_submission($submission, true);
                            }
                        }
                    }
                }
                // Create the submission if needed & return its id!

                if (!$col) {
                    // All columns (submissioncomment, grade & attendance) were collapsed!
                    $this->display_submissions();
                    break;
                }

                foreach ($ids as $id) {

                    $this->process_outcomes($id);

                    $feedback = $this->get_feedback($id); // Don't write a feedback in the DB right now!

                    // For fast grade, we need to check if any changes take place!
                    $updatedb = false;

                    if (!isset($oldgrades[$id])) {
                        $oldgrades[$id] = -1;
                    }
                    if (!isset($oldfeedbacks[$id])) {
                        $oldfeedbacks[$id] = null;
                    }
                    if (!isset($oldattendances[$id])) {
                        $oldattendances[$id] = null;
                    }
                    if (!isset($oldpresgrades[$id])) {
                        $oldpresgrades[$id] = -1;
                    }
                    if (!isset($oldpresfeedbacks[$id])) {
                        $oldpresfeedbacks[$id] = null;
                    }

                    // So we have unknown attendance stati included!
                    if ($attendance && $cantrackattendances && (!key_exists($id, $attendances) || $attendances[$id] == -1)) {
                        $attendances[$id] = null;
                    }
                    if ($attendance && $cantrackattendances && (!key_exists($id, $oldattendances) || $oldattendances[$id] == -1)) {
                        $oldattendances[$id] = null;
                    }

                    if ($attendance && $cantrackattendances && ($oldattendances[$id] !== $attendances[$id])) {
                        $updatedb = $updatedb || ($oldattendances[$id] !== $attendances[$id]);

                        if ($feedback === false) {
                            $feedback = $this->prepare_new_feedback($id);
                        }
                        $feedback->attendance = $attendances[$id];
                    } else {
                        unset($feedback->attendance); // Don't need to update this.
                    }

                    if ($presgrading && key_exists($id, $presgrades) && (($oldpresgrades[$id] != $presgrades[$id])
                                    && !($oldpresgrades[$id] === null && $presgrades[$id] == -1))) {
                        $presgrade = $presgrades[$id];
                        if ($presgrade == -1) {
                            $presgrade = null;
                        }
                        $updatedb = $updatedb || ($oldpresgrades[$id] != $presgrade);
                        if ($feedback === false) {
                            $feedback = $this->prepare_new_feedback($id);
                        }
                        $feedback->presentationgrade = $presgrade;
                    } else {
                        unset($feedback->presentationgrade);
                    }

                    if ($prescommenting && key_exists($id, $presfeedbacks)
                        && (trim($oldpresfeedbacks[$id] ?? '') != trim($presfeedbacks[$id] ?? ''))) {
                            $presfeedbackvalue = trim($presfeedbacks[$id] ?? '');
                            $updatedb = $updatedb || (trim($oldpresfeedbacks[$id] ?? '') != $presfeedbackvalue);
                        if ($feedback === false) {
                            $feedback = $this->prepare_new_feedback($id);
                        }
                        $feedback->presentationfeedback = submissionstable::convert_text_to_html($presfeedbackvalue);
                    } else {
                        unset($feedback->presentationfeedback);  // Don't need to update this.
                    }

                    if ($grading && key_exists($id, $grades) && (($oldgrades[$id] != $grades[$id])
                                    && !($oldgrades[$id] === null && $grades[$id] == -1))) {
                        $grade = $grades[$id];
                        $updatedb = $updatedb || ($oldgrades[$id] != $grade);
                        if ($feedback === false) {
                            $feedback = $this->prepare_new_feedback($id);
                        }
                        $feedback->grade = $grade;
                    } else {
                        unset($feedback->grade);  // Don't need to update this.
                    }

                    if ($commenting && key_exists($id, $feedbacks) &&
                        (trim($oldfeedbacks[$id] ?? '') != trim($feedbacks[$id] ?? ''))) {
                        $feedbackvalue = trim($feedbacks[$id] ?? '');
                        $updatedb = $updatedb || (trim($oldfeedbacks[$id] ?? '') != $feedbackvalue);
                        if ($feedback === false) {
                            $feedback = $this->prepare_new_feedback($id);
                        }
                        $feedback->feedback = submissionstable::convert_text_to_html($feedbackvalue);
                    } else {
                        unset($feedback->feedback);  // Don't need to update this.
                    }

                    if ($updatedb) {
                        $feedback->graderid = $USER->id;
                        $feedback->mailed = (int) (!$mailinfo);
                    }

                    /*
                     * If it is not an update, we don't change the last modified time etc.
                     * this will also not write into database if no submissioncomment and grade
                     * is entered.
                     */

                    if ($updatedb) {
                        $feedback->timemodified = time();

                        $DB->update_record('checkmark_feedbacks', $feedback);

                        // Trigger grade event!
                        $this->update_grade($feedback);

                        // Trigger the event!
                        \mod_checkmark\event\grade_updated::manual($this->cm, ['userid' => $feedback->userid,
                                'feedbackid' => $feedback->id, ])->trigger();
                    }
                }

                $message = $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');

                $this->display_submissions($message);
                break;

            case 'saveandnext':
                /*
                 * We are in pop up. save the current one and go to the next one.
                 * first we save the current changes!
                 */
                $this->process_feedback();
                // Now we continue straight to with the next one!
            case 'next':
                /*
                 * We are currently in pop up, but we want to skip to next one without saving.
                 * This turns out to be similar to a single case!
                 * The URL used is for the next submission.
                 */
                $nextid = required_param('nextid', PARAM_INT);
                $filter = optional_param('filter', self::FILTER_ALL, PARAM_INT);
                $id = required_param('id', PARAM_INT);
                redirect('submissions.php?id=' . $id . '&userid=' . $nextid . '&filter=' . $filter . '&mode=single');
                break;

            case 'saveandprevious':
                /*
                 * We are in pop up. save the current one and go to the next one.
                 * first we save the current changes!
                 */
                $this->process_feedback();
                // Now we continue straight to with the next one!
            case 'previous':
                /*
                 * We are currently in pop up, but we want to skip to next one without saving.
                 * This turns out to be similar to a single case!
                 * The URL used is for the next submission.
                 */
                $previousid = required_param('previousid', PARAM_INT);
                $filter = optional_param('filter', self::FILTER_ALL, PARAM_INT);
                $id = required_param('id', PARAM_INT);
                redirect('submissions.php?id=' . $id . '&userid=' . $previousid . '&filter=' . $filter . '&mode=single');
                break;
            case 'overwritechecks':
                $userid = required_param('userid', PARAM_INT);
                if ($formdata = data_submitted() && confirm_sesskey()) {

                    // Create the submission if needed & return its id!
                    $submission = $this->get_submission($userid, false);

                    foreach ($submission->get_examples() as $key => $example) {
                        if (isset($formdata->{$key}) && ($formdata->{$key} != 0)) {
                            $submission->get_example($key)->overwrite_example(\mod_checkmark\example::CHECKED);
                        } else {
                            $submission->get_example($key)->overwrite_example(\mod_checkmark\example::UNCHECKED);
                        }
                    }
                }
                $this->update_submission($submission, true);
                $this->display_submission($userid);
                break;

            case 'singlenosave':

            case 'print':
                $userid = required_param('userid', PARAM_INT);
                $this->display_submission($userid);
                break;

            default:
                echo 'something seriously is wrong!!';
                break;
        }
    }
    /**
     * Sets the attendance for a bunch of users
     *
     * @param int[] $selected Array of userids
     * @param mixed $state 1, 0 or null
     * @param bool $mailinfo whether or not the users should be notified
     * @throws coding_exception
     * @throws dml_exception
     */
    protected function set_attendance($selected, $state, $mailinfo) {
        global $DB, $USER, $OUTPUT;

        // Normalize state!
        if ($state === null) {
            $state = null;
        } else if (empty($state)) {
            $state = 0;
        } else {
            $state = 1;
        }

        // Check if any grade is locked or overridden and output some messages concerning these entries!
        if ($this->checkmark->trackattendance
                && $this->checkmark->attendancegradebook) {
            $gradinginfo = grade_get_grades($this->course->id, 'mod', 'checkmark', $this->checkmark->id, $selected);
            $item = $gradinginfo->items[CHECKMARK_ATTENDANCE_ITEM];
            foreach ($selected as $key => $cur) {
                if (key_exists($cur, $item->grades) && ($item->grades[$cur]->locked || $item->grades[$cur]->overridden)) {
                    $user = $DB->get_record('user', ['id' => $cur]);
                    echo $OUTPUT->notification(get_string('attendance_not_set_grade_locked', 'checkmark', fullname($user)), 'info');
                    unset($selected[$key]);
                }
            }
        }

        $select = 'checkmarkid = :checkmarkid';
        $params = ['checkmarkid' => $this->checkmark->id];

        if (!empty($selected) && is_array($selected)) {
            list($selsql, $selparams) = $DB->get_in_or_equal($selected, SQL_PARAMS_NAMED);
            $selsql = ' AND userid ' . $selsql;
        } else {
            $selsql = ' AND userid = -1';
            $selparams = [];
        }

        $select .= $selsql;
        $params = array_merge($params, $selparams);

        $DB->set_field_select('checkmark_feedbacks', 'graderid', $USER->id, $select, $params);
        $DB->set_field_select('checkmark_feedbacks', 'timemodified', time(), $select, $params);
        $DB->set_field_select('checkmark_feedbacks', 'attendance', $state, $select, $params);
        if ($mailinfo) {
            $DB->set_field_select('checkmark_feedbacks', 'mailed', 0, $select, $params);
        }
        if ($this->checkmark->trackattendance && $this->checkmark->attendancegradebook) {
            $grades = [];
            foreach ($selected as $sel) {
                $grades[$sel] = new stdClass();
                $grades[$sel]->userid = $sel;
                $grades[$sel]->rawgrade = $state;
            }
            checkmark_attendance_item_update($this->checkmark, $grades);
        }
    }

    /**
     *  Return a grade in user-friendly form, whether it's a scale or not
     *
     * @param mixed $grade
     * @param int $gradeitem Which gradeitem to use (CHECKMARK_GRADE_ITEM, CHECKMARK_PRESENTATION_ITEM)
     * @return string User-friendly representation of grade
     * @throws dml_exception
     */
    public function display_grade($grade, $gradeitem = CHECKMARK_GRADE_ITEM) {
        global $DB;

        // Cache scales for each checkmark - they might have different scales!
        static $scalegrades = [];
        static $presentationscales = [];

        switch ($gradeitem) {
            case CHECKMARK_PRESENTATION_ITEM:
                $maxgrade = (int) $this->checkmark->presentationgrade;
                break;
            default:
            case CHECKMARK_GRADE_ITEM:
                $maxgrade = (int) $this->checkmark->grade;
                break;
        }

        if ($maxgrade > 0) {    // Normal number?
            if (($grade == -1) || ($grade === null)) {
                return '-';
            } else {
                return round($grade, 2) . ' / ' . $maxgrade;
            }
        } else {                                // Scale?
            switch ($gradeitem) {
                case CHECKMARK_PRESENTATION_ITEM:
                    $scaletouse = -$this->checkmark->presentationgrade;
                    $scalecache = 'presentationscales';
                    break;
                default:
                case CHECKMARK_GRADE_ITEM:
                    $scaletouse = -$this->checkmark->grade;
                    $scalecache = 'scalegrades';
                    break;
            }
            if (empty(${$scalecache}[$this->checkmark->id])) {
                if ($scale = $DB->get_record('scale', ['id' => $scaletouse])) {
                    ${$scalecache}[$this->checkmark->id] = make_menu_from_list($scale->scale);
                } else {
                    return '-';
                }
            }
            if (isset(${$scalecache}[$this->checkmark->id][(int) $grade])) {
                return ${$scalecache}[$this->checkmark->id][(int) $grade];
            }
            return '-';
        }
    }

    /**
     *  Display a single submission, ready for grading on a popup window
     *
     * This default method prints the grader info and feedback box at the top and
     * the student info and submission at the bottom.
     * This method also fetches the necessary data in order to be able to
     * provide a 'Next submission' button.
     * to process submissions before they are graded
     * This method gets its arguments from the page parameters userid and offset
     * TODO look through this method - this method's buggy, due to submission-table saving it's settings elsewhere now!
     *
     * @param int $userid
     * @return stdClass|true
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function display_submission($userid) {
        global $CFG, $DB, $PAGE, $OUTPUT, $SESSION;

        $filter = get_user_preferences('checkmark_filter', self::FILTER_ALL);
        $table = submissionstable::create_submissions_table($this->cm->id, $filter);

        if (!$user = $DB->get_record('user', ['id' => $userid])) {
            throw new moodle_exception('nousers');
        }

        if (!$submission = $this->get_submission($user->id)) {
            $submission = \mod_checkmark\submission::get_mock_submission($this->checkmark->id, $userid);
        }

        $feedback = $this->get_feedback($user->id);

        $gradinginfo = grade_get_grades($this->course->id, 'mod', 'checkmark',
                $this->checkmark->id, [$user->id]);
        $gradingdisabled = $gradinginfo->items[CHECKMARK_GRADE_ITEM]->grades[$userid]->locked
                || $gradinginfo->items[CHECKMARK_GRADE_ITEM]->grades[$userid]->overridden;
        if ($this->checkmark->trackattendance && $this->checkmark->attendancegradebook) {
            $attendancedisabled = $gradinginfo->items[CHECKMARK_ATTENDANCE_ITEM]->grades[$userid]->locked
                    || $gradinginfo->items[CHECKMARK_ATTENDANCE_ITEM]->grades[$userid]->overridden;
        } else {
            $attendancedisabled = false;
        }
        if ($this->checkmark->presentationgradebook) {
            $presgradedisabled = $gradinginfo->items[CHECKMARK_PRESENTATION_ITEM]->grades[$userid]->locked
                    || $gradinginfo->items[CHECKMARK_PRESENTATION_ITEM]->grades[$userid]->overridden;
        } else {
            $presgradedisabled = false;
        }

        // Construct SQL, using current offset to find the data of the next student!
        $context = context_module::instance($this->cm->id);

        $previousid = 0;
        $nextid = 0;

        if (false !== ($triple = $table->get_triple($userid))) {
            list($previousid, $userid, $nextid) = $triple;
        }

        if (($feedback !== false) && isset($feedback->graderid) && $feedback->graderid) {
            $grader = $DB->get_record('user', ['id' => $feedback->graderid]);
        } else {
            global $USER;
            $grader = $USER;
        }

        $mformdata = new stdClass();
        $mformdata->context = $this->context;
        $mformdata->course = $this->course->id;
        $mformdata->grader = $grader;
        $mformdata->checkmark = $this->checkmark;
        $mformdata->submission = $submission;
        $mformdata->feedbackobj = $feedback;
        $mformdata->feedback = ($feedback !== false) ? $feedback->feedback : '';
        $mformdata->feedbackformat = ($feedback !== false) ? $feedback->format : 0;
        if ($this->checkmark->trackattendance) {
            $mformdata->attendance = ($feedback !== false) ? $feedback->attendance : -1;
            $mformdata->trackattendance = 1;
            $mformdata->attendancegradebook = $this->checkmark->attendancegradebook;
        } else {
            $mformdata->trackattendance = 0;
        }
        if ($this->checkmark->presentationgrading) {
            $mformdata->presentationgrading = true;
            $mformdata->instance_presentationgrade = $this->checkmark->presentationgrade;
            $mformdata->presentationgradebook = $this->checkmark->presentationgradebook;
            $mformdata->presentationgrade = ($feedback !== false) ? $feedback->presentationgrade : -1;
            $mformdata->presentationfeedback = ($feedback !== false) ? $feedback->presentationfeedback : '';
            $mformdata->presentationformat = ($feedback !== false) ? $feedback->presentationformat : FORMAT_HTML;
            $mformdata->presgradedisabled = $presgradedisabled;
            if ($mformdata->presgradedisabled) {
                // Overwrite with gradebook value!
                $mformdata->presentationgrade = $gradinginfo->items[CHECKMARK_PRESENTATION_ITEM]->grades[$userid]->str_long_grade;
                $mformdata->presentationfeedback = $gradinginfo->items[CHECKMARK_PRESENTATION_ITEM]->grades[$userid]->feedback;
            }
        }
        if ($submission) {
            $mformdata->lateness = $this->display_lateness($submission->get_timemodified(), $user->id);
        }

        $mformdata->user = $user;
        $mformdata->userid = $userid;
        $mformdata->cm = $this->cm;
        $mformdata->grading_info = $gradinginfo;
        $mformdata->enableoutcomes = $CFG->enableoutcomes;
        $mformdata->grade = $this->checkmark->grade;
        $mformdata->gradingdisabled = $gradingdisabled;
        $mformdata->attendancedisabled = $attendancedisabled;
        $mformdata->nextid = $nextid;
        $mformdata->previousid = $previousid;
        $mformdata->instance = $this;
        $mformdata->filter = $filter;
        $mformdata->mailinfo = get_user_preferences('checkmark_mailinfo', 0);

        $submitform = new mod_checkmark_grading_form(null, $mformdata);

        if ($submitform->is_cancelled()) {
            redirect('submissions.php?id=' . $this->cm->id);
        }

        $submitform->set_data($mformdata);

        $PAGE->set_title($this->course->fullname . ': ' . get_string('feedback', 'checkmark') . ' - ' .
                fullname($user));
        $PAGE->navbar->add(get_string('submissions', 'checkmark'),
                new moodle_url('/mod/checkmark/submissions.php', ['id' => $this->cm->id]));
        $PAGE->navbar->add(fullname($user));

        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('feedback', 'checkmark') . ': ' . fullname($user));

        // Display mform here!
        $submitform->display();

        echo $OUTPUT->footer();

        return true;
    }

    /**
     * update_submission($submission) - updates the submission for the actual user
     *
     * @param object $submission Submission object to update
     * @param bool $isoverwrite Indicates of submission is updated due to overwrite -> Submission date doesn't get changed
     * @throws dml_exception
     */
    public function update_submission($submission, $isoverwrite = false) {
        global $DB;
        $update = new stdClass();
        $update->id = $submission->id;
        if (!$isoverwrite) {
            $update->timemodified = time();
            $DB->update_record('checkmark_submissions', $update);

            // Update completion state if submission is changed.
            $completion = new completion_info($this->course);
            if ($completion->is_enabled($this->cm) && $this->checkmark->completionsubmit) {
                $completion->update_state($this->cm, COMPLETION_COMPLETE);
            }
        }

        foreach ($submission->examples as $key => $example) {
            $stateupdate = new stdClass();
            $stateupdate->exampleid = $key;
            if (!$id = $DB->get_field('checkmark_checks', 'id', ['submissionid' => $submission->id,
                    'exampleid' => $key, ], IGNORE_MISSING)) {
                $stateupdate->submissionid = $submission->id;
                $stateupdate->state = $example->state;
                $DB->insert_record('checkmark_checks', $stateupdate);
            } else {
                $stateupdate->id = $id;
                $stateupdate->state = $example->state;
                $DB->update_record('checkmark_checks', $stateupdate);
            }
        }
        if ($isoverwrite) {
            \mod_checkmark\event\submission_overwritten::create_from_object($this->cm, $submission)->trigger();
        } else {
            \mod_checkmark\event\submission_updated::create_from_object($this->cm, $submission)->trigger();
        }
        $this->update_grade($submission);
    }

    /**
     *  Display all the submissions ready for grading (including automated grading buttons)
     *
     * @param string $message
     * @return bool|void
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function display_submissions($message = '') {
        global $SESSION, $OUTPUT, $CFG, $DB, $OUTPUT, $PAGE, $USER;

        $PAGE->activityheader->disable();
        if (!isset($SESSION->checkmark)) {
            $SESSION->checkmark = new stdClass();
        }

        echo $OUTPUT->header();

        /*
         * First we check to see if the form has just been submitted
         * to request user_preference updates!
         */

        $filters = self::get_possible_filters($this->checkmark->trackattendance, $this->checkmark->presentationgrading);

        $updatepref = optional_param('updatepref', 0, PARAM_INT);

        if (!empty($updatepref)) {
            $perpage = optional_param('perpage', 10, PARAM_INT);
            if ($perpage >= 0 && $perpage <= 100) {
                set_user_preference('checkmark_perpage', $perpage);
            }
            $filter = optional_param('filter', self::FILTER_ALL, PARAM_INT);
            set_user_preference('checkmark_quickgrade', optional_param('quickgrade', 0, PARAM_BOOL));
            set_user_preference('checkmark_filter', $filter);
        }

        /*
         * Next we get perpage and quickgrade (allow quick grade) params
         * from database!
         */
        $perpage = get_user_preferences('checkmark_perpage', 10);
        // Replace invalid values with our standard value!
        if ($perpage < 0 || $perpage > 100) {
            $perpage = 10;
            set_user_preference('checkmark_perpage', $perpage);
        }
        $quickgrade = get_user_preferences('checkmark_quickgrade', 0);
        $filter = get_user_preferences('checkmark_filter', self::FILTER_ALL);

        $page = optional_param('page', 0, PARAM_INT);

        // Some shortcuts to make the code read better!

        $course = $this->course;
        $cm = $this->cm;

        // Trigger the event!
        \mod_checkmark\event\submissions_viewed::submissions($this->cm)->trigger();

        $PAGE->set_title(format_string($this->checkmark->name, true));
        $PAGE->set_heading($this->course->fullname);

        $urlbase = $CFG->wwwroot . '/mod/checkmark/export.php?id=' . $this->cm->id;
        echo html_writer::tag('a', get_string('strprintpreview', 'checkmark'), [
            'class' => 'btn btn-secondary float-right',
            'href' => $urlbase . 'submissions.php?id=' . $this->cm->id,
            'id' => 'submissions',
        ]);

        echo html_writer::start_tag('div', ['class' => 'usersubmissions']);

        $coursecontext = context_course::instance($course->id);

        $links = [];
        if (has_capability('gradereport/grader:view', $coursecontext) &&
            has_capability('moodle/grade:viewall', $coursecontext)) {
            $gradebookurl = $CFG->wwwroot . '/grade/report/grader/index.php?id=' . $course->id;
            $links[$gradebookurl] = get_string('viewgradebook', 'checkmark');
        }

        $gradingactions = new url_select($links);
        $gradingactions->set_label(get_string('choosegradingaction', 'checkmark'));
        $gradingactions->class .= ' mb-1';
        echo $this->get_renderer()->render($gradingactions);

        if (!empty($message)) {
            echo $message;   // Display messages here if any!
        }

        groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/checkmark/submissions.php?id=' . $this->cm->id);

        // Print quickgrade form around the table!
        $formaction = new moodle_url('/mod/checkmark/submissions.php', ['id' => $this->cm->id,
                'sesskey' => sesskey(), ]);
        $mform = new MoodleQuickForm('fastg', 'post', $formaction, '', ['class' => 'combinedprintpreviewform']);

        $mform->addElement('hidden', 'id');
        $mform->setDefault('id', $this->cm->id);
        $mform->addElement('hidden', 'mode');
        $mform->setDefault('mode', 'fastgrade');
        $mform->addElement('hidden', 'page');
        $mform->setDefault('page', $page);

        $table = submissionstable::create_submissions_table($this->cm->id, $filter);
        if ($total = $DB->count_records_sql($table->countsql, $table->countparams)) {
            ob_start();
            $table->out($total < $perpage ? $total : $perpage, true);
            $tablehtml = ob_get_contents();
            ob_end_clean();
            $mform->addElement('html', $tablehtml);

            $mform->addElement('select', 'mailinfo', get_string('notifystudent', 'checkmark'), [
                            '1' => get_string('yes'),
                            '0' => get_string('no'),
                        ]);

            $mform->addHelpButton('mailinfo', 'enablenotification', 'checkmark');
            $mailinfopref = false;
            if (get_user_preferences('checkmark_mailinfo', 1)) {
                $mailinfopref = true;
            }
            $mform->setDefault('mailinfo', $mailinfopref);

            if ($quickgrade) {
                $mform->addElement('submit', 'fastg', get_string('saveallfeedback', 'checkmark'));
            }

            $grp = [];
            $grp[] =& $mform->createElement('select', 'bulkaction', '');
            $enablebulk = false;

            if (($this->checkmark->grade <= 0)) {
                // No autograde possible if no numeric grades are selected!
                $mform->addElement('html', html_writer::div(get_string('autograde_non_numeric_grades', 'checkmark'),
                        'alert alert-error'));
                $grp[0]->addOption(get_string('grade_automatically', 'checkmark'), 'grade', ['disabled' => 'disabled']);
            } else {
                $grp[0]->addOption(get_string('grade_automatically', 'checkmark'), 'grade');
                $enablebulk = true;
            }

            if ($this->checkmark->trackattendance
                    && has_capability('mod/checkmark:trackattendance', $this->context)) {
                $grp[0]->addOption('---', '', ['disabled' => 'disabled']);
                $grp[0]->addOption(get_string('setattendant', 'checkmark'), 'setattendant');
                $grp[0]->addOption(get_string('setabsent', 'checkmark'), 'setabsent');
                $enablebulk = true;
            }

            if ($this->checkmark->trackattendance
                    && has_capability('mod/checkmark:trackattendance', $this->context)) {
                if ($this->checkmark->attendancegradelink) {
                    $mform->addElement('html', html_writer::div(get_string('attendancegradelink_hint', 'checkmark'),
                            'alert alert-info'));
                }
                if (($this->checkmark->grade <= 0)) {
                    $attr = ['disabled' => 'disabled'];
                } else {
                    $attr = [];
                    $enablebulk = true;
                }
                $grp[0]->addOption(get_string('setattendantandgrade', 'checkmark'), 'setattendantandgrade', $attr);
                $grp[0]->addOption(get_string('setabsentandgrade', 'checkmark'), 'setabsentandgrade', $attr);
            }

            $grp[0]->addOption(get_string('remove_grade', 'checkmark'), 'removegrade');
            if ($this->checkmark->presentationgrading) {
                $grp[0]->addOption(get_string('remove_presentation_grade', 'checkmark'), 'removepresentationgrade');
            }

            if (has_capability('mod/checkmark:manageoverrides', $this->context)) {
                $grp[0]->addOption('---', '', ['disabled' => 'disabled']);
                $grp[0]->addOption(get_string('grant_extension', 'checkmark'), 'extend');
                $enablebulk = true;
            }

            $attr = [];
            if (!$enablebulk) {
                $attr['disabled'] = 'disabled';
            }

            if ($enablebulk) {
                $grp[] =& $mform->createElement('submit', 'bulk', get_string('start', 'checkmark'), $attr);
                $mform->addGroup($grp, 'actiongrp', get_string('selection', 'checkmark') . '...', ' ', false);
                $mform->addHelpButton('actiongrp', 'bulk', 'checkmark');
            }
        } else {
            if ($filter == self::FILTER_SUBMITTED) {
                $mform->addElement('html',
                        $OUTPUT->notification(html_writer::tag('div', get_string('nosubmisson', 'checkmark'),
                                ['class' => 'nosubmission']), 'notifymessage'));
            } else if ($filter == self::FILTER_REQUIRE_GRADING) {
                $mform->addElement('html',
                        $OUTPUT->notification(html_writer::tag('div', get_string('norequiregrading', 'checkmark'),
                                ['class' => 'norequiregrading']), 'notifymessage'));
            } else {
                $mform->addElement('html',
                        $OUTPUT->notification(html_writer::tag('div', get_string('nostudents', 'checkmark'),
                                ['class' => 'nostudents']), 'notifymessage'));
            }
        }

        $mform->display();
        // End of fast grading form!

        echo html_writer::empty_tag('br', ['class' => 'clearfloat']);

        // Mini form for setting user preference!
        $formaction = new moodle_url('/mod/checkmark/submissions.php', ['id' => $this->cm->id]);

        $mform = new MoodleQuickForm('optionspref', 'post', $formaction, '', ['class' => 'optionspref']);
        $mform->addElement('hidden', 'updatepref');
        $mform->setDefault('updatepref', 1);
        $mform->addElement('header', 'qgprefs', get_string('optionalsettings', 'checkmark'));
        $mform->addElement('select', 'filter', get_string('show'), $filters);
        $mform->addElement('hidden', 'sesskey');
        $mform->setDefault('sesskey', sesskey());

        $mform->setDefault('filter', $filter);

        $mform->addElement('select', 'perpage', get_string('pagesize', 'checkmark'), [
                10 => 10,
                20 => 20,
                50 => 50,
                100 => 100,
                0 => get_string('all'),
        ]);
        $mform->setDefault('perpage', $perpage);

        $mform->addElement(
            'checkbox',
            'quickgrade',
            get_string('quickgrade', 'checkmark'),
            0,
            ['onchange' => "this.form.submit();"]
        );

        $mform->setDefault('quickgrade', $quickgrade);
        $mform->addHelpButton('quickgrade', 'quickgrade', 'checkmark');

        $mform->addElement('submit', 'savepreferences', get_string('savepreferences'));

        $mform->display();

        echo $OUTPUT->footer();
    }

    /**
     * Prints the submission and export tabs
     *
     * @param string $tab currently active tab
     * @throws coding_exception
     */
    public function print_submission_tabs($tab) {
        global $CFG;

        $tabs = [
            [
                new tabobject(
                    'submissions',
                    $CFG->wwwroot . '/mod/checkmark/submissions.php?id=' . $this->cm->id,
                    get_string('strsubmissions', 'checkmark'),
                    get_string('strsubmissionstabalt', 'checkmark'),
                    false
                ),
            ],
        ];

        print_tabs($tabs, $tab, [$tab], []);
    }

    /**
     * Either returns raw data for pdf/xls/ods/etc export or prints and returns table.
     *
     * @param int $filter Filter to apply (checkmark::FILTER_ALL, checkmark::FILTER_REQUIRE_GRADING, ...)
     * @param int[] $ids (optional) User-IDs to filter for
     * @param bool $dataonly (optional) return raw data-object or HTML table
     * @param int $type Type (with or without color information) that should be used for print
     * @return array|submissionstable
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function get_print_data($filter, $ids = [], $dataonly = false, $type = submissionstable::FORMAT_DOWNLOAD) {
        global $DB, $OUTPUT;

        $table = submissionstable::create_export_table($this->cm->id, $filter, $ids);
        if ($DB->count_records_sql($table->countsql, $table->countparams)) {
            if ($dataonly) {
                return $table->get_data($type);
            } else {
                echo html_writer::start_tag('div', ['class' => 'fcontainer scroll_forced',
                        'id' => 'table_begin', ]);
                $table->out(0, true);
                echo html_writer::end_tag('div');
                return $table;
            }
        } else {
            if (empty($dataonly)) {
                if ($filter == self::FILTER_SUBMITTED) {
                    echo $OUTPUT->notification(html_writer::tag('div', get_string('nosubmisson', 'checkmark'),
                            ['class' => 'nosubmisson']), 'notifymessage');
                } else if ($filter == self::FILTER_REQUIRE_GRADING) {
                    echo $OUTPUT->notification(html_writer::tag('div', get_string('norequiregrading', 'checkmark'),
                            ['class' => 'norequiregrading']), 'notifymessage');
                } else {
                    echo $OUTPUT->notification(html_writer::tag('div', get_string('nostudents', 'checkmark'),
                            ['class' => 'norequiregrading']), 'notifymessage');
                }
                return $table;
            } else {
                return [[], [], [], [], []];
            }
        }
    }

    /**
     * Handles all print preference setting (if submitted) and returns the current values!
     *
     * TODO do we really need this method (for writing preferences) when we have the form used properly now?
     *
     * @return array print preferences ($filter, $sumabs, $sumrel, $format, $printperpage, $printoptimum, $textsize,
     *                                  $pageorientation, $printheader, $forcesinglelinenames, $zipped)
     * @throws coding_exception
     */
    public function print_preferences() {
        $updatepref = optional_param('updatepref', 0, PARAM_INT);
        if ($updatepref && confirm_sesskey()) {
            $filter = optional_param('datafilter', self::FILTER_ALL, PARAM_INT);
            set_user_preference('checkmark_filter_export', $filter);
            $format = optional_param('format', \mod_checkmark\MTablePDF::OUTPUT_FORMAT_PDF, PARAM_INT);
            set_user_preference('checkmark_format', $format);
            $sumabs = optional_param('sumabs', 0, PARAM_INT);
            set_user_preference('checkmark_sumabs', $sumabs);
            $sumrel = optional_param('sumrel', 0, PARAM_INT);
            set_user_preference('checkmark_sumrel', $sumrel);
            $seperatenamecolumns = optional_param('seperatenamecolumns', 0, PARAM_BOOL);
            set_user_preference('checkmark_seperatenamecolumns', $seperatenamecolumns);
            $coursetitle = optional_param('coursetitle', '', PARAM_TEXT);
            set_user_preference('checkmark_coursetitle', $coursetitle);
            if ($format == \mod_checkmark\MTablePDF::OUTPUT_FORMAT_PDF) {
                $printperpage = optional_param('printperpage', 0, PARAM_INT);
                $printoptimum = optional_param('printoptimum', 0, PARAM_INT);
                $printperpage = (($printperpage <= 0) || $printoptimum) ? 0 : $printperpage;
                set_user_preference('checkmark_pdfprintperpage', $printperpage);
                $textsize = optional_param('textsize', \mod_checkmark\MTablePDF::FONTSIZE_SMALL, PARAM_INT);
                set_user_preference('checkmark_textsize', $textsize);
                $pageorientation = optional_param('pageorientation', \mod_checkmark\MTablePDF::LANDSCAPE, PARAM_ALPHA);
                set_user_preference('checkmark_pageorientation', $pageorientation);
                $printheader = optional_param('printheader', 0, PARAM_INT);
                set_user_preference('checkmark_printheader', $printheader);
                $forcesinglelinenames = optional_param('forcesinglelinenames', 0, PARAM_INT);
                set_user_preference('checkmark_forcesinglelinenames', $forcesinglelinenames);
                $sequentialnumbering = optional_param('sequentialnumbering', 0, PARAM_INT);
                set_user_preference('checkmark_sequentialnumbering', $sequentialnumbering);
                $zipped = optional_param('zipped', \mod_checkmark\MTablePDF::UNCOMPRESSED, PARAM_ALPHA);
                set_user_preference('checkmark_zipped', $zipped);
            } else {
                set_user_preference('checkmark_zipped', \mod_checkmark\MTablePDF::UNCOMPRESSED);
            }
        } else {
            $filter = get_user_preferences('checkmark_filter_export', self::FILTER_ALL);
            $sumabs = get_user_preferences('checkmark_sumabs', 1);
            $sumrel = get_user_preferences('checkmark_sumrel', 1);
            $seperatenamecolumns = get_user_preferences('checkmark_seperatenamecolumns', 0);
            $format = get_user_preferences('checkmark_format', \mod_checkmark\MTablePDF::OUTPUT_FORMAT_PDF);
            $coursetitle = get_user_preferences('checkmark_coursetitle', '');
        }

        if ($format != \mod_checkmark\MTablePDF::OUTPUT_FORMAT_PDF
                || !($updatepref && confirm_sesskey())) {
            $printperpage = get_user_preferences('checkmark_pdfprintperpage', 0);
            if ($printperpage == 0) {
                $printoptimum = 1;
            } else {
                $printoptimum = 0;
            }
            $textsize = get_user_preferences('checkmark_textsize', \mod_checkmark\MTablePDF::FONTSIZE_SMALL);
            $pageorientation = get_user_preferences('checkmark_pageorientation', \mod_checkmark\MTablePDF::LANDSCAPE);
            $printheader = get_user_preferences('checkmark_printheader', 1);
            $forcesinglelinenames = get_user_preferences('checkmark_forcesinglelinenames', 0);
            $sequentialnumbering = get_user_preferences('checkmark_sequentialnumbering', 0);
            $zipped = get_user_preferences('checkmark_zipped', \mod_checkmark\MTablePDF::UNCOMPRESSED);
        }

        // Keep compatibility to old user preferences!
        if ($pageorientation === 1) {
            $pageorientation = \mod_checkmark\MTablePDF::PORTRAIT;
        } else if ($pageorientation === 0) {
            $pageorientation = \mod_checkmark\MTablePDF::LANDSCAPE;
        }

        return [
                $filter,
                $sumabs,
                $sumrel,
                $seperatenamecolumns,
                $format,
                $printperpage,
                $printoptimum,
                $textsize,
                $pageorientation,
                $printheader,
                $forcesinglelinenames,
                $zipped,
                $sequentialnumbering,
                $coursetitle,
        ];
    }

    /**
     * Returns export form
     *
     * @return bool|\mod_checkmark\exportform
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function get_export_form() {
        static $mform = false;

        if (!$mform) {
            /*
             * First we check to see if the form has just been submitted
             * to request user_preference updates!
             */
            list($filter, $sumabs, $sumrel, $seperatenamecolumns, $format, $printperpage, $printoptimum, $textsize,
                    $pageorientation, $printheader, $forcesinglelinenames, $zipped) = $this->print_preferences();

            ob_start();
            $this->get_print_data($filter);
            $tablehtml = ob_get_contents();
            ob_end_clean();

            $customdata = [
                    'cm' => $this->cm,
                    'context' => $this->context,
                    'examplescount' => count($this->get_examples()),
                    'table' => $tablehtml,
                    'tracksattendance' => $this->checkmark->trackattendance,
                    'filters' => self::get_possible_filters($this->checkmark->trackattendance,
                            $this->checkmark->presentationgrading),
            ];
            $formaction = new moodle_url('/mod/checkmark/export.php', [
                    'id' => $this->cm->id,
                    'sesskey' => sesskey(),
            ]);
            $mform = new \mod_checkmark\exportform($formaction, $customdata, 'post', '', [
                    'name' => 'optionspref',
                    'class' => 'combinedprintpreviewform',
                    'data-double-submit-protection' => 'off',
            ]);

            $data = [
                    'filter' => $filter,
                    'sumabs' => $sumabs,
                    'sumrel' => $sumrel,
                    'seperatenamecolumns' => $seperatenamecolumns,
                    'format' => $format,
                    'printperpage' => $printperpage,
                    'printoptimum' => $printoptimum,
                    'textsize' => $textsize,
                    'pageorientation' => $pageorientation,
                    'printheader' => $printheader,
                    'forcesinglelinenames' => $forcesinglelinenames,
                    'zipped' => $zipped,
            ];
            $mform->set_data($data);
        }

        return $mform;
    }

    /**
     * Echo the print preview tab including a optional message!
     *
     * @param string $message The message to display in the tab!
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function view_export($message = '') {
        global $CFG, $OUTPUT, $PAGE;
        require_once($CFG->libdir . '/gradelib.php');

        // Trigger the event!
        \mod_checkmark\event\printpreview_viewed::printpreview($this->cm)->trigger();

        $PAGE->set_title(format_string($this->checkmark->name, true));
        $PAGE->set_heading($this->course->fullname);

        $urlbase = $CFG->wwwroot . '/mod/checkmark/submissions.php?id=' . $this->cm->id;
        echo html_writer::tag('a', get_string('back'), [
            'class' => 'btn btn-secondary',
            'href' => $urlbase . 'submissions.php?id=' . $this->cm->id,
            'id' => 'submissions',
        ]);

        // Form to manage print-settings!
        echo html_writer::start_tag('div', ['class' => 'usersubmissions']);

        $mform = $this->get_export_form();

        if (!empty($message)) {
            echo $message;   // Display messages here if any!
        }

        $mform->display();

        echo $OUTPUT->footer();
    }

    /**
     * Print a message along with button choices for Continue/Cancel
     *
     * If a string or moodle_url is given instead of a single_button, method defaults to post.
     * If cancel=null only continue button is displayed!
     *
     * @param string $message The question to ask the user
     * @param single_button|moodle_url|string $continue The single_button component representing
     *                                                      the Continue answer.
     *                                                      Can also be a moodle_url or string URL
     * @param single_button|moodle_url|string $cancel The single_button component representing
     *                                                the Cancel answer.
     *                                                Can also be a moodle_url or string URL
     * @return string HTML fragment
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function confirm($message, $continue, $cancel = null) {
        global $OUTPUT;
        if (!($continue instanceof single_button)) {
            if (is_string($continue)) {
                $continue = new single_button(new moodle_url($continue), get_string('continue'),
                        'post', true);
            } else if ($continue instanceof moodle_url) {
                $continue = new single_button($continue, get_string('continue'), 'post', true);
            } else {
                throw new coding_exception('The continue param to $OUTPUT->confirm() must be either a' .
                        ' URL (str/moodle_url), a single_button instance or null.');
            }
        }

        if (!($cancel instanceof single_button)) {
            if (is_string($cancel)) {
                $cancel = new single_button(new moodle_url($cancel), get_string('cancel'), 'get');
            } else if ($cancel instanceof moodle_url) {
                $cancel = new single_button($cancel, get_string('cancel'), 'get');
            } else if ($cancel == null) {
                $cancel = null;
            } else {
                throw new coding_exception('The cancel param to $OUTPUT->confirm() must be either a' .
                        ' URL (str/moodle_url), a single_button instance or null.');
            }
        }

        $output = $OUTPUT->box_start('generalbox', 'notice');
        $output .= html_writer::tag('p', $message);
        $output .= html_writer::tag('div', $OUTPUT->render($continue) . (($cancel != null) ? $OUTPUT->render($cancel) : ''),
                ['class' => 'buttons']);
        $output .= $OUTPUT->box_end();
        return $output;
    }

    /**
     * Returns applicable filters for this checkmark instance
     *
     * @return array applicable filters
     * @throws coding_exception
     */
    protected function get_filters() {
        return self::get_possible_filters($this->checkmark->trackattendance, $this->checkmark->presentationgrading);
    }

    /**
     * Returns all possible filters
     *
     * @param bool $trackattendance whether or not to include filters for attendance
     * @param bool $presentationgrading whether or not to include filters for presentationgrading
     * @return array all possible filters
     * @throws coding_exception
     */
    public static function get_possible_filters($trackattendance = false, $presentationgrading = false) {
        $filters = [
                self::FILTER_ALL => get_string('all'),
                self::FILTER_NOT_SUBMITTED => get_string('filternotsubmitted', 'checkmark'),
                self::FILTER_SUBMITTED => get_string('submitted', 'checkmark'),
                self::FILTER_GRADED => get_string('graded', 'checkmark'),
                self::FILTER_REQUIRE_GRADING => get_string('requiregrading', 'checkmark'),
                self::FILTER_EXTENSION => get_string('filtergrantedextension', 'checkmark'),
        ];

        if ($trackattendance) {
            $filters[self::FILTER_ATTENDANT] = get_string('all_attendant', 'checkmark');
            $filters[self::FILTER_ABSENT] = get_string('all_absent', 'checkmark');
            $filters[self::FILTER_UNKNOWN] = get_string('all_unknown', 'checkmark');
        }

        if ($presentationgrading) {
            $filters[self::FILTER_PRESENTATIONGRADING] = get_string('all_with_presentationgrading',
                    'checkmark');
            $filters[self::FILTER_NO_PRESENTATIONGRADING] = get_string('all_without_presentationgrading',
                    'checkmark');
        }

        return $filters;
    }

    /**
     * Returns export formats
     *
     * @return array export formats
     */
    public static function get_formats() {
        return [
                \mod_checkmark\MTablePDF::OUTPUT_FORMAT_PDF => 'PDF',
                \mod_checkmark\MTablePDF::OUTPUT_FORMAT_XLSX => 'XLSX',
                \mod_checkmark\MTablePDF::OUTPUT_FORMAT_ODS => 'ODS',
                \mod_checkmark\MTablePDF::OUTPUT_FORMAT_CSV_COMMA => 'CSV (;)',
                \mod_checkmark\MTablePDF::OUTPUT_FORMAT_CSV_TAB => 'CSV (tab)',
        ];
    }

    /**
     * Exports table with chosen template
     *
     * @param bool|string $template The templates name
     * @return array|void
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function quick_export($template = false) {
        global $PAGE;

        $classname = '\\mod_checkmark\\local\\exporttemplates\\' . $template;
        if (!class_exists($classname)) {
            return;
        }

        /*
         * First we check to see if the form has just been submitted
         * to request user_preference updates! We don't use $printoptimum here, it's implicit in $printperpage!
         */
        list($filter, , , , $format, , , , , , , $zipped) = $this->print_preferences();

        $usrlst = optional_param_array('selected', [], PARAM_INT);

        if (empty($usrlst)) {
            redirect($PAGE->url, get_string('nousers', 'checkmark'), null, 'notifyproblem');
            return;
        }

        $table = $classname::create_export_table($this->cm->id, $filter, $usrlst);

        if ($zipped === \mod_checkmark\MTablePDF::ZIPPED) {
            $this->export_zipped_group_pdfs($template);
        } else {
            if ($format == \mod_checkmark\MTablePDF::OUTPUT_FORMAT_XLSX || $format == \mod_checkmark\MTablePDF::OUTPUT_FORMAT_ODS) {
                $this->exportpdf($table->get_data(submissionstable::FORMAT_COLORS), $template);
            } else {
                $this->exportpdf($table->get_data(), $template);
            }
        }
    }

    /**
     * Creates and outputs PDF (then dies).
     *
     * @param mixed[] $exportdata Returned by table object's get_data()
     * @param string $template (optional) Template name if used
     * @throws coding_exception
     * @throws moodle_exception
     */
    protected function exportpdf($exportdata, $template = '') {
        global $PAGE;

        $filters = $this->get_filters();
        $formats = self::get_formats();

        list(, $tableheaders, $data, $columnformat, $cellwidth) = $exportdata;

        /*
         * Get all settings preferences, some will be overwritten if a template is used!
         */
        list($filter, $sumabs, $sumrel, $seperatenamecolumns, $format, $printperpage, ,
                $textsize, $orientation, $printheader, $forcesinglelinenames, $zipped,
                $sequentialnumbering, $coursetitle) = $this->print_preferences();

        if (!empty($template)) {
            $classname = '\\mod_checkmark\\local\\exporttemplates\\' . $template;
            list($sumabs, $sumrel, $orientation, $textsize, $printheader,
                    $forcesinglelinenames) = $classname::get_export_settings();
        }

        $usrlst = optional_param_array('selected', [], PARAM_INT);

        $groupmode = groups_get_activity_groupmode($this->cm);
        $currentgroup = 0;
        if ($groupmode != NOGROUPS) {
            $currentgroup = groups_get_activity_group($this->cm, true);
            if (empty($currentgroup)) {
                $grpname = get_string('all', 'checkmark');
            } else {
                $grpname = groups_get_group_name($currentgroup);
                // Remove everyone from the usrlst who's not in the currently selected group!
                $grpmembers = groups_get_members($currentgroup);
                $usrlst = array_intersect($usrlst, array_keys($grpmembers));
            }
        } else {
            $grpname = '-';
        }

        if (empty($usrlst)) {
            redirect($PAGE->url, get_string('nousers', 'checkmark'), null, 'notifyproblem');
            return;
        }

        if (!count($data)) {
            $cellwidth = [
                    ['mode' => 'Fixed', 'value' => '15'],
                    ['mode' => 'Fixed', 'value' => '50'],
                    ['mode' => 'Fixed', 'value' => '15'],
            ];
            $printheader = true;
        }
        $pdf = new \mod_checkmark\MTablePDF($orientation, $cellwidth);

        $notactivestr = get_string('notactive', 'checkmark');
        $timeavailablestr = !empty($this->checkmark->timeavailable) ? userdate($this->checkmark->timeavailable) : $notactivestr;
        $timeduestr = !empty($this->checkmark->timedue) ? userdate($this->checkmark->timedue) : $notactivestr;
        $courseheadertitle = $coursetitle == 'courseshortname' ? $this->course->shortname : $this->course->fullname;
        $courseheadertitle = format_string($courseheadertitle, true, ['context' => context_module::instance($this->cm->id)]);
        $paramarray = [get_string('course') . ':', $courseheadertitle,
                get_string('availabledate', 'checkmark') . ':', $timeavailablestr,
                !$template ? get_string('strprintpreview', 'checkmark') : '', $filters[$filter],
            // Second header row!
                get_string('strassignment', 'checkmark') . ':',
                format_string($this->checkmark->name, true, ['context' => context_module::instance($this->cm->id)]),
                get_string('duedate', 'checkmark') . ':', $timeduestr,
                get_string('groups') . ':', $grpname,
            ];
        $pdf->setheadertext($paramarray);

        $pdf->showheaderfooter($printheader);
        $pdf->sequentialnumbering($sequentialnumbering);
        $pdf->setfontsize($textsize);

        if (is_number($printperpage) && $printperpage != 0) {
            $pdf->setrowsperpage($printperpage);
        }

        // Data present?
        if (count($data)) {
            $pdf->setcolumnformat($columnformat);
            $pdf->settitles($tableheaders);
            foreach ($data as $row) {
                $pdf->addrow($row);
            }
        } else {
            if ($filter == self::FILTER_REQUIRE_GRADING) {
                $text = get_string('norequiregrading', 'checkmark');
            } else {
                $text = get_string('nosubmisson', 'checkmark');
            }
            $pdf->settitles([' ', ' ', ' ']);
            $pdf->addrow(['', $text, '']);
        }

        $pdf->setoutputformat($format);

        $export = new \mod_checkmark\export();
        $export->set_general_data($groupmode, $currentgroup, $usrlst, $filter, $format, $sumabs, $sumrel, $seperatenamecolumns);
        if ($format === \mod_checkmark\MTablePDF::OUTPUT_FORMAT_PDF) {
            $export->set_pdf_data($orientation, $printheader, $textsize, $printperpage,
                $forcesinglelinenames, $sequentialnumbering);
        }
        if ($template) {
            $export->set_used_template($template);
        }
        \mod_checkmark\event\submissions_exported::exported($this->cm, $export)->trigger();

        $filename = $this->course->shortname . '-' . $this->checkmark->name;
        $filename = format_string($filename, true, ['context' => context_module::instance($this->cm->id)]);
        if ($template) {
            $filename .= '-' . get_string('exporttemplate_' . $template, 'checkmark');
        }
        $pdf->generate($filename);
        die();
    }

    /**
     * Exports a separate PDF for each group collected in a ZIP file
     *
     * @param string|false $template The template name to use or false
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    protected function export_zipped_group_pdfs($template = false) {
        global $USER;

        $groupmode = groups_get_activity_groupmode($this->cm);
        $aag = has_capability('moodle/site:accessallgroups', $this->context);
        if ($groupmode === NOGROUPS) {
            throw new coding_exception('Groupmode NOGROUPS! - Groups not used in course or activity!');
        } else if ($groupmode == VISIBLEGROUPS || $aag) {
            $groups = groups_get_all_groups($this->course->id, 0, $this->course->defaultgroupingid);
        } else {
            $groups = groups_get_all_groups($this->course->id, $USER->id, $this->course->defaultgroupingid);
        }

        $filters = $this->get_filters();
        $formats = self::get_formats();

        /*
         * Get all settings preferences, some will be overwritten if a template is used!
         */
        list($filter, $sumabs, $sumrel, $seperatenamecolumns, $format, $printperpage, ,
                $textsize, $orientation, $printheader, $forcesinglelinenames, , $sequentialnumbering,
                $coursetitle) = $this->print_preferences();

        if (!empty($template)) {
            $classname = '\\mod_checkmark\\local\\exporttemplates\\' . $template;
            list($sumabs, $sumrel, $orientation, $textsize, $printheader,
                    $forcesinglelinenames) = $classname::get_export_settings();
        }

        $notactivestr = get_string('notactive', 'checkmark');
        $timeavailablestr = !empty($this->checkmark->timeavailable) ? userdate($this->checkmark->timeavailable) : $notactivestr;
        $timeduestr = !empty($this->checkmark->timedue) ? userdate($this->checkmark->timedue) : $notactivestr;

        $filesforzipping = [];

        foreach ($groups as $currentgroup) {
            $grpname = $currentgroup->name;
            $usrlst = array_keys(groups_get_members($currentgroup->id, 'u.id'));

            if (empty($usrlst)) {
                // We don't export empty groups here!
                continue;
            }

            // Get data!
            $printdata = $this->get_print_data($filter, $usrlst, true);
            list(, $tableheaders, $data, $columnformat, $cellwidth) = $printdata;

            if (!count($data)) {
                $cellwidth = [
                        ['mode' => 'Fixed', 'value' => '15'],
                        ['mode' => 'Fixed', 'value' => '50'],
                        ['mode' => 'Fixed', 'value' => '15'],
                ];
                $printheader = true;
            }
            $pdf = new \mod_checkmark\MTablePDF($orientation, $cellwidth);

            $pdf->setoutputformat(\mod_checkmark\MTablePDF::OUTPUT_FORMAT_PDF);
            $pdf->showheaderfooter($printheader);
            $pdf->sequentialnumbering($sequentialnumbering);
            $pdf->setfontsize($textsize);

            if (is_number($printperpage) && $printperpage != 0) {
                $pdf->setrowsperpage($printperpage);
            }
            $courseheadertitle = $coursetitle == 'courseshortname' ? $this->course->shortname : $this->course->fullname;
            $courseheadertitle = format_string($courseheadertitle, true, ['context' => context_module::instance($this->cm->id)]);
            $paramarray = [get_string('course') . ':', $courseheadertitle,
                    get_string('availabledate', 'checkmark') . ':', $timeavailablestr,
                    !$template ? get_string('strprintpreview', 'checkmark') : '', $filters[$filter],
                // Second header row!
                    get_string('strassignment', 'checkmark') . ':',
                    format_string($this->checkmark->name, true, ['context' => context_module::instance($this->cm->id)]),
                    get_string('duedate', 'checkmark') . ':', $timeduestr,
                    get_string('groups') . ':', $grpname,
            ];
            $pdf->setheadertext($paramarray);

            // Data present?
            if (count($data)) {
                $pdf->setcolumnformat($columnformat);
                $pdf->settitles($tableheaders);
                foreach ($data as $row) {
                    $pdf->addrow($row);
                }
            } else {
                if ($filter == self::FILTER_REQUIRE_GRADING) {
                    $text = get_string('norequiregrading', 'checkmark');
                } else {
                    $text = get_string('nosubmisson', 'checkmark');
                }
                $pdf->settitles([' ', ' ', ' ']);
                $pdf->addrow(['', $text, '']);
            }

            $curexport = new \mod_checkmark\export();
            $groupid = $currentgroup ? $currentgroup->id : 0;
            $curexport->set_general_data($groupmode, $groupid, $usrlst, $filter, $format, $sumabs, $sumrel, $seperatenamecolumns);
            if ($format === \mod_checkmark\MTablePDF::OUTPUT_FORMAT_PDF) {
                $curexport->set_pdf_data($orientation, $printheader, $textsize, $printperpage,
                    $forcesinglelinenames, $sequentialnumbering);
            }
            if ($template) {
                $curexport->set_used_template($template);
            }
            \mod_checkmark\event\submissions_exported::exported($this->cm, $curexport)->trigger();

            $filename = $this->course->shortname . '-' . $this->checkmark->name . '-' . $currentgroup->name;
            $filename = format_string($filename, true, ['context' => context_module::instance($this->cm->id)]);
            if ($template) {
                $filename .= '-' . get_string('exporttemplate_' . $template, 'checkmark');
            }
            if (!preg_match('/.pdf$/', $filename)) {
                $filename .= '.pdf';
            }
            $filesforzipping[$filename] = $pdf->get_temp_pdf();
        }

        $zipper = new zip_packer();
        $tmpdir = make_temp_directory('checkmark');
        $zipfile = tempnam($tmpdir, 'checkmark_');
        $zipname = $this->course->shortname . '-' . $this->checkmark->name;
        $zipname = format_string($zipname, true, ['context' => context_module::instance($this->cm->id)]);
        if (!preg_match('/.zip$/', $zipname)) {
            $zipname .= '.zip';
        }
        if ($zipper->archive_to_pathname($filesforzipping, $zipfile)) {
            send_temp_file($zipfile, $zipname); // Send file and delete after sending.
        } else {
            throw new coding_exception('Something bad happened during creating the ZIP file!');
        }
    }

    /**
     * Finaly print the submissions!
     *
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function submissions_print() {
        global $PAGE;

        /*
         * First we check to see if the form has just been submitted
         * to request user_preference updates! We don't use $printoptimum here, it's implicit in $printperpage!
         */
        list($filter, , , , $format, , , , , , , $zipped) = $this->print_preferences();

        if ($zipped === \mod_checkmark\MTablePDF::ZIPPED) {
            $this->export_zipped_group_pdfs();
        } else {
            $usrlst = optional_param_array('selected', [], PARAM_INT);

            if (empty($usrlst)) {
                redirect($PAGE->url, get_string('nousers', 'checkmark'), null, 'notifyproblem');
                return;
            }

            // Get data!
            if ($format == \mod_checkmark\MTablePDF::OUTPUT_FORMAT_XLSX || $format == \mod_checkmark\MTablePDF::OUTPUT_FORMAT_ODS) {
                $printdata = $this->get_print_data($filter, $usrlst, true, submissionstable::FORMAT_COLORS);
            } else {
                $printdata = $this->get_print_data($filter, $usrlst, true);
            }
            $this->exportpdf($printdata);
        }
    }

    /**
     *  Process teacher feedback submission
     *
     * This is called by submissions() when a grading even has taken place.
     * It gets its data from the submitted form.
     *
     * @return object|bool The updated submission object or false
     * @throws coding_exception
     * @throws dml_exception
     */
    public function process_feedback() {
        global $CFG, $USER, $DB;
        require_once($CFG->libdir . '/gradelib.php');

        $formdata = data_submitted();
        if (!$formdata || !confirm_sesskey()) {      // No incoming data?
            return false;
        }

        /*
         * For save and next, we need to know the userid to save, and the userid to go
         * We use a new hidden field in the form, and set it to -1. If it's set, we use this
         * as the userid to store!
         */
        if ((int) $formdata->saveuserid !== -1) {
            $formdata->userid = $formdata->saveuserid;
        }

        if (!empty($formdata->cancel)) {          // User hit cancel button!
            return false;
        }

        // This is no security check, this just tells us if there is posted data!
        if (isset($formdata->mailinfo) && $formdata->mailinfo !== null) {
            set_user_preference('checkmark_mailinfo', $formdata->mailinfo);
        }

        // Create the submission if needed & return its id!
        $submission = $this->get_submission($formdata->userid, true);

        foreach ($submission->get_examples_or_example_template() as $key => $example) {
            if (isset($formdata->{$key}) && ($formdata->{$key} != 0)) {
                $submission->get_example($key)->overwrite_example(\mod_checkmark\example::CHECKED);
            } else {
                $submission->get_example($key)->overwrite_example(\mod_checkmark\example::UNCHECKED);
            }
        }
        // Update checks to save overwritten entries.
        $this->update_submission($submission, true);

        $gradinginfo = grade_get_grades($this->course->id, 'mod', 'checkmark',
                $this->checkmark->id, $formdata->userid);

        // Store outcomes if needed!
        $this->process_outcomes($formdata->userid);

        $feedback = $this->get_feedback($formdata->userid); // Get or make one!
        if ($feedback === false) {
            $feedback = $this->prepare_new_feedback($formdata->userid);
        } else {
            $feedback->timemodified = time();
        }

        $update = false;

        if (!($gradinginfo->items[CHECKMARK_GRADE_ITEM]->grades[$formdata->userid]->locked
                || $gradinginfo->items[CHECKMARK_GRADE_ITEM]->grades[$formdata->userid]->overridden)) {
            $feedback->grade = $formdata->xgrade;
            $feedback->feedback = $formdata->feedback_editor['text'];
            $feedback->graderid = $USER->id;
            $update = true;
        }
        if ($this->checkmark->attendancegradebook) {
            $lockedoroverridden = $gradinginfo->items[CHECKMARK_ATTENDANCE_ITEM]->grades[$formdata->userid]->locked
                    || $gradinginfo->items[CHECKMARK_ATTENDANCE_ITEM]->grades[$formdata->userid]->overridden;
        } else {
            $lockedoroverridden = false;
        }
        $cantrackattendances = has_capability('mod/checkmark:trackattendance', $this->context);
        if ($this->checkmark->trackattendance && $cantrackattendances && !$lockedoroverridden) {
            if (($formdata->attendance === '') || ($formdata->attendance == -1)) {
                $feedback->attendance = null;
            } else if ($formdata->attendance == 1) {
                $feedback->attendance = 1;
            } else if ($formdata->attendance == 0) {
                $feedback->attendance = 0;
            }
            $feedback->graderid = $USER->id;
            $update = true;
        }
        if ($this->checkmark->presentationgradebook) {
            $presitem = $gradinginfo->items[CHECKMARK_PRESENTATION_ITEM];
            $presgradedisabled = $presitem->grades[$formdata->userid]->locked
                    || $presitem->grades[$formdata->userid]->overridden;
        } else {
            $presgradedisabled = false;
        }
        if ($this->checkmark->presentationgrading && has_capability('mod/checkmark:gradepresentation', $this->context)
                && !$presgradedisabled) {
            if ($this->checkmark->presentationgrade) {
                $feedback->presentationgrade = $formdata->presentationgrade;
                if ($formdata->presentationgrade == -1) {
                    // Normalize the presentationgrade!
                    $feedback->presentationgrade = null;
                }
            }
            $feedback->presentationfeedback = $formdata->presentationfeedback_editor['text'];
            $feedback->presentationformat = $formdata->presentationfeedback_editor['format'];
            $feedback->graderid = $USER->id;
            $update = true;
        }

        if ($update) {
            if (!empty($formdata->mailinfo)) {
                $feedback->mailed = 0;       // Make sure mail goes out (again, even)!
            } else {
                $feedback->mailed = 1;       // Treat as already mailed!
            }
            $DB->update_record('checkmark_feedbacks', $feedback);

            // Trigger grade event!
            $this->update_grade($feedback);

            // Trigger the event!
            \mod_checkmark\event\grade_updated::manual($this->cm, ['userid' => $feedback->userid,
                    'feedbackid' => $feedback->id, ])->trigger();
        }

        return $feedback;

    }

    /**
     * Process outcomes for this user.
     *
     * @param int $userid the user's ID
     */
    public function process_outcomes($userid) {
        global $CFG;

        if (empty($CFG->enableoutcomes)) {
            return;
        }

        require_once($CFG->libdir . '/gradelib.php');

        $formdata = data_submitted();
        if (!$formdata || !confirm_sesskey()) {
            return;
        }

        $data = [];
        $gradinginfo = grade_get_grades($this->course->id, 'mod', 'checkmark',
                $this->checkmark->id, $userid);

        if (!empty($gradinginfo->outcomes)) {
            foreach ($gradinginfo->outcomes as $n => $old) {
                $name = 'outcome_' . $n;
                if (isset($formdata->{$name}[$userid])
                        && $old->grades[$userid]->grade != $formdata->{$name}[$userid]) {
                    $data[$n] = $formdata->{$name}[$userid];
                }
            }
        }
        if (count($data) > 0) {
            grade_update_outcomes('mod/checkmark', $this->course->id, 'mod', 'checkmark',
                    $this->checkmark->id, $userid, $data);
        }

    }

    /**
     * Load the submission object for a particular user
     *
     * @param int $userid The id of the user whose submission we want or 0 in which case USER->id is used
     * @param bool $createnew (optional) defaults to false. If set to true a new submission object
     *                           will be created in the database
     * @return \mod_checkmark\submission The submission
     * @throws dml_exception
     */
    public function get_submission($userid = 0, $createnew = false) {
        global $USER, $DB;
        if (empty($userid)) {
            $userid = $USER->id;
        }

        $submission = mod_checkmark\submission::get_submission($this->checkmark->id, $userid);

        $examples = $this->get_examples();
        if ($submission || !$createnew) {
            return $submission;
        }

        // Create a new and empty submission!
        $newsubmission = \mod_checkmark\submission::get_mock_submission($this->checkmark->id, $userid);
        $sid = $DB->insert_record('checkmark_submissions', $newsubmission);

        $submission = $DB->get_record('checkmark_submissions', ['checkmarkid' => $this->checkmark->id,
                'userid' => $userid, ]);
        $submission = new Submission($sid, $submission);
        $submission->examples = $examples;

        return $submission;
    }

    /**
     * Insert new empty feedback in DB to be updated soon...
     *
     * @param int $userid The id of the user for whom feedback we want or 0 in which case USER->id is used
     * @return object The feedback
     * @throws dml_exception
     */
    public function prepare_new_feedback($userid = 0) {
        global $USER, $DB;

        if (empty($userid)) {
            $userid = $USER->id;
        }

        $feedback = $this->get_feedback($userid);

        if ($feedback !== false) {
            return $feedback; // Return existing feedback if there is one...
        }

        $feedback = new stdClass();
        $feedback->userid = $userid;
        $feedback->checkmarkid = $this->checkmark->id;
        $feedback->grade = -1;
        $feedback->feedback = '';
        $feedback->format = 0;
        $feedback->attendance = null;
        $feedback->graderid = $USER->id;
        $feedback->mailed = 1;
        $feedback->timecreated = time();
        $feedback->timemodified = $feedback->timecreated;

        $feedback->id = $DB->insert_record('checkmark_feedbacks', $feedback);

        return $feedback;
    }

    /**
     * Load the feedback object for a particular user
     *
     * @param int $userid The id of the user for whom feedback we want or 0 in which case USER->id is used
     * @return object|bool The feedback or false if there is none!
     * @throws dml_exception
     */
    public function get_feedback($userid = 0) {
        global $USER, $DB;
        if (empty($userid)) {
            $userid = $USER->id;
        }

        if (!$feedback = $DB->get_record('checkmark_feedbacks', ['checkmarkid' => $this->checkmark->id,
                'userid' => $userid, ])) {
            return false;
        }

        return $feedback;
    }

    /**
     * Return all checkmark submissions by ENROLLED students (even empty)
     *
     * @param string $sort optional field names for the ORDER BY in the sql query
     * @param string $dir optional specifying the sort direction, defaults to DESC
     * @return array The submission objects indexed by id
     */
    public function get_submissions($sort = '', $dir = 'DESC') {
        return checkmark_get_all_submissions($this->checkmark, $sort, $dir);
    }

    /**
     * Counts all real checkmark submissions by ENROLLED students (not empty ones)
     *
     * @param int $groupid optional If nonzero then count is restricted to this group
     * @return int The number of submissions
     */
    public function count_real_submissions($groupid = 0) {
        return checkmark_count_real_submissions($this->cm, $groupid);
    }

    /**
     * Counts all ungrades submissions by ENROLLED students (not empty ones)
     *
     * @return int The number of submissions to be graded!
     */
    public function count_real_ungraded_submissions() {
        return checkmark_count_real_ungraded_submissions($this->cm);
    }

    /**
     * Alerts teachers by email of new or changed checkmarks that need grading
     *
     * First checks whether the option to email teachers is set for this checkmark.
     * Sends an email to ALL teachers in the course (or in the group if using separate groups).
     * Uses the methods email_teachers_text() and email_teachers_html() to construct the content.
     *
     * @param object $submission The submission that has changed
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     */
    public function email_teachers($submission) {
        global $CFG, $DB;

        if (empty($this->checkmark->emailteachers)) {          // No need to do anything!
            return;
        }

        $user = $DB->get_record('user', ['id' => $submission->userid]);

        if ($teachers = $this->get_graders($user)) {

            $strsubmitted = get_string('submitted', 'checkmark');

            foreach ($teachers as $teacher) {
                $info = new stdClass();
                $info->username = fullname($user);
                $info->checkmark = format_string($this->checkmark->name, true);
                $info->url = $CFG->wwwroot . '/mod/checkmark/submissions.php?id=' . $this->cm->id;
                $info->dayupdated = userdate($submission->timemodified, get_string('strftimedate'));
                $info->timeupdated = userdate($submission->timemodified, get_string('strftimetime'));

                $postsubject = $strsubmitted . ': ' . $info->username . ' -> ' . $this->checkmark->name;
                $posttext = $this->email_teachers_text($info);
                $posthtml = ($teacher->mailformat == 1) ? $this->email_teachers_html($info) : '';

                $message = new \core\message\message();
                $message->component = 'mod_checkmark';
                $message->name = 'checkmark_updates';
                $message->courseid = $this->checkmark->course;
                $message->userfrom = $user;
                $message->userto = $teacher;
                $message->subject = $postsubject;
                $message->fullmessage = $posttext;
                $message->fullmessageformat = FORMAT_HTML;
                $message->fullmessagehtml = $posthtml;
                $message->smallmessage = $postsubject;
                $message->notification = 1;
                $message->contexturl = $info->url;
                $message->contexturlname = $info->checkmark;

                message_send($message);
            }
        }
    }

    /**
     * Returns a list of teachers that should be grading given submission
     *
     * @param object $user
     * @return array Array of users able to grade
     * @throws coding_exception
     */
    public function get_graders($user) {
        // Get potential graders!
        $potgraders = get_users_by_capability($this->context, 'mod/checkmark:grade', '', '', '',
                '', '', '', false, false);

        $graders = [];
        if (groups_get_activity_groupmode($this->cm) == SEPARATEGROUPS) {
            // Separate groups are being used!
            if ($groups = groups_get_all_groups($this->course->id, $user->id)) {
                // Try to find all groups!
                foreach ($groups as $group) {
                    foreach ($potgraders as $t) {
                        if ($t->id == $user->id) {
                            continue; // Do not send self!
                        }
                        if (groups_is_member($group->id, $t->id)) {
                            $graders[$t->id] = $t;
                        }
                    }
                }
            } else {
                // User not in group, try to find graders without group!
                foreach ($potgraders as $t) {
                    if ($t->id == $user->id) {
                        continue; // Do not send to one self!
                    }
                    if (!groups_get_all_groups($this->course->id, $t->id)) { // Ugly hack!
                        $graders[$t->id] = $t;
                    }
                }
            }
        } else {
            $context = context_course::instance($this->course->id);
            foreach ($potgraders as $t) {

                if ($t->id == $user->id || !is_enrolled($context, $t->id, '', true)) {
                    continue; // Do not send to one self or to graders not part of the course!
                }
                $graders[$t->id] = $t;
            }
        }
        return $graders;
    }

    /**
     * Creates the text content for emails to teachers
     *
     * @param object $info The info used by the 'emailteachermail' language string
     * @return string Plain-Text snippet to use in messages
     * @throws coding_exception
     */
    public function email_teachers_text($info) {
        $posttext = format_string($this->course->shortname) . ' -> ' .
                get_string('modulenameplural', 'checkmark') . ' -> ' .
                format_string($this->checkmark->name) . "\n";
        $posttext .= "---------------------------------------------------------------------\n";
        $posttext .= get_string('emailteachermail', 'checkmark', $info) . "\n";
        $posttext .= "---------------------------------------------------------------------\n";
        return $posttext;
    }

    /**
     * Creates the html content for emails to teachers
     *
     * TODO replace with template in 3.4
     *
     * @param object $info The info used by the 'emailteachermailhtml' language string
     * @return string HTML snippet to use in messages
     * @throws coding_exception
     */
    public function email_teachers_html($info) {
        global $CFG;
        $posthtml = '<p><font face="sans-serif">' .
                '<a href="' . $CFG->wwwroot . '/course/view.php?id=' . $this->course->id . '">' .
                format_string($this->course->shortname) . '</a> ->' .
                '<a href="' . $CFG->wwwroot . '/mod/checkmark/index.php?id=' .
                $this->course->id . '">' . get_string('modulenameplural', 'checkmark') . '</a> ->' .
                '<a href="' . $CFG->wwwroot . '/mod/checkmark/view.php?id=' . $this->cm->id . '">' .
                format_string($this->checkmark->name) . '</a></font></p>';
        $posthtml .= '<hr /><font face="sans-serif">';
        $posthtml .= '<p>' . get_string('emailteachermailhtml', 'checkmark', $info) . '</p>';
        $posthtml .= '</font><hr />';
        return $posthtml;
    }

    /**
     * Prints out the users submission
     *
     * @param int $userid (optional) id of the user. If 0 then $USER->id is used.
     * @param bool $return (optional) defaults to false. If true the html snippet is returned
     * @return string|bool HTML snippet if $return is true or true if $return is anything else
     * @throws dml_exception
     * @throws coding_exception
     */
    public function print_user_submission($userid = 0, $return = false) {
        global $USER;

        if (!$userid) {
            if (!isloggedin()) {
                return '';
            }
            $userid = $USER->id;
        }

        $submission = $this->get_submission($userid);
        if (!$submission) {
            $submission = \mod_checkmark\submission::get_mock_submission($this->checkmark->id);
        }

        return $submission->render();
    }

    /**
     * Returns true if the student is allowed to submit
     *
     * Checks that the checkmark has started and, cut-off-date or duedate hasn't
     * passed already. If $closeOnly is true only the cut-off-date is considered
     *
     * @param bool $closeonly If true only the cut-off-date is considered
     * @return bool
     */
    public function isopen(bool $closeonly = false) {
        $time = time();

        $timeavailable = $this->checkmark->timeavailable;
        $cutoffdate = $this->checkmark->cutoffdate;
        if ($this->overrides) {
            if ($this->overrides->timeavailable !== null) {
                $timeavailable = $this->overrides->timeavailable;
            }
            if ($this->overrides->cutoffdate !== null) {
                $cutoffdate = $this->overrides->cutoffdate;
            }
        }

        if (empty($timeavailable) || $closeonly) {
            if (empty($cutoffdate)) {
                return true;
            } else {
                return ($time <= $cutoffdate);
            }
        } else {
            if (empty($cutoffdate)) {
                return ($timeavailable <= $time);
            } else {
                return (($timeavailable <= $time) && ($time <= $cutoffdate));
            }
        }
    }

    /**
     * Return an outline of the user's interaction with the checkmark
     *
     * The default method returns the grade and timemodified
     *
     * @param object $grade Grade object
     * @return object with properties ->info and ->time
     * @throws coding_exception
     */
    public function user_outline($grade) {

        $result = new stdClass();
        $result->info = get_string('modgrade', 'grades') . ': ' . $grade->str_long_grade;
        $result->time = $grade->dategraded;
        return $result;
    }

    /**
     * Print complete information about the user's interaction with the checkmark
     *
     * @param object $user User object
     * @param object $grade (optional) Grade object
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function user_complete($user, $grade = null) {
        global $OUTPUT;
        if ($grade) {
            echo $OUTPUT->container(get_string('modgrade', 'grades') . ': ' . $grade->str_long_grade);
            if ($grade->str_feedback) {
                echo $OUTPUT->container(get_string('feedback') . ': ' . $grade->str_feedback);
            }
        }

        echo $OUTPUT->box_start();

        if ($submission = $this->get_submission($user->id)) {
            echo get_string('lastmodified') . ': ';
            echo userdate($submission->get_timemodified());
            echo $this->display_lateness($submission->get_timemodified(), $user->id);
        } else {
            print_string('notsubmittedyet', 'checkmark');
        }

        echo html_writer::empty_tag('br');

        $feedback = $this->get_feedback($user->id);
        if ($feedback && !empty($feedback->grader)) {
            $this->view_feedback($feedback);
        }

        echo $OUTPUT->box_end();
    }

    /**
     * Return a string indicating how late a submission is
     *
     * @param int $timesubmitted Submissions timestamp to compare
     * @param int $userid (optional) if lateness should be displayed for another user!
     * @return string HTML snippet containing info about submission time
     */
    public function display_lateness($timesubmitted, $userid = 0) {
        if (!empty($userid)) {
            $overrides = checkmark_get_overridden_dates($this->checkmark->id, $userid, $this->checkmark->course);
        } else {
            $overrides = $this->overrides;
        }

        if ($overrides && $overrides->timedue !== null) {
            return checkmark_display_lateness($timesubmitted, $overrides->timedue);
        }

        return checkmark_display_lateness($timesubmitted, $this->checkmark->timedue);
    }

    /**
     * Reset all submissions
     *
     * @param object $data info for which instance to reset the userdata
     * @return array status array
     * @throws dml_exception
     * @throws coding_exception
     */
    public function reset_userdata($data) {
        global $DB;

        if (!$DB->count_records('checkmark', ['course' => $data->courseid])) {
            return []; // No checkmarks present!
        }

        $componentstr = get_string('modulenameplural', 'checkmark');
        $status = [];

        if (!empty($data->reset_checkmark_submissions)) {
            $checkmarks = $DB->get_fieldset_select('checkmark', 'id', 'course = :course', ['course' => $data->courseid]);
            if (!empty($checkmarks) && is_array($checkmarks)) {
                list($checkmarksql, $params) = $DB->get_in_or_equal($checkmarks);

                $submissions = $DB->get_fieldset_sql('SELECT id
                                                        FROM {checkmark_submissions}
                                                       WHERE checkmarkid ' . $checkmarksql, $params);
                $examples = $DB->get_fieldset_sql('SELECT id
                                                     FROM {checkmark_examples}
                                                    WHERE checkmarkid ' . $checkmarksql, $params);
                $DB->delete_records_select('checkmark_submissions',
                        'checkmarkid ' . $checkmarksql, $params);
                $DB->delete_records_select('checkmark_feedbacks',
                        'checkmarkid ' . $checkmarksql, $params);
                if (!count($submissions)) {
                    $ssql = ' = NULL';
                    $sparams = [];
                } else {
                    list($ssql, $sparams) = $DB->get_in_or_equal($submissions, SQL_PARAMS_NAMED);
                }
                if (!count($examples)) {
                    $esql = ' = NULL';
                    $eparams = [];
                } else {
                    list($esql, $eparams) = $DB->get_in_or_equal($examples, SQL_PARAMS_NAMED);
                }

                $DB->delete_records_select('checkmark_checks', 'submissionid ' . $ssql . ' OR exampleid ' . $esql,
                        array_merge($sparams, $eparams));

                $status[] = ['component' => $componentstr,
                        'item' => get_string('deleteallsubmissions', 'checkmark'),
                        'error' => false,
                    ];

                if (empty($data->reset_gradebook_grades)) {
                    // Remove all grades from gradebook!
                    checkmark_reset_gradebook($data->courseid);
                }
            }
        }

        if ($data->reset_checkmark_overrides) {
            $checkmarks = $DB->get_fieldset_select('checkmark', 'id', 'course = :course', ['course' => $data->courseid]);
            if (!empty($checkmarks) && is_array($checkmarks)) {
                list($checkmarksql, $params) = $DB->get_in_or_equal($checkmarks);
                $DB->delete_records_select('checkmark_overrides', 'checkmarkid ' . $checkmarksql, $params);

                $status[] = ['component' => $componentstr,
                        'item' => get_string('deletealloverrides', 'checkmark'),
                        'error' => false,
                    ];
            }
        }

        // Updating dates - shift may be negative too!
        if ($data->timeshift) {
            shift_course_mod_dates('checkmark', ['timedue', 'timeavailable', 'cutoffdate'],
                    $data->timeshift, $data->course);

            $status[] = ['component' => $componentstr,
                    'item' => get_string('datechanged'),
                    'error' => false,
                ];
        }

        return $status;
    }

    /**
     * Splits a given checks array by users into a three-dimensional array
     *
     * @param array $checks Return 'checks' array of submissionstable
     * @return array Three-dimensional array split by users
     */
    private function split_by_user(array $checks) {
        $splitcheckarray = [];
        foreach ($checks as $check => $value) {
            $checkfragments = explode('_', $check);
            $splitcheckarray[$checkfragments[0]][$checkfragments[1]] = $value;
        }
        return $splitcheckarray;
    }

    /**
     * Get the settings for the current instance of this assignment
     * @param int|null $userid the id of the user to load the assign instance for.
     * @return stdClass The settings
     */
    public function get_instance(int|null $userid = null): stdClass {
        global $USER;
        $userid = $userid ?? $USER->id;

        $this->instance = $this->get_default_instance();

        // If we have the user instance already, just return it.
        if (isset($this->userinstances[$userid])) {
            return $this->userinstances[$userid];
        }

        // Calculate properties which vary per user.
        $this->userinstances[$userid] = $this->calculate_properties($this->instance, $userid);
        return $this->userinstances[$userid];
    }

    /**
     * Get the settings for the current instance of this assignment.
     *
     * @return stdClass The settings
     * @throws dml_exception
     */
    public function get_default_instance() {
        global $DB;
        if (!$this->instance && $this->cm) {
            $params = ['id' => $this->cm->instance];
            $this->instance = $DB->get_record('checkmark', $params, '*', MUST_EXIST);

            $this->userinstances = [];
        }
        return $this->instance;
    }

    /**
     * Get the current course module.
     *
     * @return cm_info|null The course module or null if not known
     */
    public function get_course_module() {
        if ($this->cm) {
            return $this->cm;
        }
        if (!$this->context) {
            return null;
        }

        if ($this->context->contextlevel == CONTEXT_MODULE) {
            $modinfo = get_fast_modinfo($this->course);
            $this->cm = $modinfo->get_cm($this->context->instanceid);
            return $this->cm;
        }
        return null;
    }

    /**
     * Calculates and updates various properties based on the specified user.
     *
     * @param stdClass $record the raw assign record.
     * @param int $userid the id of the user to calculate the properties for.
     * @return stdClass a new record having calculated properties.
     */
    private function calculate_properties(\stdClass $record, int $userid): \stdClass {
        $record = clone ($record);

        // Relative dates.
        if (!empty($record->duedate)) {
            $course = $this->get_course();
            $usercoursedates = course_get_course_dates_for_user_id($course, $userid);
            if ($usercoursedates['start']) {
                $userprops = ['duedate' => $record->duedate + $usercoursedates['startoffset']];
                $record = (object) array_merge((array) $record, (array) $userprops);
            }
        }
        return $record;
    }

    /**
     * Bulk remove grades from graded submissions for selected users
     *
     * @param int $filter (optional) which entrys to filter (self::FILTER_ALL, self::FILTER_REQUIRE_GRADING)
     * @param int[] $selected (optional) selected users, used if filter equals self::FILTER_SELECTED
     * @return int|array 0 if everything's ok, otherwise error code
     * @throws coding_exception
     * @throws dml_exception
     */
    public function remove_grades($filter = self::FILTER_ALL, $selected = []) {
        return $this->bulk_remove_grades($selected, 'grade');
    }

    /**
     * Bulk remove presentation grades from presentation grades for selected users
     *
     * @param int $filter (optional) which entrys to filter (self::FILTER_ALL, self::FILTER_REQUIRE_GRADING)
     * @param int[] $selected (optional) selected users, used if filter equals self::FILTER_SELECTED
     * @return int|array 0 if everything's ok, otherwise error code
     * @throws coding_exception
     * @throws dml_exception
     */
    public function remove_presentation_grades($filter = self::FILTER_ALL, $selected = []) {
        return $this->bulk_remove_grades($selected, 'presentationgrade');
    }

    /**
     * Bulk remove specified grade type from graded submissions for selected users
     *
     * @param int[] $selected Selected users
     * @param string $gradetype Type of grade to remove ('grade' or 'presentationgrade')
     * @return int|array 0 if everything's ok, otherwise error code
     * @throws coding_exception
     * @throws dml_exception
     */
    private function bulk_remove_grades($selected, $gradetype) {
        global $DB;

        $result = ['status' => GRADE_UPDATE_FAILED, 'updated' => 0];

        if (empty($selected)) {
            return $result;
        }

        $params = [];
        list($sqluserids, $userparams) = $DB->get_in_or_equal($selected, SQL_PARAMS_NAMED, 'user');
        $params = array_merge_recursive($params, $userparams);
        $params['checkmarkid'] = $this->checkmark->id;

        $sql = "SELECT u.id
              FROM {user} u
         LEFT JOIN {checkmark_feedbacks} f ON u.id = f.userid AND f.checkmarkid = :checkmarkid
             WHERE u.deleted = 0 AND u.id " . $sqluserids;

        $users = $DB->get_records_sql($sql, $params);

        if (empty($users)) {
            return $result;
        }

        // Reset time so that the status changes back to "Submitted for grading".
        $resettime = 0;
        foreach ($users as $user) {
            $feedback = $this->get_feedback($user->id);
            if ($feedback) {
                $feedback->$gradetype = -1;
                $feedback->timemodified = $resettime;
                // We update the feedback instead of deleting it to keep the feedback comment.
                $DB->update_record('checkmark_feedbacks', $feedback);
                $result['updated']++;
            }

            // Update in gradebook.
            if ($gradetype == 'grade') {
                checkmark_update_grades($this->checkmark, $user->id);
            }
            if ($gradetype == 'presentationgrade') {
                checkmark_update_presentation_grades($this->checkmark, $user->id);
            }
        }

        $result['status'] = GRADE_UPDATE_OK;
        return $result;
    }
}

/**
 * Return summary of the number of settings override that exist.
 *
 * To get a nice display of this, see the quiz_override_summary_links()
 * quiz renderer method.
 *
 * @param stdClass $checkmark the quiz settings. Only $quiz->id is used at the moment.
 * @param stdClass $cm the cm object. Only $cm->course, $cm->groupmode and
 *      $cm->groupingid fields are used at the moment.
 * @param int $currentgroup if there is a concept of current group where this method is being called
 *      (e.g. a report) pass it in here. Default 0 which means no current group.
 * @return array like 'group' => 3, 'user' => 12] where 3 is the number of group overrides,
 *      and 12 is the number of user ones.
 * @throws coding_exception
 * @throws dml_exception
 */
function checkmark_override_summary(stdClass $checkmark, stdClass $cm, int $currentgroup = 0): array {
    global $DB;

    if ($currentgroup) {
        // Currently only interested in one group.
        $groupcount = $DB->count_records('checkmark_overrides', ['checkmarkid' => $checkmark->id, 'groupid' => $currentgroup]);
        $usercount = $DB->count_records_sql("
                SELECT COUNT(1)
                  FROM {checkmark_overrides} o
                  JOIN {groups_members} gm ON o.userid = gm.userid
                 WHERE o.checkmarkid = ?
                   AND gm.groupid = ?
                    ", [$checkmark->id, $currentgroup]);
        return ['group' => $groupcount, 'user' => $usercount, 'mode' => 'onegroup'];
    }

    $quizgroupmode = groups_get_activity_groupmode($cm);
    $accessallgroups = ($quizgroupmode == NOGROUPS) ||
    has_capability('moodle/site:accessallgroups', context_module::instance($cm->id));

    if ($accessallgroups) {
        // User can see all groups.
        $groupcount = $DB->count_records_select('checkmark_overrides',
            'checkmarkid = ? AND groupid IS NOT NULL', [$checkmark->id]);
        $usercount = $DB->count_records_select('checkmark_overrides',
            'checkmarkid = ? AND userid IS NOT NULL', [$checkmark->id]);
        return ['group' => $groupcount, 'user' => $usercount, 'mode' => 'allgroups'];

    } else {
        // User can only see groups they are in.
        $groups = groups_get_activity_allowed_groups($cm);
        if (!$groups) {
            return ['group' => 0, 'user' => 0, 'mode' => 'somegroups'];
        }

        list($groupidtest, $params) = $DB->get_in_or_equal(array_keys($groups));
        $params[] = $checkmark->id;

        $groupcount = $DB->count_records_select('checkmark_overrides',
            "groupid $groupidtest AND checkmarkid = ?", $params);
        $usercount = $DB->count_records_sql("
                SELECT COUNT(1)
                  FROM {checkmark_overrides} o
                  JOIN {groups_members} gm ON o.userid = gm.userid
                 WHERE gm.groupid $groupidtest
                   AND o.checkmarkid = ?
               ", $params);

        return ['group' => $groupcount, 'user' => $usercount, 'mode' => 'somegroups'];
    }
}
