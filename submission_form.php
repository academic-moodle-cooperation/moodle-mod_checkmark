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
 * submission_form.php
 * Extend the moodleform class for checkmark submission form, branch 'MOODLE_21_STABLE'
 *
 * @package       mod_checkmark
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot.'/mod/checkmark/locallib.php');

/*
 * class checkmark_submission_form extends moodleform
 * definition of the submission form containing checkboxes
 * for each example in the checkmark
 */
class checkmark_submission_form extends moodleform {

    public function definition() {
        global $CFG, $USER, $DB, $PAGE;

        $mform =& $this->_form; // Don't forget the underscore!

        foreach ($this->_customdata->examples as $key => $example) {
            switch ($example->grade) {
                case '1':
                    $pointsstring = get_string('strpoint', 'checkmark');
                break;
                case '2':
                default:
                    $pointsstring = get_string('strpoints', 'checkmark');
                break;
            }

            $mform->addElement('advcheckbox', 'example'.$key, null,
                               get_string('strexample', 'checkmark').' '.$example->name.' ('.
                               $example->grade.' '.$pointsstring.')',
                               array('id' => 'example'.$key, 'group' => '1'), array(0, 1));
        }
        $checkmark = $DB->get_record('checkmark',
                                     array('id' => $this->_customdata->checkmarkid), '*', MUST_EXIST);

        // Here come the hidden params!
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'edit');
        $mform->setType('edit', PARAM_INT);

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton',
                                                get_string('savechanges'));
        $buttonarray[] = &$mform->createElement('reset', 'resetbutton', get_string('revert'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

        $jsdata = array($this->_customdata->examples);

        $jsmodule = array('name'     => 'mod_checkmark',
                          'fullpath' => '/mod/checkmark/yui/checkmark/checkmark.js',
                          'requires' => array('base', 'io', 'node', 'json'),
                          'strings'  => array(array('yes', 'moodle'),
                                              array('no', 'moodle')));

        $PAGE->requires->js_init_call('M.mod_checkmark.init_submission', $jsdata, false,
                                      $jsmodule);

        // Set data from last submission and hidden fields!
        $this->set_data($this->_customdata);
    }
}
