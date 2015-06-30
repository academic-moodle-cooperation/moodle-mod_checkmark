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
 * grading_form.php
 * This class contains the grading form for checkmark-submissions
 *
 * @package       mod_checkmark
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

class mod_checkmark_grading_form extends moodleform {

    public function definition() {
        global $OUTPUT;
        $mform =& $this->_form;

        $formattr = $mform->getAttributes();
        $formattr['id'] = 'submitform';
        $mform->setAttributes($formattr);
        // Here come the hidden params!
        $mform->addElement('hidden', 'offset', ($this->_customdata->offset + 1));
        $mform->setType('offset', PARAM_INT);
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

        $mform->addElement('static', 'picture', $OUTPUT->user_picture($this->_customdata->user),
                           fullname($this->_customdata->user) . '<br/>' .
                           userdate($this->_customdata->submission->timemodified) .
                           $this->_customdata->lateness );

        $this->add_submission_content();
        $this->add_grades_section();

        $this->add_feedback_section();

        if ($this->_customdata->submission->timemarked) {
            $datestring = userdate($this->_customdata->submission->timemarked).'&nbsp; ('.
                                   format_time(time() - $this->_customdata->submission->timemarked).
                                   ')';
            $mform->addElement('header', 'Last Grade', get_string('lastgrade', 'checkmark'));
            $mform->addElement('static', 'picture',
                               $OUTPUT->user_picture($this->_customdata->teacher),
                               fullname($this->_customdata->teacher).'<br/>'.$datestring);
        }
        // Buttons we need!
        $this->add_grading_buttons();

    }

    public function add_grades_section() {
        global $CFG;
        $mform =& $this->_form;
        $attributes = array();
        if ($this->_customdata->gradingdisabled) {
            $attributes['disabled'] = 'disabled';
        }

        $grademenu = make_grades_menu($this->_customdata->checkmark->grade);
        $grademenu['-1'] = get_string('nograde');

        $mform->addElement('header', 'Grades', get_string('grades', 'grades'));
        $mform->addElement('select', 'xgrade', get_string('grade').':', $grademenu, $attributes);
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
        $coursecontext = context_module::instance($this->_customdata->cm->id);
        if (has_capability('gradereport/grader:view', $coursecontext)
            && has_capability('moodle/grade:viewall', $coursecontext)) {
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
            // Visible elements!

            $mform->addElement('editor', 'submissioncomment_editor',
                               get_string('feedback', 'checkmark').':', null,
                               $this->get_editor_options() );
            $mform->setType('submissioncomment_editor', PARAM_RAW); // To be cleaned before display!
            $mform->setDefault('submissioncomment_editor',
                               $this->_customdata->submission->submissioncomment);
            $mform->addElement('hidden', 'mailinfo_h', '0');
            $mform->setType('mailinfo_h', PARAM_INT);
            $mform->addElement('checkbox', 'mailinfo',
                               get_string('enablenotification', 'checkmark').
                               $OUTPUT->help_icon('enablenotification', 'checkmark') .':' );
            $mform->setType('mailinfo', PARAM_INT);
        }
    }

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
            $itemid = null; // TODO: this is wrong, itemid MUST be known when saving files! (skodak)!
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
