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
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/checkmark/lib.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->dirroot.'/mod/checkmark/submission_form.php');
require_once($CFG->dirroot.'/mod/checkmark/grading_form.php');

// Include eventslib.php!
require_once($CFG->libdir.'/eventslib.php');
// Include formslib.php!
require_once($CFG->libdir.'/formslib.php');
// Include calendar/lib.php!
require_once($CFG->dirroot.'/calendar/lib.php');

/**
 * This class provides all the basic functionality for an checkmark-module
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class checkmark {

    /** FILTER_ALL */
    const FILTER_ALL             = 1;
    /** FILTER_SUBMITTED */
    const FILTER_SUBMITTED       = 2;
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

    /** DELIMITER Used to connect example-names, example-grades, submission-examplenumbers! */
    const DELIMITER = ',';

    /** EMPTYBOX UTF-8 empty box = &#x2610; = '☐'! */
    const EMPTYBOX = '&#x2610;';
    /** CHECKEDBOX UTF-8 box with x-mark = &#x2612; = '☒'! */
    const CHECKEDBOX = '&#x2612;';

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
     */
    public function __construct($cmid='staticonly', $checkmark=null, $cm=null, $course=null) {
        global $COURSE, $DB;

        if ($cmid == 'staticonly') {
            // Use static functions only!
            return;
        }

        if ($cm) {
            $this->cm = $cm;
        } else if (! $this->cm = get_coursemodule_from_id('checkmark', $cmid)) {
            print_error('invalidcoursemodule');
        }

        $this->context = context_module::instance($this->cm->id);

        if ($course) {
            $this->course = $course;
        } else if ($this->cm->course == $COURSE->id) {
            $this->course = $COURSE;
        } else if (! $this->course = $DB->get_record('course', array('id' => $this->cm->course))) {
            print_error('invalidid', 'checkmark');
        }

        if ($checkmark) {
            $this->checkmark = $checkmark;
        } else if (! $this->checkmark = $DB->get_record('checkmark',
                                                        array('id' => $this->cm->instance))) {
            print_error('invalidid', 'checkmark');
        }

        // Ensure compatibility with modedit checkmark obj!
        $this->checkmark->cmidnumber = $this->cm->idnumber;
        $this->checkmark->course   = $this->course->id;

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
     * Standardizes course module, checkmark and course data objects and checks for login state!
     *
     * @param int $id course module id or 0 (either $id or $c have to be set!)
     * @param int $c checkmark instance id or 0 (either $id or $c have to be set!)
     * @param moodle_url $url current url of the viewed page
     * @return object[] Returns array with coursemodule, checkmark and course objects
     */
    public static function init_checks($id, $c, $url) {
        global $PAGE, $DB;

        if ($id) {
            if (!$cm = get_coursemodule_from_id('checkmark', $id)) {
                print_error('invalidcoursemodule');
            }
            if (!$checkmark = $DB->get_record('checkmark', array('id' => $cm->instance))) {
                print_error('invalidid', 'checkmark');
            }
            if (!$course = $DB->get_record('course', array('id' => $checkmark->course))) {
                print_error('coursemisconf', 'checkmark');
            }
            $url->param('id', $id);
        } else {
            if (!$checkmark = $DB->get_record('checkmark', array('id' => $c))) {
                print_error('invalidcoursemodule');
            }
            if (!$course = $DB->get_record('course', array('id' => $checkmark->course))) {
                print_error('coursemisconf', 'checkmark');
            }
            if (!$cm = get_coursemodule_from_instance('checkmark', $checkmark->id, $course->id)) {
                print_error('invalidcoursemodule');
            }
            $url->param('id', $cm->id);
        }

        $PAGE->set_url($url);
        require_login($course->id, false, $cm);

        return array($cm, $checkmark, $course);
    }

    /**
     * Get the examples for this checkmark from the DB
     *
     * Adds the prefix if set and flexible naming is used
     *
     * @return object[] checkmark's examples from the DB (raw records)
     */
    public function get_examples() {
        global $DB;

        if (!isset($this->checkmark->examples)) {
            $examples = $DB->get_records('checkmark_examples', array('checkmarkid' => $this->checkmark->id));

            $exampleprefix = $this->checkmark->exampleprefix;

            foreach ($examples as $key => $cur) {
                $examples[$key]->shortname = $cur->name;
                $examples[$key]->name = $exampleprefix.$cur->name;
            }

            $this->checkmark->examples = $examples;
        }

        return $this->checkmark->examples;
    }

    /**
     * Get the examples for this checkmark from the DB
     *
     * Adds the prefix if set and flexible naming is used
     *
     * @param object|int $checkmarkid the checkmark object containing ID or the ID itself
     * @return object[] checkmark's examples from the DB (raw records)
     */
    public static function get_examples_static($checkmarkid) {
        global $DB;

        $examples = $DB->get_records('checkmark_examples', array('checkmarkid' => $checkmarkid));

        $exampleprefix = $DB->get_field('checkmark', 'exampleprefix', array('id' => $checkmarkid));

        foreach ($examples as $key => $cur) {
            $examples[$key]->shortname = $cur->name;
            $examples[$key]->name = $exampleprefix.$cur->name;
        }

        return $examples;
    }

    /**
     * print_example_preview() prints a preview of the set examples
     *
     * TODO use a function to get a empty submission and checkmark::add_submission_elements() instead!
     */
    public function print_example_preview() {
        global $USER;
        $context = context_module::instance($this->cm->id);
        require_capability('mod/checkmark:view_preview', $context, $USER);

        // TODO we use a form here for now, but plan to use a better template in the future!
        $mform = new MoodleQuickForm('submission', 'get', '', '');

        $mform->addElement('header', 'heading', get_string('example_preview_title', 'checkmark'));
        $mform->addHelpButton('heading', 'example_preview_title', 'checkmark');

        $examples = $this->get_examples();

        $data = new stdClass();
        $data->examples = [];
        foreach ($examples as $example) {
            switch ($example->grade) {
                case '1':
                    $pointsstring = get_string('strpoint', 'checkmark');
                break;
                case '2':
                default:
                    $pointsstring = get_string('strpoints', 'checkmark');
                break;
            }
            $mform->addElement('checkbox', $example->shortname, '', $example->name.' ('.$example->grade.' '.$pointsstring.')');
            $mform->freeze($example->shortname);
        }
        $mform->display();
    }

    /**
     * print_summary() returns a short statistic over the actual checked examples in this checkmark
     * You've checked out X from a maximum of Y examples. (A out of B points)
     *
     * @return string short summary
     */
    public function print_summary() {
        global $USER;

        $submission = $this->get_submission($USER->id, false); // Get the submission!

        $a = checkmark_getsubmissionstats($submission, $this->checkmark);

        $output = html_writer::tag('div', get_string('checkmark_summary', 'checkmark', $a),
                                   array('class' => 'chkmrksubmissionsummary')).
                  html_writer::empty_tag('br');

        return $output;
    }

    /**
     * print_student_answer($userid) returns a short HTML-coded string
     * with the checked examples in black an unchecked ones lined through and in a light grey.
     *
     * @param int $userid The user-ID to print the student anwer for.
     * @return string checked examples
     */
    public function print_student_answer($userid) {
        $output = '';

        if (!$submission = $this->get_submission($userid)) {
            return get_string('nosubmission', 'checkmark');
        }

        foreach ($submission->examples as $example) {
            if ($output != '') {
                $output .= ', ';
            } else {
                $output .= get_string('strexamples', 'checkmark').': ';
            }
            if ($example->state) { // Is it checked?
                $class = 'checked';
            } else {
                $class = 'unchecked';
            }
            $output .= html_writer::tag('span', $example->shortname,
                                        array('class' => $class));
        }

        // Wrapper!
        return html_writer::tag('div', $output, array('class' => 'examplelist'));
    }

    /**
     * Every view for checkmark (teacher/student/etc.)
     */
    public function view() {
        global $OUTPUT, $USER, $PAGE;

        $edit  = optional_param('edit', 0, PARAM_BOOL);
        $saved = optional_param('saved', 0, PARAM_BOOL);

        $context = context_module::instance($this->cm->id);
        require_capability('mod/checkmark:view', $context);

        /* TRIGGER THE VIEW EVENT */
        $event = \mod_checkmark\event\course_module_viewed::create(array(
            'objectid' => $this->cm->instance,
            'context'  => context_module::instance($this->cm->id),
            'other'    => array(
                'name' => $this->checkmark->name,
            ),
        ));
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
            $editable = $this->isopen()
                        && (!$submission || $this->checkmark->resubmit || ($feedback === false) );
            if (groups_get_activity_groupmode($this->cm, $this->course) != NOGROUPS) {
                $editable = $editable && groups_has_membership($this->cm);
            }
        }
        $editmode = ($editable and $edit);

        $data = new stdClass();
        $data->id         = $this->cm->id;
        $data->checkmarkid = $this->checkmark->id;
        $data->edit       = $editmode;
        $data->examples   = $this->get_examples();
        if ($submission) {
            $data->sid        = $submission->id;
        } else {
            $data->sid        = null;
        }

        if (!empty($submission->examples)) {
            foreach ($submission->examples as $key => $example) {
                $name = 'example'.$key;
                $data->$name = empty($example->state) ? 0 : 1;
            }
        }
        $mform = new checkmark_submission_form(null, $data);

        if ($editmode) {
            // Prepare form and process submitted data!

            if ($mform->is_cancelled()) {
                redirect(new moodle_url($PAGE->url, array('id' => $this->cm->id)));
            }

            if ($formdata = $mform->get_data()) {

                // Create the submission if needed & return its id!
                $submission = $this->get_submission($USER->id, true);

                foreach ($submission->examples as $key => $example) {
                    $name = 'example'.$key;
                    if (isset($formdata->{$name}) && ($formdata->{$name} != 0)) {
                        $submission->examples[$key]->state = 1;
                    } else {
                        $submission->examples[$key]->state = 0;
                    }
                }

                $this->update_submission($submission);

                $this->email_teachers($submission);

                // Trigger the event!
                \mod_checkmark\event\submission_updated::create_from_object($this->cm, $submission)->trigger();

                // Redirect to get updated submission date!
                redirect(new moodle_url($PAGE->url, array('id' => $this->cm->id, 'saved' => 1)));
            }
        }

        // Print header, etc. and display form if needed!
        if ($editmode) {
            $this->view_header(get_string('editmysubmission', 'checkmark'));
        } else {
            $this->view_header();
        }

        if ($saved) {
            echo $OUTPUT->box_start('generalbox', 'notification');
            echo $OUTPUT->notification(get_string('submissionsaved', 'checkmark'), 'notifysuccess');
            echo $OUTPUT->box_end();
        }

        $this->view_intro();
        echo "\n";
        $this->view_dates();
        echo "\n";
        $this->view_attendancehint();
        echo "\n";

        if ($editmode && !empty($mform)) {
            echo $OUTPUT->box_start('generalbox boxaligncenter', 'checkmarkform');
            echo $this->print_summary();
            $mform->display();
            echo $OUTPUT->box_end();
            echo "\n";
        } else {
            echo $OUTPUT->box_start('generalbox boxaligncenter', 'checkmark');
            // Display overview!
            if (!empty($submission) && has_capability('mod/checkmark:submit', $context, $USER, false)) {
                echo $this->print_summary();
                echo html_writer::start_tag('div', array('class' => 'mform'));
                echo html_writer::start_tag('div', array('class' => 'clearfix'));
                echo $this->print_user_submission($USER->id, true);
                echo html_writer::end_tag('div');
                echo html_writer::end_tag('div');

            } else if (has_capability('mod/checkmark:submit', $context, $USER, false)) {
                // No submission present!
                echo html_writer::tag('div', get_string('nosubmission', 'checkmark'));
                $this->print_example_preview();
            } else if (has_capability('mod/checkmark:view_preview', $context)) {
                $this->print_example_preview();
            } else {
                /*
                 * If he isn't allowed to view the preview and has no submission
                 * tell him he has no submission!
                 */
                echo html_writer::tag('div', get_string('nosubmission', 'checkmark'));
            }
            echo $OUTPUT->box_end();
            echo "\n";
        }

        if (!$editmode && $editable && has_capability('mod/checkmark:submit', $context, $USER, false)) {
            if (!empty($submission)) {
                $submitbutton = 'editmysubmission';
            } else {
                $submitbutton = 'addsubmission';
            }
            $url = new moodle_url('view.php',
                                 array('id' => $this->cm->id, 'edit' => '1'));
            $button = $OUTPUT->single_button($url, get_string($submitbutton, 'checkmark'), 'post', array('primary' => true));
            echo html_writer::tag('div', $button, array('class' => 'centered'));
            echo "\n";
        }

        $this->view_feedback();
        echo "\n";
        $this->view_footer();
        echo "\n";
    }

    /**
     * Display the header and top of a page
     *
     * This is used by the view() method to print the header of view.php but
     * it can be used on other pages in which case the string to denote the
     * page in the navigation trail should be passed as an argument
     *
     * @param string $subpage Description of subpage to be used in navigation trail
     */
    public function view_header($subpage='') {
        global $CFG, $PAGE, $OUTPUT;

        if ($subpage) {
            $PAGE->navbar->add($subpage);
        }

        $pagetitle = strip_tags($this->course->shortname.': '.get_string('modulename', 'checkmark').
                                ': '.format_string($this->checkmark->name, true));
        $PAGE->set_title($pagetitle);
        $PAGE->set_heading($this->course->fullname);

        echo $OUTPUT->header();

        groups_print_activity_menu($this->cm,
                                   $CFG->wwwroot . '/mod/checkmark/view.php?id=' . $this->cm->id);

        echo html_writer::tag('div', $this->submittedlink(), array('class' => 'text-info'));
        echo html_writer::tag('div', '', array('class' => 'clearer'));
    }

    /**
     * Display the checkmark intro
     *
     * The default implementation prints the checkmark description in a box
     */
    public function view_intro() {
        global $OUTPUT;
        if ($this->checkmark->alwaysshowdescription || time() > $this->checkmark->timeavailable) {
            if (!empty($this->checkmark->intro)) {
                echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
                echo format_module_intro('checkmark', $this->checkmark, $this->cm->id);
                echo $OUTPUT->box_end();
            }
        }
        plagiarism_print_disclosure($this->cm->id);
    }

    /**
     * Display the checkmark dates
     *
     * Prints the checkmark start and end dates in a box.
     */
    public function view_dates() {
        global $OUTPUT;
        if (!$this->checkmark->timeavailable && !$this->checkmark->timedue) {
            return;
        }

        echo $OUTPUT->box_start('generalbox boxaligncenter', 'dates');
        echo html_writer::start_tag('table');
        if ($this->checkmark->timeavailable) {
            $row = html_writer::tag('td', get_string('availabledate', 'checkmark').':',
                                    array('class' => 'c0')).
                   html_writer::tag('td', userdate($this->checkmark->timeavailable),
                                    array('class' => 'c1'));
            echo html_writer::tag('tr', $row);
        }
        if ($this->checkmark->timedue) {
            $row = html_writer::tag('td', get_string('duedate', 'checkmark').':',
                                    array('class' => 'c0')).
                   html_writer::tag('td', userdate($this->checkmark->timedue),
                                    array('class' => 'c1'));
            echo html_writer::tag('tr', $row);
        }
        echo html_writer::end_tag('table');
        echo $OUTPUT->box_end();
    }

    /**
     * Display the hint if attendance is tracked and linked to grades
     */
    public function view_attendancehint() {
        global $OUTPUT;
        if (!$this->checkmark->trackattendance || !$this->checkmark->attendancegradelink) {
            return;
        }

        echo $OUTPUT->box(get_string('attendancegradelink_hint', 'checkmark'), 'generalbox', 'attendancehint');
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
     */
    public function view_feedback($feedback=null) {
        global $USER, $CFG, $DB, $OUTPUT;
        require_once($CFG->libdir.'/gradelib.php');

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
        $gradedby   = $grade->usermodified;
        $showfeedback = false;
        if (empty($gradedby)) {
            // Only show attendance or presentationgrade!
            $gradedby = $feedback->graderid;
            $dategraded = $feedback->timemodified;
            if (!$grader = $DB->get_record('user', array('id' => $gradedby))) {
                print_error('cannotfindteacher');
            }
        } else {
            // We need the teacher info!
            if (!$grader = $DB->get_record('user', array('id' => $gradedby))) {
                print_error('cannotfindteacher');
            }
            $showfeedback = true;
        }

        // Print the feedback!
        echo $OUTPUT->heading(get_string('feedbackfromteacher', 'checkmark', fullname($grader)));
        if ($grader) {
            $userpicture = $OUTPUT->user_picture($grader);
            $from = html_writer::tag('div', html_writer::tag('strong', fullname($grader)), array('class' => 'fullname'));
        } else {
            $userpicture = '';
            $from = '';
        }
        $from .= html_writer::tag('div', html_writer::tag('strong', userdate($dategraded)), array('class' => 'time'));
        $topic = html_writer::tag('div', $from, array('class' => 'from'));
        $row = html_writer::tag('td', $userpicture, array('class' => 'left picture'));
        $row .= html_writer::tag('td', $topic, array('class' => 'topic'));
        $tablecontent = html_writer::tag('tr', $row);
        // Second row!
        if ($showfeedback) {
            if ($this->checkmark->grade) {
                $content = html_writer::tag('div', html_writer::tag('strong', get_string('grade').': ').$grade->str_long_grade,
                                            array('class' => 'grade'));
            } else {
                $content = '';
            }
            $content .= html_writer::tag('div', '', array('class' => 'clearer')).
                        html_writer::tag('div', $grade->str_feedback, array('class' => 'comment'));
            $row = html_writer::tag('td', $content, array('class' => 'content', 'colspan' => 2));
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
            $attendance = checkmark_get_attendance_symbol($feedback->attendance).$attendancestr;
            // Third row --> attendance info!
            $row = html_writer::tag('td', html_writer::tag('strong', get_string('attendance', 'checkmark').': ').$attendance,
                                    array('class' => 'content', 'colspan' => 2));
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
                $content = html_writer::tag('div', html_writer::tag('strong', get_string('presentationgrade', 'checkmark').': ').
                                                   $presgrade, array('class' => 'grade')).
                           html_writer::tag('div', '', array('class' => 'clearer')).
                           html_writer::tag('div', $presfeedback, array('class' => 'comment'));
                $row = html_writer::tag('td', $content, array('class' => 'content', 'colspan' => 2));
                $tablecontent .= html_writer::tag('tr', $row);
            }
        }

        echo html_writer::tag('table', $tablecontent, array('cellspacing' => 0, 'class' => 'feedback'));
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
     */
    public function submittedlink($allgroups=false) {
        global $USER, $CFG;

        $submitted = '';
        $urlbase = $CFG->wwwroot.'/mod/checkmark/';

        $context = context_module::instance($this->cm->id);
        if (has_capability('mod/checkmark:grade', $context)) {
            if ($allgroups and has_capability('moodle/site:accessallgroups', $context)) {
                $group = 0;
            } else {
                $group = groups_get_activity_group($this->cm);
            }
            if ($cnt = $this->count_real_submissions($group)) {
                $submitted = html_writer::tag('a', get_string('viewsubmissions', 'checkmark', $cnt),
                                              array('href'  => $urlbase.'submissions.php?id='.$this->cm->id,
                                                    'id' => 'submissions'));
            } else {
                $submitted = html_writer::tag('a', get_string('noattempts', 'checkmark'),
                                              array('href'  => $urlbase.'submissions.php?id='.$this->cm->id,
                                                    'id' => 'submissions'));
            }
        } else {
            if (isloggedin()) {
                if ($submission = $this->get_submission($USER->id)) {
                    if ($submission->timemodified) {
                        $date = userdate($submission->timemodified);
                        if ($submission->timemodified <= $this->checkmark->timedue
                            || empty($this->checkmark->timedue)) {
                            $submitted = html_writer::tag('span', $date, array('class' => 'text-success'));
                        } else {
                            $submitted = html_writer::tag('span', $date, array('class' => 'text-error'));
                        }
                    }
                }
            }
        }

        return $submitted;
    }

    /**
     * Calculate the grade corresponding to the users checks
     *
     * @param int $userid the user's ID
     * @return int the user's grade according to his checks
     */
    public function calculate_grade($userid) {
        global $USER;
        $grade = 0;

        if (!isset($userid)) {
            $userid = $USER->id;
        }

        $submission = $this->get_submission($userid, false); // Get the submission!

        if ($submission) {
            foreach ($submission->examples as $example) {
                if ($example->state) { // Is it checked?
                    $grade += $example->grade;
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
     */
    public function autograde_submissions($filter = self::FILTER_ALL, $selected = array(), $countonly = false) {
        global $DB, $USER;

        $result = array();
        $result['status'] = false;
        $result['updated'] = '0';

        $params = array('itemname' => $this->checkmark->name,
                        'idnumber' => $this->checkmark->cmidnumber);

        if ($this->checkmark->grade > 0) {
            $params['gradetype'] = GRADE_TYPE_VALUE;
            $params['grademax']  = $this->checkmark->grade;
            $params['grademin']  = 0;
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
            if ($groupmode == VISIBLEGROUPS or $aag) {
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
                $usrlst = $selected;

                list($sqluserids, $userparams) = $DB->get_in_or_equal($usrlst, SQL_PARAMS_NAMED, 'user');
                $params = array_merge_recursive($params, $userparams);

                $sql = "SELECT u.id, f.attendance
                          FROM {user} u
                     LEFT JOIN {checkmark_feedbacks} f ON u.id = f.userid AND f.checkmarkid = :checkmarkid
                         WHERE u.deleted = 0 AND u.id ".$sqluserids;
                $params['checkmarkid'] = $this->checkmark->id;
                break;
            case self::FILTER_REQUIRE_GRADING:
                $wherefilter = ' AND (COALESCE(f.timemodified,0) < COALESCE(s.timemodified,0)) ';
                $sql = "  SELECT u.id, f.attendance
                            FROM {user} u
                       LEFT JOIN (".$esql.") eu ON eu.id=u.id
                       LEFT JOIN {checkmark_submissions} s ON (u.id = s.userid)
                       LEFT JOIN {checkmark_feedbacks} f ON u.id = f.userid AND s.checkmarkid = f.checkmarkid
                           WHERE u.deleted = 0
                                 AND s.checkmarkid = :checkmarkid".
                       $wherefilter;
                       $params = array_merge_recursive($params, array('checkmarkid' => $this->checkmark->id));
                break;
            case self::FILTER_ALL:
            default:
                $sql = "  SELECT u.id, f.attendance
                            FROM {user} u
                       LEFT JOIN (".$esql.") eu ON eu.id=u.id
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
                    $grades = array();
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
                    \mod_checkmark\event\grade_updated::automatic($this->cm, array('userid'     => $currentuser->id,
                                                                                   'feedbackid' => $feedback->id))->trigger();
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
     * Any preprocessing needed for the settings form
     *
     * @param array $defaultvalues - array to fill in with the default values in the form 'formelement' => 'value'
     * @param object $form - the form that is to be displayed
     */
    public function form_data_preprocessing(&$defaultvalues, $form) {
        if (isset($this->checkmark)) {
            if (checkmark_count_real_submissions($this->cm->id) != 0) {
                $form->addElement('hidden', 'allready_submit', 'yes');
                $defaultvalues['allready_submit'] = 'yes';
            } else {
                $form->addElement('hidden', 'allready_submit', 'no');
                $defaultvalues['allready_submit'] = 'no';
            }
        }
    }

    /**
     * Return if flexiblenaming is used/can be used with this examples
     *
     * @return bool flexible naming is used or not
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
     */
    public static function is_using_flexiblenaming_static($instanceid) {
        global $DB;
        if ($instanceid == 0) {
            return false;
        }

        $examples = $DB->get_records('checkmark_examples', array('checkmarkid' => $instanceid));

        $oldname = null;
        $oldgrade = null;
        $flexiblenaming = false;
        foreach ($examples as $example) {
            if (($oldname != null) && ($oldgrade != null)) {
                if ((intval($oldname + 1) != intval($example->name))
                    || (intval($oldgrade) != intval($example->grade))) {
                    $flexiblenaming = true;
                }
            }
            $oldname = $example->name;
            $oldgrade = $example->grade;
        }

        return $flexiblenaming;
    }

    /**
     * Any extra validation checks needed for the settings
     *
     * See lib/formslib.php, 'validation' function for details
     *
     * @param array $data Form data as submitted
     * @return array Array of strings with error messages
     */
    public function form_validation($data) {
        $errors = array();
        if ($data['allready_submit'] == 'yes') {
            $data['flexiblenaming'] = self::is_using_flexiblenaming_static($data['instance']);
        } else if (!isset($data['flexiblenaming'])) {
            $data['flexiblenaming'] = 0;
        }
        if ($data['flexiblenaming'] == 1 && (intval($data['grade']) > 0)) {
            // Check if amount of examplenames equals amount of examplegrades?

            $grades = explode(self::DELIMITER, $data['examplegrades']);
            $names = explode(self::DELIMITER, $data['examplenames']);
            if (count($grades) != count($names)) {
                $a = new stdClass();
                $a->gradecount = count($grades);
                $a->namecount  = count($names);
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
                    $errors['examplecount'] .= '<br />'.get_string('grade_mismatch', 'checkmark');
                }
                $errors['grade'] = get_string('grade_mismatch', 'checkmark');
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
     * Top-level function for handling of submissions called by submissions.php
     *
     * This is for handling the teacher interaction with the grading interface
     *
     * @param string $mode Specifies the kind of teacher interaction taking place
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
        }

        // This is no security check, this just tells us if there is posted data!
        if (data_submitted() && confirm_sesskey() && $mailinfo !== null) {
            set_user_preference('checkmark_mailinfo', $mailinfo);
        }

        switch ($mode) {
            case 'grade':                       // We are in a main window grading!
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
                    $this->display_submission();
                }
                break;

            case 'bulk':
                $message = '';
                $bulkaction = optional_param('bulkaction', null, PARAM_ALPHA);
                $selected = optional_param_array('selected', array(), PARAM_INT);
                $confirm = optional_param('confirm', 0, PARAM_BOOL);
                if ($selected == array() && $confirm) {
                    $selected = $SESSION->checkmark->autograde->selected;
                }
                if (isset($selected) && (count($selected) == 0)) {
                    $message .= $OUTPUT->notification(get_string('bulk_no_users_selected', 'checkmark'), 'error');
                }
                if ($bulkaction && ($selected || ($confirm && !empty($SESSION->checkmark->autograde->selected)))) {
                    // Process bulk action!
                    // Check if some of the selected users don't have a feedback entry and create on if so!
                    foreach ($selected as $sel) {
                        $this->prepare_new_feedback($sel); // Make one if missing!
                    }
                    // First only the attendance changes!
                    switch($bulkaction) {
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

                    // Now all the grading stuff!
                    if (in_array($bulkaction, array('setattendantandgrade', 'setabsentandgrade', 'grade'))) {
                        if (!optional_param('confirm', 0, PARAM_BOOL)) {
                            $PAGE->set_title(format_string($this->checkmark->name, true));
                            $PAGE->set_heading($this->course->fullname);
                            if (!isset($SESSION->checkmark)) {
                                $SESSION->checkmark = new stdClass();
                            }
                            if (!isset($SESSION->checkmark->autograde)) {
                                $SESSION->checkmark->autograde = new stdClass();
                            }
                            $SESSION->checkmark->autograde->selected = $selected;
                            $result = $this->autograde_submissions(self::FILTER_SELECTED, $selected, true);
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
                                        && (count($selected) != $result)) {
                                    $amountinfo = get_string('autograde_users_with_unknown_attendance', 'checkmark',
                                                       (count($selected) - $result));
                                }
                                echo $OUTPUT->header();
                                $confirmboxcontent = $OUTPUT->notification(get_string('autograde_confirm', 'checkmark', $amount).
                                                                           html_writer::empty_tag('br').$amountinfo, 'info').
                                                     $OUTPUT->confirm(get_string('autograde_confirm_continue', 'checkmark'),
                                                                      'submissions.php?id='.$this->cm->id.'&bulk=1&bulkaction='.
                                                                      $bulkaction.'&confirm=1',
                                                                      'submissions.php?id='.$this->cm->id);
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
                                print_error('autogradegrade_error', 'checkmark');
                            }
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
                $attendance     = false;
                $presgrading    = false;
                $prescommenting = false;
                $grading        = false;
                $commenting     = false;
                $col            = false;

                $grades = optional_param_array('menu', array(), PARAM_INT);
                $feedbacks = optional_param_array('feedback', array(), PARAM_TEXT);
                $attendances = optional_param_array('attendance', array(), PARAM_INT);
                $presgrades = optional_param_array('presentationgrade', array(), PARAM_INT);
                $presfeedbacks = optional_param_array('presentationfeedback', array(), PARAM_TEXT);
                $oldgrades = optional_param_array('oldgrade', array(), PARAM_INT);
                $oldfeedbacks = optional_param_array('oldfeedback', array(), PARAM_TEXT);
                $oldattendances = optional_param_array('oldattendance', array(), PARAM_INT);
                $oldpresgrades = optional_param_array('oldpresentationgrade', array(), PARAM_INT);
                $oldpresfeedbacks = optional_param_array('oldpresentationfeedback', array(), PARAM_TEXT);

                $cantrackattendances = has_capability('mod/checkmark:trackattendance',  $this->context);
                $cangradepres = has_capability('mod/checkmark:gradepresentation', $this->context);

                $ids = array();

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

                    if (!array_key_exists($id, $oldgrades)) {
                        $oldgrades[$id] = null;
                    }
                    if (!array_key_exists($id, $oldfeedbacks)) {
                        $oldfeedbacks[$id] = null;
                    }
                    if (!array_key_exists($id, $oldattendances)) {
                        $oldattendances[$id] = null;
                    }
                    if (!array_key_exists($id, $oldpresgrades)) {
                        $oldpresgrades[$id] = null;
                    }
                    if (!array_key_exists($id, $oldpresfeedbacks)) {
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
                            && (trim($oldpresfeedbacks[$id]) != trim($presfeedbacks[$id]))) {
                        $presfeedbackvalue = trim($presfeedbacks[$id]);
                        $updatedb = $updatedb || (trim($oldpresfeedbacks[$id]) != $presfeedbackvalue);
                        if ($feedback === false) {
                            $feedback = $this->prepare_new_feedback($id);
                        }
                        $feedback->presentationfeedback = str_replace('\n', '<br />', $presfeedbackvalue);
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

                    if ($commenting && key_exists($id, $feedbacks) && (trim($oldfeedbacks[$id]) != trim($feedbacks[$id]))) {
                        $feedbackvalue = trim($feedbacks[$id]);
                        $updatedb = $updatedb || (trim($oldfeedbacks[$id]) != $feedbackvalue);
                        if ($feedback === false) {
                            $feedback = $this->prepare_new_feedback($id);
                        }
                        $feedback->feedback = $feedbackvalue;
                    } else {
                        unset($feedback->feedback);  // Don't need to update this.
                    }

                    if ($updatedb) {
                        $feedback->graderid    = $USER->id;
                        $feedback->mailed = (int)(!$mailinfo);
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
                        \mod_checkmark\event\grade_updated::manual($this->cm, array('userid'     => $feedback->userid,
                                                                                    'feedbackid' => $feedback->id))->trigger();
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
                $offset = required_param('offset', PARAM_INT);
                $nextid = required_param('nextid', PARAM_INT);
                $filter = optional_param('filter', self::FILTER_ALL, PARAM_INT);
                $id = required_param('id', PARAM_INT);
                $offset = (int)$offset + 1;
                redirect('submissions.php?id='.$id.'&userid='. $nextid . '&filter='.$filter.
                         '&mode=single&offset='.$offset);
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
                $offset = required_param('offset', PARAM_INT);
                $previousid = required_param('previousid', PARAM_INT);
                $filter = optional_param('filter', self::FILTER_ALL, PARAM_INT);
                $id = required_param('id', PARAM_INT);
                $offset = (int)$offset - 1;
                redirect('submissions.php?id='.$id.'&userid='. $previousid . '&filter='.$filter.
                         '&mode=single&offset='.$offset);
                break;

            case 'singlenosave':
                $this->display_submission();
                break;

            case 'print':
                $this->display_submission();
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
                    $user = $DB->get_record('user', array('id' => $cur));
                    echo $OUTPUT->notification(get_string('attendance_not_set_grade_locked', 'checkmark', fullname($user)), 'info');
                    unset($selected[$key]);
                }
            }
        }

        $select = 'checkmarkid = :checkmarkid';
        $params = array('checkmarkid' => $this->checkmark->id);

        if (!empty($selected) && is_array($selected)) {
            list($selsql, $selparams) = $DB->get_in_or_equal($selected, SQL_PARAMS_NAMED);
            $selsql = ' AND userid '.$selsql;
        } else {
            $selsql = ' AND userid = -1';
            $selparams = array();
        }

        $select .= $selsql;
        $params = array_merge($params, $selparams);

        $DB->set_field_select('checkmark_feedbacks', 'graderid', $USER->id, $select, $params);
        $DB->set_field_select('checkmark_feedbacks', 'timemodified', time(), $select, $params);
        $DB->set_field_select('checkmark_feedbacks', 'attendance', $state, $select, $params);
        if ($mailinfo) {
            $DB->set_field_select('checkmark_feedbacks', 'mailed', 0, $select, $params);
        }
        $grades = array();
        foreach ($selected as $sel) {
            $grades[$sel] = new stdClass();
            $grades[$sel]->userid = $sel;
            $grades[$sel]->rawgrade = $state;
        }
        checkmark_attendance_item_update($this->checkmark, $grades);
    }

    /**
     *  Return a grade in user-friendly form, whether it's a scale or not
     *
     * @param mixed $grade
     * @param int $gradeitem Which gradeitem to use (CHECKMARK_GRADE_ITEM, CHECKMARK_PRESENTATION_ITEM)
     * @return string User-friendly representation of grade
     */
    public function display_grade($grade, $gradeitem=CHECKMARK_GRADE_ITEM) {
        global $DB;

        // Cache scales for each checkmark - they might have different scales!
        static $scalegrades = array();
        static $presentationscales = array();

        switch ($gradeitem) {
            case CHECKMARK_PRESENTATION_ITEM:
                $maxgrade = (int)$this->checkmark->presentationgrade;
                break;
            default:
            case CHECKMARK_GRADE_ITEM:
                $maxgrade = (int)$this->checkmark->grade;
                break;
        }

        if ($maxgrade > 0) {    // Normal number?
            if (($grade == -1) || ($grade === null)) {
                return '-';
            } else {
                return round($grade, 2).' / '.$maxgrade;
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
                if ($scale = $DB->get_record('scale', array('id' => $scaletouse))) {
                    ${$scalecache}[$this->checkmark->id] = make_menu_from_list($scale->scale);
                } else {
                    return '-';
                }
            }
            if (isset(${$scalecache}[$this->checkmark->id][(int)$grade])) {
                return ${$scalecache}[$this->checkmark->id][(int)$grade];
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
     * TODO 3.4 --> rewrite to use submissionstable-class!
     *
     * @param int $offset (optional)
     * @param int $userid (optional)
     * @param bool $display (optional) defaults to true. Wether to echo the content or return it
     * @return stdClass|true
     */
    public function display_submission($offset=-1, $userid =-1, $display=true) {
        global $CFG, $DB, $PAGE, $OUTPUT, $SESSION;
        require_once($CFG->libdir.'/gradelib.php');
        require_once($CFG->libdir.'/tablelib.php');
        require_once($CFG->dirroot.'/repository/lib.php');
        if ($userid == -1) {
            $userid = required_param('userid', PARAM_INT);
        }
        if ($offset == -1) {
            // Offset for where to start looking for student.
            $offset = required_param('offset', PARAM_INT);
        }
        $filter = optional_param('filter', self::FILTER_ALL, PARAM_INT);

        if (!$user = $DB->get_record('user', array('id' => $userid))) {
            print_error('nousers');
        }

        if (!$submission = $this->get_submission($user->id)) {
            $submission = $this->prepare_new_submission($userid);
        }

        $feedback = $this->get_feedback($user->id);

        $gradinginfo = grade_get_grades($this->course->id, 'mod', 'checkmark',
                                        $this->checkmark->id, array($user->id));
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

        // Get all ppl that can submit checkmarks!
        $groupmode = groups_get_activity_groupmode($this->cm);
        $currentgroup = groups_get_activity_group($this->cm);
        $users = get_enrolled_users($context, 'mod/checkmark:submit', $currentgroup, 'u.id');
        $previousid = 0;
        $nextid = 0;
        $where = '';
        if ($filter == self::FILTER_SUBMITTED) {
            $where .= 's.timemodified > 0 AND ';
        } else if ($filter == self::FILTER_REQUIRE_GRADING) {
            $where .= 'COALESCE(f.timemodified,0) < COALESCE(s.timemodified,0) AND ';
        }
        $params = array();
        if ($users) {
            $userfields = user_picture::fields('u', array('lastaccess', 'idnumber'));

            list($sqluserids, $userparams) = $DB->get_in_or_equal(array_keys($users), SQL_PARAMS_NAMED, 'user');
            $params = array_merge_recursive($params, $userparams);

            $params['checkmarkid'] = $this->checkmark->id;
            $params['checkmarkid2'] = $this->checkmark->id;

            if ($groupmode != NOGROUPS) {
                if (isset($SESSION->checkmark->orderby) && ($SESSION->checkmark->orderby == 'groups')) {
                    if (isset($SESSION->checkmark->orderdirection)
                        && $SESSION->checkmark->orderdirection == 'DESC') {
                        $groupselect = 'MAX(grps.name)';
                        $grouporder = ' ORDER BY grps.name '.$SESSION->checkmark->orderdirection;
                    } else {
                        $groupselect = 'MIN(grps.name)';
                        $grouporder = ' ORDER BY grps.name ASC';
                    }
                } else {
                    $groupselect = 'MIN(grps.name)';
                    $grouporder = ' ORDER BY grps.name ASC';
                }
                $getgroupsql = 'SELECT grps.courseid, '.$groupselect;
                $params['courseid'] = $this->course->id;
                $getgroupsql .= ' AS groups, grpm.userid AS userid
                             FROM {groups_members} grpm
                             LEFT JOIN {groups} grps
                             ON grps.id = grpm.groupid
                             WHERE grps.courseid = :courseid
                             GROUP BY grpm.userid'.
                             $grouporder;
                $groupssql = ' LEFT JOIN ('.$getgroupsql.') grpq ON u.id = grpq.userid ';
            } else {
                $groupssql = '';
            }

            $select = 'SELECT '.$userfields.',
                              s.id AS submissionid, f.grade, f.feedback,
                              s.timemodified, f.timemodified AS timemarked ';
            if ($groupmode != NOGROUPS) {
                $select .= ', groups ';
            }
            $sql = 'FROM {user} u '.
                   'LEFT JOIN {checkmark_submissions} s ON u.id = s.userid AND s.checkmarkid = :checkmarkid
                    LEFT JOIN {checkmark_feedbacks} f ON u.id = f.userid AND f.checkmarkid = :checkmarkid2'.
                   $groupssql.
                   'WHERE '.$where.'u.id '.$sqluserids;
            // Construct sort!
            if (empty($SESSION->flextable['mod-checkmark-submission'])
                    || !is_array($SESSION->flextable['mod-checkmark-submissions']->sortby)) {
                $sort = '';
            } else {
                $bits = array();
                $sortby = $SESSION->flextable['mod-checkmark-submissions']->sortby;
                foreach ($sortby as $column => $order) {
                    if ($order == SORT_ASC) {
                        $bits[] = $column . ' ASC';
                    } else {
                        $bits[] = $column . ' DESC';
                    }
                }

                $sort = implode(', ', $bits);
            }
            if (!empty($sort)) {
                $sort = 'ORDER BY '.$sort;
            }
            if ($offset >= 1) {
                $auser = $DB->get_records_sql($select.$sql.$sort, $params, $offset - 1, 3);
                $previoususer = current($auser);
                $previousid = $previoususer->id;
                $moreexistent = is_array($auser) && (count($auser) > 2);
                next($auser);   // Reset array to last position!
            } else {
                $auser = $DB->get_records_sql($select.$sql.$sort, $params, $offset, 2);
                $moreexistent = is_array($auser) && (count($auser) > 1);
            }
            if ($moreexistent) {
                $nextuser = next($auser);
                // Calculate user status!
                $nextuser->status = ($nextuser->timemarked > 0)
                                     && ($nextuser->timemarked >= $nextuser->timemodified);
                $nextid = $nextuser->id;
            }
        }

        if (($feedback !== false) && isset($feedback->graderid) && $feedback->graderid) {
            $grader = $DB->get_record('user', array('id' => $feedback->graderid));
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
        $mformdata->lateness = $this->display_lateness($submission->timemodified);
        $mformdata->auser = $auser;
        $mformdata->user = $user;
        $mformdata->offset = $offset;
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

        $submitform = new mod_checkmark_grading_form( null, $mformdata );

        if (!$display) {
            $return = new stdClass();
            $return->mform = $submitform;
            $return->fileui_options = $mformdata->fileui_options;
            return $return;
        }

        if ($submitform->is_cancelled()) {
            redirect('submissions.php?id='.$this->cm->id);
        }

        $submitform->set_data($mformdata);

        $PAGE->set_title($this->course->fullname . ': ' .get_string('feedback', 'checkmark').' - '.
                         fullname($user));
        $PAGE->set_heading($this->course->fullname);
        $PAGE->navbar->add(get_string('submissions', 'checkmark'),
                           new moodle_url('/mod/checkmark/submissions.php', array('id' => $this->cm->id)));
        $PAGE->navbar->add(fullname($user));

        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('feedback', 'checkmark').': '.fullname($user));

        // Display mform here!
        $submitform->display();

        echo $OUTPUT->footer();

        return true;
    }

    /**
     * update_submission($submission) - updates the submission for the actual user
     *
     * @param object $submission Submission object to update
     */
    public function update_submission(&$submission) {
        global $USER, $DB;

        $update = new stdClass();
        $update->id           = $submission->id;
        $update->timemodified = time();
        $DB->update_record('checkmark_submissions', $update);
        foreach ($submission->examples as $key => $example) {
            $stateupdate = new stdClass();
            $stateupdate->exampleid = $key;
            if (!$id = $DB->get_field('checkmark_checks', 'id', array('submissionid' => $submission->id,
                                                                      'exampleid'    => $key), IGNORE_MISSING)) {
                $stateupdate->submissionid = $submission->id;
                $stateupdate->state = $example->state;
                $DB->insert_record('checkmark_checks', $stateupdate);
            } else {
                $stateupdate->id = $id;
                $stateupdate->state = $example->state;
                $DB->update_record('checkmark_checks', $stateupdate);
            }
        }
        $submission = $this->get_submission($USER->id);

        $this->update_grade($submission);
    }

    /**
     *  Display all the submissions ready for grading (including automated grading buttons)
     *
     * @param string $message
     * @return bool|void
     */
    public function display_submissions($message='') {
        global $SESSION, $OUTPUT, $CFG, $DB, $OUTPUT, $PAGE;

        if (!isset($SESSION->checkmark)) {
            $SESSION->checkmark = new stdClass();
        }

        echo $OUTPUT->header();

        $this->print_submission_tabs('submissions');

        /*
         * First we check to see if the form has just been submitted
         * to request user_preference updates!
         */

        $filters = array(self::FILTER_ALL             => get_string('all'),
            self::FILTER_SUBMITTED       => get_string('submitted', 'checkmark'),
            self::FILTER_REQUIRE_GRADING => get_string('requiregrading', 'checkmark'));

        if ($this->checkmark->trackattendance) {
            $filters[self::FILTER_ATTENDANT] = get_string('all_attendant', 'checkmark');
            $filters[self::FILTER_ABSENT] = get_string('all_absent', 'checkmark');
            $filters[self::FILTER_UNKNOWN] = get_string('all_unknown', 'checkmark');
        }

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
        $perpage    = get_user_preferences('checkmark_perpage', 10);
        // Replace invalid values with our standard value!
        if ($perpage < 0 || $perpage > 100) {
            $perpage = 10;
            set_user_preference('checkmark_perpage', $perpage);
        }
        $quickgrade = get_user_preferences('checkmark_quickgrade', 0);
        $filter = get_user_preferences('checkmark_filter', self::FILTER_ALL);

        $page = optional_param('page', 0, PARAM_INT);

        // Some shortcuts to make the code read better!

        $course     = $this->course;
        $cm         = $this->cm;

        // Trigger the event!
        \mod_checkmark\event\submissions_viewed::submissions($this->cm)->trigger();

        $PAGE->set_title(format_string($this->checkmark->name, true));
        $PAGE->set_heading($this->course->fullname);

        echo html_writer::start_tag('div', array('class' => 'usersubmissions'));

        // Hook to allow plagiarism plugins to update status/print links.
        plagiarism_update_status($this->course, $this->cm);

        $coursecontext = context_course::instance($course->id);
        if (has_capability('gradereport/grader:view', $coursecontext)
                && has_capability('moodle/grade:viewall', $coursecontext)) {
            $linkhref = $CFG->wwwroot.'/grade/report/grader/index.php?id='.$course->id;
            $link = html_writer::tag('a', get_string('seeallcoursegrades', 'grades'),
                                     array('href' => $linkhref));
            echo html_writer::tag('div', $link, array('class' => 'allcoursegrades'));
        }

        if (!empty($message)) {
            echo $message;   // Display messages here if any!
        }

        groups_print_activity_menu($cm, $CFG->wwwroot.'/mod/checkmark/submissions.php?id='.$this->cm->id);

        // Print quickgrade form around the table!
        $formaction = new moodle_url('/mod/checkmark/submissions.php', array('id'      => $this->cm->id,
                                                                             'sesskey' => sesskey()));
        $mform = new MoodleQuickForm('fastg', 'post', $formaction, '', array('class' => 'combinedprintpreviewform'));

        $mform->addElement('hidden', 'id');
        $mform->setDefault('id', $this->cm->id);
        $mform->addElement('hidden', 'mode');
        $mform->setDefault('mode', 'fastgrade');
        $mform->addElement('hidden', 'page');
        $mform->setDefault('page', $page);

        $table = \mod_checkmark\submissionstable::create_submissions_table($this->cm->id, $filter);
        if ($total = $DB->count_records_sql($table->countsql, $table->countparams)) {
            ob_start();
            $table->out($total < $perpage ? $total : $perpage, true);
            $tablehtml = ob_get_contents();
            ob_end_clean();
            $mform->addElement('html', $tablehtml);

            $mform->addElement('advcheckbox', 'mailinfo', get_string('enablenotification', 'checkmark'));
            $mform->addHelpButton('mailinfo', 'enablenotification', 'checkmark');
            $mailinfopref = false;
            if (get_user_preferences('checkmark_mailinfo', 1)) {
                $mailinfopref = true;
            }
            $mform->setDefault('mailinfo', $mailinfopref);

            if ($quickgrade) {
                $mform->addElement('submit', 'fastg', get_string('saveallfeedback', 'checkmark'));
            }

            $mform->addElement('header', 'bulk_header', get_string('bulk', 'checkmark'));

            $mform->addElement('static', 'checkboxcontroller', get_string('select', 'checkmark'), $table->checkbox_controller());

            $grp = array();
            $grp[] =& $mform->createElement( 'select', 'bulkaction', '' );
            $enablebulk = false;
            if ($this->checkmark->trackattendance
                && has_capability('mod/checkmark:trackattendance', $this->context)) {
                $grp[0]->addOption(get_string('setattendant', 'checkmark'), 'setattendant');
                $grp[0]->addOption(get_string('setabsent', 'checkmark'), 'setabsent');
                $grp[0]->addOption('---', '', array( 'disabled' => 'disabled' ) );
                $enablebulk = true;
            }
            if (($this->checkmark->grade <= 0)) {
                // No autograde possible if no numeric grades are selected!
                $mform->addElement('html',
                                   $OUTPUT->notification(get_string('autograde_non_numeric_grades', 'checkmark'), 'error'));
                $grp[0]->addOption(get_string('grade_automatically', 'checkmark'), 'grade', array('disabled' => 'disabled'));
            } else {
                $grp[0]->addOption(get_string('grade_automatically', 'checkmark'), 'grade');
                $enablebulk = true;
            }

            if ($this->checkmark->trackattendance
                    && has_capability('mod/checkmark:trackattendance', $this->context)) {
                if ($this->checkmark->attendancegradelink) {
                    $mform->addElement('html', $OUTPUT->notification(get_string('attendancegradelink_hint', 'checkmark'), 'info'));
                }
                if (($this->checkmark->grade <= 0)) {
                    $attr = array('disabled' => 'disabled');
                } else {
                    $attr = array();
                    $enablebulk = true;
                }
                $grp[0]->addOption('---', '', array( 'disabled' => 'disabled' ) );
                $grp[0]->addOption(get_string('setattendantandgrade', 'checkmark'), 'setattendantandgrade', $attr);
                $grp[0]->addOption(get_string('setabsentandgrade', 'checkmark'), 'setabsentandgrade', $attr);
            }

            $attr = array();
            if (!$enablebulk) {
                $attr['disabled'] = 'disabled';
            }

            if ($enablebulk) {
                $grp[] =& $mform->createElement('submit', 'bulk', get_string('start', 'checkmark'), $attr);
                $mform->addGroup($grp, 'actiongrp', get_string('selection', 'checkmark').'...', ' ', false);
                $mform->addHelpButton('actiongrp', 'bulk', 'checkmark');
            }
        } else {
            if ($filter == self::FILTER_SUBMITTED) {
                $mform->addElement('html',
                                   $OUTPUT->notification(html_writer::tag('div', get_string('nosubmisson', 'checkmark'),
                                                                          array('class' => 'nosubmission')), 'notifymessage'));
            } else if ($filter == self::FILTER_REQUIRE_GRADING) {
                $mform->addElement('html',
                                   $OUTPUT->notification(html_writer::tag('div', get_string('norequiregrading', 'checkmark'),
                                                                          array('class' => 'norequiregrading')), 'notifymessage'));
            } else {
                $mform->addElement('html',
                                   $OUTPUT->notification(html_writer::tag('div', get_string('nostudents', 'checkmark'),
                                                                          array('class' => 'nostudents')), 'notifymessage'));
            }
        }

        $mform->display();
        // End of fast grading form!

        echo html_writer::empty_tag('br', array('class' => 'clearfloat'));

        // Mini form for setting user preference!
        // TODO tscpr: should we make this form in a seperate file and handle there the saving of options?
        $formaction = new moodle_url('/mod/checkmark/submissions.php', array('id' => $this->cm->id));
        $mform = new MoodleQuickForm('optionspref', 'post', $formaction, '', array('class' => 'optionspref'));

        $mform->addElement('hidden', 'updatepref');
        $mform->setDefault('updatepref', 1);
        $mform->addElement('header', 'qgprefs', get_string('optionalsettings', 'checkmark'));
        $mform->addElement('select', 'filter', get_string('show'), $filters);

        $mform->addElement('hidden', 'sesskey');
        $mform->setDefault('sesskey', sesskey());

        $mform->setDefault('filter', $filter);

        $mform->addElement('select', 'perpage', get_string('pagesize', 'checkmark'), [
            0 => get_string('all'),
            10 => 10,
            20 => 20,
            50 => 50,
            100 => 100
        ]);
        $mform->setDefault('perpage', $perpage);

        $mform->addElement('checkbox', 'quickgrade', get_string('quickgrade', 'checkmark'));
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
     */
    public function print_submission_tabs($tab) {
        global $CFG;

        $tabs = [[
            new tabobject('submissions', $CFG->wwwroot.'/mod/checkmark/submissions.php?id='.$this->cm->id,
                    get_string('strsubmissions', 'checkmark'), get_string('strsubmissionstabalt', 'checkmark'), false),
            new tabobject('export', $CFG->wwwroot.'/mod/checkmark/export.php?id='.$this->cm->id,
                    get_string('strprintpreview', 'checkmark'), get_string('strprintpreviewtabalt', 'checkmark'), false)
        ]];

        print_tabs($tabs, $tab, [$tab], []);
    }

    /**
     * Either returns raw data for pdf/xls/ods/etc export or prints and returns table.
     *
     * @param int $filter Filter to apply (checkmark::FILTER_ALL, checkmark::FILTER_REQUIRE_GRADING, ...)
     * @param int[] $ids (optional) User-IDs to filter for
     * @param bool $dataonly (optional) return raw data-object or HTML table
     * @return array|\mod_checkmark\submissionstable
     */
    public function get_print_data($filter, $ids=array(), $dataonly=false) {
        global $DB, $OUTPUT;

        $table = \mod_checkmark\submissionstable::create_export_table($this->cm->id, $filter, $ids);
        if ($DB->count_records_sql($table->countsql, $table->countparams)) {
            if ($dataonly) {
                return $table->get_data();
            } else {
                $perpage = get_user_preferences('checkmark_perpage', 10);
                echo html_writer::start_tag('div', array('class' => 'fcontainer scroll_forced',
                                                         'id'    => 'table_begin'));
                echo html_writer::tag('div', $table->checkbox_controller(), array('class' => 'checkboxcontroller'));
                $table->out($perpage, true);
                echo html_writer::end_tag('div');
                return $table;
            }
        } else {
            if (empty($dataonly)) {
                if ($filter == self::FILTER_SUBMITTED) {
                    echo $OUTPUT->notification(html_writer::tag('div', get_string('nosubmisson', 'checkmark'),
                                                                array('class' => 'nosubmisson')), 'notifymessage');
                } else if ($filter == self::FILTER_REQUIRE_GRADING) {
                    echo $OUTPUT->notification(html_writer::tag('div', get_string('norequiregrading', 'checkmark'),
                                                                array('class' => 'norequiregrading')), 'notifymessage');
                } else {
                    echo $OUTPUT->notification(html_writer::tag('div', get_string('nostudents', 'checkmark'),
                                                                array('class' => 'norequiregrading')), 'notifymessage');
                }
                return $table;
            } else {
                return array(array(), array(), array(), array(), array());
            }
        }
    }

    /**
     * Handles all print preference setting (if submitted) and returns the current values!
     *
     * TODO do we really need this method (for writing preferences) when we have the form used properly now?
     *
     * @return array print preferences ($filter, $sumabs, $sumrel, $format, $printperpage, $printoptimum, $textsize,
     *                                  $pageorientation, $printheader, $forcesinglelinenames)
     */
    public function print_preferences() {
        $updatepref = optional_param('updatepref', 0, PARAM_INT);
        if ($updatepref && confirm_sesskey()) {
            $filter = optional_param('datafilter', self::FILTER_ALL, PARAM_INT);
            set_user_preference('checkmark_filter', $filter);
            $format = optional_param('format', \mod_checkmark\MTablePDF::OUTPUT_FORMAT_PDF, PARAM_INT);
            set_user_preference('checkmark_format', $format);
            $sumabs = optional_param('sumabs', 0, PARAM_INT);
            set_user_preference('checkmark_sumabs', $sumabs);
            $sumrel = optional_param('sumrel', 0, PARAM_INT);
            set_user_preference('checkmark_sumrel', $sumrel);
            if ($format == \mod_checkmark\MTablePDF::OUTPUT_FORMAT_PDF) {
                $printperpage = optional_param('printperpage', 0, PARAM_INT);
                $printoptimum = optional_param('printoptimum', 0, PARAM_INT);
                $printperpage = (($printperpage <= 0) || $printoptimum) ? 0 : $printperpage;
                set_user_preference('checkmark_pdfprintperpage', $printperpage);
                $textsize = optional_param('textsize', 0, PARAM_INT);
                set_user_preference('checkmark_textsize', $textsize);
                $pageorientation = optional_param('pageorientation', \mod_checkmark\MTablePDF::LANDSCAPE, PARAM_ALPHA);
                set_user_preference('checkmark_pageorientation', $pageorientation);
                $printheader = optional_param('printheader', 0, PARAM_INT);
                set_user_preference('checkmark_printheader', $printheader);
                $forcesinglelinenames = optional_param('forcesinglelinenames', 0, PARAM_INT);
                set_user_preference('checkmark_forcesinglelinenames', $forcesinglelinenames);
            }
        } else {
            $filter = get_user_preferences('checkmark_filter', self::FILTER_ALL);
            $sumabs = get_user_preferences('checkmark_sumabs', 1);
            $sumrel = get_user_preferences('checkmark_sumrel', 1);
            $format = get_user_preferences('checkmark_format', \mod_checkmark\MTablePDF::OUTPUT_FORMAT_PDF);
        }

        if ($format != \mod_checkmark\MTablePDF::OUTPUT_FORMAT_PDF
                || !($updatepref && confirm_sesskey())) {
            $printperpage    = get_user_preferences('checkmark_pdfprintperpage', 0);
            if ($printperpage == 0) {
                $printoptimum = 1;
            } else {
                $printoptimum = 0;
            }
            $textsize = get_user_preferences('checkmark_textsize', 0);
            $pageorientation = get_user_preferences('checkmark_pageorientation', \mod_checkmark\MTablePDF::LANDSCAPE);
            $printheader = get_user_preferences('checkmark_printheader', 1);
            $forcesinglelinenames = get_user_preferences('checkmark_forcesinglelinenames', 0);
        }

        // Keep compatibility to old user preferences!
        if ($pageorientation === 1) {
            $pageorientation = \mod_checkmark\MTablePDF::PORTRAIT;
        } else if ($pageorientation === 0) {
            $pageorientation = \mod_checkmark\MTablePDF::LANDSCAPE;
        }

        return array($filter, $sumabs, $sumrel, $format, $printperpage, $printoptimum, $textsize, $pageorientation, $printheader,
            $forcesinglelinenames);
    }

    /**
     * Returns export form
     *
     * @return bool|\mod_checkmark\exportform
     */
    public function get_export_form() {
        static $mform = false;

        if (!$mform) {
            /*
             * First we check to see if the form has just been submitted
             * to request user_preference updates!
             */
            list($filter, $sumabs, $sumrel, $format, $printperpage, $printoptimum, $textsize, $pageorientation,
                $printheader, $forcesinglelinenames) = $this->print_preferences();

            ob_start();
            $this->get_print_data($filter);
            $tablehtml = ob_get_contents();
            ob_end_clean();

            $customdata = [
                'cm' => $this->cm,
                'context' => $this->context,
                'examplescount' => count($this->get_examples()),
                'table' => $tablehtml,
                'tracksattendance' => $this->checkmark->trackattendance
            ];
            $formaction = new moodle_url('/mod/checkmark/export.php', [
                'id'      => $this->cm->id,
                'sesskey' => sesskey()
            ]);
            $mform = new \mod_checkmark\exportform($formaction, $customdata, 'post', '', [
                'name' => 'optionspref',
                'class' => 'combinedprintpreviewform'
            ]);

            $data = [
                'filter' => $filter,
                'sumabs' => $sumabs,
                'sumrel' => $sumrel,
                'format' => $format,
                'printperpage' => $printperpage,
                'printoptimum' => $printoptimum,
                'textsize' => $textsize,
                'pageorientation' => $pageorientation,
                'printheader' => $printheader,
                'forcesinglelinenames' => $forcesinglelinenames
            ];
            $mform->set_data($data);
        }

        return $mform;
    }

    /**
     * Echo the print preview tab including a optional message!
     *
     * @param string $message The message to display in the tab!
     */
    public function view_export($message='') {
        global $CFG, $OUTPUT, $PAGE;
        require_once($CFG->libdir.'/gradelib.php');

        // Trigger the event!
        \mod_checkmark\event\printpreview_viewed::printpreview($this->cm)->trigger();

        $PAGE->set_title(format_string($this->checkmark->name, true));
        $PAGE->set_heading($this->course->fullname);

        // Form to manage print-settings!
        echo html_writer::start_tag('div', array('class' => 'usersubmissions'));

        $mform = $this->get_export_form();

        // Hook to allow plagiarism plugins to update status/print links.
        plagiarism_update_status($this->course, $this->cm);

        if (!empty($message)) {
            echo $message;   // Display messages here if any!
        }

        // TODO JS to reload table via AJAX as soon as smth in the form changes?!?
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
                throw new coding_exception('The continue param to $OUTPUT->confirm() must be either a'.
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
                throw new coding_exception('The cancel param to $OUTPUT->confirm() must be either a'.
                                           ' URL (str/moodle_url), a single_button instance or null.');
            }
        }

        $output = $OUTPUT->box_start('generalbox', 'notice');
        $output .= html_writer::tag('p', $message);
        $output .= html_writer::tag('div', $OUTPUT->render($continue) . (($cancel != null) ? $OUTPUT->render($cancel) : ''),
                                    array('class' => 'buttons'));
        $output .= $OUTPUT->box_end();
        return $output;
    }

    /**
     * Returns applicable filters
     *
     * @return array applicable filters
     */
    protected function get_filters() {
        $filters = [
            self::FILTER_ALL             => get_string('all'),
            self::FILTER_SUBMITTED       => get_string('submitted', 'checkmark'),
            self::FILTER_REQUIRE_GRADING => get_string('requiregrading', 'checkmark')
        ];

        if ($this->checkmark->trackattendance) {
            $filters[self::FILTER_ATTENDANT] = get_string('all_attendant', 'checkmark');
            $filters[self::FILTER_ABSENT] = get_string('all_absent', 'checkmark');
            $filters[self::FILTER_UNKNOWN] = get_string('all_unknown', 'checkmark');
        }

        return $filters;
    }

    /**
     * Returns export formats
     *
     * @return array export formats
     */
    static protected function get_formats() {
        return [
            \mod_checkmark\MTablePDF::OUTPUT_FORMAT_PDF        => 'PDF',
            \mod_checkmark\MTablePDF::OUTPUT_FORMAT_XLSX       => 'XLSX',
            \mod_checkmark\MTablePDF::OUTPUT_FORMAT_ODS        => 'ODS',
            \mod_checkmark\MTablePDF::OUTPUT_FORMAT_CSV_COMMA  => 'CSV (;)',
            \mod_checkmark\MTablePDF::OUTPUT_FORMAT_CSV_TAB    => 'CSV (tab)'
        ];
    }

    /**
     * Exports table with chosen template
     *
     * @param bool|string $template The templates name
     * @return array|void
     */
    public function quick_export($template = false) {
        global $PAGE;

        $classname = '\\mod_checkmark\\local\\exporttemplates\\'.$template;
        if (!class_exists($classname)) {
            return;
        }

        /*
         * First we check to see if the form has just been submitted
         * to request user_preference updates! We don't use $printoptimum here, it's implicit in $printperpage!
         */
        list($filter, , , , , , , , , ) = $this->print_preferences();

        $usrlst = optional_param_array('selected', array(), PARAM_INT);

        if (empty($usrlst)) {
            redirect($PAGE->url, get_string('nousers', 'checkmark'), null, 'notifyproblem');
            return;
        }

        $table = $classname::create_export_table($this->cm->id, $filter, $usrlst);

        $this->exportpdf($table->get_data(), $template);
    }

    /**
     * Creates and outputs PDF (then dies).
     *
     * @param mixed[] $exportdata Returned by table object's get_data()
     * @param string $template (optional) Template name if used
     */
    protected function exportpdf($exportdata, $template = '') {
        global $PAGE;

        $filters = $this->get_filters();
        $formats = self::get_formats();

        list(, $tableheaders, $data, $columnformat, $cellwidth) = $exportdata;

        /*
         * Get all settings preferences, some will be overwritten if a template is used!
         */
        list($filter, $sumabs, $sumrel, $format, $printperpage, ,
             $textsize, $orientation, $printheader, $forcesinglelinenames) = $this->print_preferences();

        if (!empty($template)) {
            $classname = '\\mod_checkmark\\local\\exporttemplates\\'.$template;
            list($sumabs, $sumrel, $orientation, $textsize, $printheader,
                    $forcesinglelinenames) = $classname::get_export_settings();
        }

        $groupmode = groups_get_activity_groupmode($this->cm);
        $currentgroup = 0;
        if ($groupmode != NOGROUPS) {
            $currentgroup = groups_get_activity_group($this->cm, true);
            if (empty($currentgroup)) {
                $grpname = get_string('all', 'checkmark');
            } else {
                $grpname = groups_get_group_name($currentgroup);
            }
        } else {
            $grpname = '-';
        }

        $usrlst = optional_param_array('selected', array(), PARAM_INT);

        if (empty($usrlst)) {
            redirect($PAGE->url, get_string('nousers', 'checkmark'), null, 'notifyproblem');
            return;
        }

        $pdf = new \mod_checkmark\MTablePDF($orientation, $cellwidth);

        $notactivestr = get_string('notactive', 'checkmark');
        $timeavailablestr = !empty($this->checkmark->timeavailable) ? userdate($this->checkmark->timeavailable) : $notactivestr;
        $timeduestr = !empty($this->checkmark->timedue) ? userdate($this->checkmark->timedue) : $notactivestr;
        $pdf->setHeaderText(get_string('course').':', $this->course->fullname,
            get_string('availabledate', 'checkmark').':', $timeavailablestr,
            !$template ? get_string('strprintpreview', 'checkmark') : '', $filters[$filter],
            // Second header row!
            get_string('strassignment', 'checkmark').':', $this->checkmark->name,
            get_string('duedate', 'checkmark').':', $timeduestr,
            get_string('groups').':', $grpname);

        $pdf->ShowHeaderFooter($printheader);
        $pdf->SetFontSize($textsize);

        if (is_number($printperpage) && $printperpage != 0) {
            $pdf->setRowsperPage($printperpage);
        }

        // Data present?
        if (count($data)) {
            $pdf->setColumnFormat($columnformat);
            $pdf->setTitles($tableheaders);
            foreach ($data as $row) {
                $pdf->addRow($row);
            }
        } else {
            if ($filter == self::FILTER_REQUIRE_GRADING) {
                $pdf->addRow(array('', get_string('norequiregrading', 'checkmark'), ''));
                $pdf->setTitles(array(' ', ' ', ' '));
            } else {
                $pdf->addRow(array('', get_string('nosubmisson', 'checkmark'), ''));
                $pdf->setTitles(array(' ', ' ', ' '));
            }
        }

        $pdf->setOutputFormat($format);

        $data = array(
            'groupmode'       => $groupmode,
            'groupid'         => $currentgroup,
            'selected'        => $usrlst,
            'filter'          => $filter,
            'filter_readable' => $filters[$filter],
            'format'          => $format,
            'format_readable' => $formats[$format],
            'sumabs'          => $sumabs,
            'sumrel'          => $sumrel,
        );

        if ($data['format'] == \mod_checkmark\MTablePDF::OUTPUT_FORMAT_PDF) {
            $data['orientation']  = $orientation;
            $data['printheader']  = $printheader;
            $data['textsize']     = $textsize;
            $data['printperpage'] = $printperpage;
            $data['forcesinglelinenames'] = $forcesinglelinenames;
        }
        if ($template) {
            $data['template'] = $template;
        }
        \mod_checkmark\event\submissions_exported::exported($this->cm, $data)->trigger();

        $filename = $this->course->shortname.'-'.$this->checkmark->name;
        if ($template) {
            $filename .= '-' . get_string('exporttemplate_' . $template, 'checkmark');
        }
        $pdf->generate($filename);
        die();
    }

    /**
     * Finaly print the submissions!
     */
    public function submissions_print() {
        global $PAGE;

        /*
         * First we check to see if the form has just been submitted
         * to request user_preference updates! We don't use $printoptimum here, it's implicit in $printperpage!
         */
        list($filter, , , , , , , , , ) = $this->print_preferences();

        $usrlst = optional_param_array('selected', array(), PARAM_INT);

        if (empty($usrlst)) {
            redirect($PAGE->url, get_string('nousers', 'checkmark'), null, 'notifyproblem');
            return;
        }

        // Get data!
        $printdata = $this->get_print_data($filter, $usrlst, true);

        $this->exportpdf($printdata);
    }

    /**
     *  Process teacher feedback submission
     *
     * This is called by submissions() when a grading even has taken place.
     * It gets its data from the submitted form.
     *
     * @return object|bool The updated submission object or false
     */
    public function process_feedback() {
        global $CFG, $USER, $DB;
        require_once($CFG->libdir.'/gradelib.php');

        if (!$formdata = data_submitted() or !confirm_sesskey()) {      // No incoming data?
            return false;
        }

        /*
         * For save and next, we need to know the userid to save, and the userid to go
         * We use a new hidden field in the form, and set it to -1. If it's set, we use this
         * as the userid to store!
         */
        if ((int)$formdata->saveuserid !== -1) {
            $formdata->userid = $formdata->saveuserid;
        }

        if (!empty($formdata->cancel)) {          // User hit cancel button!
            return false;
        }

        // This is no security check, this just tells us if there is posted data!
        if (isset($formdata->mailinfo) && $formdata->mailinfo !== null) {
            set_user_preference('checkmark_mailinfo', $formdata->mailinfo);
        }

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
              || $gradinginfo->items[CHECKMARK_GRADE_ITEM]->grades[$formdata->userid]->overridden) ) {
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
            \mod_checkmark\event\grade_updated::manual($this->cm, array('userid'       => $feedback->userid,
                                                                        'feedbackid' => $feedback->id))->trigger();
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

        require_once($CFG->libdir.'/gradelib.php');

        if (!$formdata = data_submitted() or !confirm_sesskey()) {
            return;
        }

        $data = array();
        $gradinginfo = grade_get_grades($this->course->id, 'mod', 'checkmark',
                                        $this->checkmark->id, $userid);

        if (!empty($gradinginfo->outcomes)) {
            foreach ($gradinginfo->outcomes as $n => $old) {
                $name = 'outcome_'.$n;
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
     * @return object The submission
     */
    public function get_submission($userid=0, $createnew=false) {
        global $USER, $DB;
        if (empty($userid)) {
            $userid = $USER->id;
        }

        $submission = $DB->get_record('checkmark_submissions', array('checkmarkid' => $this->checkmark->id,
                                                                     'userid'      => $userid));
        $examples = $this->get_examples();
        if ($submission || !$createnew) {
            if ($submission) {
                if (!$submission->examples = $DB->get_records_sql('
                    SELECT exampleid AS id, state
                      FROM {checkmark_checks}
                     WHERE submissionid = :subid', array('subid' => $submission->id))) {
                    // Empty submission!
                    foreach ($examples as $key => $example) {
                        $submission->examples[$key] = new stdClass();
                        $submission->examples[$key]->id = $key;
                        $submission->examples[$key]->name = $examples[$key]->name;
                        $submission->examples[$key]->shortname = $examples[$key]->shortname;
                        $submission->examples[$key]->grade = $examples[$key]->grade;
                        $submission->examples[$key]->state = null;
                        $DB->insert_record('checkmark_checks', (object)['exampleid'    => $key,
                                                                        'submissionid' => $submission->id,
                                                                        'state'        => null]);
                    }
                } else {
                    foreach ($submission->examples as $key => $ex) {
                        $submission->examples[$key]->name = $examples[$ex->id]->name;
                        $submission->examples[$key]->shortname = $examples[$ex->id]->shortname;
                        $submission->examples[$key]->grade = $examples[$ex->id]->grade;
                    }
                }
            }
            return $submission;
        }

        // Create a new and empty submission!
        $newsubmission = $this->prepare_new_submission($userid);
        $sid = $DB->insert_record('checkmark_submissions', $newsubmission);

        foreach ($examples as $key => $example) {
            $DB->insert_record('checkmark_checks', (object)['exampleid'    => $key,
                                                            'submissionid' => $sid,
                                                            'state'        => null]);
        }

        $submission = $DB->get_record('checkmark_submissions', array('checkmarkid' => $this->checkmark->id,
                                                                     'userid'      => $userid));
        $submission->examples = $DB->get_records_sql('SELECT exampleid AS id, state
                                                        FROM {checkmark_checks} chks
                                                       WHERE submissionid = :subid',
                                                     array('subid' => $sid));
        foreach ($submission->examples as $key => $ex) {
            $submission->examples[$key]->name = $examples[$ex->id]->name;
            $submission->examples[$key]->shortname = $examples[$ex->id]->shortname;
            $submission->examples[$key]->grade = $examples[$ex->id]->grade;
        }

        return $submission;
    }

    /**
     * Insert new empty feedback in DB to be updated soon...
     *
     * @param int $userid The id of the user for whom feedback we want or 0 in which case USER->id is used
     * @return object The feedback
     */
    public function prepare_new_feedback($userid=0) {
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
     */
    public function get_feedback($userid=0) {
        global $USER, $DB;
        if (empty($userid)) {
            $userid = $USER->id;
        }

        if (!$feedback = $DB->get_record('checkmark_feedbacks', array('checkmarkid' => $this->checkmark->id,
                                                                      'userid'      => $userid))) {
            return false;
        }

        return $feedback;
    }

    /**
     * Instantiates a new submission object for a given user
     *
     * Sets the checkmark, userid and times, everything else is set to default values.
     *
     * @param int $userid The userid for which we want a submission object
     * @return object The submission
     */
    public function prepare_new_submission($userid) {
        $submission = new stdClass();
        $submission->checkmarkid            = $this->checkmark->id;
        $submission->userid                 = $userid;
        $submission->timecreated            = time();
        $submission->timemodified           = $submission->timecreated;
        return $submission;
    }

    /**
     * Return all checkmark submissions by ENROLLED students (even empty)
     *
     * @param string $sort optional field names for the ORDER BY in the sql query
     * @param string $dir optional specifying the sort direction, defaults to DESC
     * @return array The submission objects indexed by id
     */
    public function get_submissions($sort='', $dir='DESC') {
        return checkmark_get_all_submissions($this->checkmark, $sort, $dir);
    }

    /**
     * Counts all real checkmark submissions by ENROLLED students (not empty ones)
     *
     * @param int $groupid optional If nonzero then count is restricted to this group
     * @return int The number of submissions
     */
    public function count_real_submissions($groupid=0) {
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
     */
    public function email_teachers($submission) {
        global $CFG, $DB;

        if (empty($this->checkmark->emailteachers)) {          // No need to do anything!
            return;
        }

        $user = $DB->get_record('user', array('id' => $submission->userid));

        if ($teachers = $this->get_graders($user)) {

            $strsubmitted  = get_string('submitted', 'checkmark');

            foreach ($teachers as $teacher) {
                $info = new stdClass();
                $info->username = fullname($user);
                $info->checkmark = format_string($this->checkmark->name, true);
                $info->url = $CFG->wwwroot.'/mod/checkmark/submissions.php?id='.$this->cm->id;
                $info->dayupdated = userdate($submission->timemodified, get_string('strftimedate'));
                $info->timeupdated = userdate($submission->timemodified, get_string('strftimetime'));

                $postsubject = $strsubmitted.': '.$info->username.' -> '.$this->checkmark->name;
                $posttext = $this->email_teachers_text($info);
                $posthtml = ($teacher->mailformat == 1) ? $this->email_teachers_html($info) : '';

                $message = new \core\message\message();
                $message->component         = 'mod_checkmark';
                $message->name              = 'checkmark_updates';
                $message->courseid          = $this->checkmark->course;
                $message->userfrom          = $user;
                $message->userto            = $teacher;
                $message->subject           = $postsubject;
                $message->fullmessage       = $posttext;
                $message->fullmessageformat = FORMAT_HTML;
                $message->fullmessagehtml   = $posthtml;
                $message->smallmessage      = $postsubject;
                $message->notification      = 1;
                $message->contexturl        = $info->url;
                $message->contexturlname    = $info->checkmark;

                message_send($message);
            }
        }
    }

    /**
     * Returns a list of teachers that should be grading given submission
     *
     * @param object $user
     * @return array Array of users able to grade
     */
    public function get_graders($user) {
        // Get potential graders!
        $potgraders = get_users_by_capability($this->context, 'mod/checkmark:grade', '', '', '',
                                              '', '', '', false, false);

        $graders = array();
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
            foreach ($potgraders as $t) {
                if ($t->id == $user->id) {
                    continue; // Do not send to one self!
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
     */
    public function email_teachers_text($info) {
        $posttext  = format_string($this->course->shortname).' -> '.
                     get_string('modulenameplural', 'checkmark').' -> '.
                     format_string($this->checkmark->name)."\n";
        $posttext .= "---------------------------------------------------------------------\n";
        $posttext .= get_string('emailteachermail', 'checkmark', $info)."\n";
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
     */
    public function email_teachers_html($info) {
        global $CFG;
        $posthtml  = '<p><font face="sans-serif">'.
                     '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$this->course->id.'">'.
                     format_string($this->course->shortname).'</a> ->'.
                     '<a href="'.$CFG->wwwroot.'/mod/checkmark/index.php?id='.
                     $this->course->id.'">'.get_string('modulenameplural', 'checkmark').'</a> ->'.
                     '<a href="'.$CFG->wwwroot.'/mod/checkmark/view.php?id='.$this->cm->id.'">'.
                     format_string($this->checkmark->name).'</a></font></p>';
        $posthtml .= '<hr /><font face="sans-serif">';
        $posthtml .= '<p>'.get_string('emailteachermailhtml', 'checkmark', $info).'</p>';
        $posthtml .= '</font><hr />';
        return $posthtml;
    }

    /**
     * Prints out the users submission
     *
     * @param int $userid (optional) id of the user. If 0 then $USER->id is used.
     * @param bool $return (optional) defaults to false. If true the html snippet is returned
     * @return string|bool HTML snippet if $return is true or true if $return is anything else
     */
    public function print_user_submission($userid=0, $return=false) {
        global $USER;

        if (!$userid) {
            if (!isloggedin()) {
                return '';
            }
            $userid = $USER->id;
        }

        $output = '';

        $submission = $this->get_submission($userid);
        if (!$submission) {
            return $output;
        }

        // TODO we use a form here for now, but plan to use a better template in the future!
        $mform = new MoodleQuickForm('submission', 'get', '', '');

        self::add_submission_elements($mform, $submission);

        if ($return === true) {
            $output = $mform->toHtml();
            return $output;
        }

        echo $output;

        return true;
    }

    /**
     * Adds the elements representing the submission to the MoodleQuickForm!
     *
     * @param \MoodleQuickForm $mform
     * @param \stdClass $submission
     */
    public static function add_submission_elements(\MoodleQuickForm &$mform, \stdClass $submission) {
        if (empty($submission) || !object_property_exists($submission, 'examples') || empty($submission->examples)) {
            // If there's no submission, we have nothing to do here!
            return;
        }

        foreach ($submission->examples as $example) {
            switch ($example->grade) {
                case '1':
                    $pointsstring = get_string('strpoint', 'checkmark');
                    break;
                case '2':
                default:
                    $pointsstring = get_string('strpoints', 'checkmark');
                    break;
            }
            $mform->addElement('checkbox', $example->shortname, '', $example->name.' ('.$example->grade.' '.$pointsstring.')');
            if ($example->state) { // Is it checked?
                $mform->setDefault($example->shortname, 1);
            }
            $mform->freeze($example->shortname);
        }
    }

    /**
     * Returns true if the student is allowed to submit
     *
     * Checks that the checkmark has started and, cut-off-date or duedate hasn't
     * passed already.
     * @return bool
     */
    public function isopen() {
        $time = time();

        if (empty($this->checkmark->timeavailable)) {
            if (empty($this->checkmark->cutoffdate)) {
                return true;
            } else {
                return ($time <= $this->checkmark->cutoffdate);
            }
        } else {
            if (empty($this->checkmark->cutoffdate)) {
                return ($this->checkmark->timeavailable <= $time);
            } else {
                return (($this->checkmark->timeavailable <= $time) && ($time <= $this->checkmark->cutoffdate));
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
     */
    public function user_outline($grade) {

        $result = new stdClass();
        $result->info = get_string('grade').': '.$grade->str_long_grade;
        $result->time = $grade->dategraded;
        return $result;
    }

    /**
     * Print complete information about the user's interaction with the checkmark
     *
     * @param object $user User object
     * @param object $grade (optional) Grade object
     */
    public function user_complete($user, $grade=null) {
        global $OUTPUT;
        if ($grade) {
            echo $OUTPUT->container(get_string('grade').': '.$grade->str_long_grade);
            if ($grade->str_feedback) {
                echo $OUTPUT->container(get_string('feedback').': '.$grade->str_feedback);
            }
        }

        echo $OUTPUT->box_start();

        if ($submission = $this->get_submission($user->id)) {
            echo get_string('lastmodified').': ';
            echo userdate($submission->timemodified);
            echo $this->display_lateness($submission->timemodified);
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
     * @return string HTML snippet containing info about submission time
     */
    public function display_lateness($timesubmitted) {
        return checkmark_display_lateness($timesubmitted, $this->checkmark->timedue);
    }

    /**
     * Reset all submissions
     *
     * @param object $data info for which instance to reset the userdata
     * @return array status array
     */
    public function reset_userdata($data) {
        global $DB;

        if (!$DB->count_records('checkmark', array('course' => $data->courseid))) {
            return array(); // No checkmarks present!
        }

        $componentstr = get_string('modulenameplural', 'checkmark');
        $status = array();

        if (!empty($data->reset_checkmark_submissions)) {
            $checkmarks = $DB->get_fieldset('checkmark', 'id', array('course' => $data->courseid));
            if (!empty($checkmarks) && is_array($checkmarks)) {
                list($checkmarksql, $params) = $DB->get_in_or_equal($checkmarks);

                $submissions = $DB->get_fieldset_sql('SELECT id
                                                        FROM {checkmark_submissions}
                                                       WHERE checkmarkid '.$checkmarksql, $params);
                $examples = $DB->get_fieldset_sql('SELECT id
                                                     FROM {checkmark_examples}
                                                    WHERE checkmarkid '.$checkmarksql, $params);
                $DB->delete_records_select('checkmark_submissions',
                                           'checkmarkid '.$checkmarksql, $params);
                $DB->delete_records_select('checkmark_feedbacks',
                                           'checkmarkid IN ('.$checkmarksql.')', $params);
                if (!count($submissions)) {
                    $ssql = ' = NULL';
                    $sparams = array();
                } else {
                    list($ssql, $sparams) = $DB->get_in_or_equal($submissions, SQL_PARAMS_NAMED);
                }
                if (!count($examples)) {
                    $esql = ' = NULL';
                    $eparams = array();
                } else {
                    list($esql, $eparams) = $DB->get_in_or_equal($examples, SQL_PARAMS_NAMED);
                }

                $DB->delete_records_select('checkmark_checks', 'submissionid '.$ssql.' OR exampleid '.$esql,
                                           array_merge($sparams, $eparams));

                $status[] = array('component' => $componentstr,
                                  'item'      => get_string('deleteallsubmissions', 'checkmark'),
                                  'error'     => false);

                if (empty($data->reset_gradebook_grades)) {
                    // Remove all grades from gradebook!
                    checkmark_reset_gradebook($data->courseid);
                }
            }
        }

        // Updating dates - shift may be negative too!
        if ($data->timeshift) {
            shift_course_mod_dates('checkmark', array('timedue', 'timeavailable', 'cutoffdate'),
                                   $data->timeshift, $data->course);
            $status[] = array('component' => $componentstr,
                              'item'      => get_string('datechanged'),
                              'error'     => false);
        }

        return $status;
    }

}

