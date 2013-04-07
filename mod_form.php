<?php
// This file is made for Moodle - http://moodle.org/
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
 * Moodleform for checkmark-settings
 *
 * @package       mod_checkmark
 * @author        Philipp Hager (e0803285@gmail.com)
 * @copyright     2011 TSC TU Vienna
 * @since         Moodle 2.1
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//  It must be included from a Moodle page!
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot . "/mod/checkmark/locallib.php");

class mod_checkmark_mod_form extends moodleform_mod {
    protected $_checkmarkinstance = null;

    public function definition() {
        global $CFG, $DB;
        $mform =& $this->_form;

        $checkmarkinstance = new checkmark();

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('checkmarkname', 'checkmark'),
                           array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');

        $this->add_intro_editor(true, get_string('description', 'checkmark'));

        $mform->addElement('date_time_selector', 'timeavailable',
                           get_string('availabledate', 'checkmark'), array('optional'=>true));
        $mform->setDefault('timeavailable', strtotime('now', time()));
        $mform->addElement('date_time_selector', 'timedue', get_string('duedate', 'checkmark'),
                           array('optional'=>true));
        $mform->setDefault('timedue', date('U', strtotime('+1week 23:55', time())));

        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));

        $mform->addElement('select', 'preventlate', get_string('preventlate', 'checkmark'),
                           $ynoptions);
        $mform->setDefault('preventlate', 0);

        $typetitle = get_string('modulename', 'checkmark');

        $mform->addElement('header', 'typedesc', $typetitle);

        $mform->addElement('hidden', 'course', optional_param('course', 0, PARAM_INT));

        $checkmarkinstance->setup_elements($mform);

        $this->standard_grading_coursemodule_elements();

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

    public function standard_grading_coursemodule_elements() {
        $mform =& $this->_form;
        parent::standard_grading_coursemodule_elements();
        $mform->addHelpButton('grade', 'grade', 'checkmark');
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


    public function data_preprocessing(&$default_values) {
        /* Allow plugin checkmarks to preprocess form data
         * (needed if it include any filemanager elements)!
         */
        $this->get_checkmark_instance()->form_data_preprocessing($default_values, $this);
    }

    public function validation($data, $files) {
        // Allow plugin checkmarks to do any extra validation after the form has been submitted!
        $errors = parent::validation($data, $files);

        if ($data['timeavailable'] && $data['timedue']) {
            if ($data['timeavailable'] > $data['timedue']) {
                $errors['timedue'] = get_string('duedatevalidation', 'checkmark');
            }
        }
        $errors = array_merge($errors, $this->get_checkmark_instance()->form_validation($data,
                                                                                        $files));
        return $errors;
    }
}

