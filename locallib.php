<?php
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
 * This file contains checkmark-class with all logic-methods used by checkmark
 * and moodleform definitions for grading form
 *
 * @package       mod
 * @subpackage    checkmark
 * @author        Philipp Hager
 * @copyright     2011 Philipp Hager
 * @since         Moodle 2.1
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/mod/checkmark/lib.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/checkmark/submission_form.php');
require_once($CFG->dirroot.'/mod/checkmark/checkmark_pdf.php');

/** Include eventslib.php */
require_once($CFG->libdir.'/eventslib.php');
/** Include formslib.php */
require_once($CFG->libdir.'/formslib.php');
/** Include textlib.php **/
require_once($CFG->libdir.'/textlib.class.php');
/** Include calendar/lib.php */
require_once($CFG->dirroot.'/calendar/lib.php');

/**
 * This class provides all the basic functionality for an checkmark-module
 *
 * @package       mod_checkmark
 * @author        Philipp Hager
 * @copyright     2012 Philipp Hager
 * @since         Moodle 2.2
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class checkmark {

    const FILTER_ALL             = 1;
    const FILTER_SUBMITTED       = 2;
    const FILTER_REQUIRE_GRADING = 3;
    const FILTER_SELECTED = 4;  //additional value for filter
    const DELIMITER = ',';      //used to connect example-names, example-grades,
                                //submission-examplenumbers

    const TEST_LONG_DATA = 0;   //switch to use the data 16 times (for longer prints)
                                // JUST FOR TESTING
    const HTML_OUTPUT = 0;      //dumps data to browser instead of creating pdf

    //&#x2612; = ☒ = UTF-8 box with x-mark
    //&#x2610; = ☐ = UTF-8 empty box
    //&#x2713; Ã¢Å“â€œÃ¢â‚¬â€¹ check mark
    //&#x2714; Ã¢Å“â€�Ã¢â‚¬â€¹ heavy check mark
    //&#x2610; Ã¢Ëœï¿½Ã¢â‚¬â€¹ ballot box
    //&#x2611; Ã¢Ëœâ€˜Ã¢â‚¬â€¹ ballot box with check
    const EMPTYBOX = '☐';
    const CHECKEDBOX = '☒';

    /** @var object */
    public $cm;
    /** @var object */
    public $course;
    /** @var object */
    public $checkmark;
    /** @var string */
    public $strcheckmark;
    /** @var string */
    public $strcheckmarks;
    /** @var string */
    public $strsubmissions;
    /** @var string */
    public $strlastmodified;
    /** @var string */
    public $pagetitle;
    /** @var bool */
    public $usehtmleditor;
    /**
     * @todo document this var
     */
    public $defaultformat;
    /**
     * @todo document this var
     */
    public $context;

    /**
     * Constructor for the base checkmark class
     *
     * Constructor for the base checkmark class.
     * If cmid is set create the cm, course, checkmark objects.
     * If the checkmark is hidden and the user is not a teacher then
     * this prints a page header and notice.
     *
     * @global object
     * @global object
     * @param int $cmid the current course module id - not set for new checkmarks
     * @param object $checkmark usually null, but if we have it we pass it to save db access
     * @param object $cm usually null, but if we have it we pass it to save db access
     * @param object $course usually null, but if we have it we pass it to save db access
     */
    public function __construct($cmid='staticonly', $checkmark=null, $cm=null, $course=null) {
        global $COURSE, $DB;

        if ($cmid == 'staticonly') {
            //use static functions only!
            return;
        }

        global $CFG;

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
        } else if (! $this->course = $DB->get_record('course', array('id'=>$this->cm->course))) {
            print_error('invalidid', 'checkmark');
        }

        if ($checkmark) {
            $this->checkmark = $checkmark;
        } else if (! $this->checkmark = $DB->get_record('checkmark',
                                                        array('id'=>$this->cm->instance))) {
            print_error('invalidid', 'checkmark');
        }

        // compatibility with modedit checkmark obj
        $this->checkmark->cmidnumber = $this->cm->idnumber;
        $this->checkmark->course   = $this->course->id;

        $this->strcheckmark = get_string('modulename', 'checkmark');
        $this->strcheckmarks = get_string('modulenameplural', 'checkmark');
        $this->strsubmissions = get_string('submissions', 'checkmark');
        $this->strlastmodified = get_string('lastmodified');
        $this->pagetitle = strip_tags($this->course->shortname.': '.$this->strcheckmark.': '.
                                      format_string($this->checkmark->name, true));

        // visibility handled by require_login() with $cm parameter
        // get current group only when really needed

        /// Set up things for a HTML editor if it's needed
        $this->defaultformat = editors_get_preferred_format();
    }

    /*
     * print_example_preview() returns a preview of the set examples
     *
     * @return string example-preview
     */
    public function print_example_preview() {
        global $USER, $OUTPUT;
        $context = context_module::instance($this->cm->id);
        require_capability("mod/checkmark:view_preview", $context, $USER);
        echo html_writer::start_tag('div', array('class'=>'mform'));
        echo html_writer::start_tag('div', array('class'=>'clearfix')).
             get_string('example_preview_title', 'checkmark');
        echo $OUTPUT->help_icon('example_preview_title', 'checkmark');
        if ( $this->checkmark->flexiblenaming ) {
            $examplenames = explode(self::DELIMITER, $this->checkmark->examplenames);
            $examplegrades = explode(self::DELIMITER, $this->checkmark->examplegrades);
            for ($i=0; $i<count($examplenames); $i++) {
                $examplenumber = strval($i+1);
                $name = 'example'.$examplenumber;
                switch ($examplegrades[$i]) {
                    case '1':
                        $pointsstring = get_string('strpoint', 'checkmark');
                    break;
                    case '2':
                    default:
                        $pointsstring = get_string('strpoints', 'checkmark');
                    break;
                }
                $symbol = self::EMPTYBOX;
                $label = get_string('strexample', 'checkmark').' '.$examplenames[$i];
                $grade = '('.$examplegrades[$i].' '.$pointsstring.')';
                $content = html_writer::tag('div', '&nbsp;', array('class'=>'fitemtitle')).
                           html_writer::tag('div', $symbol.'&nbsp;'.$label.'&nbsp;'.$grade,
                                            array('class'=>'felement'));
                echo html_writer::tag('div', $content, array('class'=>'fitem uncheckedexample'));
            }
        } else {
            $i = 0;
            $points = $this->checkmark->grade/$this->checkmark->examplecount;
            switch ($points) {
                case '1':
                    $pointsstring = get_string('strpoint', 'checkmark');
                break;
                case '2':
                default:
                    $pointsstring = get_string('strpoints', 'checkmark');
                break;
            }
            do {
                $examplenumber = strval($i+$this->checkmark->examplestart);
                $symbol = self::EMPTYBOX;
                $label = get_string('strexample', 'checkmark').' '.$examplenumber;

                $grade = '('.$points.' '.$pointsstring.')';
                $content = html_writer::tag('div', '&nbsp;', array('class'=>'fitemtitle')).
                           html_writer::tag('div', $symbol.'&nbsp;'.$label.'&nbsp;'.$grade,
                                            array('class'=>'felement'));
                echo html_writer::tag('div', $content, array('class'=>'fitem uncheckedexample'));

                $i++;
            } while ($i<$this->checkmark->examplecount);
        }
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');

    }

    /*
     * print_summary() returns a short statistic over the actual checked examples in this checkmark
     * You've checked out X from a maximum of Y examples. (A out of B points)
     *
     * @return string short summary
     */
    public function print_summary() {

        GLOBAL $USER, $CFG;

        $checked_examples = 0;
        $checked_grades = 0;
        $max_checked_examples = 0;
        $max_checked_grades = 0;

        $submission = $this->get_submission($USER->id, false); //get the submission

        if ($submission) {
            $examplestates = explode(self::DELIMITER, $submission->checked);

            if ($this->checkmark->flexiblenaming) {
                $examplenames = explode(self::DELIMITER, $this->checkmark->examplenames);
                $examplegrades = explode(self::DELIMITER, $this->checkmark->examplegrades);
                for ($i=0; $i<count($examplenames); $i++) {
                    $examplenumber = $i+1;
                    $state = 0;
                    foreach ($examplestates as $singlestate) {
                        if (intval($singlestate) == $examplenumber) {
                            $state = 1;
                            break;
                        }
                    }

                    if ($state) { //is checked?
                        $checked_examples++;
                        $checked_grades += intval($examplegrades[$i]);
                    }
                    $max_checked_examples++;
                    $max_checked_grades += intval($examplegrades[$i]);
                }
            } else {
                $i = 0;
                do {
                    $state = 0;
                    $examplenumber = strval($i+$this->checkmark->examplestart);
                    foreach ($examplestates as $singlestate) {
                        if (intval($singlestate) == ($i+1)) {
                            $state = 1;
                            break;
                        }
                    }
                    if ($state) { //is checked?
                        $checked_examples++;
                        $checked_grades += $this->checkmark->grade/$this->checkmark->examplecount;
                    }
                    $max_checked_examples++;
                    $max_checked_grades += $this->checkmark->grade/$this->checkmark->examplecount;
                    $i++;
                } while ($i<$this->checkmark->examplecount);
            }
        } else {
            if ($this->checkmark->flexiblenaming) {
                $examplegrades = explode(self::DELIMITER, $this->checkmark->examplegrades);
                $max_checked_examples = count($examplegrades);
                $max_checked_grades = 0;
                for ($i=0; $i<count($examplegrades); $i++) {
                    $max_checked_grades += intval($examplegrades[$i]);
                }
            } else {
                $max_checked_examples = $this->checkmark->examplecount;
                $max_checked_grades = $this->checkmark->grade;
            }
            $checked_examples = 0;
            $checked_grades = 0;
        }
        $a = new stdClass();
        $a->checked = $checked_examples;
        $a->total = $max_checked_examples;
        $a->checkedgrade = $checked_grades;
        $a->maxgrade = $max_checked_grades;
        $output = html_writer::tag('div', get_string('checkmark_summary', 'checkmark', $a),
                                   array('class'=>'chkmrksubmissionsummary')).
                  html_writer::empty_tag('br');

        return $output;
    }

    /**
     * print_student_answer($userid, $return) returns a short HTML-coded string
     * with the checked examples in black an unchecked ones lined through and in a light grey.
     *
     * @param $userid
     * @param $return
     * @return string checked examples
     */
    public function print_student_answer($userid, $return=false) {
        global $OUTPUT, $CFG;
        $output = '';
        $output .= html_writer::start_tag('div', array('class'=>'examplelist'));
        if (!$submission = $this->get_submission($userid)) {
            return get_string('nosubmission', 'checkmark');
        }
        $output .= get_string('strexamples', 'checkmark').': ';
        $examplestates = explode(self::DELIMITER, $submission->checked);
        if ($this->checkmark->flexiblenaming) {
            $examplenames = explode(self::DELIMITER, $this->checkmark->examplenames);
            for ($i=0; $i<count($examplenames); $i++) {
                if ($i != 0) {
                    $output .= ', ';
                }
                $examplenumber = strval($i+1);
                $state = 0;
                foreach ($examplestates as $singlestate) {
                    if (intval($singlestate) == intval($examplenumber)) {
                        $state = 1;
                        break;
                    }
                }

                if ($state) { //is checked?
                    $output .= html_writer::tag('span', $examplenames[$i],
                                                array('class'=>'checked'));
                } else {
                    $output .= html_writer::tag('span', $examplenames[$i],
                                                array('class'=>'unchecked'));
                }
            }
        } else {
            $i = 0;
            do {
                if ($i != 0) {
                    $output .= ', ';
                }
                $state = 0;
                $examplenumber = strval($i+$this->checkmark->examplestart);
                foreach ($examplestates as $singlestate) {
                    if (intval($singlestate) == ($i+1)) {
                        $state = 1;
                        break;
                    }
                }
                if ($state) { //is checked?
                    $output .= html_writer::tag('span', $examplenumber, array('class'=>'checked'));
                } else {
                    $output .= html_writer::tag('span', $examplenumber,
                                                array('class'=>'unchecked'));
                }
                $i++;
            } while ($i<$this->checkmark->examplecount);
        }

        $output .= html_writer::end_tag('div');

        return $output;
    }

    /**
     * Every view for checkmark (teacher/student/etc.)
     */
    public function view() {
        global $OUTPUT, $USER, $CFG, $PAGE, $DB;

        $edit  = optional_param('edit', 0, PARAM_BOOL);
        $saved = optional_param('saved', 0, PARAM_BOOL);

        $context = context_module::instance($this->cm->id);
        require_capability('mod/checkmark:view', $context);

        $submission = $this->get_submission($USER->id, false);

        //Guest can not submit nor edit an checkmark (bug: 4604)
        if (!is_enrolled($this->context, $USER, 'mod/checkmark:submit')) {
            $editable = false;
        } else {
            $editable = $this->isopen()
                        && (!$submission || $this->checkmark->resubmit || !$submission->timemarked);
            if (groups_get_activity_groupmode($this->cm, $this->course) != NOGROUPS) {
                $editable = $editable && groups_has_membership($this->cm);
            }
        }
        $editmode = ($editable and $edit);

        $data = new stdClass();
        $data->id         = $this->cm->id;
        $data->checkmarkid = $this->checkmark->id;
        $data->edit       = $editmode;
        if ($submission) {
            $data->sid        = $submission->id;
            $data->checked   = $submission->checked;
        } else {
            $data->sid        = null;
            $data->checked       = '';
        }

        if ( $this->checkmark->flexiblenaming ) {
            $examplenames = explode(self::DELIMITER, $this->checkmark->examplenames);
            $examplestates = explode(self::DELIMITER, $data->checked);
            for ($i=0; $i<count($examplenames); $i++) {
                $examplenumber = strval($i+1);
                $state = 0;
                foreach ($examplestates as $singlestate) {
                    if (intval($singlestate) == intval($examplenumber)) {
                        $state = 1;
                        break;
                    }
                }
                $name = 'example'.$examplenumber;
                $data->$name = $state;
            }

        } else {
            $i = 0;
            $examplestates = explode(self::DELIMITER, $data->checked);
            do {
                $state = 0;
                foreach ($examplestates as $singlestate) {
                    if (intval($singlestate) == ($i+1)) {
                        $state = 1;
                        break;
                    }
                }
                $name = 'example'.strval($i+1);
                $data->$name = $state;
                $i++;
            } while ($i<$this->checkmark->examplecount);
        }

        if ($editmode) {

            // prepare form and process submitted data
            $mform = new checkmark_submission_form(null, $data);

            if ($mform->is_cancelled()) {
                redirect(new moodle_url($PAGE->url, array('id'=>$this->cm->id)));
            }

            if ($form_data = $mform->get_data()) {

                //create the submission if needed & return its id
                $submission = $this->get_submission($USER->id, true);

                $data = new StdClass();
                $data->checked = '';

                if ($this->checkmark->flexiblenaming) {
                    $count = count(explode(self::DELIMITER, $this->checkmark->examplenames));
                    for ($i = 1; $i <= $count; $i++) {
                        $name = 'example'.$i;
                        if (isset($form_data->{$name}) && ($form_data->{$name} != 0)) {
                            if ($data->checked != '') {
                                $data->checked .= self::DELIMITER;
                            }
                            $data->checked .= $i;
                        } else {
                            continue;
                        }
                    }
                } else {
                    for ($i = 1; $i<=$this->checkmark->examplecount; $i++) {
                        $name = 'example'.$i;
                        if (isset($form_data->{$name}) && ($form_data->{$name} != 0)) {
                            if ($data->checked != '') {
                                $data->checked .= self::DELIMITER;
                            }
                            $data->checked .= $i;
                        } else {
                            continue;
                        }
                    }
                }

                $submission = $this->update_submission($data);

                $this->email_teachers($submission);

                add_to_log($this->course->id, 'checkmark', 'update submission',
                           'view.php?a='.$this->checkmark->id, $this->checkmark->id,
                           $this->cm->id);

                //redirect to get updated submission date and word count
                redirect(new moodle_url($PAGE->url, array('id'=>$this->cm->id, 'saved'=>1)));
            }
        }

        add_to_log($this->course->id, "checkmark", "view", "view.php?id={$this->cm->id}",
                   $this->checkmark->id, $this->cm->id);

        // print header, etc. and display form if needed
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
        if (is_enrolled($this->context, $USER)) {
            if ($editmode) {
                echo $OUTPUT->box_start('generalbox boxaligncenter', 'checkmarkform');
                echo $this->print_summary();
                $mform->display();
                echo $OUTPUT->box_end();
                echo "\n";
            } else {
                echo $OUTPUT->box_start('generalbox boxaligncenter', 'checkmark');
                //display overview
                if (!empty($submission) && has_capability("mod/checkmark:submit", $context, $USER,
                                                          false)) {
                    echo $this->print_summary();
                    echo html_writer::start_tag('div', array('class'=>'mform'));
                    echo html_writer::start_tag('div', array('class'=>'clearfix'));
                    echo $this->print_user_submission($USER->id, true);
                    echo html_writer::end_tag('div');
                    echo html_writer::end_tag('div');

                } else if (has_capability("mod/checkmark:submit", $context, $USER, false)) {
                    // no submission present
                    echo html_writer::tag('div', get_string('nosubmission', 'checkmark'));
                    echo $this->print_example_preview();
                } else if (has_capability("mod/checkmark:view_preview", $context)) {
                    echo $this->print_example_preview();
                } else {
                    //if he isn't allowed to view the preview and has no submission
                    // tell him he has no submission
                    echo html_writer::tag('div', get_string('nosubmission', 'checkmark'));
                }
                echo $OUTPUT->box_end();
                echo "\n";
            }

            if (!$editmode && $editable && has_capability("mod/checkmark:submit", $context, $USER,
                                                          false)) {
                if (!empty($submission)) {
                    $submitbutton = "editmysubmission";
                } else {
                    $submitbutton = "addsubmission";
                }
                $url = new moodle_url('view.php',
                                     array('id'=>$this->cm->id, 'edit'=>'1'));
                $button = $OUTPUT->single_button($url, get_string($submitbutton, 'checkmark'));
                echo html_writer::tag('div', $button, array('class'=>'centered'));
                echo "\n";
            }

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
     * @global object
     * @param string $subpage Description of subpage to be used in navigation trail
     */
    public function view_header($subpage='') {
        global $CFG, $PAGE, $OUTPUT;

        if ($subpage) {
            $PAGE->navbar->add($subpage);
        }

        $PAGE->set_title($this->pagetitle);
        $PAGE->set_heading($this->course->fullname);

        echo $OUTPUT->header();

        groups_print_activity_menu($this->cm,
                                   $CFG->wwwroot . '/mod/checkmark/view.php?id=' . $this->cm->id);

        echo html_writer::tag('div', $this->submittedlink(), array('class'=>'reportlink'));
        echo html_writer::tag('div', '', array('class'=>'clearer'));
    }

    /**
     * Display the checkmark intro
     *
     * The default implementation prints the checkmark description in a box
     */
    public function view_intro() {
        global $OUTPUT;
        echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
        echo format_module_intro('checkmark', $this->checkmark, $this->cm->id);
        echo $OUTPUT->box_end();
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
                                    array('class'=>'c0')).
                   html_writer::tag('td', userdate($this->checkmark->timeavailable),
                                    array('class'=>'c1'));
            echo html_writer::tag('tr', $row);
        }
        if ($this->checkmark->timedue) {
            $row = html_writer::tag('td', get_string('duedate', 'checkmark').':',
                                    array('class'=>'c0')).
                   html_writer::tag('td', userdate($this->checkmark->timedue),
                                    array('class'=>'c1'));
            echo html_writer::tag('tr', $row);
        }
        echo html_writer::end_tag('table');
        echo $OUTPUT->box_end();
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
     * @global object
     * @global object
     * @global object
     * @param object $submission The submission object or null in which case it will be loaded
     */
    public function view_feedback($submission=null) {
        global $USER, $CFG, $DB, $OUTPUT;
        require_once($CFG->libdir.'/gradelib.php');

        if (!is_enrolled($this->context, $USER, 'mod/checkmark:view')) {
            // can not submit checkmarks -> no feedback
            return;
        }

        if (!$submission) { /// Get submission for this checkmark
            $submission = $this->get_submission($USER->id);
        }
        // Check the user can submit
        $cansubmit = has_capability('mod/checkmark:submit', $this->context, $USER->id, false);
        // If not then check if the user still has the view cap and has a previous submission
        $cansubmit = $cansubmit || (!empty($submission) && has_capability('mod/checkmark:view',
                                                                          $this->context,
                                                                          $USER->id,
                                                                          false));

        if (!$cansubmit) {
            // can not submit checkmarks -> no feedback
            return;
        }

        $grading_info = grade_get_grades($this->course->id, 'mod', 'checkmark',
                                         $this->checkmark->id, $USER->id);
        $item = $grading_info->items[0];
        $grade = $item->grades[$USER->id];

        if ($grade->hidden or $grade->grade === false) { // hidden or error
            return;
        }

        if ($grade->grade === null and empty($grade->str_feedback)) {   /// Nothing to show yet
            return;
        }

        $graded_date = $grade->dategraded;
        $graded_by   = $grade->usermodified;

        /// We need the teacher info
        if (!$teacher = $DB->get_record('user', array('id'=>$graded_by))) {
            print_error('cannotfindteacher');
        }

        /// Print the feedback
        echo $OUTPUT->heading(get_string('feedbackfromteacher', 'checkmark', fullname($teacher)));
        if ($teacher) {
            $userpicture = $OUTPUT->user_picture($teacher);
            $from = html_writer::tag('div', fullname($teacher), array('class'=>'fullname'));
        } else {
            $userpicture = '';
            $from = '';
        }
        $from .= html_writer::tag('div', userdate($graded_date), array('class'=>'time'));
        $topic = html_writer::tag('div', $from, array('class'=>'from'));
        $row = html_writer::tag('td', $userpicture, array('class'=>'left picture'));
        $row .= html_writer::tag('td', $topic, array('class'=>'topic'));
        $tablecontent = html_writer::tag('tr', $row);
        //second row
        $row = html_writer::tag('td', '&nbsp;', array('class'=>'left side'));
        $content = html_writer::tag('div', get_string('grade').': '.$grade->str_long_grade,
                                    array('class'=>'grade')).
                   html_writer::tag('div', '', array('class'=>'clearer')).
                   html_writer::tag('div', $grade->str_feedback, array('class'=>'comment'));
        $row .= html_writer::tag('td', $content, array('class'=>'content'));
        $tablecontent .= html_writer::tag('tr', $row);

        echo html_writer::tag('table', $tablecontent, array('cellspacing'=>0, 'class'=>'feedback'));
    }

    /**
     * Returns a link with info about the state of the checkmark submissions
     *
     * This is used by view_header to put this link at the top right of the page.
     * For teachers it gives the number of submitted checkmarks with a link
     * For students it gives the time of their submission.
     *
     * @global object
     * @global object
     * @param bool $allgroup print all groups info if user can access all groups, suitable for
     *                       index.php
     * @return string
     */
    public function submittedlink($allgroups=false) {
        global $USER;
        global $CFG;

        $submitted = '';
        $urlbase = "{$CFG->wwwroot}/mod/checkmark/";

        $context = context_module::instance($this->cm->id);
        if (has_capability('mod/checkmark:grade', $context)) {
            if ($allgroups and has_capability('moodle/site:accessallgroups', $context)) {
                $group = 0;
            } else {
                $group = groups_get_activity_group($this->cm);
            }
            if ($cnt = $this->count_real_submissions($group)) {
                $submitted = html_writer::tag('a', get_string('viewsubmissions', 'checkmark', $cnt),
                                              array('href'=>$urlbase.'submissions.php?id='.
                                                            $this->cm->id));
            } else {
                $submitted = html_writer::tag('a', get_string('noattempts', 'checkmark'),
                                              array('href'=>$urlbase.'submissions.php?id='.
                                                            $this->cm->id));
            }
        } else {
            if (isloggedin()) {
                if ($submission = $this->get_submission($USER->id)) {
                    if ($submission->timemodified) {
                        $date =  userdate($submission->timemodified);
                        if ($submission->timemodified <= $this->checkmark->timedue
                                 || empty($this->checkmark->timedue)) {
                            $submitted = html_writer::tag('span', $date, array('class'=>'early'));
                        } else {
                            $submitted = html_writer::tag('span', $date, array('class'=>'late'));
                        }
                    }
                }
            }
        }

        return $submitted;
    }

    public function calculate_grade($userid) {
        global $CFG, $USER, $OUTPUT;
        $grade = 0;

        if (!isset($userid)) {
            $userid = $USER->id;
        }

        $submission = $this->get_submission($userid, false); //get the submission

        if ($submission) {
            $examplestates = explode(self::DELIMITER, $submission->checked);

            if ($this->checkmark->flexiblenaming) {
                $examplenames = explode(self::DELIMITER, $this->checkmark->examplenames);
                $examplegrades = explode(self::DELIMITER, $this->checkmark->examplegrades);
                for ($i=0; $i<count($examplenames); $i++) {
                    $examplenumber = $i+1;
                    $state = 0;
                    foreach ($examplestates as $singlestate) {
                        if (intval($singlestate) == $examplenumber) {
                            $state = 1;
                            break;
                        }
                    }

                    if ($state) { //is checked?
                        $grade += intval($examplegrades[$i]);
                    }
                }
            } else {
                $i = 0;
                do {
                    $state = 0;
                    $examplenumber = strval($i+$this->checkmark->examplestart);
                    foreach ($examplestates as $singlestate) {
                        if (intval($singlestate) == ($i+1)) {
                            $state = 1;
                            break;
                        }
                    }
                    if ($state) { //is checked?
                        $grade += $this->checkmark->grade/$this->checkmark->examplecount;
                    }
                    $i++;
                } while ($i<$this->checkmark->examplecount);
            }
        } else {
            $grade = 0;
        }

        return $grade;
    }

    /**
     * Defines additional elements for the setup-form for checkmarks
     *
     * @see mod/checkmark/checkmark_base::setup_elements()
     */
    public function setup_elements(&$mform) {
        global $CFG, $COURSE, $PAGE, $OUTPUT;

        $jsdata = array(self::DELIMITER);
        $jsmodule = array(
                    'name'     =>   'checkmark_local',
                    'fullpath' =>   '/mod/checkmark/checkmark_local.js',
                    'requires' =>   array('base', 'io', 'node', 'json'),
                    'strings'  =>   array(
        array('yes', 'moodle'),
        array('no', 'moodle')
        )
        );

        $PAGE->requires->js_init_call('M.checkmark_local.init_settings', $jsdata, false, $jsmodule);
        $update = optional_param('update', 0, PARAM_INT);
        $cm = empty($update) ? null : get_coursemodule_from_id('', $update, 0, false, MUST_EXIST);
        $submissioncount = empty($update) ? 0 : checkmark_count_real_submissions($cm);

        if ($submissioncount) {
            $mform->addElement('hidden', 'allready_submit', 'yes');
        } else {
            $mform->addElement('hidden', 'allready_submit', 'no');
        }

        // disable manual grading settings if submissions are present
        $mform->disabledIf('grade', 'allready_submit', 'eq', 'yes');
        $mform->disabledIf('gradecat', 'allready_submit', 'eq', 'yes');

        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));

        $mform->addElement('select', 'resubmit', get_string('allowresubmit', 'checkmark'),
                           $ynoptions);
        $mform->addHelpButton('resubmit', 'allowresubmit', 'checkmark');
        $mform->setDefault('resubmit', 0);

        $mform->addElement('select', 'emailteachers', get_string('emailteachers', 'checkmark'),
                           $ynoptions);
        $mform->addHelpButton('emailteachers', 'emailteachers', 'checkmark');
        $mform->setDefault('emailteachers', 0);

        if (!empty($update) && $submissioncount) {
            $mform->addElement('html', '<div class="elements_disabled_warning">'.
                                       get_string('elements_disabled', 'checkmark').'</div>');
        }
        $mform->addElement('text', 'examplecount', get_string('numberofexamples', 'checkmark'),
                           array("id"=>"id_examplecount"));
        $mform->addHelpButton('examplecount', 'numberofexamples', 'checkmark');
        $mform->disabledIf('examplecount', 'flexiblenaming', 'checked');
        $mform->disabledIf('examplecount', 'allready_submit', 'eq', 'yes');
        if (isset($CFG->checkmark_stdexamplecount)) {
            $mform->setDefault('examplecount', $CFG->checkmark_stdexamplecount);
            $mform->setDefault('grade', $CFG->checkmark_stdexamplecount);
        } else {
            $mform->setDefault('examplecount', '10');
            $mform->setDefault('grade', '10');
        }

        $mform->addElement('text', 'examplestart', get_string('firstexamplenumber', 'checkmark'));
        $mform->addHelpButton('examplestart', 'firstexamplenumber', 'checkmark');
        $mform->disabledIf('examplestart', 'flexiblenaming', 'checked');
        $mform->disabledIf('examplestart', 'allready_submit', 'eq', 'yes');
        if (isset($CFG->checkmark_stdexamplestart)) {
            $mform->setDefault('examplestart', $CFG->checkmark_stdexamplestart);
        } else {
            $mform->setDefault('examplestart', '1');
        }

        $mform->addElement('checkbox', 'flexiblenaming', get_string('flexiblenaming', 'checkmark'),
                           get_string('activateindividuals', 'checkmark'),
                           array("id"=>"id_flexiblenaming"));
        $mform->addHelpButton('flexiblenaming', 'flexiblenaming', 'checkmark');

        $mform->disabledIf('flexiblenaming', 'allready_submit', 'eq', 'yes');
        $mform->setAdvanced('flexiblenaming');

        $mform->addElement('text', 'examplenames',
                           get_string('examplenames', 'checkmark')." (".self::DELIMITER.")");
        $mform->addHelpButton('examplenames', 'examplenames', 'checkmark');
        if (isset($CFG->checkmark_stdnames)) {
            $mform->setDefault('examplenames', $CFG->checkmark_stdnames);
        } else {
            $mform->setDefault('examplenames', '1,2,3,4,5,6,7,8,9,10');
        }

        $mform->disabledIf('examplenames', 'flexiblenaming', 'notchecked');
        $mform->disabledIf('examplenames', 'allready_submit', 'eq', 'yes');
        $mform->setAdvanced('examplenames');

        $mform->addElement('text', 'examplegrades',
                           get_string('examplegrades', 'checkmark')." (".self::DELIMITER.")",
                           array("id"=>"id_examplegrades"));
        $mform->addHelpButton('examplegrades', 'examplegrades', 'checkmark');
        if (isset($CFG->checkmark_stdgrades)) {
            $mform->setDefault('examplegrades', $CFG->checkmark_stdgrades);
        } else {
            $mform->setDefault('examplegrades', '10,10,10,10,10,10,10,10,10,10');
        }
        $mform->disabledIf('examplegrades', 'flexiblenaming', 'notchecked');
        $mform->disabledIf('examplegrades', 'allready_submit', 'eq', 'yes');
        $mform->setAdvanced('examplegrades');

        $course_context = context_course::instance($COURSE->id);
        plagiarism_get_form_elements_module($mform, $course_context);
    }

    /**
     * grades submissions from this checkmark-instance (either all or those which require grading)
     *
     * @param filter which entrys to filter (self::FILTER_ALL, self::FILTER_REQUIRE_GRADING)
     *               optional, std: FILTER_ALL
     * @return 0 if everything's ok, otherwise error code
     */
    public function autograde_submissions($filter = self::FILTER_ALL) {
        global $CFG, $COURSE, $PAGE, $DB, $OUTPUT, $USER, $SESSION;
        require_once($CFG->libdir.'/gradelib.php');
        require_once($CFG->dirroot.'/mod/checkmark/locallib.php');

        $result = array();
        $result['status'] = false;
        $result['updated'] = "0";

        $params = array('itemname' => $this->checkmark->name,
                        'idnumber' => $this->checkmark->cmidnumber);

        if ($this->checkmark->grade > 0) {
            $params['gradetype'] = GRADE_TYPE_VALUE;
            $params['grademax']  = $this->checkmark->grade;
            $params['grademin']  = 0;
        } else {
            $result['status'] = AUTOGRADE_SCALE_NOT_SUPPORTED;
            return $result;
        }

        // Get all ppl that are allowed to submit checkmarks
        $context = context_module::instance($this->cm->id);
        list($esql, $params) = get_enrolled_sql($context, 'mod/checkmark:view');
        switch ($filter) {
            case self::FILTER_SELECTED:
                //prepare list with selected users
                $usrlst = $SESSION->checkmark->autograde->selected;

                //prepare SQL manually because the following 2 commands
                //won't work properly with "get_enrolled_sql"
                //list($sqluserids, $userparams) = $DB->get_in_or_equal($usrlst);
                //$params = array_merge_recursive($params, $userparams);
                $sqluserids = null;
                foreach ($usrlst as $sqluser) {
                    if ($sqluserids == null) {
                        $sqluserids = "IN (:user".$sqluser;
                    } else {
                        $sqluserids .= ", :user".$sqluser;
                    }
                    $params["user".$sqluser] = $sqluser;
                }
                $sqluserids .= ") ";

                $sql = "SELECT u.id FROM {user} u ".
                       "WHERE u.deleted = 0".
                       " AND u.id ".$sqluserids;
                break;
            case self::FILTER_REQUIRE_GRADING:
                /* changing comment of next to lines will get checkmarks to insert empty
                 * submissions when autograding submissions who require grading
                 */
                //$wherefilter = ' AND (s.timemarked < s.timemodified OR s.grade = -1) ';
                $wherefilter = ' AND (s.timemarked < s.timemodified) ';
                $sql = "SELECT u.id FROM {user} u ".
                       "LEFT JOIN ($esql) eu ON eu.id=u.id ".
                       "LEFT JOIN {checkmark_submissions} s ON (u.id = s.user_id) " .
                       "WHERE u.deleted = 0 AND eu.id=u.id ".
                       'AND s.checkmark_id = :checkmarkid'.
                       $wherefilter;
                       $params = array_merge_recursive($params,
                                                       array('checkmarkid'=>$this->checkmark->id));
                break;
            case self::FILTER_ALL:
            default:
                $sql = "SELECT u.id FROM {user} u ".
                       "LEFT JOIN ($esql) eu ON eu.id=u.id ".
                       //comment next line to really autograde all (even those without submissions)
                       "LEFT JOIN {checkmark_submissions} s ON (u.id = s.user_id) " .
                       "WHERE u.deleted = 0 AND eu.id=u.id ".
                       'AND s.checkmark_id = :checkmarkid';
                       $params = array_merge_recursive($params,
                                                       array('checkmarkid'=>$this->checkmark->id));
                break;
        }

        $users = $DB->get_records_sql($sql, $params);
        if ($users == null) {
            $result['status'] = GRADE_UPDATE_OK;
            return $result;
        } else {
            //for each user enrolled in course
            foreach ($users as $current_user) {
                $submission = $this->get_submission($current_user->id, true); //get or make one

                $time_marked = time();
                $calculated_grade = $this->calculate_grade($current_user->id);
                $submission->grade = $calculated_grade;
                if ($submission->submissioncomment == null) {
                    $submission->submissioncomment = get_string('strautograded', 'checkmark');
                } else if (!strstr($submission->submissioncomment, get_string('strautograded',
                                                                              'checkmark'))) {

                    $submission->submissioncomment .= get_string('strautograded', 'checkmark');
                }
                $submission->teacher_id = $USER->id;
                $submission->timemarked = $time_marked;
                $grades[$current_user->id]->userid = $current_user->id;
                $grades[$current_user->id]->rawgrade = $calculated_grade;
                $grades[$current_user->id]->dategraded = $time_marked;
                $grades[$current_user->id]->feedback = $submission->submissioncomment;
                $grades[$current_user->id]->feedbackformat = $submission->format;
                $mailinfo = get_user_preferences('checkmark_mailinfo', 0);
                if (!$mailinfo) {
                    $submission->mailed = 1;       // treat as already mailed
                } else {
                    $submission->mailed = 0;       // Make sure mail goes out (again, even)
                }

                //don't update these
                unset($submission->checked);

                $DB->update_record('checkmark_submissions', $submission);
                $result['updated']++;
                $url = 'submissions.php?id='.$this->cm->id.'&autograde=1&autograde_filter='.$filter;
                add_to_log($this->course->id, 'checkmark', 'update grades', $url,
                           'autograding '.$current_user->id, $this->cm->id);
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
     * @param array $default_values - array to fill in with the default values
     *      in the form 'formelement' => 'value'
     * @param object $form - the form that is to be displayed
     * @return none
     */
    public function form_data_preprocessing(&$default_values, $form) {
        if (isset($this->checkmark)) {
            if (count_real_submissions() != 0) {
                $form->addElement('hidden', 'allready_submit', 'yes');
                $default_values['allready_submit'] = 'yes';
            } else {
                $form->addElement('hidden', 'allready_submit', 'no');
                $default_values['allready_submit'] = 'no';
            }
        }
    }

    /**
     * Any extra validation checks needed for the settings
     *
     * See lib/formslib.php, 'validation' function for details
     */
    public function form_validation($data, $files) {
        global $CFG;
        $errors = array();
        if (!isset($data['flexiblenaming'])) {
            $data['flexiblenaming'] = 0;
        }
        if ($data['flexiblenaming'] == 1) {
            //check if amount of examplenames equals amount of examplegrades

            $grades = explode(self::DELIMITER, $data['examplegrades']);
            $names = explode(self::DELIMITER, $data['examplenames']);
            if (count($grades) != count($names)) {
                $a->gradecount = count($grades);
                $a->namecount = count($names);
                $errors['examplegrades'] = get_string('count_individuals_mismatch', 'checkmark',
                                                      $a);
                $errors['examplenames'] = get_string('count_individuals_mismatch', 'checkmark', $a);
            }
            //if we use individual grades/names we also have to check
            // if the gradesum matches the sum of individual grades
            $gradesum = 0;
            for ($i = 0; $i<count($grades); $i++) {
                $gradesum += intval($grades[$i]);
            }
            if ($gradesum != intval($data['grade'])) {
                if (!isset($errors['examplegrades'])) {
                    $errors['examplegrades'] = "";
                } else {
                    $errors['examplegrades'] .= "<br />";
                }
                $a->gradesum = $gradesum;
                $a->maxgrade = $data['grade'];
                $errors['grade'] = get_string('gradesum_mismatch', 'checkmark', $a);
                $errors['examplegrades'] .= get_string('gradesum_mismatch', 'checkmark', $a);
            }
        } else {
            //grade has to be examplecount multiplied with an integer
            if ($data['grade']%$data['examplecount']) {
                $errors['examplecount'] = get_string('grade_mismatch', 'checkmark');
                $errors['grade'] = get_string('grade_mismatch', 'checkmark');
            }
        }

        return $errors;
    }

    /**
     * Update grade item for this submission.
     */
    public function update_grade($submission) {
        checkmark_update_grades($this->checkmark, $submission->user_id);
    }

    /**
     * Top-level function for handling of submissions called by submissions.php
     *
     * This is for handling the teacher interaction with the grading interface
     *
     * @global object
     * @param string $mode Specifies the kind of teacher interaction taking place
     */
    public function submissions($mode) {
        ///The main switch is changed to facilitate
        ///1) Batch fast grading
        ///2) Skip to the next one on the popup
        ///3) Save and Skip to the next one on the popup

        //make user global so we can use the id
        global $USER, $OUTPUT, $DB, $PAGE;

        $mailinfo = optional_param('mailinfo', null, PARAM_BOOL);

        if (optional_param('next', null, PARAM_BOOL)) {
            $mode='next';
        }
        if (optional_param('saveandnext', null, PARAM_BOOL)) {
            $mode='saveandnext';
        }

        if (is_null($mailinfo)) {
            //this is no security check, this just tells us if there is posted data
            if (data_submitted() && confirm_sesskey()) {
                set_user_preference('checkmark_mailinfo', $mailinfo);
            } else {
                $mailinfo = get_user_preferences('checkmark_mailinfo', 0);
            }
        } else {
            set_user_preference('checkmark_mailinfo', $mailinfo);
        }

        switch ($mode) {
            case 'grade':                       // We are in a main window grading
                if ($submission = $this->process_feedback()) {
                    $this->display_submissions(get_string('changessaved'));
                } else {
                    $this->display_submissions();
                }
                break;

            case 'single':                      // We are in a main window displaying one submission
                if ($submission = $this->process_feedback()) {
                    $this->display_submissions(get_string('changessaved'));
                } else {
                    $this->display_submission();
                }
                break;

            case 'all':                          // Main window, display everything
                $this->display_submissions();
                break;

            case 'fastgrade':
                ///do the fast grading stuff  - this process should work for all 3 subclasses
                $grading    = false;
                $commenting = false;
                $col        = false;
                if (isset($_POST['submissioncomment'])) {
                    $col = 'submissioncomment';
                    $commenting = true;
                }
                if (isset($_POST['menu'])) {
                    $col = 'menu';
                    $grading = true;
                }
                
                if (!(data_submitted() && confirm_sesskey())) {
                    $col = false;
                }
                
                if (!$col) {
                    //both submissioncomment and grade columns collapsed..
                    $this->display_submissions();
                    break;
                }

                foreach ($_POST[$col] as $id => $unusedvalue) {

                    $id = (int)$id; //clean parameter name

                    $this->process_outcomes($id);

                    if (!$submission = $this->get_submission($id)) {
                        $submission = $this->prepare_new_submission($id);
                        $newsubmission = true;
                    } else {
                        $newsubmission = false;
                    }
                    unset($submission->checked);  // Don't need to update this.

                    //for fast grade, we need to check if any changes take place
                    $updatedb = false;

                    if ($grading) {
                        $grade = $_POST['menu'][$id];
                        $updatedb = $updatedb || ($_POST['oldgrade'.$id] != $grade);
                        $submission->grade = $grade;
                    } else {
                        if (!$newsubmission) {
                            unset($submission->grade);  // Don't need to update this.
                        }
                    }
                    if ($commenting) {
                        $commentvalue = trim($_POST['submissioncomment'][$id]);
                        $updatedb = $updatedb || (trim($_POST['oldcomment'.$id]) != $commentvalue);
                        $submission->submissioncomment = $commentvalue;
                    } else {
                        unset($submission->submissioncomment);  // Don't need to update this.
                    }

                    $submission->teacher_id    = $USER->id;
                    if ($updatedb) {
                        $submission->mailed = (int)(!$mailinfo);
                    }

                    $submission->timemarked = time();

                    //if it is not an update, we don't change the last modified time etc.
                    //this will also not write into database if no submissioncomment and grade
                    //is entered.

                    if ($updatedb) {
                        if ($newsubmission) {
                            if (!isset($submission->submissioncomment)) {
                                $submission->submissioncomment = '';
                            }
                            $sid = $DB->insert_record('checkmark_submissions', $submission);
                            $submission->id = $sid;
                        } else {
                            $DB->update_record('checkmark_submissions', $submission);
                        }

                        // trigger grade event
                        $this->update_grade($submission);

                        //add to log only if updating
                        add_to_log($this->course->id, 'checkmark', 'update grades',
                                   'submissions.php?id='.$this->cm->id.'&user='.
                                   $submission->user_id,
                                   $submission->user_id, $this->cm->id);
                    }

                }

                $message = $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');

                $this->display_submissions($message);
                break;

            case 'saveandnext':
                ///We are in pop up. save the current one and go to the next one.
                //first we save the current changes
                if ($submission = $this->process_feedback()) {
                    continue; //prevents codechecker errors
                }

            case 'next':
                /// We are currently in pop up, but we want to skip to next one without saving.
                ///    This turns out to be similar to a single case
                /// The URL used is for the next submission.
                $offset = required_param('offset', PARAM_INT);
                $nextid = required_param('nextid', PARAM_INT);
                $filter = optional_param('filter', self::FILTER_ALL, PARAM_INT);
                $id = required_param('id', PARAM_INT);
                $offset = (int)$offset+1;
                redirect('submissions.php?id='.$id.'&userid='. $nextid . '&filter='.$filter.
                         '&mode=single&offset='.$offset);
                break;

            case 'singlenosave':
                $this->display_submission();
                break;

            case 'print':
                $this->display_submission();
                break;
            default:
                echo "something seriously is wrong!!";
                break;
        }
    }

    /**
     * Helper method updating the listing on the main script from popup using javascript
     *
     * @global object
     * @global object
     * @param $submission object The submission whose data is to be updated on the main page
     */
    public function update_main_listing($submission) {
        global $SESSION, $CFG, $OUTPUT;

        $output = '';

        $perpage = get_user_preferences('checkmark_perpage', 10);

        $quickgrade = get_user_preferences('checkmark_quickgrade', 0);

        /// Run some Javascript to try and update the parent page
        $output .= '<script type="text/javascript">'."\n<!--\n";
        $comment = $SESSION->flextable['mod-checkmark-submissions']->collapse['submissioncomment'];
        if (empty($comment)) {
            if ($quickgrade) {
                $output.= 'opener.document.getElementById("submissioncomment'.$submission->userid.
                          '").value="'.trim($submission->submissioncomment).'";'."\n";
            } else {
                $shortcomment = shorten_text(trim(strip_tags($submission->submissioncomment)), 15);
                $output.= 'opener.document.getElementById("com'.$submission->userid.'")'.
                          '.innerHTML="'.$shortcomment."\";\n";
            }
        }

        if (empty($SESSION->flextable['mod-checkmark-submissions']->collapse['grade'])) {
            if ($quickgrade) {
                $output.= 'opener.document.getElementById("menumenu'.$submission->userid.
                '").selectedIndex="'.optional_param('menuindex', 0, PARAM_INT).'";'."\n";
            } else {
                $output.= 'opener.document.getElementById("g'.$submission->userid.'").innerHTML="'.
                $this->display_grade($submission->grade)."\";\n";
            }
        }
        //need to add student's checkmarks in there too.
        if (empty($SESSION->flextable['mod-checkmark-submissions']->collapse['timemodified'])
             && $submission->timemodified) {
            $output.= 'opener.document.getElementById("ts'.$submission->userid.'").innerHTML="'.
                      addslashes_js($this->print_student_answer($submission->userid)).
                      userdate($submission->timemodified)."\";\n";
        }

        if (empty($SESSION->flextable['mod-checkmark-submissions']->collapse['timemarked']) &&
        $submission->timemarked) {
            $output.= 'opener.document.getElementById("tt'.$submission->userid.
                 '").innerHTML="'.userdate($submission->timemarked)."\";\n";
        }

        if (empty($SESSION->flextable['mod-checkmark-submissions']->collapse['status'])) {
            $output.= 'opener.document.getElementById("up'.$submission->userid.'").className="s1";';
            $buttontext = get_string('update');
            $url = new moodle_url('/mod/checkmark/submissions.php', array(
                    'id' => $this->cm->id,
                    'userid' => $submission->userid,
                    'mode' => 'single',
                    'offset' => (optional_param('offset', '', PARAM_INT)-1)));
            $button = $OUTPUT->action_link($url, $buttontext,
                                           new popup_action('click', $url,
                                                            'grade'.$submission->userid,
                                                            array('height' => 450,
                                                                  'width'  => 700)),
                                           array('title'=>$buttontext));

            $output .= 'opener.document.getElementById("up'.$submission->userid.'").innerHTML="'.
                       addslashes_js($button).'";';
        }

        $grading_info = grade_get_grades($this->course->id, 'mod', 'checkmark',
                                         $this->checkmark->id, $submission->userid);

        if (empty($SESSION->flextable['mod-checkmark-submissions']->collapse['finalgrade'])) {
            $userid = $submission->userid;
            $output .= 'opener.document.getElementById("finalgrade_'.$userid.'").innerHTML="'.
                       $grading_info->items[0]->grades[$submission->userid]->str_grade.'";'."\n";
        }

        if (!empty($CFG->enableoutcomes)
            && empty($SESSION->flextable['mod-checkmark-submissions']->collapse['outcome'])) {

            if (!empty($grading_info->outcomes)) {
                foreach ($grading_info->outcomes as $n => $outcome) {
                    if ($outcome->grades[$submission->userid]->locked) {
                        continue;
                    }

                    if ($quickgrade) {
                        $output.= 'opener.document.getElementById("outcome_'.$n.'_'.
                                  $submission->userid.'").selectedIndex="'.
                                  $outcome->grades[$submission->userid]->grade.'";'."\n";

                    } else {
                        $options = make_grades_menu(-$outcome->scaleid);
                        $options[0] = get_string('nooutcome', 'grades');
                        $output.= 'opener.document.getElementById("outcome_'.$n.'_'.
                                  $submission->userid.'").innerHTML="'.
                                  $options[$outcome->grades[$submission->userid]->grade]."\";\n";
                    }

                }
            }
        }

        $output .= "\n-->\n</script>";
        return $output;
    }

    /**
     *  Return a grade in user-friendly form, whether it's a scale or not
     *
     * @global object
     * @param mixed $grade
     * @return string User-friendly representation of grade
     */
    public function display_grade($grade) {
        global $DB;

        // Cache scales for each checkmark - they might have different scales!!
        static $scalegrades = array();

        if ($this->checkmark->grade >= 0) {    // Normal number
            if ($grade == -1) {
                return '-';
            } else {
                return $grade.' / '.$this->checkmark->grade;
            }

        } else {                                // Scale
            if (empty($scalegrades[$this->checkmark->id])) {
                if ($scale = $DB->get_record('scale', array('id'=>-($this->checkmark->grade)))) {
                    $scalegrades[$this->checkmark->id] = make_menu_from_list($scale->scale);
                } else {
                    return '-';
                }
            }
            if (isset($scalegrades[$this->checkmark->id][$grade])) {
                return $scalegrades[$this->checkmark->id][$grade];
            }
            return '-';
        }
    }

    /**
     * returns html-string for column headers
     *
     * @param $columnname
     * @param $columnstring
     */
    public function get_submissions_column_header($columnname=null, $columnstring=null) {
        global $CFG, $OUTPUT, $SESSION;
        $return = null;
        $columnlink = null;

        if ($columnname == null) {
            //error
            return $return;
        }
        if ($columnstring == null) {
            //error
            return $return;
        }
        //as users name get displayed in a single column we need a hack to support sorting for first
        // and lastname
        //@todo better implementation of firstname lastname via CONCAT in SQL and sortability
        // will remain?!?
        if (($columnname!='fullnameuser') && !strstr($columnname, 'example')) {
            if (isset($SESSION->checkmark->columns[$columnname]->sortable)
                    && ($SESSION->checkmark->columns[$columnname]->sortable == 0)) {
                $columnlink = $columnstring;
            } else {
                $columnlink = html_writer::link($CFG->wwwroot.'/mod/checkmark/submissions.php?id='.
                                                $this->cm->id.'&tsort='.$columnname, $columnstring,
                                                array('title' => get_string('sortby').' '.
                                                                 $columnstring));
            }
            //print order pictogramm
            if (isset($SESSION->checkmark->orderby)
                    && ($columnname == $SESSION->checkmark->orderby)) {
                if ($SESSION->checkmark->orderdirection == 'ASC') {
                    $columnlink .= html_writer::empty_tag('img',
                                                          array('src' => $OUTPUT->pix_url('t/down'),
                                                                'alt' => get_string('asc')));
                } else {
                    $columnlink .= html_writer::empty_tag('img',
                                                          array('src' => $OUTPUT->pix_url('t/up'),
                                                                'alt' => get_string('desc')));
                }
            }
        } else if (!strstr($columnname, 'example') && !strstr($columnname, 'group')) {
            if (isset($SESSION->checkmark->columns['firstname'])
                    && ($SESSION->checkmark->columns['firstname']->sortable == 0)) {
                $columnlink .= get_string('firstname');
            } else {
                $columnlink .= html_writer::link($CFG->wwwroot.'/mod/checkmark/submissions.php?id='.
                                                 $this->cm->id.'&tsort=firstname',
                                                 get_string('firstname'),
                                                 array('title' => get_string('sortby').' '.
                                                                  get_string('firstname')));
            }
            if (isset($SESSION->checkmark->orderby)
                    && ($SESSION->checkmark->orderby == 'firstname')) {
                //print order pictogramm
                if ($SESSION->checkmark->orderdirection == 'ASC') {
                    $columnlink .= html_writer::empty_tag('img',
                                                          array('src' => $OUTPUT->pix_url('t/down'),
                                                                'alt' => get_string('asc')));
                } else {
                    $columnlink .= html_writer::empty_tag('img',
                                                          array('src' => $OUTPUT->pix_url('t/up'),
                                                                'alt' => get_string('desc')));
                }
            }
            $columnlink .= ' / ';
            if (isset($SESSION->checkmark->columns['lastname'])
                    && ($SESSION->checkmark->columns['lastname']->sortable == 0)) {
                $columnlink .= get_string('lastname');
            } else {
                $columnlink .= html_writer::link($CFG->wwwroot.'/mod/checkmark/submissions.php?id='.
                                                 $this->cm->id.'&tsort=lastname',
                                                 get_string('lastname'),
                                                 array('title' => get_string('sortby').' '.
                                                                  get_string('lastname')));
            }
            if (isset($SESSION->checkmark->orderby)
                    && ($SESSION->checkmark->orderby == 'lastname')) {
                //print order pictogramm
                if ($SESSION->checkmark->orderdirection == 'ASC') {
                    $columnlink .= html_writer::empty_tag('img',
                                                          array('src' => $OUTPUT->pix_url('t/down'),
                                                                'alt' => get_string('asc')));
                } else {
                    $columnlink .= html_writer::empty_tag('img',
                                                          array( 'src' => $OUTPUT->pix_url('t/up'),
                                                                 'alt' => get_string('desc')));
                }
            }
        } else { //can't sort by examples because of database restrictions
            //@todo sorting by examples will be implemented as soon as theres a new DB-Structure
            // (instead of old comma-separated fields)
            $columnlink = $columnstring;
        }

        if (isset($SESSION->checkmark->columns[$columnname])
                && ($SESSION->checkmark->columns[$columnname]->visibility == 0)) {
            //show link to show column
            $imgattr = array('src' => $OUTPUT->pix_url('t/switch_plus'),
                             'alt' => get_string('show'));
            $return = html_writer::link($CFG->wwwroot.'/mod/checkmark/submissions.php?id='.
                                        $this->cm->id.'&tshow='.$columnname,
                                        html_writer::empty_tag('img', $imgattr,
                                                               array('title'=>get_string('show').
                                                                              ' '.$columnstring)));
        } else {
            if (!isset($SESSION->checkmark->columns[$columnname])) {
                $SESSION->checkmark->columns[$columnname] = new stdClass();
            }
            $SESSION->checkmark->columns[$columnname]->visibility = 1;
            $imgattr = array('src' => $OUTPUT->pix_url('t/switch_minus'),
                             'alt' => get_string('hide'));
            $return = $columnlink.' '.
                      html_writer::link($CFG->wwwroot.'/mod/checkmark/submissions.php?id='.
                                        $this->cm->id.'&thide='.$columnname,
                                        html_writer::empty_tag('img', $imgattr),
                                        array('title' => get_string('hide') . ' ' .
                                                         $columnstring));
        }
        return $return;
    }

    /**
     * checks if a column in submissions or print-previews table is set to hidden
     * @param $columnname
     */
    public function column_is_hidden($columnname) {
        global $SESSION;
        if (isset($SESSION->checkmark->columns[$columnname])
                && ($SESSION->checkmark->columns[$columnname]->visibility == 0)) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * Helper function, used by {@link print_initials_bar()} to output one initial bar.
     * @param array $alpha of letters in the alphabet.
     * @param string $current the currently selected letter.
     * @param string $class class name to add to this initial bar.
     * @param string $title the name to put in front of this initial bar.
     * @param string $urlvar URL parameter name for this initial.
     */
    protected function print_one_moodleform_initials_bar($alpha, $current, $class, $title,
                                                         $urlvar) {
        global $CFG;
        $return = '';
        $return .= html_writer::start_tag('div', array('class' => 'fitem ' . $class)) .
                   html_writer::start_tag('div', array('class' => 'fitemtitle')) .
                   $title .
                   html_writer::end_tag('div').
                   html_writer::start_tag('div', array('class' => 'felement'));
        if ($current) {
            $return .= html_writer::link($CFG->wwwroot.'/mod/checkmark/submissions.php?id='.
                                         $this->cm->id.'&'.$urlvar.'=', get_string('all'));
        } else {
            $return .= html_writer::tag('strong', get_string('all'));
        }

        foreach ($alpha as $letter) {
            if ($letter === $current) {
                $return .= html_writer::tag('strong', $letter);
            } else {
                $return .= html_writer::link($CFG->wwwroot.'/mod/checkmark/submissions.php?id='.
                                             $this->cm->id.'&'.$urlvar.'='.$letter, $letter);
            }
        }

        return $return.html_writer::end_tag('div').html_writer::end_tag('div');
    }

    /**
     * This function is not part of the public api.
     */
    protected function print_moodleform_initials_bar() {
        global $SESSION;

        $alpha  = explode(',', get_string('alphabet', 'langconfig'));

        // Bar of first initials
        if (!empty($SESSION->checkmark->ifirst)) {
            $ifirst = $SESSION->checkmark->ifirst;
        } else {
            $ifirst = '';
        }

        // Bar of last initials
        if (!empty($SESSION->checkmark->ilast)) {
            $ilast = $SESSION->checkmark->ilast;
        } else {
            $ilast = '';
        }
        return      $this->print_one_moodleform_initials_bar($alpha, $ifirst, 'firstinitial',
                    get_string('firstname'), 'ifirst').
                    $this->print_one_moodleform_initials_bar($alpha, $ilast, 'lastinitial',
                    get_string('lastname'), 'ilast');
    }

    /**
     * Print group menu selector for activity.
     *
     * @param stdClass $cm course module object
     * @param string|moodle_url $urlroot return address that users get to if they choose an option;
     *   should include any parameters needed, e.g. "$CFG->wwwroot/mod/forum/view.php?id=34"
     * @param boolean $return return as string instead of printing
     * @param boolean $hideallparticipants If true, this prevents the 'All participants'
     *   option from appearing in cases where it normally would. This is intended for
     *   use only by activities that cannot display all groups together. (Note that
     *   selecting this option does not prevent groups_get_activity_group from
     *   returning 0; it will still do that if the user has chosen 'all participants'
     *   in another activity, or not chosen anything.)
     * @return mixed void or string depending on $return param
     */
    public function moodleform_groups_print_activity_menu($cm, $return=false,
                                                          $hideallparticipants=false) {
        global $USER, $OUTPUT, $CFG;

        if (!$groupmode = groups_get_activity_groupmode($cm)) {
            if ($return) {
                return '';
            } else {
                return;
            }
        }

        $context = context_module::instance($cm->id);
        $aag = has_capability('moodle/site:accessallgroups', $context);

        if ($groupmode == VISIBLEGROUPS or $aag) {
            // any group in grouping
            $allowedgroups = groups_get_all_groups($cm->course, 0, $cm->groupingid);
        } else {
            // only assigned groups
            $allowedgroups = groups_get_all_groups($cm->course, $USER->id, $cm->groupingid);
        }

        $activegroup = groups_get_activity_group($cm, true, $allowedgroups);

        $groupsmenu = array();
        if ((!$allowedgroups or $groupmode == VISIBLEGROUPS or $aag) and !$hideallparticipants) {
            $groupsmenu[0] = get_string('allparticipants');
        }

        if ($allowedgroups) {
            foreach ($allowedgroups as $group) {
                $groupsmenu[$group->id] = format_string($group->name);
            }
        }

        if ($groupmode == VISIBLEGROUPS) {
            $grouplabel = get_string('groupsvisible');
        } else {
            $grouplabel = get_string('groupsseparate');
        }

        if ($aag and $cm->groupingid) {
            if ($grouping = groups_get_grouping($cm->groupingid)) {
                $grouplabel = $grouplabel . ' (' . format_string($grouping->name) . ')';
            }
        }

        if (count($groupsmenu) == 1) {
            $groupname = reset($groupsmenu);
            $output = html_writer::start_tag('div', array('class'=>'fitemtitle')).
                       html_writer::label($grouplabel, null).
                       html_writer::end_tag('div');
            $output .= html_writer::start_tag('div', array('class'=>'felement')).
                       $groupname.
                       html_writer::end_tag('div');
        } else {
            $url = new moodle_url($CFG->wwwroot . '/mod/checkmark/submissions.php');
            $select = new single_select($url, 'group', $groupsmenu, $activegroup, null,
                                        'selectgroup');
            $select->label = $grouplabel;
            $output = $this->render_moodleform_singleselect($select);
        }

        // then div wrapper for xhtml strictness
        $output = html_writer::tag('div', $output, array('class'=>'fitem'));
        // and another wrapper with modform-element-class
        $output = html_writer::tag('div', $output, array('class'=>'groupselector'));

        return $output;

    }

    /**
     * Internal implementation of single_select rendering
     * @param single_select $select
     * @return string HTML fragment
     */
    protected function render_moodleform_singleselect(single_select $select) {
        global $PAGE;
        $select = clone($select);
        if (empty($select->formid)) {
            $select->formid = html_writer::random_id('single_select_f');
        }

        $output = '';

        if ($select->method === 'post') {
            $params['sesskey'] = sesskey();
        }
        if (isset($params)) {
            foreach ($params as $name => $value) {
                $output .= html_writer::empty_tag('input', array('type'  => 'hidden',
                                                                 'name'  => $name,
                                                                 'value' => $value));
            }
        }

        if (empty($select->attributes['id'])) {
            $select->attributes['id'] = html_writer::random_id('single_select');
        }

        if ($select->disabled) {
            $select->attributes['disabled'] = 'disabled';
        }

        if ($select->tooltip) {
            $select->attributes['title'] = $select->tooltip;
        }

        if ($select->label) {
            $output .= html_writer::start_tag('div', array('class'=>'fitemtitle')).
                       html_writer::label($select->label, $select->attributes['id']).
                       html_writer::end_tag('div');
        }

        if ($select->helpicon instanceof help_icon) {
            $output .= $this->render($select->helpicon);
        } else if ($select->helpicon instanceof old_help_icon) {
            $output .= $this->render($select->helpicon);
        }

        $output .= html_writer::start_tag('div', array('class'=>'felement')).
                   html_writer::select($select->options, $select->name, $select->selected,
                                       $select->nothing, $select->attributes).
                   html_writer::end_tag('div');

        //go button obsolete because this is just for moodle forms --> submit button available

        $nothing = empty($select->nothing) ? false : key($select->nothing);
        $PAGE->requires->js_init_call('M.util.init_select_autosubmit', array($select->formid,
                                      $select->attributes['id'], $nothing));

        // then div wrapper for xhtml strictness
        $output = html_writer::tag('div', $output, array('class'=>'fitem'));

        // and finally one more wrapper with class
        return html_writer::tag('div', $output, array('class' => $select->class));
    }

    /**
     *  Display a single submission, ready for grading on a popup window
     *
     * This default method prints the teacher info and submissioncomment box at the top and
     * the student info and submission at the bottom.
     * This method also fetches the necessary data in order to be able to
     * provide a "Next submission" button.
     * to process submissions before they are graded
     * This method gets its arguments from the page parameters userid and offset
     *
     * @global object
     * @global object
     * @param string $extra_javascript
     */
    public function display_submission($offset=-1, $userid =-1, $display=true) {
        global $CFG, $DB, $PAGE, $OUTPUT, $SESSION;
        require_once($CFG->libdir.'/gradelib.php');
        require_once($CFG->libdir.'/tablelib.php');
        require_once("$CFG->dirroot/repository/lib.php");
        if ($userid==-1) {
            $userid = required_param('userid', PARAM_INT);
        }
        if ($offset==-1) {
            //offset for where to start looking for student.
            $offset = required_param('offset', PARAM_INT);
        }
        $filter = optional_param('filter', self::FILTER_ALL, PARAM_INT);

        if (!$user = $DB->get_record('user', array('id'=>$userid))) {
            print_error('nousers');
        }

        if (!$submission = $this->get_submission($user->id)) {
            $submission = $this->prepare_new_submission($userid);
        }

        $grading_info = grade_get_grades($this->course->id, 'mod', 'checkmark',
                                         $this->checkmark->id, array($user->id));
        $gradingdisabled = $grading_info->items[0]->grades[$userid]->locked
                           || $grading_info->items[0]->grades[$userid]->overridden;

        /// construct SQL, using current offset to find the data of the next student
        $course     = $this->course;
        $checkmark = $this->checkmark;
        $cm         = $this->cm;
        $context = context_module::instance($cm->id);

        /// Get all ppl that can submit checkmarks
        $groupmode = groups_get_activity_groupmode($cm);
        $currentgroup = groups_get_activity_group($cm);
        $users = get_enrolled_users($context, 'mod/checkmark:submit', $currentgroup, 'u.id');
        if ($users) {
            $users = array_keys($users);
            // if groupmembersonly used, remove users who are not in any group
            if (!empty($CFG->enablegroupmembersonly) and $cm->groupmembersonly) {
                if ($groupingusers = groups_get_grouping_members($cm->groupingid, 'u.id', 'u.id')) {
                    $users = array_intersect($users, array_keys($groupingusers));
                }
            }
        }

        $nextid = 0;
        $where = '';
        if ($filter == self::FILTER_SUBMITTED) {
            $where .= 's.timemodified > 0 AND ';
        } else if ($filter == self::FILTER_REQUIRE_GRADING) {
            $where .= 's.timemarked < s.timemodified AND ';
        }
        $params = array();
        if ($users) {
            $userfields = user_picture::fields('u', array('lastaccess', 'idnumber'));

            //prepare SQL manually because the following 2 commands
            //won't work properly with "get_enrolled_sql"
            $sqluserids = null;
            foreach ($users as $sqluser) {
                if ($sqluserids == null) {
                    $sqluserids = "IN (:user".$sqluser;
                } else {
                    $sqluserids .= ", :user".$sqluser;
                }
                $params["user".$sqluser] = $sqluser;
            }
            $sqluserids .= ") ";

            $params['checkmark_id'] = $this->checkmark->id;

            if ($groupmode != NOGROUPS) {
                $getgroupsql = "SELECT grps.courseid, GROUP_CONCAT(DISTINCT grps.name
                                                                   ORDER BY grps.name ";
                $params['courseid'] = $this->course->id;
                $getgroupsql .= " SEPARATOR ', ') AS groups, grpm.userid AS userid
                             FROM {groups_members} grpm
                             LEFT JOIN {groups} grps
                             ON grps.id = grpm.groupid
                             WHERE grps.courseid = :courseid
                             GROUP BY grpm.userid";
                $groupssql = " LEFT JOIN ($getgroupsql) AS grpq ON u.id = grpq.userid ";
            } else {
                $groupssql = "";
            }

            $select = "SELECT $userfields,
                              s.id AS submissionid, s.grade, s.submissioncomment,
                              s.timemodified, s.timemarked ";
            if ($groupmode != NOGROUPS) {
                $select .= ", groups ";
            }
            $sql = 'FROM {user} u '.
                   'LEFT JOIN {checkmark_submissions} s ON u.id = s.user_id
                   AND s.checkmark_id = :checkmark_id'.
                   $groupssql.
                   'WHERE '.$where.'u.id '.$sqluserids;
            //construct sort
            if (!is_array($SESSION->flextable['mod-checkmark-submissions']->sortby)) {
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

            $auser = $DB->get_records_sql($select.$sql.$sort, $params, $offset, 2);
            if (is_array($auser) && count($auser)>1) {
                $nextuser = next($auser);
                /// Calculate user status
                $nextuser->status = ($nextuser->timemarked > 0)
                                     && ($nextuser->timemarked >= $nextuser->timemodified);
                $nextid = $nextuser->id;
            }
        }

        if (isset($submission->teacher) && $submission->teacher) {
            $teacher = $DB->get_record('user', array('id'=>$submission->teacher));
        } else {
            global $USER;
            $teacher = $USER;
        }

        $this->preprocess_submission($submission);

        $mformdata = new stdClass();
        $mformdata->context = $this->context;
        $mformdata->course = $this->course->id;
        $mformdata->teacher = $teacher;
        $mformdata->checkmark = $checkmark;
        $mformdata->submission = $submission;
        $mformdata->lateness = $this->display_lateness($submission->timemodified);
        $mformdata->auser = $auser;
        $mformdata->user = $user;
        $mformdata->offset = $offset;
        $mformdata->userid = $userid;
        $mformdata->cm = $this->cm;
        $mformdata->grading_info = $grading_info;
        $mformdata->enableoutcomes = $CFG->enableoutcomes;
        $mformdata->grade = $this->checkmark->grade;
        $mformdata->gradingdisabled = $gradingdisabled;
        $mformdata->nextid = $nextid;
        $mformdata->submissioncomment= $submission->submissioncomment;
        $mformdata->submissioncommentformat= FORMAT_HTML;
        $mformdata->submission_content= $this->print_user_submission($user->id, true);
        $mformdata->filter = $filter;
        $mformdata->mailinfo = get_user_preferences('checkmark_mailinfo', 0);

        $submitform = new mod_checkmark_grading_form( null, $mformdata );

        if (!$display) {
            $ret_data = new stdClass();
            $ret_data->mform = $submitform;
            $ret_data->fileui_options = $mformdata->fileui_options;
            return $ret_data;
        }

        if ($submitform->is_cancelled()) {
            redirect('submissions.php?id='.$this->cm->id);
        }

        $submitform->set_data($mformdata);

        $PAGE->set_title($this->course->fullname . ': ' .get_string('feedback', 'checkmark').' - '.
                         fullname($user, true));
        $PAGE->set_heading($this->course->fullname);
        $PAGE->navbar->add(get_string('submissions', 'checkmark'),
                           new moodle_url('/mod/checkmark/submissions.php', array('id'=>$cm->id)));
        $PAGE->navbar->add(fullname($user, true));

        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('feedback', 'checkmark').': '.fullname($user, true));

        // display mform here...
        $submitform->display();

        $customfeedback = $this->custom_feedbackform($submission, true);
        if (!empty($customfeedback)) {
            echo $customfeedback;
        }

        echo $OUTPUT->footer();
    }

    /**
     *  Preprocess submission before grading
     *
     * Called by display_submission()
     *
     * @param object $submission The submission object
     */
    public function preprocess_submission(&$submission) {
    }

    /**
     * update_submission($data) - updates the submission for the actual user
     *
     * @param $data
     * @global $USER
     * @global $CFG
     * @global $DB
     * @return $submission
     */
    public function update_submission($data) {
        global $CFG, $USER, $DB;

        $submission = $this->get_submission($USER->id, true);

        $update = new stdClass();
        $update->id           = $submission->id;
        $update->checked        = $data->checked;
        $update->timemodified = time();

        $DB->update_record('checkmark_submissions', $update);

        $submission = $this->get_submission($USER->id);
        $this->update_grade($submission);
        return $submission;
    }

    /**
     *  Display all the submissions ready for grading (including User-ID = Matrikelnumber
     *   and automated grading buttons)
     *
     * @global object
     * @global object
     * @global object
     * @global object
     * @param string $message
     * @return bool|void
     */
    public function display_submissions($message='') {
        global $SESSION, $CFG, $DB, $USER, $DB, $OUTPUT, $PAGE;

        $id = required_param('id', PARAM_INT);

        $inactive = null;
        $activetwo = null;
        $tabs = array();
        $row = array();

        $row[] = new tabobject('submissions',
                               $CFG->wwwroot.'/mod/checkmark/submissions.php?id='.$id.
                               '&amp;tab=submissions', get_string('strsubmissions', 'checkmark'),
                               get_string('strsubmissionstabalt', 'checkmark'), false);
        $row[] = new tabobject('printpreview',
                               $CFG->wwwroot.'/mod/checkmark/submissions.php?id='.$id.
                               '&amp;tab=printpreview', get_string('strprintpreview', 'checkmark'),
                               get_string('strprintpreviewtabalt', 'checkmark'), false);

        $tab = optional_param('tab', null, PARAM_ALPHAEXT);
        if ($tab) {
            $SESSION->checkmark->currenttab = $tab;
        }

        if (isset($SESSION->checkmark->currenttab)) {
            $currenttab = $SESSION->checkmark->currenttab;
        } else {
            if (!isset($SESSION->checkmark)) {
                $SESSION->checkmark = new stdClass();
            }
            $SESSION->checkmark->currenttab = 'submissions';
            $currenttab = 'submissions';
        }

        $ifirst = optional_param('ifirst', null, PARAM_RAW);

        if (!is_null($ifirst)
             && ($ifirst === '' || strstr(get_string('alphabet', 'langconfig'), $ifirst))) {
            //filter first names
            $SESSION->checkmark->ifirst = $ifirst;
        }

        $ilast = optional_param('ilast', null, PARAM_RAW);
        if (!is_null($ilast)
             && ($ilast === '' || strpos(get_string('alphabet', 'langconfig'), $ilast) !== false)) {
            //filter last names
            $SESSION->checkmark->ilast = $ilast;
        }

        $thide = optional_param('thide', null, PARAM_ALPHANUMEXT);
        if ($thide) { //hide table-column
            $SESSION->checkmark->columns[$thide]->visibility = 0;
        }

        $tshow = optional_param('tshow', null, PARAM_ALPHANUMEXT);
        if ($tshow) { //show table-column
            $SESSION->checkmark->columns[$tshow]->visibility = 1;
        }

        $tsort = optional_param('tsort', null, PARAM_ALPHANUMEXT);
        if ($tsort) { //sort table by column
            if (isset($SESSION->checkmark->columns[$tsort]->sortable)
                && ($SESSION->checkmark->columns[$tsort]->sortable==false)) {
                echo $OUTPUT->notification("$tsort is not sortable", 'notifyproblem');
            } else {
                if (isset($SESSION->checkmark->orderby)
                    && ($SESSION->checkmark->orderby == $tsort)) {
                    //change direction
                    if ($SESSION->checkmark->orderdirection == 'ASC') {
                        $SESSION->checkmark->orderdirection = 'DESC';
                    } else {
                        $SESSION->checkmark->orderdirection = 'ASC';
                    }
                } else {
                    //set new column for ordering
                    $SESSION->checkmark->orderby = $tsort;
                    $SESSION->checkmark->orderdirection = 'ASC';
                }
            }
        }

        switch ($SESSION->checkmark->currenttab) {
            case 'submissions':
                $inactive = array();
                $inactive[] = 'submissions';
                break;
            case 'printpreview':
                $inactive = array();
                $inactive[] = 'printpreview';
                break;
        }

        $tabs[] = $row;

        $autograde = optional_param('autograde', null, PARAM_INT);

        if (($autograde != null) && !isset($_POST['confirm'])) {
            $PAGE->set_title(format_string($this->checkmark->name, true));
            $PAGE->set_heading($this->course->fullname);

            if ($autograde == self::FILTER_SELECTED) {
                $selected = array();
                //@todo YOU shall not access $_POST directly!
                foreach ($_POST as $idx => $var) {
                    if ($var == "selected") {
                        //selecteduser[ID]
                        $usrid = substr($idx, 13);
                        array_push($selected, $usrid);
                    }
                }
                $SESSION->checkmark->autograde->selected = $selected;
            }
            switch ($autograde) {
                case self::FILTER_SELECTED:
                    if (count($selected) == 1) {
                        $amount = get_string('autograde_stronesubmission', 'checkmark');
                    } else {
                        $amount = get_string('autograde_strmultiplesubmissions', 'checkmark',
                                             count($selected));
                    }
                    break;
                case self::FILTER_REQUIRE_GRADING:
                    $amount = get_string('autograde_strreq', 'checkmark');
                    break;
                default:
                case self::FILTER_ALL:
                    $amount = get_string('autograde_strall', 'checkmark');
                    break;
            }

            echo $OUTPUT->header();
            $confirmboxcontent = $OUTPUT->confirm(get_string('autograde_confirm', 'checkmark',
                                                             $amount),
                                                  "submissions.php?id=$id&autograde=".
                                                  "$autograde&confirm=1", "submissions.php?id=$id");
            echo $OUTPUT->box($confirmboxcontent, 'generalbox');
            echo $OUTPUT->footer();
            exit;
        } else if ( $autograde != null) {

            if (has_capability('mod/checkmark:grade', context_module::instance($this->cm->id))) {
                $result = $this->autograde_submissions($autograde);
                if (!isset($message)) {
                    $message = '';
                } else {
                    $message .= html_writer::empty_tag('br');
                }
                if ($result['status'] == GRADE_UPDATE_OK) {
                    $message .= $OUTPUT->notification(get_string('autograde_success', 'checkmark',
                                                                 $result['updated']),
                                                      'notifysuccess');
                } else {
                    $message .= $OUTPUT->notification(get_string('autograde_failed', 'checkmark'),
                                                      'notifyproblem');
                }
            } else {
                print_error('autogradegrade_error', 'checkmark');
            }
        }

        $outputtabs = print_tabs($tabs, $currenttab, $inactive, $activetwo, true);

        switch ($currenttab) {
            case 'printpreview':
                echo $this->print_preview_tab($message, $outputtabs);
                break;
            case 'submissions':
            default:
                echo $this->submissions_tab($message, $outputtabs);
                break;
        }

    }

    /**
     *
     * content of the tab for normal submissions-view
     * @param $message
     */
    public function submissions_tab($message='', $outputtabs='') {
        global $SESSION, $CFG, $DB, $USER, $DB, $OUTPUT, $PAGE;
        require_once($CFG->libdir.'/gradelib.php');

        /* first we check to see if the form has just been submitted
         * to request user_preference updates
         */

        $returnstring = '';

        $filters = array(self::FILTER_ALL             => get_string('all'),
        self::FILTER_SUBMITTED       => get_string('submitted', 'checkmark'),
        self::FILTER_REQUIRE_GRADING => get_string('requiregrading', 'checkmark'));

        $updatepref = optional_param('updatepref', 0, PARAM_INT);

        if (isset($_POST['updatepref'])) {
            $perpage = optional_param('perpage', 10, PARAM_INT);
            $perpage = ($perpage <= 0) ? 10 : $perpage;
            $filter = optional_param('filter', self::FILTER_ALL, PARAM_INT);
            set_user_preference('checkmark_perpage', $perpage);
            set_user_preference('checkmark_quickgrade', optional_param('quickgrade', 0,
                                                                       PARAM_BOOL));
            set_user_preference('checkmark_filter', $filter);
        }

        /* next we get perpage and quickgrade (allow quick grade) params
         * from database
         */
        $perpage    = get_user_preferences('checkmark_perpage', 10);
        $quickgrade = get_user_preferences('checkmark_quickgrade', 0);
        $filter = get_user_preferences('checkmark_filter', self::FILTER_ALL);
        $grading_info = grade_get_grades($this->course->id, 'mod', 'checkmark',
                                         $this->checkmark->id);

        if (!empty($CFG->enableoutcomes) and !empty($grading_info->outcomes)) {
            $uses_outcomes = true;
        } else {
            $uses_outcomes = false;
        }

        $page    = optional_param('page', 0, PARAM_INT);
        $strsaveallfeedback = get_string('saveallfeedback', 'checkmark');

        /// Some shortcuts to make the code read better

        $course     = $this->course;
        $checkmark = $this->checkmark;
        $cm         = $this->cm;
        $hassubmission = false;

        $tabindex = 1; //tabindex for quick grading tabbing; Not working for dropdowns yet
        add_to_log($course->id, 'checkmark', 'view submission', 'submissions.php?id='.$this->cm->id,
                   $this->checkmark->id, $this->cm->id);

        $PAGE->set_title(format_string($this->checkmark->name, true));
        $PAGE->set_heading($this->course->fullname);
        $returnstring .= $OUTPUT->header();

        $returnstring .= $outputtabs;

        $returnstring .= html_writer::start_tag('div', array('class'=>'usersubmissions'));

        //hook to allow plagiarism plugins to update status/print links.
        plagiarism_update_status($this->course, $this->cm);

        $course_context = context_course::instance($course->id);
        if (has_capability('gradereport/grader:view', $course_context)
             && has_capability('moodle/grade:viewall', $course_context)) {
            $link_href = $CFG->wwwroot . '/grade/report/grader/index.php?id=' . $course->id;
            $link = html_writer::tag('a', get_string('seeallcoursegrades', 'grades'),
                                     array('href'=>$link_href));
            $returnstring .= html_writer::tag('div', $link, array('class'=>'allcoursegrades'));
        }

        if (!empty($message)) {
            $returnstring .= $message;   // display messages here if any
        }

        $context = context_module::instance($cm->id);

        /// Check to see if groups are being used in this checkmark
        echo $returnstring;
        $returnstring = '';
        /// find out current groups mode
        $groupmode = groups_get_activity_groupmode($cm);
        $currentgroup = groups_get_activity_group($cm, true);
        groups_print_activity_menu($cm, $CFG->wwwroot .
                                        '/mod/checkmark/submissions.php?id=' . $this->cm->id);

        /// Print quickgrade form around the table
        $formattrs = array();
        $formattrs['action'] = new moodle_url('/mod/checkmark/submissions.php');
        $formattrs['id'] = 'fastg';
        $formattrs['method'] = 'post';
        $formattrs['class'] = 'mform';

        $returnstring .= html_writer::start_tag('form', $formattrs);
        $returnstring .= html_writer::start_tag('div');
        $returnstring .= html_writer::empty_tag('input', array('type'  => 'hidden',
                                                               'name'  => 'id',
                                                               'value' =>  $this->cm->id));
        $returnstring .= html_writer::empty_tag('input', array('type'  => 'hidden',
                                                               'name'  => 'mode',
                                                               'value' => 'fastgrade'));
        $returnstring .= html_writer::empty_tag('input', array('type'  => 'hidden',
                                                               'name'  =>'page',
                                                               'value' => $page));
        $returnstring .= html_writer::empty_tag('input', array('type'  => 'hidden',
                                                               'name'  => 'sesskey',
                                                               'value' => sesskey()));
        $returnstring .= html_writer::end_tag('div');
        //}
        echo $returnstring;
        $returnstring = '';
        /// Get all ppl that are allowed to submit checkmarks
        list($esql, $params) = get_enrolled_sql($context, 'mod/checkmark:submit', $currentgroup);

        if ($filter == self::FILTER_ALL) {
            $sql = "SELECT u.id FROM {user} u ".
                   "LEFT JOIN ($esql) eu ON eu.id=u.id ".
                   "WHERE u.deleted = 0 AND eu.id=u.id ";
        } else {
            $wherefilter = '';
            if ($filter == self::FILTER_SUBMITTED) {
                $wherefilter = ' AND s.timemodified > 0';
            } else if ($filter == self::FILTER_REQUIRE_GRADING) {
                $wherefilter = ' AND s.timemarked < s.timemodified ';
            }

            $params['checkmarkid'] = $this->checkmark->id;
            $sql = "SELECT u.id FROM {user} u ".
                   "LEFT JOIN ($esql) eu ON eu.id=u.id ".
                   "LEFT JOIN {checkmark_submissions} s ON (u.id = s.user_id) " .
                   "WHERE u.deleted = 0 AND eu.id=u.id ".
                   'AND s.checkmark_id = :checkmarkid '.
                   $wherefilter;
        }

        $users = $DB->get_records_sql($sql, $params);
        if (!empty($users)) {
            $users = array_keys($users);
        }

        // if groupmembersonly used, remove users who are not in any group
        if ($users and !empty($CFG->enablegroupmembersonly) and $cm->groupmembersonly) {
            if ($groupingusers = groups_get_grouping_members($cm->groupingid, 'u.id', 'u.id')) {
                $users = array_intersect($users, array_keys($groupingusers));
            }
        }

        $tablecolumns = array('selection', 'picture', 'fullname', 'idnumber');
        $tableheaders = array('', '', get_string('fullnameuser'),
                              get_string('str_user_id', 'checkmark'));
        if ($groupmode != NOGROUPS) {
            $tableheaders[] = get_string('group');
            $tablecolumns[] = 'groups';
        }
        $tableheaders[] = get_string('grade');
        $tablecolumns[] = 'grade';
        $tableheaders[] = get_string('comment', 'checkmark');
        $tablecolumns[] = 'submissioncomment';
        $tableheaders[] = get_string('lastmodified').' ('.get_string('submission', 'checkmark').')';
        $tablecolumns[] = 'timemodified';
        $tableheaders[] = get_string('lastmodified').' ('.get_string('grade').')';
        $tablecolumns[] = 'timemarked';
        $tableheaders[] = get_string('status');
        $tablecolumns[] = 'status';
        $tableheaders[] = get_string('finalgrade', 'grades');
        $tablecolumns[] = 'finalgrade';
        if ($uses_outcomes) {
            $tableheaders[] = get_string('outcome', 'grades');
            $tablecolumns[] = 'outcome'; // no sorting based on outcomes column
        }

        require_once($CFG->libdir.'/tablelib.php');
        $table = new flexible_table('mod-checkmark-submissions');

        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $table->define_baseurl($CFG->wwwroot.'/mod/checkmark/submissions.php?id='.$this->cm->id.
                               '&amp;currentgroup='.$currentgroup);

        $table->sortable(true, 'lastname');//sorted by lastname by default
        $table->collapsible(true);
        $table->initialbars(true);

        $table->column_suppress('picture');
        $table->column_suppress('fullname');

        $table->column_class('picture', 'picture');
        $table->column_class('fullname', 'fullname');
        $table->column_class('idnumber', 'idnumber');
        if ($groupmode != NOGROUPS) {
            $table->column_class('groups', 'groups');
        }
        $table->column_class('grade', 'grade');
        $table->column_class('submissioncomment', 'comment');
        $table->column_class('timemodified', 'timemodified');
        $table->column_class('timemarked', 'timemarked');
        $table->column_class('status', 'status');
        $table->column_class('finalgrade', 'finalgrade');
        if ($uses_outcomes) {
            $table->column_class('outcome', 'outcome');
        }

        $table->set_attribute('cellspacing', '0');
        $table->set_attribute('id', 'attempts');
        $table->set_attribute('class', 'submissions');
        $table->set_attribute('width', '100%');

        $table->no_sorting('finalgrade');
        $table->no_sorting('outcome');

        // Start working -- this is necessary as soon as the niceties are over
        $table->setup();

        /// Construct the SQL
        list($where, $params) = $table->get_sql_where();
        if ($where) {
            $where .= ' AND ';
        }

        if ($filter == self::FILTER_SUBMITTED) {
            $where .= 's.timemodified > 0 AND ';
        } else if ($filter == self::FILTER_REQUIRE_GRADING) {
            $where .= 's.timemarked < s.timemodified AND ';
        }

        if ($sort = $table->get_sql_sort()) {
            $sort = ' ORDER BY '.$sort;
            if (($groupmode == NOGROUPS) && (isset($SESSION->checkmark->orderby))
                && ($SESSION->checkmark->orderby == 'groups')) {
                unset($SESSION->checkmark->orderby);

                $sort = '';
            }
        }

        if ($groupmode != NOGROUPS) {
            $getgroupsql = "SELECT grps.courseid, GROUP_CONCAT(DISTINCT grps.name
                                                               ORDER BY grps.name ";
            if (isset($SESSION->checkmark->orderby) && ($SESSION->checkmark->orderby == 'groups')) {
                if (isset($SESSION->checkmark->orderdirection)) {
                    $getgroupsql .= $SESSION->checkmark->orderdirection;
                } else {
                    $getgroupsql .= 'ASC';
                }
            }
            $params['courseid'] = $this->course->id;
            $getgroupsql .= " SEPARATOR ', ') AS groups, grpm.userid AS userid
                         FROM {groups_members} grpm
                         LEFT JOIN {groups} grps
                         ON grps.id = grpm.groupid
                         WHERE grps.courseid = :courseid
                         GROUP BY grpm.userid";
            $groupssql = " LEFT JOIN ($getgroupsql) AS grpq ON u.id = grpq.userid ";
        } else {
            $groupssql = "";
        }

        $ufields = user_picture::fields('u', array('idnumber'));
        if (!empty($users)) {
            $select = "SELECT $ufields,
                              s.id AS submissionid, s.grade, s.submissioncomment,
                              s.timemodified, s.timemarked ";
            if ($groupmode != NOGROUPS) {
                $select .= ", groups ";
            }

            //prepare SQL manually because the following 2 commands
            //won't work properly with "get_enrolled_sql"
            $sqluserids = null;
            foreach ($users as $sqluser) {
                if ($sqluserids == null) {
                    $sqluserids = "IN (:user".$sqluser;
                } else {
                    $sqluserids .= ", :user".$sqluser;
                }
                $params["user".$sqluser] = $sqluser;
            }
            $sqluserids .= ") ";

            $params['checkmark_id'] = $this->checkmark->id;
            $sql = 'FROM {user} u '.
                   'LEFT JOIN {checkmark_submissions} s ON u.id = s.user_id
                    AND s.checkmark_id = :checkmark_id '.
                    $groupssql.
                   'WHERE '.$where.'u.id '.$sqluserids;

            $ausers = $DB->get_records_sql($select.$sql.$sort, $params, $table->get_page_start(),
                                           $table->get_page_size());

            $table->pagesize($perpage, count($users));

            ///offset used to calculate index of student in that particular query,
            // needed for the pop up to know who's next
            $offset = $page * $perpage;
            $strupdate = get_string('update');
            $strgrade  = get_string('grade');
            $grademenu = make_grades_menu($this->checkmark->grade);

             $selectstate = optional_param('checkbox_controller1', null, PARAM_INT);

            if ($ausers !== false) {
                $grading_info = grade_get_grades($this->course->id, 'mod', 'checkmark',
                                                 $this->checkmark->id, array_keys($ausers));
                $endposition = $offset + $perpage;
                $currentposition = 0;
                foreach ($ausers as $auser) {
                    if ($currentposition == $offset && $offset < $endposition) {
                        $final_grade = $grading_info->items[0]->grades[$auser->id];
                        $grademax = $grading_info->items[0]->grademax;
                        $final_grade->formatted_grade = round($final_grade->grade, 2).
                                                        ' / '.round($grademax, 2);
                        $locked_overridden = 'locked';
                        if ($final_grade->overridden) {
                            $locked_overridden = 'overridden';
                        }

                        /// Calculate user status
                        $auser->status = ($auser->timemarked > 0)
                                         && ($auser->timemarked >= $auser->timemodified);
                        $selected_user = html_writer::checkbox('selectedeuser'.$auser->id,
                                                               'selected', $selectstate, null,
                                                               array('class'=>'checkboxgroup1'));
                        $picture = $OUTPUT->user_picture($auser);
                        if (!empty($auser->idnumber)) {
                            $idnumber = html_writer::tag('div', $auser->idnumber,
                                                         array('id'=>'uid'.$auser->id));
                        } else {
                            $idnumber = html_writer::tag('div', '-', array('id'=>'uid'.$auser->id));
                        }

                        if (isset($auser->groups)) {
                            $group = html_writer::tag('div', $auser->groups,
                                                      array('id'=>'gr'.$auser->id));
                        } else {
                            $group = html_writer::tag('div', '-', array('id'=>'gr'.$auser->id));
                        }

                        if (empty($auser->submissionid)) {
                            $auser->grade = -1; //no submission yet
                        }

                        $late = 0;
                        if (!empty($auser->submissionid)) {
                            $hassubmission = true;
                            ///Prints student answer and student modified date
                            ///Refer to print_student_answer in inherited classes.
                            if ($auser->timemodified > 0) {
                                $content = $this->print_student_answer($auser->id).
                                           userdate($auser->timemodified);
                                if ($this->checkmark->preventlate == 0) {
                                    $content .= $this->display_lateness($auser->timemodified);
                                }
                                $studentmodified = html_writer::tag('div', $content,
                                                                    array('id'=>'ts'.$auser->id));
                                $time = $this->checkmark->timedue - $auser->timemodified;
                                if ($time < 0) {
                                    $late = 1;
                                }
                            } else {
                                $studentmodified = html_writer::tag('div', '-',
                                                                    array('id'=>'ts'.$auser->id));
                            }
                            ///Print grade, dropdown or text
                            if ($auser->timemarked > 0) {
                                $date = userdate($auser->timemarked);
                                $teachermodified = html_writer::tag('div', $date,
                                                                    array('id'=>'tt'.$auser->id));

                                if ($final_grade->locked or $final_grade->overridden) {
                                    $gradeattr = array('id'    => 'g'.$auser->id,
                                                       'class' => '.$locked_overridden');
                                    $grade = html_writer::tag('div', $final_grade->formatted_grade,
                                                              $gradeattr);
                                } else if ($quickgrade) {
                                    $attributes = array();
                                    $attributes['tabindex'] = $tabindex++;
                                    $mademenu = make_grades_menu($this->checkmark->grade);
                                    $menu = html_writer::select($mademenu,
                                                                'menu['.$auser->id.']',
                                                                $auser->grade,
                                                                array(-1=>get_string('nograde')),
                                                                $attributes);
                                    $oldgradeattr = array('type'  => 'hidden',
                                                          'name'  => 'oldgrade'.$auser->id,
                                                          'value' => $auser->grade);
                                    $oldgrade = html_writer::empty_tag('input', $oldgradeattr);
                                    $grade = html_writer::tag('div', $menu.$oldgrade,
                                                              array('id'=>'g'.$auser->id));
                                } else {
                                    $grade = html_writer::tag('div',
                                                              $this->display_grade($auser->grade),
                                                              array('id'=>'g'.$auser->id));
                                }

                            } else {
                                $teachermodified = html_writer::tag('div', '-',
                                                                    array('id'=>'tt'.$auser->id));
                                if ($final_grade->locked or $final_grade->overridden) {
                                    $grade = html_writer::tag('div', $final_grade->formatted_grade,
                                                              array('id'=>'g'.$auser->id,
                                                                    'class'=>$locked_overridden));
                                } else if ($quickgrade) {
                                    $attributes = array();
                                    $attributes['tabindex'] = $tabindex++;
                                    $mademenu = make_grades_menu($this->checkmark->grade);
                                    $menu = html_writer::select($mademenu,
                                                                'menu['.$auser->id.']',
                                                                $auser->grade,
                                                                array(-1=>get_string('nograde')),
                                                                $attributes);
                                    $inputarr = array('type'  => 'hidden',
                                                      'name'  => 'oldgrade'.$auser->id,
                                                      'value' => $auser->grade);
                                    $oldgrade = html_writer::empty_tag('input', $inputarr);
                                    $grade = html_writer::tag('div', $menu.$oldgrade,
                                                              array('id'=>'g'.$auser->id));
                                } else {
                                    $grade = html_writer::tag('div',
                                                              $this->display_grade($auser->grade),
                                                              array('id'=>'g'.$auser->id));
                                }
                            }
                            ///Print Comment
                            if ($final_grade->locked or $final_grade->overridden) {
                                $shrtcom = shorten_text(strip_tags($final_grade->str_feedback), 15);
                                $comment = html_writer::tag('div', $shrtcom,
                                                            array('id'=>'com'.$auser->id));
                            } else if ($quickgrade) {
                                $inputarr =  array('type'  => 'hidden',
                                                   'name'  => 'oldcomment'.$auser->id,
                                                   'value' => trim($auser->submissioncomment));
                                $oldcomment = html_writer::empty_tag('input', $inputarr);
                                $content = html_writer::tag('textarea', $auser->submissioncomment,
                                                            array('tabindex'=>$tabindex++,
                                                                  'name'=>'submissioncomment['.
                                                                          $auser->id.']',
                                                                  'id'=>'submissioncomment'.
                                                                        $auser->id,
                                                                  'rows'=>2,
                                                                  'cols'=>20));
                                $comment = html_writer::tag('div', $content.$oldcomment,
                                                            array('id'=>'com'.$auser->id));
                            } else {
                                $shortcom = shorten_text(strip_tags($auser->submissioncomment), 15);
                                $comment = html_writer::tag('div', $shortcom,
                                                            array('id'=>'com'.$auser->id));
                            }
                        } else {
                            $studentmodified = html_writer::tag('div', '&nbsp;',
                                                                array('id'=>'ts'.$auser->id));
                            $teachermodified = html_writer::tag('div', '&nbsp;',
                                                                array('id'=>'tt'.$auser->id));
                            $status = html_writer::tag('div', '&nbsp;',
                                                       array('id'=>'st'.$auser->id));

                            if ($final_grade->locked or $final_grade->overridden) {
                                $grade = html_writer::tag('div', $final_grade->formatted_grade,
                                                          array('id'=>'g'.$auser->id));
                                $hassubmission = true;
                            } else if ($quickgrade) {   // allow editing
                                $attributes = array();
                                $attributes['tabindex'] = $tabindex++;
                                $mademenue = make_grades_menu($this->checkmark->grade);
                                $menu = html_writer::select($mademenue,
                                                           'menu['.$auser->id.']',
                                                           $auser->grade,
                                                           array(-1=>get_string('nograde')),
                                                           $attributes);
                                $oldgradearr = array('type'  => 'hidden',
                                                     'name'  => 'oldgrade'.$auser->id,
                                                     'value' => $auser->grade);
                                $oldgrade = html_writer::empty_tag('input', $oldgradearr);
                                $grade = html_writer::tag('div', $menu.$oldgrade,
                                                          array('id'=>'g'.$auser->id));
                                $hassubmission = true;
                            } else {
                                $grade = html_writer::tag('div', '-', array('id'=>'g'.$auser->id));
                            }

                            if ($final_grade->locked or $final_grade->overridden) {
                                $comment = html_writer::tag('div', $final_grade->str_feedback,
                                                            array('id'=>'com'.$auser->id));
                            } else if ($quickgrade) {
                                $inputarr = array('type'  => 'hidden',
                                                  'name'  => 'oldcomment'.$auser->id,
                                                  'value' => trim($auser->submissioncomment));
                                $oldcomment = html_writer::empty_tag('input', $inputarr);

                                $content = html_writer::tag('textarea', $auser->submissioncomment,
                                                            array('tabindex='=>$tabindex++,
                                                                  'name'=>'submissioncomment['.
                                                                          $auser->id.']',
                                                                  'id'=>'submissioncomment'.
                                                                        $auser->id,
                                                                  'rows'=>'2',
                                                                  'cols'=>'20'));
                                $comment = html_writer::tag('div', $content.$oldcomment,
                                                            array('id'=>'com'.$auser->id));
                            } else {
                                $comment = html_writer::tag('div', '&nbsp;',
                                                            array('id'=>'com'.$auser->id));
                            }
                        }

                        if (empty($auser->status)) { /// Confirm we have exclusively 0 or 1
                            $auser->status = 0;
                        } else {
                            $auser->status = 1;
                        }

                        $buttontext = ($auser->status == 1) ? $strupdate : $strgrade;

                        ///No more buttons, we use popups ;-).
                        $popup_url = '/mod/checkmark/submissions.php?id='.$this->cm->id.
                                     '&amp;userid='.$auser->id.'&amp;mode=single'.
                                     '&amp;filter='.$filter.'&amp;offset='.$offset++;

                        $button = $OUTPUT->action_link($popup_url, $buttontext);

                        $status = html_writer::tag('div', $button, array('id'=>'up'.$auser->id,
                                                                         'class'=>'s'.
                                                                                  $auser->status));

                        $finalgrade = html_writer::tag('span', $final_grade->str_grade,
                                                       array('id'=>'finalgrade_'.$auser->id));

                        $outcomes = '';

                        if ($uses_outcomes) {

                            foreach ($grading_info->outcomes as $n => $outcome) {
                                $outcomes .= html_writer::start_tag('div',
                                                                    array('class'=>'outcome'));
                                $outcomes .= html_writer::tag('label', $outcome->name);
                                $options = make_grades_menu(-$outcome->scaleid);

                                if ($outcome->grades[$auser->id]->locked or !$quickgrade) {
                                    $options[0] = get_string('nooutcome', 'grades');
                                    $index = $outcome->grades[$auser->id]->grade;
                                    $outcomes .= ': '.
                                                html_writer::tag('span',
                                                                 $options[$index],
                                                                 array('id'=>'outcome_'.$n.'_'.
                                                                             $auser->id));
                                } else {
                                    $attributes = array();
                                    $attributes['tabindex'] = $tabindex++;
                                    $attributes['id'] = 'outcome_'.$n.'_'.$auser->id;
                                    $usr = $auser->id;
                                    $outcomes .= ' '.
                                                 html_writer::select($options,
                                                                     'outcome_'.$n.'['.$usr.']',
                                                                     $outcome->grades[$usr]->grade,
                                                                     array(get_string('nooutcome',
                                                                                      'grades')),
                                                                     $attributes);
                                }
                                $outcomes .= html_writer::end_tag('div');
                            }
                        }
                        $linktext = fullname($auser, has_capability('moodle/site:viewfullnames',
                                                                    $this->context));
                        $linkurl = $CFG->wwwroot . '/user/view.php?id=' . $auser->id .
                                   '&amp;course=' . $course->id;
                        $userlink = html_writer::tag('a', $linktext, array('href'=>$linkurl));
                        $row = array($selected_user, $picture, $userlink, $idnumber);
                        if ($groupmode != NOGROUPS) {
                            $row[] = $group;
                        }
                        $row[] = $grade;
                        $row[] = $comment;
                        $row[] = $studentmodified;
                        $row[] = $teachermodified;
                        $row[] = $status;
                        $row[] = $finalgrade;
                        if ($uses_outcomes) {
                            $row[] = $outcomes;
                        }
                        $table->add_data($row, $late ? 'late':'');
                    }
                    $currentposition++;
                }
            } else {
                $returnstring = html_writer::tag('div', get_string('nousers', 'checkmark'),
                                                 array('class'=>'nosubmisson'));
            }
            echo $returnstring;
            $returnstring = '';
            $table->print_html();  /// Print the whole table
            echo html_writer::tag('div', $this->add_checkbox_controller(1, null, null, 0),
                                  array('class'=>'checkboxcontroller'));
        } else {
            if ($filter == self::FILTER_SUBMITTED) {
                $returnstring .= html_writer::tag('div', get_string('nosubmisson', 'checkmark'),
                                                  array('class'=>'nosubmission'));
            } else if ($filter == self::FILTER_REQUIRE_GRADING) {
                $returnstring .= html_writer::tag('div',
                                                  get_string('norequiregrading', 'checkmark'),
                                                  array('class'=>'norequiregrading'));
            } else {
                $returnstring .= html_writer::tag('div', get_string('nostudents', 'checkmark'),
                                                  array('class'=>'nostudents'));
            }
        }

        /// Print quickgrade form around the table
        if ($quickgrade && $table->started_output && !empty($users)) {
            $mailinfopref = false;
            if (get_user_preferences('checkmark_mailinfo', 1)) {
                $mailinfopref = true;
            }
            $emailnotification =  html_writer::checkbox('mailinfo', 1, $mailinfopref,
                                                        get_string('enablenotification',
                                                                   'checkmark'));

            $emailnotification .= $OUTPUT->help_icon('enablenotification', 'checkmark');
            $returnstring .= html_writer::tag('div', $emailnotification,
                                              array('class'=>'emailnotification'));

            $savefeedback = html_writer::empty_tag('input',
                                                   array('type'  => 'submit',
                                                         'name'  => 'fastg',
                                                         'value' => get_string('saveallfeedback',
                                                                               'checkmark')));
            $returnstring .= html_writer::tag('div', $savefeedback,
                                              array('class'=>'fastgbutton optionspref'));
        }
        $autograde_fieldset = html_writer::tag('legend',
                                               $OUTPUT->help_icon('autograde_str', 'checkmark').
                                               get_string('autogradebuttonstitle', 'checkmark',
                                                          $checkmark->name));
        $autograde_custom = html_writer::empty_tag('input',
                                                   array('type'  => 'submit',
                                                         'name'  => 'autograde_custom_submit',
                                                         'value' => get_string('autograde_custom',
                                                                               'checkmark')));
        $autograde_fieldset .= html_writer::tag('div', $autograde_custom,
                                                array('class'=>'autogradingform'));
        $autograde_req = html_writer::empty_tag('input',
                                                array('type'  => 'submit',
                                                      'name'  => 'autograde_req_submit',
                                                      'value' => get_string('autograde_req',
                                                                            'checkmark')));
        $autograde_fieldset .= html_writer::tag('div', $autograde_req,
                                                array('class'=>'autogradingform'));
        $autograde = html_writer::empty_tag('input',
                                            array('type'  => 'submit',
                                                  'name'  => 'autograde_all_submit',
                                                  'value' => get_string('autograde_all',
                                                                        'checkmark')));
        $autograde_fieldset .= html_writer::tag('div', $autograde,
                                               array('class'=>'autogradingform'));

        $returnstring .= html_writer::tag('fieldset', $autograde_fieldset,
                                          array('class' => 'clearfix',
                                                'id'    => 'autogradingfieldset'));
        $returnstring .= html_writer::end_tag('form');

        $returnstring .= html_writer::end_tag('div');
        /// End of fast grading form

        $returnstring .= html_writer::empty_tag('br', array('class'=>'clearfloat'));

        /// Mini form for setting user preference
        echo $returnstring;
        $returnstring = '';

        $formaction = new moodle_url('/mod/checkmark/submissions.php', array('id'=>$this->cm->id));
        $mform = new MoodleQuickForm('optionspref', 'post', $formaction, '',
                                     array('class'=>'optionspref'));

        $mform->addElement('hidden', 'updatepref');
        $mform->setDefault('updatepref', 1);
        $mform->addElement('header', 'qgprefs', get_string('optionalsettings', 'checkmark'));
        $mform->addElement('select', 'filter', get_string('show'), $filters);

        $mform->addElement('hidden', 'sesskey');
        $mform->setDefault('sesskey', sesskey());
        
        $mform->setDefault('filter', $filter);

        $mform->addElement('text', 'perpage', get_string('pagesize', 'checkmark'),
                           array('size' => 1));
        $mform->setDefault('perpage', $perpage);

        $mform->addElement('checkbox', 'quickgrade', get_string('quickgrade', 'checkmark'));
        $mform->setDefault('quickgrade', $quickgrade);
        $mform->addHelpButton('quickgrade', 'quickgrade', 'checkmark');

        $mform->addElement('submit', 'savepreferences', get_string('savepreferences'));

        $mform->display();

        $returnstring .= $OUTPUT->footer();

        return $returnstring;
    }

    public function print_preview_tab($message='', $outputtabs='') {
        global $SESSION, $CFG, $DB, $USER, $OUTPUT, $PAGE;
        require_once($CFG->libdir.'/gradelib.php');

        /* first we check to see if the form has just been submitted
         * to request user_preference updates
         */
        $returnstring = '';

        $filters = array(self::FILTER_ALL             => get_string('all'),
        self::FILTER_SUBMITTED       => get_string('submitted', 'checkmark'),
        self::FILTER_REQUIRE_GRADING => get_string('requiregrading', 'checkmark'));

        $updatepref = optional_param('updatepref', 0, PARAM_INT);

        if ($updatepref && confirm_sesskey()) {
            $printperpage = optional_param('printperpage', 0, PARAM_INT);
            $printperpage = ($printperpage <= 0) ? 0 : $printperpage;
            $filter = optional_param('filter', self::FILTER_ALL, PARAM_INT);
            set_user_preference('checkmark_printperpage', $printperpage);
            set_user_preference('checkmark_filter', $filter);
        }
        $textsize = optional_param('textsize', 0, PARAM_INT);
        $pageorientation = optional_param('pageorientation', 0, PARAM_INT);

        /* next we get perpage (allow quick grade) params
         * from database
         */
        $printperpage    = get_user_preferences('checkmark_printperpage', 10);
        $filter = get_user_preferences('checkmark_filter', self::FILTER_ALL);
        $grading_info = grade_get_grades($this->course->id, 'mod', 'checkmark',
                                         $this->checkmark->id);

        if (!empty($CFG->enableoutcomes) and !empty($grading_info->outcomes)) {
            $uses_outcomes = true;
        } else {
            $uses_outcomes = false;
        }

        /// Some shortcuts to make the code read better

        $course     = $this->course;
        $checkmark = $this->checkmark;
        $cm         = $this->cm;
        $hassubmission = false;

        $tabindex = 1; //tabindex for quick grading tabbing; Not working for dropdowns yet
        add_to_log($this->checkmark->course, 'checkmark', 'view print-preview',
                   'submissions.php?id='.$this->cm->id, $this->checkmark->id, $this->cm->id);

        $PAGE->set_title(format_string($this->checkmark->name, true));
        $PAGE->set_heading($this->course->fullname);
        $returnstring .= $OUTPUT->header();

        $returnstring .= $outputtabs;

        /// form to manage print-settings
        $returnstring .= html_writer::start_tag('div', array('class'=>'usersubmissions'));
        $formaction = new moodle_url('/mod/checkmark/submissions.php', array('id'=>$this->cm->id,
                                                                             'updatepref'=>1,
                                                                             'sesskey'=>sesskey()));
        $formhtml = "";

        $datasettingselements = "";

        $select = html_writer::tag('div', get_string('show'), array('class'=>'fitemtitle')).
                  html_writer::tag('div', html_writer::select($filters, 'filter', $filter, false),
                                   array('class'=>'felement'));
        $datasettingselements .= html_writer::tag('div', $select, array('class'=>'fitem'));

        /// Check to see if groups are being used in this checkmark
        /// find out current groups mode
        $groupmode = groups_get_activity_groupmode($cm);
        $currentgroup = groups_get_activity_group($cm, true);
        $datasettingselements .= $this->moodleform_groups_print_activity_menu($cm, true);

        $datasettingselements .= $this->print_moodleform_initials_bar();

        $button = html_writer::tag('button', get_string('strrefreshdata', 'checkmark'),
                                   array('type'  => 'submit',
                                         'name'  => 'submitdataview',
                                         'value' => 'true'));
        $submit = html_writer::tag('div', "&nbsp;", array('class'=>'fitemtitle')).
                  html_writer::tag('div', $button, array('class'=>'felement'));
        $datasettingselements .= html_writer::tag('div', $submit, array('class'=>'fitem'));
        $datasettingselements = html_writer::tag('legend',
                                                 get_string('datasettingstitle', 'checkmark')).
                                html_writer::tag('div', $datasettingselements,
                                                 array('class'=>'fcontainer'));

        $formhtml .= html_writer::tag('fieldset', $datasettingselements,
                                      array('name'  => 'data_settings_header',
                                            'class' => 'clearfix'));

        $printsettingselements = "";

        //using zero for autopaging
        $inputattr = array('size'  => 3,
                           'name'  => 'printperpage',
                           'value' => $printperpage,
                           'type'  => 'text');
        $input = html_writer::empty_tag('input', $inputattr);
        $printperpagehtml = html_writer::tag('div', get_string('pagesize', 'checkmark'),
                                             array('class'=>'fitemtitle')).
                            html_writer::tag('div', $input, array('class'=>'felement'));
        $printsettingselements .= html_writer::tag('div', $printperpagehtml,
                                                   array('class'=>'fitem'));

        $textsizes = array(  0=>get_string('strsmall', 'checkmark'),
                             1=>get_string('strmedium', 'checkmark'),
                             2=>get_string('strlarge', 'checkmark'));

        $select = html_writer::select($textsizes, 'textsize', $textsize, false);
        $textsizehtml = html_writer::tag('div', get_string('strtextsize', 'checkmark'),
                                         array('class'=>'fitemtitle')).
                        html_writer::tag('div', $select, array('class'=>'felement'));
        $printsettingselements .= html_writer::tag('div', $textsizehtml, array('class'=>'fitem'));

        $pageorientations = array(  0=>get_string('strlandscape', 'checkmark'),
                                    1=>get_string('strportrait', 'checkmark')   );
        $select = html_writer::select($pageorientations, 'pageorientation', $pageorientation,
                                      false);
        $pageorientationhtml = html_writer::tag('div',
                                                get_string('strpageorientation', 'checkmark'),
                                                array('class'=>'fitemtitle')).
                               html_writer::tag('div', $select, array('class'=>'felement'));
        $printsettingselements .= html_writer::tag('div', $pageorientationhtml,
                                                   array('class'=>'fitem'));

        $printheaderattr = array('name'=>'printheader', 'value'=>'1', 'type'=>'checkbox');
        if (optional_param('printheader', 1, PARAM_INT)) {
            $printheaderattr['checked'] = 'checked';
        }
        $advcheckbox = html_writer::empty_tag('input', array('name'  => 'printheader',
                                                             'value' => '0',
                                                             'type'  => 'hidden')).
                       html_writer::empty_tag('input', $printheaderattr);
        $printheaderhtml = html_writer::tag('div', get_string('strprintheader', 'checkmark').
                                                   $OUTPUT->help_icon('strprintheader',
                                                                      'checkmark'),
                                            array('class'=>'fitemtitle')).
                           html_writer::tag('div', $advcheckbox, array('class'=>'felement'));
        $printsettingselements .= html_writer::tag('div', $printheaderhtml,
                                                   array('class'=>'fitem'));

        $button = html_writer::tag('button', get_string('strprint', 'checkmark'),
                                   array('name'  => 'submittoprint',
                                         'type'  => 'submit',
                                         'value' => 'true'));
        $submithtml = html_writer::tag('div', "&nbsp;", array('class'=>'fitemtitle')).
                      html_writer::tag('div', $button, array('class'=>'felement'));
        $printsettingselements .= html_writer::tag('div', $submithtml, array('class'=>'fitem'));
        $printsettingselements = html_writer::tag('legend',
                                                  get_string('printsettingstitle', 'checkmark')).
                                 html_writer::tag('div', $printsettingselements,
                                                  array('class'=>'fcontainer'));
        $formhtml .= html_writer::tag('fieldset', $printsettingselements,
                                      array('name'=>'print_settings_header', 'class'=>'clearfix'));

        //hook to allow plagiarism plugins to update status/print links.
        plagiarism_update_status($this->course, $this->cm);

        if (!empty($message)) {
            $returnstring .= $message;   // display messages here if any
        }

        $context = context_module::instance($cm->id);

        /// Get all ppl that are allowed to submit checkmarks
        list($esql, $params) = get_enrolled_sql($context, 'mod/checkmark:submit', $currentgroup);

        if ($filter == self::FILTER_ALL) {
            $sql = "SELECT u.id FROM {user} u ".
                   "LEFT JOIN ($esql) eu ON eu.id=u.id ".
                   "WHERE u.deleted = 0 AND eu.id=u.id ";
        } else {
            $wherefilter = '';
            if ($filter == self::FILTER_SUBMITTED) {
                $wherefilter = ' AND s.timemodified > 0';
            } else if ($filter == self::FILTER_REQUIRE_GRADING) {
                $wherefilter = ' AND s.timemarked < s.timemodified ';
            }
            $params['checkmarkid'] = $this->checkmark->id;
            $sql = "SELECT u.id FROM {user} u ".
                   "LEFT JOIN ($esql) eu ON eu.id=u.id ".
                   "LEFT JOIN {checkmark_submissions} s ON (u.id = s.user_id) " .
                   "WHERE u.deleted = 0 AND eu.id=u.id ".
                   'AND s.checkmark_id = :checkmarkid '.
                   $wherefilter;
        }

        $users = $DB->get_records_sql($sql, $params);
        if (!empty($users)) {
            $users = array_keys($users);
        }

        // if groupmembersonly used, remove users who are not in any group
        if ($users and !empty($CFG->enablegroupmembersonly) and $cm->groupmembersonly) {
            if ($groupingusers = groups_get_grouping_members($cm->groupingid, 'u.id', 'u.id')) {
                $users = array_intersect($users, array_keys($groupingusers));
            }
        }

        $tablecolumns = array('selection', 'fullnameuser', 'idnumber');
        $tableheaders = array('',
            $this->get_submissions_column_header('fullnameuser', get_string('fullnameuser')),
            $this->get_submissions_column_header('idnumber',
                                                 get_string('str_user_id', 'checkmark')));
        if ($groupmode != NOGROUPS) {
            $tableheaders[] = $this->get_submissions_column_header('groups', get_string('group'));
            $tablecolumns[] = 'groups';
        }
        //dynamically add examples
        if ($this->checkmark->flexiblenaming) {
            $names = explode(self::DELIMITER, $this->checkmark->examplenames);
            $grades = explode(self::DELIMITER, $this->checkmark->examplegrades);
            foreach ($names as $key => $name) {
                $count = $key+1;
                $tablecolumns[] = 'example'.$count;
                $tableheaders[] = $this->get_submissions_column_header('example'.$count,
                                                                       $name.
                                                                       html_writer::empty_tag('br').
                                                                       '('.$grades[$key].' P)');
            }
        } else {
            $points = $this->checkmark->grade/$this->checkmark->examplecount;
            for ($i=1; $i<=$this->checkmark->examplecount; $i++) {
                $number = $i+$this->checkmark->examplestart-1;
                $tablecolumns[] = 'example'.$i;
                $tableheaders[] = $this->get_submissions_column_header('example'.$i,
                                                                       get_string('strexample',
                                                                                  'checkmark').
                                                                       ' '.$number.
                                                                       html_writer::empty_tag('br').
                                                                       '('.$points.' P)');
            }
        }
        $tablecolumns[] = 'grade';
        $tableheaders[] = $this->get_submissions_column_header('grade', get_string('grade'));
        $tablecolumns[] = 'comment';
        $tableheaders[] = $this->get_submissions_column_header('comment',
                                                               get_string('comment', 'checkmark'));
        if ($uses_outcomes) {
            $tablecolumns[] = 'outcome'; // no sorting based on outcomes column
            $tableheaders[] = $this->get_submissions_column_header('outcome', get_string('outcome',
                                                                   'grades'));
        }

        $table = new html_table();
        if (!isset($table->attributes)) {
            $table->attributes = array('class' => 'coloredrows');
        } else if (!isset($table->attributes['class'])) {
            $table->attributes['class'] = 'coloredrows';
        } else {
            $table->attributes['class'] .= ' coloredrows';
        }

        $table->colclasses = $tablecolumns;
        //instead of the strings a array of html_table_cells can be set as head
        $table->head = $tableheaders;

        /// Construct the SQL
        $conditions = array();
        $params = array();
        if (in_array('fullnameuser', $tablecolumns)) {
            static $i = 0;
            $i++;
            if (!empty($SESSION->checkmark->ifirst)) {
                $conditions[] = $DB->sql_like('firstname', ':ifirstc'.$i, false, false);
                $params['ifirstc'.$i] = $SESSION->checkmark->ifirst.'%';
            }
            if (!empty($SESSION->checkmark->ilast)) {
                $conditions[] = $DB->sql_like('lastname', ':ilastc'.$i, false, false);
                $params['ilastc'.$i] = $SESSION->checkmark->ilast.'%';
            }
        }
        list($where, $params) = array(implode(" AND ", $conditions), $params);

        if ($where) {
            $where .= ' AND ';
        }

        if ($filter == self::FILTER_SUBMITTED) {
            $where .= 's.timemodified > 0 AND ';
        } else if ($filter == self::FILTER_REQUIRE_GRADING) {
            $where .= 's.timemarked < s.timemodified AND ';
        }

        $sort = '';
        $ufields = user_picture::fields('u');

        if ($groupmode != NOGROUPS) {
            $getgroupsql = "SELECT grps.courseid, GROUP_CONCAT(DISTINCT grps.name
                                                               ORDER BY grps.name ";
            if (isset($SESSION->checkmark->orderby) && ($SESSION->checkmark->orderby == 'groups')) {
                if (isset($SESSION->checkmark->orderdirection)) {
                    $getgroupsql .= $SESSION->checkmark->orderdirection;
                } else {
                    $getgroupsql .= 'ASC';
                }
            }
            $getgroupsql .= " SEPARATOR ', ') AS groups, grpm.userid AS userid
                         FROM {groups_members} grpm
                         LEFT JOIN {groups} grps
                         ON grps.id = grpm.groupid
                         WHERE grps.courseid = :courseid
                         GROUP BY grpm.userid";
            $params['courseid'] = $this->course->id;
            $groupssql = " LEFT JOIN ($getgroupsql) AS grpq ON u.id = grpq.userid ";
        } else {
            $groupssql = "";
        }

        if (!empty($users)) {
            $select = " SELECT $ufields, u.idnumber,
                              s.id AS submissionid, s.grade, s.submissioncomment,
                              s.timemodified, s.timemarked ";
            if ($groupmode != NOGROUPS) {
                    $select .= ", groups ";
            }
            $params['checkmarkid'] = $this->checkmark->id;

            //prepare SQL manually because the following 2 commands
            //won't work properly with "get_enrolled_sql"
            //list($sqluserids, $userparams) = $DB->get_in_or_equal($usrlst);
            //$params = array_merge_recursive($params, $userparams);
            $sqluserids = null;
            foreach ($users as $sqluser) {
                if ($sqluserids == null) {
                    $sqluserids = "IN (:user".$sqluser;
                } else {
                    $sqluserids .= ", :user".$sqluser;
                }
                $params["user".$sqluser] = $sqluser;
            }
            $sqluserids .= ") ";

            $sql = 'FROM {user} AS u '.
                   'LEFT JOIN {checkmark_submissions} AS s ON u.id = s.user_id
                    AND s.checkmark_id = :checkmarkid '.
                   $groupssql.
                   'WHERE '.$where.'u.id '.$sqluserids;

            if (isset($SESSION->checkmark->orderby)) {
                $sort = ' ORDER BY '.$SESSION->checkmark->orderby;
                if (isset($SESSION->checkmark->orderdirection)
                        && ($SESSION->checkmark->orderdirection == 'DESC')) {
                    $sort .= ' DESC';
                } else {
                    $sort .= ' ASC';
                }
                if (($groupmode == NOGROUPS) && ($SESSION->checkmark->orderby == 'groups')) {
                    unset($SESSION->checkmark->orderby);
                    $sort = '';
                }
            }

            $ausers = $DB->get_records_sql($select.$sql.$sort, $params);

            $strupdate = get_string('update');
            $strgrade  = get_string('grade');
            $grademenu = make_grades_menu($this->checkmark->grade);

            if (!empty($ausers)) {
                $grading_info = grade_get_grades($this->course->id, 'mod', 'checkmark',
                                                 $this->checkmark->id, array_keys($ausers));

                foreach ($ausers as $auser) {
                    if (isset($auser->groups)) {
                        $group = html_writer::tag('div', $auser->groups,
                                                  array('id'=>'gr'.$auser->id));
                    } else {
                        $group = html_writer::tag('div', '-', array('id'=>'gr'.$auser->id));
                    }

                    $final_grade = $grading_info->items[0]->grades[$auser->id];
                    $grademax = $grading_info->items[0]->grademax;
                    $final_grade->formatted_grade = round($final_grade->grade, 2)
                                                    .' / '.round($grademax, 2);
                    $locked_overridden = 'locked';
                    if ($final_grade->overridden) {
                        $locked_overridden = 'overridden';
                    }

                    /// Calculate user status
                    $auser->status = ($auser->timemarked > 0)
                                     && ($auser->timemarked >= $auser->timemodified);
                    if (optional_param('nosubmit_checkbox_controller2', 0, PARAM_BOOL)) {
                        $state = optional_param('checkbox_controller2', true, PARAM_INT);
                    } else {
                        $state = 1;
                    }
                    $selected_user = html_writer::checkbox('selecteduser'.$auser->id, 'selected',
                                                           $state, null,
                                                           array('class'=>'checkboxgroup2'));

                    $user_id = html_writer::tag('div', $auser->idnumber,
                                                array('id'=>'uid'.$auser->id));

                    if (empty($auser->submissionid)) {
                        $auser->grade = -1; //no submission yet
                    }

                    if (!empty($auser->submissionid)) {
                        $hassubmission = true;
                        ///Print examples
                        $submission = $this->get_submission($auser->id);
                        $checked_examples = explode(self::DELIMITER, $submission->checked);

                        if ($this->checkmark->flexiblenaming) {
                            $names = explode(self::DELIMITER, $this->checkmark->examplenames);
                            $examples[0] = null;
                            for ($i=1; $i<=count($names); $i++) {
                                $columnname = 'example'.$i+1;
                                if (isset($SESSION->checkmark->columns[$columnname])) {
                                    $vis = $SESSION->checkmark->columns[$columnname]->visibility;
                                } else {
                                    $vis = 1;
                                }
                                if ($vis == 0) {
                                    $examples[$i] = '&nbsp;';
                                } else {
                                    $examples[$i] = html_writer::tag('div', self::EMPTYBOX,
                                                                     array('id'=>'ex'.$auser->id.
                                                                                 '_'.$i));
                                }
                            }
                        } else {
                            $examples[0] = null;
                            for ($i=1; $i<=$this->checkmark->examplecount; $i++) {
                                $columnname = 'example'.$i+1;
                                if (isset($SESSION->checkmark->columns[$columnname])) {
                                    $vis = $SESSION->checkmark->columns[$columnname]->visibility;
                                } else {
                                    $vis = 1;
                                }
                                
                                if ($vis == 0) {
                                    $examples[$i] = '&nbsp;';
                                } else {
                                    $examples[$i] = html_writer::tag('div', self::EMPTYBOX,
                                                                     array('id'=>'ex'.$auser->id.
                                                                                 '_'.$i));
                                }
                            }
                        }

                        foreach ($checked_examples as $checked_example) {
                            $columnname = 'example'.$checked_example;
                            if (isset($SESSION->checkmark->columns[$columnname])) {
                                $vis = $SESSION->checkmark->columns[$columnname]->visibility;
                            } else {
                                $vis = 1;
                            }
                            
                            if ($vis == 0) {
                                $examples[$checked_example] = '&nbsp;';
                            } else {
                                $temp = html_writer::tag('div', self::CHECKEDBOX,
                                                         array('id'=>'ex'.$auser->id.'_'.
                                                                     $checked_example));
                                $examples[$checked_example] = $temp;
                            }
                        }

                        ///Print grade or text
                        if ($final_grade->locked or $final_grade->overridden) {
                            $grade = html_writer::tag('div', $final_grade->formatted_grade,
                                                      array('id'=>'g'.$auser->id,
                                                            'class'=>$locked_overridden));
                        } else {
                            $grade = html_writer::tag('div', $this->display_grade($auser->grade),
                                                      array('id'=>'g'.$auser->id));
                        }
                        ///Print Comment
                        if ($final_grade->locked or $final_grade->overridden) {
                            $shortcom = shorten_text(strip_tags($final_grade->str_feedback), 15);
                            $comment = html_writer::tag('div', $shortcom,
                                                        array('id'=>'com'.$auser->id));

                        } else {
                            $shortcom = shorten_text(strip_tags($auser->submissioncomment), 15);
                            $comment = html_writer::tag('div', $shortcom,
                                                        array('id'=>'com'.$auser->id));
                        }
                    } else {
                        $studentmodified = html_writer::tag('div', '&nbsp;',
                                                            array('id'=>'ts'.$auser->id));
                        $teachermodified = html_writer::tag('div', '&nbsp;',
                                                            array('id'=>'tt'.$auser->id));
                        $status = html_writer::tag('div', '&nbsp;', array('id'=>'st'.$auser->id));

                        if ($this->checkmark->flexiblenaming) {
                            $names = explode(self::DELIMITER, $this->checkmark->examplenames);
                            for ($i=0; $i<=count($names); $i++) {
                                if ($i==0) {
                                    $examples[$i] = null;
                                } else {
                                    //☒ = UTF-8 box with x-mark
                                    //☐ = UTF-8 empty box
                                    $examples[$i] = html_writer::tag('div', self::EMPTYBOX,
                                                                     array('id'=>'ex'.$auser->id.
                                                                                 '_'.$i));
                                }
                            }
                        } else {
                            $examples[0] = null;
                            for ($i=1; $i<=$this->checkmark->examplecount; $i++) {
                                $examples[$i] = html_writer::tag('div', self::EMPTYBOX,
                                                                 array('id'=>'ex'.$auser->id.
                                                                             '_'.$i));
                            }
                        }

                        if ($final_grade->locked or $final_grade->overridden) {
                            $grade = html_writer::tag('div', $final_grade->formatted_grade,
                                                      array('id'=>'g'.$auser->id));
                            $hassubmission = true;
                        } else {
                            $grade = html_writer::tag('div', '-', array('id'=>'g'.$auser->id));
                        }

                        if ($final_grade->locked or $final_grade->overridden) {
                            $comment = html_writer::tag('div', $final_grade->str_feedback,
                                                        array('id'=>'com'.$auser->id));
                        } else {
                            $comment = html_writer::tag('div', '&nbsp;',
                                                        array('id'=>'com'.$auser->id));
                        }
                    }

                    if (empty($auser->status)) { /// Confirm we have exclusively 0 or 1
                        $auser->status = 0;
                    } else {
                        $auser->status = 1;
                    }

                    $linkurl = $CFG->wwwroot.'/user/view.php?id='.$auser->id.
                               '&amp;course='.$course->id;
                    $linktext = fullname($auser, has_capability('moodle/site:viewfullnames',
                                         $this->context));
                    $userlink = html_writer::tag('a', $linktext, array('href'=>$linkurl));

                    $row = array($selected_user, $userlink, $user_id);

                    if ($groupmode != NOGROUPS) {
                        $row[] = $group;
                    }

                    if ($this->checkmark->flexiblenaming) {
                        $names = explode(self::DELIMITER, $this->checkmark->examplenames);
                        for ($i=1; $i<=count($names); $i++) {
                            $row[] = $examples[$i];
                        }
                    } else {
                        for ($i=1; $i<=$this->checkmark->examplecount; $i++) {
                            $row[] = $examples[$i];
                        }
                    }

                    $row[] = $grade;
                    $row[] = $comment;

                    //hide all data in hidden columns
                    foreach ($tablecolumns as $key => $columnname) {
                        if ($this->column_is_hidden($columnname)) {
                            $row[$key] = '';
                        }
                    }
                    $table->data[] = $row;
                }
                $help_icon = new help_icon('data_preview', 'checkmark');
                $tablehtml = html_writer::start_tag('div', array('class' => 'scroll_forced',
                                                                 'id'    => 'table_begin'));
                $tablehtml .= html_writer::tag('div', get_string('data_preview', 'checkmark').
                                                      $OUTPUT->render($help_icon),
                        array('class' => 'datapreviewtitle')).
                        html_writer::tag('div', $this->add_checkbox_controller(2, null, null, 1),
                                         array('class' => 'checkboxcontroller')).
                        html_writer::table($table);
                $tablehtml .= html_writer::end_tag('div');
            } else {
                $tablehtml = $OUTPUT->box($OUTPUT->notification(get_string('nostudentsmatching',
                                                                           'checkmark'),
                                                                'notifyproblem'), 'generalbox');
            }

        } else {
            if ($filter == self::FILTER_SUBMITTED) {
                $tablehtml = html_writer::tag('div', get_string('nosubmisson', 'checkmark'),
                                              array('class'=>'nosubmisson'));
            } else if ($filter == self::FILTER_REQUIRE_GRADING) {
                $tablehtml = html_writer::tag('div', get_string('norequiregrading', 'checkmark'),
                                              array('class'=>'norequiregrading'));
            } else {
                $tablehtml = html_writer::tag('div', get_string('nostudents', 'checkmark'),
                                              array('class'=>'norequiregrading'));
            }
        }

        $tablehtml = html_writer::tag('div', $tablehtml, array('class'=>'fcontainer'));
        $formhtml .= html_writer::tag('div', $tablehtml, array('class'=>'clearfix'));
        $formhtml .= html_writer::input_hidden_params($formaction);
        $url = $formaction->out_omit_querystring($formaction);
        $returnstring .= html_writer::tag('form', $formhtml, array('class'  => 'mform',
                                                                   'action' => $url,
                                                                   'method' => 'post'));
        $returnstring .= html_writer::end_tag('div');
        $returnstring .= $OUTPUT->footer();
        return $returnstring;
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
                                              'post');
            } else if ($continue instanceof moodle_url) {
                $continue = new single_button($continue, get_string('continue'), 'post');
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
        $output .= html_writer::tag('div',
                                    $OUTPUT->render($continue).
                                    (($cancel != null) ? $OUTPUT->render($cancel) : ""),
                                    array('class' => 'buttons'));
        $output .= $OUTPUT->box_end();
        return $output;
    }

    public function submissions_print() {
        global $SESSION, $CFG, $DB, $USER, $DB, $OUTPUT, $PAGE;
        require_once($CFG->libdir.'/gradelib.php');
        require_once($CFG->libdir.'/tcpdf/tcpdf.php');

        /* first we check to see if the form has just been submitted
         * to request user_preference updates
         */
        $returnstring = '';

        $filters = array(self::FILTER_ALL             => get_string('all'),
        self::FILTER_SUBMITTED       => get_string('submitted', 'checkmark'),
        self::FILTER_REQUIRE_GRADING => get_string('requiregrading', 'checkmark'));

        $updatepref = optional_param('updatepref', 0, PARAM_INT);

        if (isset($_POST['updatepref'])) {
            $printperpage = optional_param('printperpage', 0, PARAM_INT);
            $printperpage = ($printperpage <= 0) ? 0 : $printperpage;
            $filter = optional_param('filter', 0, PARAM_INT);
            set_user_preference('checkmark_printperpage', $printperpage);
            set_user_preference('checkmark_filter', $filter);
        }

        /* next we get perpage (allow quick grade) params
         * from database
         */
        $printperpage    = get_user_preferences('checkmark_printperpage', null);
        $filter = get_user_preferences('checkmark_filter', 0);
        $grading_info = grade_get_grades($this->course->id, 'mod', 'checkmark',
                                         $this->checkmark->id);

        if (!empty($CFG->enableoutcomes) and !empty($grading_info->outcomes)) {
            $uses_outcomes = true;
        } else {
            $uses_outcomes = false;
        }

        /// Some shortcuts to make the code read better

        $course     = $this->course;
        $checkmark = $this->checkmark;
        $cm         = $this->cm;
        $hassubmission = false;

        $tabindex = 1; //tabindex for quick grading tabbing; Not working for dropdowns yet
        add_to_log($course->id, 'checkmark', 'export pdf', 'submissions.php?id='.$this->cm->id,
                   $this->checkmark->id, $this->cm->id);

        /// Check to see if groups are being used in this checkmark
        /// find out current groups mode
        $groupmode = groups_get_activity_groupmode($cm);
        $currentgroup = groups_get_activity_group($cm, true);

        $context = context_module::instance($cm->id);

        /// Get all ppl that are allowed to submit checkmarks
        list($esql, $params) = get_enrolled_sql($context, 'mod/checkmark:submit', $currentgroup);

        $selected = array();
        //@todo YOU Shall not acces $_POST directly!
        foreach ($_POST as $idx => $var) {
            if ($var == "selected") {
                //selecteduser[ID]
                $usrid = substr($idx, 12);
                array_push($selected, $usrid);
            }
        }
        $usrlst = $selected;

        if (!empty($usrlst)) {
            //prepare SQL manually because the following 2 commands
            //won't work properly with "get_enrolled_sql"
            //list($sqluserids, $userparams) = $DB->get_in_or_equal($usrlst);
            //$params = array_merge_recursive($params, $userparams);
            $sqluserids = null;
            foreach ($usrlst as $sqluser) {
                if ($sqluserids == null) {
                    $sqluserids = "IN (:user".$sqluser;
                } else {
                    $sqluserids .= ", :user".$sqluser;
                }
                $params["user".$sqluser] = $sqluser;
            }
            $sqluserids .= ") ";

            switch ($filter) {
                case self::FILTER_SELECTED:
                    $sql = "SELECT u.id FROM {user} u ".
                           //"LEFT JOIN ($esql) eu ON eu.id=u.id ".
                           "WHERE u.deleted = 0".
                           //" AND eu.id=u.id".
                           " AND u.id ".$sqluserids;
                    break;
                case self::FILTER_REQUIRE_GRADING:
                    $wherefilter = ' AND (s.timemarked < s.timemodified OR s.grade = -1) ';
                    $sql = "SELECT u.id FROM {user} u ".
                           "LEFT JOIN ($esql) eu ON eu.id=u.id ".
                           "LEFT JOIN {checkmark_submissions} s ON (u.id = s.user_id) " .
                           "WHERE u.deleted = 0 AND eu.id=u.id ".
                           'AND s.checkmark_id = :checkmarkid'.
                           "AND u.id ".$sqluserids.
                           $wherefilter;
                           $params['checkmarkid'] = $this->checkmark->id;
                    break;
                case self::FILTER_ALL:
                default:
                    $sql = "SELECT u.id FROM {user} u ".
                           "LEFT JOIN ($esql) eu ON eu.id=u.id ".
                           "WHERE u.deleted = 0 AND eu.id=u.id ".
                           "AND u.id ".$sqluserids;
                    break;
            }

            $users = $DB->get_records_sql($sql, $params);
        } else {
            echo $OUTPUT->header();
            $url = new moodle_url($PAGE->url);
            $button = new single_button($url, get_string('continue'));
            echo $this->confirm($OUTPUT->notification(get_string('nousers', 'checkmark'),
                                                        'notifyproblem'),
                                $button);
            echo $OUTPUT->footer();
            exit();
        }
        if (!empty($users)) {
            $users = array_keys($users);
        }

        // if groupmembersonly used, remove users who are not in any group
        if ($users and !empty($CFG->enablegroupmembersonly) and $cm->groupmembersonly) {
            if ($groupingusers = groups_get_grouping_members($cm->groupingid, 'u.id', 'u.id')) {
                $users = array_intersect($users, array_keys($groupingusers));
            }
        }

        $tableheaders = array(get_string('fullnameuser'),
                              get_string('str_user_id', 'checkmark'));
        $tablecolumns = array('fullnameuser', 'idnumber');
        $width = array(50, 25);
        $align = array('L', 'L');
        if ($groupmode != NOGROUPS) {
            $width[] = 20;
            $tableheaders[] = get_string('group');
            $tablecolumns[] = 'groups';
            $align[] = 'L';
        }
        //dynamically add examples
        if ($this->checkmark->flexiblenaming) {
            $names = explode(self::DELIMITER, $this->checkmark->examplenames);
            $grades = explode(self::DELIMITER, $this->checkmark->examplegrades);
            foreach ($names as $key => $name) {
                $count = $key+1;
                $width[] = null;
                $tableheaders[] = $name."\n(".$grades[$key]."P)";
                $tablecolumns[] = 'example'.$count;
                $align[] = 'C';
            }
        } else {
            $points = $this->checkmark->grade/$this->checkmark->examplecount;
            for ($i=1; $i<=$this->checkmark->examplecount; $i++) {
                $number = $i+$this->checkmark->examplestart-1;
                $width[] = null;
                $tableheaders[] = $number."\n(".$points." P)";
                $tablecolumns[] = 'example'.$i;
                $align[] = 'C';
            }
        }
        $width[] = 20;
        $tableheaders[] = get_string('grade');
        $tablecolumns[] = 'grade';
        $align[] = 'R';
        $width[] = 30;
        $tableheaders[] = get_string('comment', 'checkmark');
        $tablecolumns[] = 'comment';
        $align[] = 'L';
        if ($uses_outcomes) {
            $width[] = 50;
            $align[] = 'L';
            $tableheaders[] = get_string('outcome', 'grades');
            $tablecolumns[] = 'outcome';
        }

        /// Construct the SQL
        /*
         * replaces the old get_sql_where();
         */
        $conditions = array();
        $params = array();

        if (1 || isset($table->colclasses['fullname'])) {
            static $i = 0;
            $i++;

            if (!empty($SESSION->checkmark->ifirst)) {
                $conditions[] = $DB->sql_like('firstname', ':ifirstc'.$i, false, false);
                $params['ifirstc'.$i] = $SESSION->checkmark->ifirst.'%';
            }
            if (!empty($SESSION->checkmark->ilast)) {
                $conditions[] = $DB->sql_like('lastname', ':ilastc'.$i, false, false);
                $params['ilastc'.$i] = $SESSION->checkmark->ilast.'%';
            }
        }

        list($where, $params) = array(implode(" AND ", $conditions), $params);
        /*
         * end of replacement for get_sql_where();
         */

        $sort = '';
        $ufields = user_picture::fields('u');

        if ($where) {
            $where .= ' AND ';
        }

        if ($filter == self::FILTER_SUBMITTED) {
            $where .= 's.timemodified > 0 AND ';
        } else if ($filter == self::FILTER_REQUIRE_GRADING) {
            $where .= 's.timemarked < s.timemodified AND ';
        }

        if ($groupmode != NOGROUPS) {
            $getgroupsql = "SELECT grps.courseid, GROUP_CONCAT(DISTINCT grps.name
                                                               ORDER BY grps.name ";
            if (isset($SESSION->checkmark->orderby)
                    && ($SESSION->checkmark->orderby == 'groups')) {
                if (isset($SESSION->checkmark->orderdirection)) {
                    $getgroupsql .= $SESSION->checkmark->orderdirection;
                } else {
                    $getgroupsql .= 'ASC';
                }
            }
            $params['courseid'] = $this->course->id;
            $getgroupsql .= " SEPARATOR ', ') AS groups, grpm.userid AS userid
                         FROM {groups_members} grpm
                         LEFT JOIN {groups} grps
                         ON grps.id = grpm.groupid
                         WHERE grps.courseid = :courseid".
                         " GROUP BY grpm.userid";
            $groupssql = " LEFT JOIN ($getgroupsql) AS grpq ON u.id = grpq.userid ";
        } else {
            $groupssql = "";
        }

        if (!empty($users)) {
            $select = "SELECT $ufields, u.idnumber,
                              s.id AS submissionid, s.grade, s.submissioncomment,
                              s.timemodified, s.timemarked ";
            if ($groupmode != NOGROUPS) {
                    $select .= ", groups ";
            }
            $params['checkmarkid'] = $this->checkmark->id;

            //prepare SQL manually because the following 2 commands
            //won't work properly with "get_enrolled_sql"
            //list($sqluserids, $userparams) = $DB->get_in_or_equal($usrlst);
            //$params = array_merge_recursive($params, $userparams);
            $sqluserids = null;
            foreach ($usrlst as $sqluser) {
                if ($sqluserids == null) {
                    $sqluserids = "IN (:user".$sqluser;
                } else {
                    $sqluserids .= ", :user".$sqluser;
                }
                $params["user".$sqluser] = $sqluser;
            }
            $sqluserids .= ") ";

            $sql = 'FROM {user} u '.
                   'LEFT JOIN {checkmark_submissions} s ON u.id = s.user_id
                    AND s.checkmark_id = :checkmarkid '.
                   $groupssql.
                   ' WHERE '.$where.' u.id '.$sqluserids;

            if (isset($SESSION->checkmark->orderby)) {
                $sort = ' ORDER BY '.
                        $SESSION->checkmark->orderby;
                if (isset($SESSION->checkmark->orderdirection)
                    && ($SESSION->checkmark->orderdirection == 'DESC')) {
                    $sort .= ' DESC';
                } else {
                    $sort .= ' ASC';
                }
                if (($groupmode == NOGROUPS) && ($SESSION->checkmark->orderby == 'groups')) {
                    unset($SESSION->checkmark->orderby);
                    $sort = '';
                }
            }

            $ausers = $DB->get_records_sql($select.$sql.$sort, $params);

            $data = array();

            if ($ausers !== false) {
                $grading_info = grade_get_grades($this->course->id, 'mod', 'checkmark',
                                                 $this->checkmark->id, array_keys($ausers));
                foreach ($ausers as $auser) {
                    if (isset($auser->groups)) {
                        $group = shorten_text($auser->groups, 20);
                    } else {
                        $group = '-';
                    }
                    $final_grade = $grading_info->items[0]->grades[$auser->id];
                    $grademax = $grading_info->items[0]->grademax;
                    $final_grade->formatted_grade = round($final_grade->grade, 2).
                                                    ' / '.round($grademax, 2);
                    $locked_overridden = 'locked';
                    if ($final_grade->overridden) {
                        $locked_overridden = 'overridden';
                    }

                    /// Calculate user status
                    $auser->status = ($auser->timemarked > 0)
                                     && ($auser->timemarked >= $auser->timemodified);
                    $user_id = $auser->idnumber;

                    if (empty($auser->submissionid)) {
                        $auser->grade = -1; //no submission yet
                    }

                    if (!empty($auser->submissionid)) {
                        $hassubmission = true;
                        ///Print examples
                        $submission = $this->get_submission($auser->id);
                        $checked_examples = explode(self::DELIMITER, $submission->checked);

                        if ($this->checkmark->flexiblenaming) {
                            $names = explode(self::DELIMITER, $this->checkmark->examplenames);
                            for ($i=0; $i<=count($names); $i++) {
                                $colname = 'example'.$i+1;
                                if (isset($SESSION->checkmark->columns[$colname])
                                    && ($SESSION->checkmark->columns[$colname]->visibility == 0)) {
                                    $examples[$i] = ' ';
                                } else {
                                    if ($i==0) {
                                        $examples[$i] = null;
                                    } else {
                                        $examples[$i] = "☐";
                                    }
                                }
                            }
                        } else {
                            $examples[0] = null;
                            for ($i=1; $i<=$this->checkmark->examplecount; $i++) {
                                $colname = 'example'.$i+1;
                                if (isset($SESSION->checkmark->columns[$colname])
                                    && ($SESSION->checkmark->columns[$colname]->visibility == 0)) {
                                    $examples[$i] = ' ';
                                } else {
                                    $examples[$i] = "☐";
                                }
                            }
                        }

                        foreach ($checked_examples as $checked_example) {
                            $colname = 'example'.$checked_example;
                            if (isset($SESSION->checkmark->columns[$colname])
                                 && ($SESSION->checkmark->columns[$colname]->visibility == 0)) {
                                $examples[$checked_example] = ' ';
                            } else {
                                $examples[$checked_example] = "☒";
                            }
                        }

                        ///Print grade or text
                        if ($final_grade->locked or $final_grade->overridden) {
                            $grade = ''.$final_grade->formatted_grade;
                        } else {
                            $grade = $this->display_grade($auser->grade);
                        }
                        ///Print Comment
                        if ($final_grade->locked or $final_grade->overridden) {
                            $comment = shorten_text(strip_tags($final_grade->str_feedback), 15, 1);

                        } else {
                            $comment = shorten_text(strip_tags($auser->submissioncomment), 15, 1);
                        }
                    } else {
                        $studentmodified = ' ';
                        $teachermodified = ' ';
                        $status          = ' ';

                        if ($this->checkmark->flexiblenaming) {
                            $names = explode(self::DELIMITER, $this->checkmark->examplenames);
                            for ($i=0; $i<=count($names); $i++) {
                                if ($i==0) {
                                    $examples[$i] = null;
                                } else {
                                    //☒ = UTF-8 box with x-mark
                                    //&#x2610; = UTF-8 empty box
                                    $examples[$i] = "☐";
                                }
                            }
                        } else {
                            $examples[0] = null;
                            for ($i=1; $i<=$this->checkmark->examplecount; $i++) {
                                $examples[$i] = "☐";
                            }
                        }

                        if ($final_grade->locked or $final_grade->overridden) {
                            $grade = $final_grade->formatted_grade;
                            $hassubmission = true;
                        } else {
                            $grade = '-';
                        }

                        if ($final_grade->locked or $final_grade->overridden) {
                            $comment = $final_grade->str_feedback;
                        } else {
                            $comment = ' ';
                        }
                    }

                    if (empty($auser->status)) { /// Confirm we have exclusively 0 or 1
                        $auser->status = 0;
                    } else {
                        $auser->status = 1;
                    }

                    $fullname = fullname($auser, has_capability('moodle/site:viewfullnames',
                                                                $this->context));
                    $row = array($fullname, $user_id);

                    if ($groupmode != NOGROUPS) {
                        $row[] = $group;
                    }

                    if ($this->checkmark->flexiblenaming) {
                        $names = explode(self::DELIMITER, $this->checkmark->examplenames);
                        for ($i=1; $i<=count($names); $i++) {
                            $row[] = $examples[$i];
                        }
                    } else {
                        for ($i=1; $i<=$this->checkmark->examplecount; $i++) {
                            $row[] = $examples[$i];
                        }
                    }

                    $row[] = $grade;
                    $row[] = $comment;

                    //hide all data in hidden columns
                    foreach ($tablecolumns as $key => $columnname) {
                        if ($this->column_is_hidden($columnname)) {
                            unset($row[$key]);
                            unset($tableheaders[$key]);
                        }
                    }
                    $data[] = $row;
                }
            }
        } else {
            if ($filter == self::FILTER_REQUIRE_GRADING) {
                $data = array('', get_string('norequiregrading', 'checkmark'), '');
                $tableheaders = array(' ', ' ', ' ');
            } else {
                $data = array('', get_string('nosubmisson', 'checkmark'), '');
                $tableheaders = array(' ', ' ', ' ');
            }
        }

        if (self::TEST_LONG_DATA) {
            for ($i=0; $i<4; $i++) {
                $data = array_merge_recursive($data, $data);
            }
        }

        if (class_exists('checkmark_pdf') && !self::HTML_OUTPUT) {
            $pdf = new checkmark_pdf();

            // set orientation (P/L)
            $orientation = optional_param('pageorientation', 0, PARAM_INT);
            if ($orientation == 0) {
                $pdf->setPageOrientation("L");
            }

            // set document information
            $pdf->SetCreator('TUWEL');
            $pdf->SetAuthor($USER->firstname . " " . $USER->lastname);

            $coursename = $this->course->fullname;
            $timeavailable = $this->checkmark->timeavailable;
            $checkmarkname = $this->checkmark->name;
            $timedue = $this->checkmark->timedue;
            $filter = optional_param('filter', self::FILTER_ALL, PARAM_INT);

            $viewname = $filters[$filter];

            $pdf->setHeaderStrings($coursename, $checkmarkname, $timeavailable, $timedue, $viewname);

            $printheader = optional_param('printheader', false, PARAM_BOOL);

            // set header/ footer
            $pdf->setPrintHeader($printheader);
            $pdf->setPrintFooter(false);

            if ($groupmode != NOGROUPS) {
                if ($currentgroup == "") {
                    $grpname = get_string('all', 'extserver');
                } else {
                    $grpname = groups_get_group_name($currentgroup);
                }

                $pdf->setGroups($grpname);
            }

            $textsize = optional_param('textsize', 1, PARAM_INT);
            switch ($textsize) {
                case "0":
                    $pdf->SetFontSize(8);
                    break;
                case "1":
                    $pdf->SetFontSize(10);
                    break;
                case "2":
                    $pdf->SetFontSize(12);
                    break;
            }

            //set margins
            if ($printheader) {
                $pdf->SetMargins(10, 30, 10); //Left Top Right
            } else {
                $pdf->SetMargins(10, 10, 10);
            }
            // set default monospaced font
            $pdf->SetDefaultMonospacedFont(/*PDF_FONT_MONOSPACED*/'freserif');

            //set margins
            $pdf->SetHeaderMargin(7);

            //set auto page breaks
            $pdf->SetAutoPageBreak(true, /*PDF_MARGIN_BOTTOM*/10);

            //set image scale factor
            $pdf->setImageScale(/*PDF_IMAGE_SCALE_RATIO*/1);

            // ---------------------------------------------------------

            // set font
            $pdf->SetFont('freeserif', '');

            $maxrows = $printperpage;
            if ($maxrows == 0) {
                $maxrows = null;
            }
            $pdf->setData($data, $tableheaders, $width, $align, false, $maxrows);
            ob_clean();
            $unclean_filename = $coursename . '_' . $checkmarkname . '_' .
                                get_string('strsubmissions', 'checkmark').'.pdf';
            $filename = textlib::specialtoascii($unclean_filename);
            $pdf->Output($filename, 'D');
            exit();
        }
    }

    /**
     * Renders a link to select/deselect all checkboxes of a group
     *
     * Based upon @see moodleform::add_checkbox_controller() but also useable without moodleform
     * use
     *     $state = optional_param('checkbox_controller'.$group, $CHKBOXDEFAULT, PARAM_INT);
     * to determin the current state for the checkboxes
     *
     * @global object
     * @param int    $groupid The id of the group of advcheckboxes this element controls
     * @param string $text The text of the link. Defaults to selectallornone ("select all/none")
     * @param array  $attributes associative array of HTML attributes
     * @param int    $origval The original general state of the checkboxes before the user
     *                              first clicks this element
     */
    public function add_checkbox_controller($groupid, $text=null, $attributes=null, $origval=0) {
        global $CFG;

        // Set the default text if none was specified
        if (empty($text)) {
            $text = get_string('selectallornone', 'form');
        }

        $select_value = optional_param('checkbox_controller'. $groupid, null, PARAM_INT);

        if ($select_value == 0 || is_null($select_value)) {
            $new_select_value = 1;
        } else {
            $new_select_value = 0;
        }

        $hiddenstate = html_writer::empty_tag('input',
                                              array('type'  => 'hidden',
                                                    'name'  => 'checkbox_controller'.$groupid,
                                                    'value' => $new_select_value));

        $checkbox_controller_name = 'nosubmit_checkbox_controller' . $groupid;

        // Prepare Javascript for submit element
        $js = "\n//<![CDATA[\n";
        if (!defined('HTML_QUICKFORM_CHECKBOXCONTROLLER_EXISTS')) {
            $js .= <<<EOS
function html_quickform_toggle_checkboxes(group) {
    var checkboxes = document.getElementsByClassName('checkboxgroup' + group);
    var newvalue = false;
    var global = eval('html_quickform_checkboxgroup' + group + ';');
    if (global == 1) {
        eval('html_quickform_checkboxgroup' + group + ' = 0;');
        newvalue = '';
    } else {
        eval('html_quickform_checkboxgroup' + group + ' = 1;');
        newvalue = 'checked';
    }

    for (i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = newvalue;
    }
}
EOS;
            define('HTML_QUICKFORM_CHECKBOXCONTROLLER_EXISTS', true);
        }
        $js .= "\nvar html_quickform_checkboxgroup$groupid=$origval;\n";

        $js .= "//]]>\n";

        require_once("$CFG->libdir/form/submitlink.php");
        $submitlink = new MoodleQuickForm_submitlink($checkbox_controller_name, $text, $attributes);
        $submitlink->_js = $js;
        $submitlink->_onclick = "html_quickform_toggle_checkboxes($groupid); return false;";
        return $hiddenstate."<div>".$submitlink->toHTML()."</div>";
    }

    /**
     *  Process teacher feedback submission
     *
     * This is called by submissions() when a grading even has taken place.
     * It gets its data from the submitted form.
     *
     * @global object
     * @global object
     * @global object
     * @return object|bool The updated submission object or false
     */
    public function process_feedback($formdata=null) {
        global $CFG, $USER, $DB;
        require_once($CFG->libdir.'/gradelib.php');

        if (!$feedback = data_submitted() or !confirm_sesskey()) {      // No incoming data?
            return false;
        }

        ///For save and next, we need to know the userid to save, and the userid to go
        ///We use a new hidden field in the form, and set it to -1. If it's set, we use this
        ///as the userid to store
        if ((int)$feedback->saveuserid !== -1) {
            $feedback->user_id = $feedback->saveuser_id;
        }

        if (!empty($feedback->cancel)) {          // User hit cancel button
            return false;
        }

        $grading_info = grade_get_grades($this->course->id, 'mod', 'checkmark',
                                         $this->checkmark->id, $feedback->user_id);

        // store outcomes if needed
        $this->process_outcomes($feedback->user_id);

        $submission = $this->get_submission($feedback->user_id, true);  // Get or make one

        if (!($grading_info->items[0]->grades[$feedback->user_id]->locked ||
        $grading_info->items[0]->grades[$feedback->user_id]->overridden) ) {

            $submission->grade      = $feedback->xgrade;
            $submission->submissioncomment    = $feedback->submissioncomment_editor['text'];
            $submission->teacher_id    = $USER->id;
            $mailinfo = get_user_preferences('checkmark_mailinfo', 0);
            if (!$mailinfo) {
                $submission->mailed = 1;       // treat as already mailed
            } else {
                $submission->mailed = 0;       // Make sure mail goes out (again, even)
            }
            $submission->timemarked = time();

            unset($submission->checked);  // Don't need to update this.

            $DB->update_record('checkmark_submissions', $submission);

            // triger grade event
            $this->update_grade($submission);

            add_to_log($this->course->id, 'checkmark', 'update grades',
                       'submissions.php?id='.$this->cm->id.'&user='.$feedback->user_id,
                       $feedback->user_id, $this->cm->id);
        }

        return $submission;

    }

    public function process_outcomes($user_id) {
        global $CFG, $USER;

        if (empty($CFG->enableoutcomes)) {
            return;
        }

        require_once($CFG->libdir.'/gradelib.php');

        if (!$formdata = data_submitted() or !confirm_sesskey()) {
            return;
        }

        $data = array();
        $grading_info = grade_get_grades($this->course->id, 'mod', 'checkmark',
                                         $this->checkmark->id, $user_id);

        if (!empty($grading_info->outcomes)) {
            foreach ($grading_info->outcomes as $n => $old) {
                $name = 'outcome_'.$n;
                if (isset($formdata->{$name}[$user_id])
                    && $old->grades[$user_id]->grade != $formdata->{$name}[$user_id]) {
                    $data[$n] = $formdata->{$name}[$user_id];
                }
            }
        }
        if (count($data) > 0) {
            grade_update_outcomes('mod/checkmark', $this->course->id, 'mod', 'checkmark',
                                  $this->checkmark->id, $user_id, $data);
        }

    }

    /**
     * Load the submission object for a particular user
     *
     * @global object
     * @global object
     * @param $user_id int The id of the user whose submission we want or 0 in which case USER->id
     *                     is used
     * @param $createnew boolean optional Defaults to false. If set to true a new submission object
     *                           will be created in the database
     * @param bool $teachermodified student submission set if false
     * @return object The submission
     */
    public function get_submission($user_id=0, $createnew=false, $teachermodified=false) {
        global $USER, $DB;

        if (empty($user_id)) {
            $user_id = $USER->id;
        }

        $submission = $DB->get_record('checkmark_submissions',
                                      array('checkmark_id' => $this->checkmark->id,
                                            'user_id'      => $user_id));

        if ($submission || !$createnew) {
            return $submission;
        }
        $newsubmission = $this->prepare_new_submission($user_id, $teachermodified);
        $DB->insert_record("checkmark_submissions", $newsubmission);

        return $DB->get_record('checkmark_submissions',
                               array('checkmark_id' => $this->checkmark->id,
                                     'user_id'      => $user_id));
    }

    /**
     * Instantiates a new submission object for a given user
     *
     * Sets the checkmark, userid and times, everything else is set to default values.
     *
     * @param int $user_id The userid for which we want a submission object
     * @param bool $teachermodified student submission set if false
     * @return object The submission
     */
    public function prepare_new_submission($user_id, $teachermodified=false) {
        $submission = new stdClass();
        $submission->checkmark_id   = $this->checkmark->id;
        $submission->user_id       = $user_id;
        $submission->timecreated = time();
        // teachers should not be modifying modified date, except offline checkmarks
        if ($teachermodified) {
            $submission->timemodified = 0;
        } else {
            $submission->timemodified = $submission->timecreated;
        }
        $submission->checked        = '';
        $submission->grade        = -1;
        $submission->submissioncomment      = '';
        $submission->format       = 0;
        $submission->teacher      = 0;
        $submission->timemarked   = 0;
        $submission->mailed       = 0;
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
     * Alerts teachers by email of new or changed checkmarks that need grading
     *
     * First checks whether the option to email teachers is set for this checkmark.
     * Sends an email to ALL teachers in the course (or in the group if using separate groups).
     * Uses the methods email_teachers_text() and email_teachers_html() to construct the content.
     *
     * @global object
     * @global object
     * @param $submission object The submission that has changed
     * @return void
     */
    public function email_teachers($submission) {
        global $CFG, $DB;

        if (empty($this->checkmark->emailteachers)) {          // No need to do anything
            return;
        }

        $user = $DB->get_record('user', array('id'=>$submission->user_id));

        if ($teachers = $this->get_graders($user)) {

            $strcheckmarks = get_string('modulenameplural', 'checkmark');
            $strcheckmark  = get_string('modulename', 'checkmark');
            $strsubmitted  = get_string('submitted', 'checkmark');

            foreach ($teachers as $teacher) {
                $info = new stdClass();
                $info->username = fullname($user, true);
                $info->checkmark = format_string($this->checkmark->name, true);
                $info->url = $CFG->wwwroot.'/mod/checkmark/submissions.php?id='.$this->cm->id;
                $info->timeupdated = strftime('%c', $submission->timemodified);

                $postsubject = $strsubmitted.': '.$info->username.' -> '.$this->checkmark->name;
                $posttext = $this->email_teachers_text($info);
                $posthtml = ($teacher->mailformat == 1) ? $this->email_teachers_html($info) : '';

                $eventdata = new stdClass();
                $eventdata->modulename       = 'checkmark';
                $eventdata->userfrom         = $user;
                $eventdata->userto           = $teacher;
                $eventdata->subject          = $postsubject;
                $eventdata->fullmessage      = $posttext;
                $eventdata->fullmessageformat = FORMAT_PLAIN;
                $eventdata->fullmessagehtml  = $posthtml;
                $eventdata->smallmessage     = $postsubject;

                $eventdata->name            = 'checkmark_updates';
                $eventdata->component       = 'mod_checkmark';
                $eventdata->notification    = 1;
                $eventdata->contexturl      = $info->url;
                $eventdata->contexturlname  = $info->checkmark;

                message_send($eventdata);
            }
        }
    }

    /**
     * @param string $filearea
     * @param array $args
     * @return bool
     */
    public function send_file($filearea, $args) {
        debugging('plugin does not implement file sending', DEBUG_DEVELOPER);
        return false;
    }

    /**
     * Returns a list of teachers that should be grading given submission
     *
     * @param object $user
     * @return array
     */
    public function get_graders($user) {
        //potential graders
        $potgraders = get_users_by_capability($this->context, 'mod/checkmark:grade', '', '', '',
                                              '', '', '', false, false);

        $graders = array();
        if (groups_get_activity_groupmode($this->cm) == SEPARATEGROUPS) {
            // Separate groups are being used
            if ($groups = groups_get_all_groups($this->course->id, $user->id)) {
                // Try to find all groups
                foreach ($groups as $group) {
                    foreach ($potgraders as $t) {
                        if ($t->id == $user->id) {
                            continue; // do not send self
                        }
                        if (groups_is_member($group->id, $t->id)) {
                            $graders[$t->id] = $t;
                        }
                    }
                }
            } else {
                // user not in group, try to find graders without group
                foreach ($potgraders as $t) {
                    if ($t->id == $user->id) {
                        continue; // do not send self
                    }
                    if (!groups_get_all_groups($this->course->id, $t->id)) { //ugly hack
                        $graders[$t->id] = $t;
                    }
                }
            }
        } else {
            foreach ($potgraders as $t) {
                if ($t->id == $user->id) {
                    continue; // do not send self
                }
                $graders[$t->id] = $t;
            }
        }
        return $graders;
    }

    /**
     * Creates the text content for emails to teachers
     *
     * @param $info object The info used by the 'emailteachermail' language string
     * @return string
     */
    public function email_teachers_text($info) {
        $posttext  = format_string($this->course->shortname).' -> '.$this->strcheckmarks.' -> '.
        format_string($this->checkmark->name)."\n";
        $posttext .= '---------------------------------------------------------------------'."\n";
        $posttext .= get_string("emailteachermail", "checkmark", $info)."\n";
        $posttext .= "\n---------------------------------------------------------------------\n";
        return $posttext;
    }

    /**
     * Creates the html content for emails to teachers
     *
     * @param $info object The info used by the 'emailteachermailhtml' language string
     * @return string
     */
    public function email_teachers_html($info) {
        global $CFG;
        $posthtml  = '<p><font face="sans-serif">'.
                     '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$this->course->id.'">'.
                     format_string($this->course->shortname).'</a> ->'.
                     '<a href="'.$CFG->wwwroot.'/mod/checkmark/index.php?id='.
                     $this->course->id.'">'.$this->strcheckmarks.'</a> ->'.
                     '<a href="'.$CFG->wwwroot.'/mod/checkmark/view.php?id='.$this->cm->id.'">'.
                     format_string($this->checkmark->name).'</a></font></p>';
        $posthtml .= '<hr /><font face="sans-serif">';
        $posthtml .= '<p>'.get_string('emailteachermailhtml', 'checkmark', $info).'</p>';
        $posthtml .= '</font><hr />';
        return $posthtml;
    }

    /**
     * Produces a list of links to the files uploaded by a user
     *
     * @param $user_id int optional id of the user. If 0 then $USER->id is used.
     * @param $return boolean optional defaults to false. If true the html
     * @return string optional
     */
    public function print_user_submission($user_id=0, $return=false) {
        global $CFG, $USER, $OUTPUT;

        if (!$user_id) {
            if (!isloggedin()) {
                return '';
            }
            $user_id = $USER->id;
        }

        $output = '';

        $submission = $this->get_submission($user_id);
        if (!$submission) {
            return $output;
        }

        if ( $this->checkmark->flexiblenaming ) {
            $examplenames = explode(self::DELIMITER, $this->checkmark->examplenames);
            $examplegrades = explode(self::DELIMITER, $this->checkmark->examplegrades);
            $examplestates = explode(self::DELIMITER, $submission->checked);
            for ($i=0; $i<count($examplenames); $i++) {
                $examplenumber = strval($i+1);
                $state = 0;
                foreach ($examplestates as $singlestate) {
                    if (intval($singlestate) == intval($examplenumber)) {
                        $state = 1;
                        break;
                    }
                }
                $name = 'example'.$examplenumber;
                switch ($examplegrades[$i]) {
                    case '1':
                        $pointsstring = get_string('strpoint', 'checkmark');
                    break;
                    case '2':
                    default:
                        $pointsstring = get_string('strpoints', 'checkmark');
                    break;
                }
                if ($state) { //is checked?
                    $symbol = self::CHECKEDBOX;
                    $label = get_string('strexample', 'checkmark').' '.$examplenames[$i];
                    $grade = '('.$examplegrades[$i].' '.$pointsstring.')';
                    $content = html_writer::tag('div', '&nbsp;', array('class'=>'fitemtitle')).
                               html_writer::tag('div', $symbol.'&nbsp;'.$label.'&nbsp;'.$grade,
                                                array('class'=>'felement'));
                    $output .= html_writer::tag('div', $content,
                                                array('class'=>'fitem checkedexample'));
                } else {
                    $symbol = self::EMPTYBOX;
                    $label = get_string('strexample', 'checkmark').' '.$examplenames[$i];
                    $grade = '('.$examplegrades[$i].' '.$pointsstring.')';
                    $content = html_writer::tag('div', '&nbsp;', array('class'=>'fitemtitle')).
                               html_writer::tag('div', $symbol.'&nbsp;'.$label.'&nbsp;'.$grade,
                                                array('class'=>'felement'));
                    $output .= html_writer::tag('div', $content,
                                                array('class'=>'fitem uncheckedexample'));
                }

            }
        } else {
            $i = 0;
            $points = $this->checkmark->grade/$this->checkmark->examplecount;
            switch ($points) {
                case '1':
                    $pointsstring = get_string('strpoint', 'checkmark');
                break;
                case '2':
                default:
                    $pointsstring = get_string('strpoints', 'checkmark');
                break;
            }
            $examplestates = explode(self::DELIMITER, $submission->checked);
            do {
                $state = 0;
                $examplenumber = strval($i+$this->checkmark->examplestart);
                foreach ($examplestates as $singlestate) {
                    if (intval($singlestate) == ($i+1)) {
                        $state = 1;
                        break;
                    }
                }
                if ($state) { //is checked?
                    $symbol = self::CHECKEDBOX;
                    $label = get_string('strexample', 'checkmark').' '.$examplenumber;
                    $grade = '('.$points.' '.$pointsstring.')';
                    $content = html_writer::tag('div', '&nbsp;', array('class'=>'fitemtitle')).
                               html_writer::tag('div', $symbol.'&nbsp;'.$label.'&nbsp;'.$grade,
                                                array('class'=>'felement'));
                    $output .= html_writer::tag('div', $content,
                                                array('class'=>'fitem checkedexample'));
                } else {
                    $symbol = self::EMPTYBOX;
                    $label = get_string('strexample', 'checkmark').' '.$examplenumber;
                    $grade = '('.$points.' '.$pointsstring.')';
                    $content = html_writer::tag('div', '&nbsp;', array('class'=>'fitemtitle')).
                               html_writer::tag('div', $symbol.'&nbsp;'.$label.'&nbsp;'.$grade,
                                                array('class'=>'felement'));
                    $output .= html_writer::tag('div', $content,
                                                array('class'=>'fitem uncheckedexample'));
                }
                $i++;
            } while ($i<$this->checkmark->examplecount);
        }

        if ($return) {
            return $output;
        }
        echo $output;
    }

    /**
     * Count the files uploaded by a given user
     *
     * @param $itemid int The submission's id as the file's itemid.
     * @return int
     */
    public function count_user_files($itemid) {
        $fs = get_file_storage();
        $files = $fs->get_area_files($this->context->id, 'mod_checkmark', 'submission', $itemid,
                                     "id", false);
        return count($files);
    }

    /**
     * Returns true if the student is allowed to submit
     *
     * Checks that the checkmark has started and, if the option to prevent late
     * submissions is set, also checks that the checkmark has not yet closed.
     * @return boolean
     */
    public function isopen() {
        $time = time();
        if ($this->checkmark->preventlate && $this->checkmark->timedue) {
            return ($this->checkmark->timeavailable <= $time && $time <= $this->checkmark->timedue);
        } else {
            return ($this->checkmark->timeavailable <= $time);
        }
    }

    /**
     * Return true if is set description is hidden till available date
     *
     * This is needed by calendar so that hidden descriptions do not
     * come up in upcoming events.
     *
     * Check that description is hidden till available date
     * By default return false
     * @return boolen
     */
    public function description_is_hidden() {
        return false;
    }

    /**
     * Return an outline of the user's interaction with the checkmark
     *
     * The default method prints the grade and timemodified
     * @param $grade object
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
     * @param $user object
     */
    public function user_complete($user, $grade=null) {
        global $OUTPUT;
        if ($grade) {
            echo $OUTPUT->container(get_string('grade').': '.$grade->str_long_grade);
            if ($grade->str_feedback) {
                echo $OUTPUT->container(get_string('feedback').': '.$grade->str_feedback);
            }
        }

        if ($submission = $this->get_submission($user->id)) {

            $fs = get_file_storage();

            if ($files = $fs->get_area_files($this->context->id, 'mod_checkmark', 'submission',
                                             $submission->id, "timemodified", false)) {
                $countfiles = count($files)." ".get_string("uploadedfiles", "checkmark");
                foreach ($files as $file) {
                    $countfiles .= "; ".$file->get_filename();
                }
            }

            echo $OUTPUT->box_start();
            echo get_string("lastmodified").": ";
            echo userdate($submission->timemodified);
            echo $this->display_lateness($submission->timemodified);

            $this->print_user_files($user->id);

            echo '<br />';

            $this->view_feedback($submission);

            echo $OUTPUT->box_end();

        } else {
            print_string("notsubmittedyet", "checkmark");
        }
    }

    /**
     * Return a string indicating how late a submission is
     *
     * @param $timesubmitted int
     * @return string
     */
    public function display_lateness($timesubmitted) {
        return checkmark_display_lateness($timesubmitted, $this->checkmark->timedue);
    }

    /**
     * Empty method stub for all delete actions.
     */
    public function delete() {
        //nothing by default
        redirect('view.php?id='.$this->cm->id);
    }

    /**
     * Empty custom feedback grading form.
     */
    public function custom_feedbackform($submission, $return=false) {
        //nothing by default
        return '';
    }

    /**
     * Given a course_module object, this function returns any "extra" information that may be
     * needed when printing this activity in a course listing.  See get_array_of_activities()
     * in course/lib.php.
     *
     * @param $coursemodule object The coursemodule object (record).
     * @return object An object on information that the courses will know about
     *                (most noticeably, an icon).
     *
     */
    public function get_coursemodule_info($coursemodule) {
        return false;
    }

    /**
     * Plugin cron method - do not use $this here, create new checkmark instances if needed.
     * @return void
     */
    public function cron() {
        //no plugin cron by default - override if needed
    }

    /**
     * Reset all submissions
     */
    public function reset_userdata($data) {
        global $CFG, $DB;

        if (!$DB->count_records('checkmark', array('course'=>$data->courseid))) {
            return array(); // no checkmarks present
        }

        $componentstr = get_string('modulenameplural', 'checkmark');
        $status = array();

        if (!empty($data->reset_checkmark_submissions)) {
            $checkmarkssql = "SELECT a.id
                                 FROM {checkmark} a
                                WHERE a.course=:courseid";
            $params = array('courseid' => $data->courseid);

            // now get rid of all submissions and responses
            $fs = get_file_storage();
            if ($checkmarks = $DB->get_records_sql($checkmarkssql, $params)) {
                foreach ($checkmarks as $checkmarkid => $unused) {
                    if (!$cm = get_coursemodule_from_instance('checkmark', $checkmarkid)) {
                        continue;
                    }
                    $context = context_module::instance($cm->id);
                    $fs->delete_area_files($context->id, 'mod_checkmark', 'submission');
                    $fs->delete_area_files($context->id, 'mod_checkmark', 'response');
                }
            }

            $DB->delete_records_select('checkmark_submissions', "checkmark_id IN ($checkmarkssql)",
                                       $params);

            $status[] = array('component' => $componentstr,
                              'item'      => get_string('deleteallsubmissions', 'checkmark'),
                              'error'     => false);

            if (empty($data->reset_gradebook_grades)) {
                // remove all grades from gradebook
                checkmark_reset_gradebook($data->courseid);
            }
        }

        /// updating dates - shift may be negative too
        if ($data->timeshift) {
            shift_course_mod_dates('checkmark', array('timedue', 'timeavailable'),
                                   $data->timeshift, $data->course);
            $status[] = array('component' => $componentstr,
                              'item'      => get_string('datechanged'),
                              'error'     => false);
        }

        return $status;
    }

    /**
     *
     * @param filehandle $bf file handle for xml file to write to
     * @param mixed $preferences the complete backup preference object
     *
     * @return boolean
     *
     * @static
     */
    public static function backup_one_mod($bf, $preferences, $checkmark) {
        return true;
    }

    /**
     *
     * @param filehandle $bf file handle for xml file to write to
     * @param mixed $preferences the complete backup preference object
     * @param object $submission the checkmark submission db record
     *
     * @return boolean
     *
     * @static
     */
    public static function backup_one_submission($bf, $preferences, $checkmark, $submission) {
        return true;
    }

    /**
     *
     * @param array  $info the array representing the xml
     * @param object $restore the restore preferences
     *
     * @return boolean
     *
     * @static
     */
    public static function restore_one_mod($info, $restore, $checkmark) {
        return true;
    }

    /**
     *
     * @param object $submission the newly created submission
     * @param array  $info the array representing the xml
     * @param object $restore the restore preferences
     *
     * @return boolean
     *
     * @static
     */
    public static function restore_one_submission($info, $restore, $checkmark, $submission) {
        return true;
    }

} ////// End of the checkmark_base class

class mod_checkmark_grading_form extends moodleform {

    public function definition() {
        global $OUTPUT;
        $mform =& $this->_form;

        $formattr = $mform->getAttributes();
        $formattr['id'] = 'submitform';
        $mform->setAttributes($formattr);
        // hidden params
        $mform->addElement('hidden', 'offset', ($this->_customdata->offset+1));
        $mform->setType('offset', PARAM_INT);
        $mform->addElement('hidden', 'user_id', $this->_customdata->userid);
        $mform->setType('user_id', PARAM_INT);
        $mform->addElement('hidden', 'nextid', $this->_customdata->nextid);
        $mform->setType('nextid', PARAM_INT);
        $mform->addElement('hidden', 'id', $this->_customdata->cm->id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'sesskey', sesskey());
        $mform->setType('sesskey', PARAM_ALPHANUM);
        $mform->addElement('hidden', 'mode', 'grade');
        $mform->setType('mode', PARAM_TEXT);
        $mform->addElement('hidden', 'menuindex', "0");
        $mform->setType('menuindex', PARAM_INT);
        $mform->addElement('hidden', 'saveuserid', "-1");
        $mform->setType('saveuserid', PARAM_INT);
        $mform->addElement('hidden', 'filter', "0");
        $mform->setType('filter', PARAM_INT);

        $mform->addElement('static', 'picture', $OUTPUT->user_picture($this->_customdata->user),
        fullname($this->_customdata->user, true) . '<br/>' .
        userdate($this->_customdata->submission->timemodified) .
        $this->_customdata->lateness );

        $this->add_submission_content();
        $this->add_grades_section();

        $this->add_feedback_section();

        if ($this->_customdata->submission->timemarked) {
            $datestring = userdate($this->_customdata->submission->timemarked)."&nbsp; (".
                                   format_time(time() - $this->_customdata->submission->timemarked).
                                   ")";
            $mform->addElement('header', 'Last Grade', get_string('lastgrade', 'checkmark'));
            $mform->addElement('static', 'picture',
                               $OUTPUT->user_picture($this->_customdata->teacher),
                               fullname($this->_customdata->teacher, true).'<br/>'.$datestring);
        }
        // buttons
        $this->add_grading_buttons();

    }

    public function add_grades_section() {
        global $CFG;
        $mform =& $this->_form;
        $attributes = array();
        if ($this->_customdata->gradingdisabled) {
            $attributes['disabled'] ='disabled';
        }

        $grademenu = make_grades_menu($this->_customdata->checkmark->grade);
        $grademenu['-1'] = get_string('nograde');

        $mform->addElement('header', 'Grades', get_string('grades', 'grades'));
        $mform->addElement('select', 'xgrade', get_string('grade').':', $grademenu, $attributes);
        //@fixme some bug when element called 'grade' makes it break
        $mform->setDefault('xgrade', $this->_customdata->submission->grade );
        $mform->setType('xgrade', PARAM_INT);

        if (!empty($this->_customdata->enableoutcomes)) {
            foreach ($this->_customdata->grading_info->outcomes as $n => $outcome) {
                $options = make_grades_menu(-$outcome->scaleid);
                if ($outcome->grades[$this->_customdata->submission->userid]->locked) {
                    $options[0] = get_string('nooutcome', 'grades');
                    echo $options[$outcome->grades[$this->_customdata->submission->userid]->grade];
                } else {
                    $options[''] = get_string('nooutcome', 'grades');
                    $attributes = array('id' => 'menuoutcome_'.$n );
                    $mform->addElement('select', 'outcome_'.$n.'['.$this->_customdata->userid.']',
                                       $outcome->name.':', $options, $attributes );
                    $mform->setType('outcome_'.$n.'['.$this->_customdata->userid.']', PARAM_INT);
                    $grade = $outcome->grades[$this->_customdata->submission->userid]->grade;
                    $mform->setDefault('outcome_'.$n.'['.$this->_customdata->userid.']', $grade);
                }
            }
        }
        $course_context = context_module::instance($this->_customdata->cm->id);
        if (has_capability('gradereport/grader:view', $course_context)
                && has_capability('moodle/grade:viewall', $course_context)) {
            $gradeitem = $this->_customdata->grading_info->items[0];
            $grade = '<a href="'.$CFG->wwwroot.'/grade/report/grader/index.php?id='.
                     $this->_customdata->course .'" >'.
                     $gradeitem->grades[$this->_customdata->userid]->str_grade.'</a>';
        } else {
            $gradeitem = $this->_customdata->grading_info->items[0];
            $grade = $gradeitem->grades[$this->_customdata->userid]->str_grade;
        }
        $mform->addElement('static', 'finalgrade', get_string('currentgrade', 'checkmark').':' ,
                           $grade);
        $mform->setType('finalgrade', PARAM_INT);
    }

    /**
     *
     * @global core_renderer $OUTPUT
     */
    public function add_feedback_section() {
        global $OUTPUT;
        $mform =& $this->_form;
        $mform->addElement('header', 'Feed Back', get_string('feedback', 'grades'));

        if ($this->_customdata->gradingdisabled) {
            $gradeitem = $this->_customdata->grading_info->items[0];
            $feedback = $gradeitem->grades[$this->_customdata->userid]->str_feedback;
            $mform->addElement('static', 'disabledfeedback', '', $feedback);
        } else {
            // visible elements

            $mform->addElement('editor', 'submissioncomment_editor',
                               get_string('feedback', 'checkmark').':', null,
                               $this->get_editor_options() );
            $mform->setType('submissioncomment_editor', PARAM_RAW); // to be cleaned before display
            $mform->setDefault('submissioncomment_editor',
                               $this->_customdata->submission->submissioncomment);
            $mform->addElement('hidden', 'mailinfo_h', "0");
            $mform->setType('mailinfo_h', PARAM_INT);
            $mform->addElement('checkbox', 'mailinfo',
                               get_string('enablenotification', 'checkmark').
                               $OUTPUT->help_icon('enablenotification', 'checkmark') .':' );
            $mform->setType('mailinfo', PARAM_INT);
        }
    }

    public function add_grading_buttons() {
        $mform =& $this->_form;
        //if there are more to be graded.
        if ($this->_customdata->nextid>0) {
            $buttonarray=array();
            $buttonarray[] = &$mform->createElement('submit', 'submitbutton',
                                                    get_string('savechanges'));
            //@todo: fix accessibility: javascript dependency not necessary
            $buttonarray[] = &$mform->createElement('submit', 'saveandnext',
                                                    get_string('saveandnext'));
            $buttonarray[] = &$mform->createElement('submit', 'next', get_string('next'));
            $buttonarray[] = &$mform->createElement('cancel');
        } else {
            $buttonarray=array();
            $buttonarray[] = &$mform->createElement('submit', 'submitbutton',
                                                    get_string('savechanges'));
            $buttonarray[] = &$mform->createElement('cancel');
        }
        $mform->addGroup($buttonarray, 'grading_buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('grading_buttonar');
        $mform->setType('grading_buttonar', PARAM_RAW);
    }

    public function add_submission_content() {
        $mform =& $this->_form;
        $mform->addElement('header', 'Submission', get_string('submission', 'checkmark'));
        $mform->addElement('html', $this->_customdata->submission_content);
    }

    protected function get_editor_options() {
        $editoroptions = array();
        $editoroptions['context'] = context_module::instance($this->_customdata->cm->id);
        $editoroptions['component'] = 'mod_checkmark';
        $editoroptions['filearea'] = 'feedback';
        $editoroptions['noclean'] = false;
        $editoroptions['maxfiles'] = 0;
        return $editoroptions;
    }

    public function set_data($data) {
        $editoroptions = $this->get_editor_options();
        if (!isset($data->text)) {
            $data->text = '';
        }
        if (!isset($data->format)) {
            $data->textformat = FORMAT_HTML;
        } else {
            $data->textformat = $data->format;
        }

        if (!empty($this->_customdata->submission->id)) {
            $itemid = $this->_customdata->submission->id;
        } else {
            $itemid = null;
        }

        $data = file_prepare_standard_editor($data, 'submissioncomment', $editoroptions,
                                             $editoroptions['context'], $editoroptions['component'],
                                             $editoroptions['filearea'], $itemid);
        return parent::set_data($data);
    }

    public function get_data() {
        $data = parent::get_data();

        if (!empty($this->_customdata->submission->id)) {
            $itemid = $this->_customdata->submission->id;
        } else {
            $itemid = null; //TODO: this is wrong, itemid MUST be known when saving files!! (skodak)
        }

        if ($data) {
            $editoroptions = $this->get_editor_options();
            $data = file_postupdate_standard_editor($data, 'submissioncomment', $editoroptions,
                                                    $this->_customdata->context,
                                                    $editoroptions['component'],
                                                    $editoroptions['filearea'], $itemid);
        }
        return $data;
    }
}
