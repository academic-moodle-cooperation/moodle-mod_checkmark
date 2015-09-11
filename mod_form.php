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
 * mod_form.php
 * Moodleform for checkmark-settings
 *
 * @package       mod_checkmark
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

//  It must be included from a Moodle page!
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/checkmark/locallib.php');

class mod_checkmark_mod_form extends moodleform_mod {
    protected $_checkmarkinstance = null;

    public function definition() {
        global $CFG, $DB, $COURSE, $PAGE, $OUTPUT;
        $mform =& $this->_form;

        $checkmarkinstance = new checkmark();

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('checkmarkname', 'checkmark'),
                           array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');

        $requiremodintro = get_config('checkmark', 'requiremodintro');
        $this->add_intro_editor($requiremodintro, get_string('description', 'checkmark'));

        $mform->addElement('header', 'availability', get_string('availability', 'assign'));
        $mform->setExpanded('availability', true);

        $name = get_string('availabledate', 'checkmark');
        $options = array('optional' => true);
        $mform->addElement('date_time_selector', 'timeavailable', $name, $options);
        $mform->addHelpButton('timeavailable', 'availabledate', 'checkmark');
        $mform->setDefault('timeavailable', time());

        $name = get_string('duedate', 'checkmark');
        $mform->addElement('date_time_selector', 'timedue', $name, array('optional' => true));
        $mform->addHelpButton('timedue', 'duedate', 'checkmark');
        $mform->setDefault('timedue', date('U', strtotime('+1week 23:55', time())));

        $name = get_string('cutoffdate', 'checkmark');
        $mform->addElement('date_time_selector', 'cutoffdate', $name, array('optional' => true));
        $mform->addHelpButton('cutoffdate', 'cutoffdate', 'checkmark');
        $mform->setDefault('cutoffdate', date('U', strtotime('+1week 23:55', time())));

        $name = get_string('alwaysshowdescription', 'checkmark');
        $mform->addElement('advcheckbox', 'alwaysshowdescription', $name);
        $mform->addHelpButton('alwaysshowdescription', 'alwaysshowdescription', 'checkmark');
        $mform->setDefault('alwaysshowdescription', 1);
        $mform->disabledIf('alwaysshowdescription', 'timeavailable[enabled]', 'notchecked');

        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));

        $typetitle = get_string('modulename', 'checkmark');

        $mform->addElement('header', 'typedesc', $typetitle);
        $mform->setExpanded('typedesc');

        $mform->addElement('hidden', 'course', optional_param('course', 0, PARAM_INT));

        $jsdata = array(checkmark::DELIMITER);
        $jsmodule = array('name'     => 'mod_checkmark',
                          'fullpath' => '/mod/checkmark/yui/checkmark/checkmark.js',
                          'requires' => array('base', 'io', 'node', 'json', 'event-valuechange'),
                          'strings'  => array(array('yes', 'moodle'),
                                              array('no', 'moodle')));

        $PAGE->requires->js_init_call('M.mod_checkmark.init_settings', $jsdata, true, $jsmodule);
        $update = optional_param('update', 0, PARAM_INT);
        $cm = empty($update) ? null : get_coursemodule_from_id('', $update, 0, false, MUST_EXIST);
        $submissioncount = empty($update) ? 0 : checkmark_count_real_submissions($cm);

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
            $mform->addElement('html', $OUTPUT->notification(get_string('elements_disabled', 'checkmark'), 'notifymessage'));
        }
        $mform->addElement('text', 'examplecount', get_string('numberofexamples', 'checkmark'),
                           array('id' => 'id_examplecount'));
        // We're going to clean them by ourselves...
        $mform->setType('examplecount', PARAM_RAW);
        $mform->addHelpButton('examplecount', 'numberofexamples', 'checkmark');
        $mform->disabledIf('examplecount', 'flexiblenaming', 'checked');
        $stdexamplecount = get_config('checkmark', 'stdexamplecount');
        $mform->setDefault('examplecount', $stdexamplecount);

        $mform->addElement('text', 'examplestart', get_string('firstexamplenumber', 'checkmark'));
        // We're going to clean them by ourselves...
        $mform->setType('examplestart', PARAM_RAW);
        $mform->addHelpButton('examplestart', 'firstexamplenumber', 'checkmark');
        $mform->disabledIf('examplestart', 'flexiblenaming', 'checked');
        $stdexamplestart = get_config('checkmark', 'stdexamplestart');
        $mform->setDefault('examplestart', $stdexamplestart);

        $mform->addElement('checkbox', 'flexiblenaming', get_string('flexiblenaming', 'checkmark'),
                           get_string('activateindividuals', 'checkmark'),
                           array('id' => 'id_flexiblenaming'));
        $mform->addHelpButton('flexiblenaming', 'flexiblenaming', 'checkmark');

        $mform->setAdvanced('flexiblenaming');

        $mform->addElement('text', 'examplenames',
                           get_string('examplenames', 'checkmark').' ('.checkmark::DELIMITER.')');
        // We clean these by ourselves!
        $mform->setType('examplenames', PARAM_RAW);
        $mform->addHelpButton('examplenames', 'examplenames', 'checkmark');
        $stdnames = get_config('checkmark', 'stdnames');
        $mform->setDefault('examplenames', $stdnames);

        $mform->disabledIf('examplenames', 'flexiblenaming', 'notchecked');
        $mform->setAdvanced('examplenames');

        $mform->addElement('text', 'examplegrades',
                           get_string('examplegrades', 'checkmark').' ('.checkmark::DELIMITER.')',
                           array('id' => 'id_examplegrades'));
        // We clean these by ourselves!
        $mform->setType('examplegrades', PARAM_RAW);
        $mform->addHelpButton('examplegrades', 'examplegrades', 'checkmark');
        $stdgrades = get_config('checkmark', 'stdgrades');
        $mform->setDefault('examplegrades', $stdgrades);
        $mform->disabledIf('examplegrades', 'flexiblenaming', 'notchecked');
        $mform->setAdvanced('examplegrades');

        $coursecontext = context_course::instance($COURSE->id);
        plagiarism_get_form_elements_module($mform, $coursecontext);

        $this->standard_grading_coursemodule_elements();

        $this->standard_coursemodule_elements();

        if ($submissioncount) {
            $mform->freeze('grade');
            $mform->freeze('examplecount');
            $mform->freeze('examplestart');
            $mform->freeze('flexiblenaming');
            $mform->freeze('examplenames');
            $mform->freeze('examplegrades');
            $mform->addElement('hidden', 'allready_submit', 'yes');
        } else {
            $mform->addElement('hidden', 'allready_submit', 'no');
        }
        $mform->setType('allready_submit', PARAM_ALPHA);

        $this->add_action_buttons();
    }

    public function standard_grading_coursemodule_elements() {
        global $CFG;
        $mform =& $this->_form;
        parent::standard_grading_coursemodule_elements();
        $mform->addHelpButton('grade', 'grade', 'checkmark');
        $stdexamplecount = get_config('checkmark', 'stdexamplecount');
        if (100 % $stdexamplecount) {
            $mform->setDefault('grade', $stdexamplecount);
        }
        $mform->setExpanded('modstandardgrade');
    }

    // Needed by plugin checkmark if it includes a filemanager element in the settings form!
    public function has_instance() {
        return ($this->_instance != null);
    }

    // Needed by plugin checkmarks if it includes a filemanager element in the settings form!
    public function get_context() {
        return $this->context;
    }

    protected function get_checkmark_instance() {
        global $CFG, $DB;

        if ($this->_checkmarkinstance) {
            return $this->_checkmarkinstance;
        }
        require_once($CFG->dirroot.'/mod/checkmark/lib.php');
        $this->checkmarkinstance = new checkmark();
        return $this->checkmarkinstance;
    }

    public function data_preprocessing(&$defaultvalues) {
        /* Allow plugin checkmarks to preprocess form data
         * (needed if it include any filemanager elements)!
         */
        $this->get_checkmark_instance()->form_data_preprocessing($defaultvalues, $this);

        if ($defaultvalues['instance']) {
            $examples = checkmark::get_examples($defaultvalues['instance']);
            $flexiblenaming = false;
            $oldname = null;
            $oldgrade = null;
            $names = '';
            $grades = '';
            $examplestart = '';
            $examplecount = count($examples);
            foreach ($examples as $key => $example) {
                if (($oldname == null) && ($oldgrade == null)) {
                    $oldname = $example->name;
                    $oldgrade = $example->grade;
                    $names = $example->name;
                    $grades = $example->grade;
                    $examplestart = $example->name;
                } else {
                    if ((intval($oldname) + 1 != intval($example->name))
                        || (intval($oldgrade) != intval($example->grade))) {
                            $flexiblenaming = true;
                    }
                    $names .= checkmark::DELIMITER.$example->name;
                    $grades .= checkmark::DELIMITER.$example->grade;
                }
                $oldgrade = $example->grade;
                $oldname = $example->name;
            }
            if ($flexiblenaming) {
                $defaultvalues['examplegrades'] = $grades;
                $defaultvalues['examplenames'] = $names;
                $defaultvalues['flexiblenaming'] = true;
            } else {
                $defaultvalues['flexiblenaming'] = false;
                $defaultvalues['examplestart'] = $examplestart;
                $defaultvalues['examplecount'] = $examplecount;
            }
        }
    }

    public function validation($data, $files) {
        // Allow plugin checkmarks to do any extra validation after the form has been submitted!
        $errors = parent::validation($data, $files);

        if ($data['timeavailable'] && $data['timedue']) {
            if ($data['timeavailable'] > $data['timedue']) {
                $errors['timedue'] = get_string('duedatevalidation', 'assign');
            }
        }
        if ($data['timedue'] && $data['cutoffdate']) {
            if ($data['timedue'] > $data['cutoffdate']) {
                $errors['cutoffdate'] = get_string('cutoffdatevalidation', 'assign');
            }
        }
        if ($data['timeavailable'] && $data['cutoffdate']) {
            if ($data['timeavailable'] > $data['cutoffdate']) {
                $errors['cutoffdate'] = get_string('cutoffdatefromdatevalidation', 'assign');
            }
        }
        $errors = array_merge($errors, $this->get_checkmark_instance()->form_validation($data,
                                                                                        $files));
        return $errors;
    }
}

