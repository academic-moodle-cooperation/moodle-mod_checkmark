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
 * grading_form.php This file contains the grading form for checkmark-submissions
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @author    Daniel Binder (from 2019 onwards)
 * @copyright 2019 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * This class contains the grading form for checkmark-submissions
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @author    Daniel Binder (from 2019 onwards)
 * @copyright 2019 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_checkmark_grading_form extends moodleform {

    /**
     * Definition of the grading form.
     */
    public function definition() {
        global $OUTPUT, $PAGE;
        $PAGE->requires->js_call_amd('mod_checkmark/grading', 'init');
        $mform =& $this->_form;
        $formattr = $mform->getAttributes();
        $formattr['id'] = 'submitform';
        $mform->setAttributes($formattr);
        // Here come the hidden params!
        $mform->addElement('hidden', 'userid', $this->_customdata->userid);
        $mform->setType('userid', PARAM_INT);
        $mform->addElement('hidden', 'nextid', $this->_customdata->nextid);
        $mform->setType('nextid', PARAM_INT);
        $mform->addElement('hidden', 'previousid', $this->_customdata->previousid);
        $mform->setType('previousid', PARAM_INT);
        $mform->addElement('hidden', 'id', $this->_customdata->cm->id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'sesskey', sesskey());
        $mform->setType('sesskey', PARAM_ALPHANUM);
        $mform->addElement('hidden', 'mode', 'grade');
        $mform->setType('mode', PARAM_TEXT);
        $mform->addElement('hidden', 'menuindex', '0');
        $mform->setType('menuindex', PARAM_INT);
        $mform->addElement('hidden', 'saveuserid', '-1');
        $mform->setType('saveuserid', PARAM_INT);
        $mform->addElement('hidden', 'filter', '0');
        $mform->setType('filter', PARAM_INT);
        $mform->addElement('hidden', 'maxgrade', $this->_customdata->checkmark->grade);
        $mform->setType('maxgrade', PARAM_INT);

        $mform->addElement('static', 'picture', $OUTPUT->user_picture($this->_customdata->user),
                           fullname($this->_customdata->user) . '<br/>' .
                           userdate($this->_customdata->submission->timemodified) .
                           $this->_customdata->lateness );

        $this->add_submission_content();

        $this->add_feedback_section();

        if ($this->_customdata->feedbackobj !== false) {
            $datestring = userdate($this->_customdata->feedbackobj->timemodified).'&nbsp; ('.
                                   format_time(time() - $this->_customdata->feedbackobj->timemodified).
                                   ')';
            $mform->addElement('header', 'Last Grade', get_string('lastgrade', 'checkmark'));
            $mform->addElement('static', 'picture',
                               $OUTPUT->user_picture($this->_customdata->grader),
                               fullname($this->_customdata->grader).'<br/>'.$datestring);
        }
        // Buttons we need!
        $this->add_grading_buttons();

    }

    /**
     * Returns the MoodleQuickForm element to have the submission elements added!
     * @return MoodleQuickForm
     */
    public function get_moodleform() {
        $mform = &$this->_form;
        return $mform;
    }

    /**
     * Add the feedback section to the form.
     */
    public function add_feedback_section() {
        global $OUTPUT;
        $mform =& $this->_form;
        $mform->addElement('header', 'Feed Back', get_string('feedback', 'grades'));
        $mform->setExpanded('Feed Back');

        // Grades elements!
        $this->add_grades_elements();

        // Outcomes elements!
        $this->add_outcomes_elements();

        // Oldgrade elements!
        $this->add_old_grade();

        // Feedback elements!
        if ($this->_customdata->gradingdisabled) {
            $gradeitem = $this->_customdata->grading_info->items[CHECKMARK_GRADE_ITEM];
            $feedback = $gradeitem->grades[$this->_customdata->userid]->str_feedback;
            $mform->addElement('static', 'disabledfeedback', '', $feedback);
        } else {
            // Visible elements!
            $mform->addElement('editor', 'feedback_editor', get_string('feedback', 'checkmark').':', null,
                               $this->get_editor_options() );
            $mform->setDefault('feedback_editor', $this->_customdata->feedback);
        }

        // Attendance elements!
        $this->add_attendance_elements();

        // Presentation grade elements!
        $this->add_presentation_grade_elements();

        $mform->addElement('hidden', 'mailinfo_h', '0');
        $mform->setType('mailinfo_h', PARAM_INT);
        $mform->addElement('checkbox', 'mailinfo',
                           get_string('enablenotification', 'checkmark').
                           $OUTPUT->help_icon('enablenotification', 'checkmark') .':' );
        $mform->setType('mailinfo', PARAM_INT);
    }

    /**
     * Adds the grade elements!
     */
    public function add_grades_elements() {
        $mform =& $this->_form;

        $attributes = array();
        if ($this->_customdata->gradingdisabled) {
            $attributes['disabled'] = 'disabled';
        }
        if ($this->_customdata->checkmark->grade > 0) {
            $name = get_string('gradeoutof', 'checkmark', $this->_customdata->checkmark->grade);
            $gradegroup = [];
            $gradegroup[] = &$mform->createElement('hidden', 'maxgrade', $this->_customdata->checkmark->grade);
            $gradegroup[] = &$mform->createElement('text', 'xgrade', $name);
            $gradegroup[] = &$mform->createElement('hidden', 'nullgrade', 0);
            $mform->setType('gradegroup[maxgrade]', PARAM_INT);
            $mform->setType('gradegroup[nullgrade]', PARAM_INT);
            $mform->addGroup($gradegroup, 'gradegroup', $name);

            //$mform->addElement('text', 'xgrade', $name);
            $mform->addHelpButton('gradegroup', 'gradeoutofhelp', 'checkmark');
            $mform->setType('gradegroup[xgrade]', PARAM_INT);
            $mform->addRule('gradegroup', get_string('maxgradeviolation', 'checkmark', $this->_customdata->checkmark->grade), 'compare', '>=', 'client');
            $mform->addGroupRule('gradegroup', get_string('maxgradeviolationneg', 'checkmark', $this->_customdata->checkmark->grade), 'regex', '/^\d+$/', 3,  'client');
        } else {
            $grademenu = array(-1 => get_string('nograde')) + make_grades_menu($this->_customdata->checkmark->grade);

            $mform->addElement('select', 'xgrade', get_string('grade', 'grades').':', $grademenu, $attributes);
            $mform->setType('xgrade', PARAM_INT);
        }
        if ($this->_customdata->feedbackobj !== false) {
            $mform->setDefault('xgrade', $this->_customdata->feedbackobj->grade );
        }
    }

    /**
     * Adds the outcome elements if used!
     */
    public function add_outcomes_elements() {
        $mform =& $this->_form;

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
    }

    /**
     * Displays the current grade
     */
    public function add_old_grade() {
        global $CFG;

        $mform =& $this->_form;

        $coursecontext = context_module::instance($this->_customdata->cm->id);
        if (has_capability('gradereport/grader:view', $coursecontext)
            && has_capability('moodle/grade:viewall', $coursecontext)) {
            $gradeitem = $this->_customdata->grading_info->items[CHECKMARK_GRADE_ITEM];
            $grade = '<a href="'.$CFG->wwwroot.'/grade/report/grader/index.php?id='.
                     $this->_customdata->course .'" >'.
                     $gradeitem->grades[$this->_customdata->userid]->str_grade.'</a>';
        } else {
            $gradeitem = $this->_customdata->grading_info->items[CHECKMARK_GRADE_ITEM];
            $grade = $gradeitem->grades[$this->_customdata->userid]->str_grade;
        }
        $mform->addElement('static', 'finalgrade', get_string('currentgrade', 'checkmark').':' ,
                           $grade);
        $mform->setType('finalgrade', PARAM_INT);
    }

    /**
     * Adds the attendance elements to the form!
     */
    public function add_attendance_elements() {
        $mform =& $this->_form;
        $context = context_module::instance($this->_customdata->cm->id);

        // Attendance section!
        if ($this->_customdata->trackattendance) {
            if ($this->_customdata->attendancedisabled || !has_capability('mod/checkmark:trackattendance', $context)) {
                if ($this->_customdata->attendancegradebook) {
                    $attendanceitem = $this->_customdata->grading_info->items[CHECKMARK_ATTENDANCE_ITEM];
                    $attendance = $attendanceitem->grades[$this->_customdata->userid]->grade;
                } else {
                    // If he has no right to grade and there's no attendance in gradebook, we have to use the regular attendance!
                    $attendance = $this->_customdata->attendance;
                }
                $symbol = checkmark_get_attendance_symbol($attendance);
                $mform->addElement('static', 'disabledattendance', get_string('attendance', 'checkmark').':', $symbol);
            } else {
                // TODO: if there's time, we add JS to show a beautiful select with symbols!
                $options = array(-1 => '? '.strtolower(get_string('unknown', 'checkmark')),
                                 1  => '✓ '.strtolower(get_string('attendant', 'checkmark')),
                                 0  => '✗ '.strtolower(get_string('absent', 'checkmark')));
                $mform->addElement('select', 'attendance', get_string('attendance', 'checkmark').':', $options);
                $mform->setType('attendance', PARAM_INT); // To be cleaned before display!
                $mform->setDefault('attendance', $this->_customdata->attendance);
            }
        }
    }

    /**
     * Adds the presentation grade elements to the form!
     */
    public function add_presentation_grade_elements() {
        $mform =& $this->_form;
        $context = context_module::instance($this->_customdata->cm->id);

        // Presentation grade section!
        if (!empty($this->_customdata->presentationgrading)) {
            if ($this->_customdata->presentationgradebook) {
                $presentationitem = $this->_customdata->grading_info->items[CHECKMARK_PRESENTATION_ITEM];
                if ($this->_customdata->instance_presentationgrade) {
                    $presentationgrade = $presentationitem->grades[$this->_customdata->userid]->grade;
                }
                $presentationfeedback = $presentationitem->grades[$this->_customdata->userid]->str_feedback;
            } else {
                /* If he has no right to grade and there's no presentation grade in gradebook,
                   we have to use the regular presentation grade! */
                if ($this->_customdata->instance_presentationgrade) {
                    $presentationgrade = $this->_customdata->presentationgrade;
                }
                $presentationfeedback = $this->_customdata->presentationfeedback;
            }
            if ($this->_customdata->presentationgradebook &&
                    ($this->_customdata->presgradedisabled || !has_capability('mod/checkmark:gradepresentation', $context))) {
                $mform->addElement('static', 'presentationgrade', get_string('presentationgrade', 'checkmark').':',
                                   $presentationitem->grades[$this->_customdata->userid]->str_long_grade);
                if ($presentationfeedback == '') {
                    $presentationfeedback = '-';
                }
                $mform->addElement('static', 'disabledpresentationfeedback', get_string('presentationfeedback', 'checkmark').':',
                                   $presentationfeedback);
            } else if ($this->_customdata->instance_presentationgrade) {
                $grademenu = array(-1 => get_string('nograde'));
                $grademenu = $grademenu + make_grades_menu($this->_customdata->checkmark->presentationgrade);
                if ($presentationgrade == '') {
                    $presentationgrade = -1;
                }
                $mform->addElement('select', 'presentationgrade', get_string('presentationgrade', 'checkmark').':', $grademenu);
                $mform->setType('presentationgrade', PARAM_INT);
                $mform->setDefault('presentationgrade', $presentationgrade);

                $mform->addElement('editor', 'presentationfeedback_editor', get_string('presentationfeedback', 'checkmark').':',
                                   null, $this->get_editor_options($this->_customdata->cm, 'presentationfeedback') );
                $mform->setDefault('presentationfeedback_editor', $presentationfeedback);
            } else if ($this->_customdata->instance_presentationgrade == 0) {
                // Print only the presentationfeedback field in case grading is set to 'none'.
                $mform->addElement('editor', 'presentationfeedback_editor', get_string('presentationfeedback', 'checkmark').':',
                        null, $this->get_editor_options($this->_customdata->cm, 'presentationfeedback') );
                $mform->setDefault('presentationfeedback_editor', $presentationfeedback);
            }
        }
    }

    /**
     * Adds standard grading-buttons to the form.
     */
    public function add_grading_buttons() {
        $mform =& $this->_form;
        $buttonarray = array();
        $buttonarray2 = array();
        if ($this->_customdata->previousid > 0) {
            $buttonarray2[] = &$mform->createElement('submit', 'previous', get_string('previous'));
        }
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton',
                                                get_string('savechanges'));
        // If there are more to be graded.
        if ($this->_customdata->nextid > 0) {
            $buttonarray[] = &$mform->createElement('submit', 'saveandnext',
                                                    get_string('saveandnext'));
            $buttonarray2[] = &$mform->createElement('submit', 'next', get_string('next'));
        }
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'grading_buttonar', '', array(' '), false);
        if (!empty($buttonarray2)) {
            $mform->addGroup($buttonarray2, 'grading_buttonar2', '', array(' '), false);
        }
        $mform->closeHeaderBefore('grading_buttonar');
    }

    /**
     * Add submission content to the form.
     */
    public function add_submission_content() {
        $mform =& $this->_form;
        $mform->addElement('header', 'Submission', get_string('submission', 'checkmark'));
        $this->add_submission_elements($mform, $this->_customdata->submission);
    }

    /**
     * Helper method to get the editor options easily.
     *
     * @param string $editor filearea for editor to use
     * @return mixed[] Editor-options
     */
    protected function get_editor_options($editor = 'feedback') {
        $editoroptions = array();
        $editoroptions['context'] = context_module::instance($this->_customdata->cm->id);
        $editoroptions['component'] = 'mod_checkmark';
        $editoroptions['filearea'] = $editor;
        $editoroptions['format'] = FORMAT_HTML;
        $editoroptions['maxfiles'] = 0;
        return $editoroptions;
    }

    /**
     * Overwrites parents set_data() method to perform some actions in addition.
     * TODO: do we need this still here?
     *
     * @param object $data Data to set the form elements
     * @return array form data
     */
    public function set_data($data) {
        $editoroptions = $this->get_editor_options();
        if (!isset($data->feedback)) {
            $data->feedback = '';
        }
        if (!isset($data->format)) {
            $data->feedbackformat = FORMAT_HTML;
        } else {
            $data->feedbackformat = $data->format;
        }

        if (!isset($data->presentationfeedbackformat)) {
            if (isset($data->presentationformat)) {
                $data->presentationfeedbackformat = $data->presentationformat;
            } else {
                $data->presentationfeedbackformat = FORMAT_HTML;
            }
        } else {
            $data->presentationfeedbackformat = FORMAT_HTML;
        }

        if (($this->_customdata->feedbackobj !== false)
            && !empty($this->_customdata->feedbackobj->id)) {
            $itemid = $this->_customdata->feedbackobj->id;
        } else {
            $itemid = null;
        }

        $data = file_prepare_standard_editor($data, 'feedback', $editoroptions,
                                             $editoroptions['context'], $editoroptions['component'],
                                             $editoroptions['filearea'], $itemid);
        if (!empty($this->_customdata->presentationgrading)) {
            $editoroptions = $this->get_editor_options($this->_customdata->cm, 'presentatonfeedback');
            $data = file_prepare_standard_editor($data, 'presentationfeedback', $editoroptions,
                                                 $editoroptions['context'], $editoroptions['component'],
                                                 $editoroptions['filearea'], $itemid);
        }
        return parent::set_data($data);
    }

    /**
     * Overwrites parents get_data() method to perform some actions in addition.
     * TODO: do we need this still here?
     *
     * @return array form data
     */
    public function get_data() {
        $data = parent::get_data();

        if (($this->_customdata->feedbackobj !== false)
            && $this->_customdata->feedbackobj->id) {
            $itemid = $this->_customdata->feedbackobj->id;

            if ($data) {
                $editoroptions = $this->get_editor_options();
                $data = file_postupdate_standard_editor($data, 'feedback', $editoroptions,
                                                        $this->_customdata->context,
                                                        $editoroptions['component'],
                                                        $editoroptions['filearea'], $itemid);
            }
        }
        return $data;
    }

    /**
     * Adds the elements representing the submission to the MoodleQuickForm!
     *
     * @param \MoodleQuickForm $mform
     * @param \mod_checkmark\submission $submission
     * @throws coding_exception
     * @throws dml_exception
     */
    public function add_submission_elements(\MoodleQuickForm &$mform, \mod_checkmark\submission $submission) {
        if (empty($submission)) {
            // If there's no submission, we have nothing to do here!
            return;
        }
        $examples = $submission->get_examples_or_example_template();
        foreach ($examples as $example) {
            $examplearray = [];
            $examplearray[] =& $mform->createElement('advcheckbox', $example->get_id(), '',
                    $example->get_name().' ('.$example->get_grade().' '.
                    $example->get_pointsstring().')', array('class' => 'examplecheck $' . $example->get_grade()));
            $examplearray[] =& $mform->createElement('html', $example->render_forced_hint());
            $mform->addGroup($examplearray, 'examplearr', '', array(' '), false);

            if ($example->is_checked()) { // Is it checked?
                $mform->setDefault($example->get_id(), 1);
            }
        }
    }

    /**
     * Validates current checkmark grade submission
     *
     * @param array $data data from the module form
     * @param array $files data about files transmitted by the module form
     * @return string[] array of error messages, to be displayed at the form fields
     */
    public function validation($data, $files) {
        // Allow plugin checkmarks to do any extra validation after the form has been submitted!
        $errors = parent::validation($data, $files);

        if (isset($data['xgrade']) && $data['xgrade'] > $this->_customdata->checkmark->grade && $this->_customdata->checkmark->grade > 0){
            $errors['xgrade'] = get_string('maxgradeviolation', 'checkmark', $this->_customdata->checkmark->grade);
        }
        return $errors;
    }
}
