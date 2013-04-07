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
 * Extend the moodleform class for checkmark submission form, branch 'MOODLE_21_STABLE'
 *
 * @package       mod_checkmark
 * @author        Philipp Hager (e0803285@gmail.com)
 * @copyright     2011 TSC TU Vienna
 * @since         Moodle 2.1
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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

        $checkmark = $DB->get_record('checkmark',
        array('id'=>$this->_customdata->checkmarkid),
                                      '*', MUST_EXIST);

        if ( $checkmark->flexiblenaming ) {

            $examplenames = explode(checkmark::DELIMITER,
            $checkmark->examplenames);
            $examplegrades = explode(checkmark::DELIMITER,
            $checkmark->examplegrades);

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

                $mform->addElement('checkbox', $name, null,
                                   get_string('strexample', 'checkmark').
                                   ' '.$examplenames[$i].' ('.$examplegrades[$i].' '.
                                   $pointsstring.')',
                                   array('id'=>$name));
            }
        } else {
            $i = 0;
            $points = $checkmark->grade/$checkmark->examplecount;
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
                $examplenumber = strval($i+$checkmark->examplestart);
                $mform->addElement('advcheckbox', 'example'.strval($i+1), null,
                                   get_string('strexample', 'checkmark').' '.$examplenumber.' ('.
                                   $points.' '.$pointsstring.')',
                                   array('id'=>'example'.strval($i+1), 'group'=>'1'), array(0, 1));
                 $i++;
            } while ($i<$checkmark->examplecount);
        }

        // Here come the hidden params!
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'edit');
        $mform->setType('edit', PARAM_INT);

        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton',
        get_string('savechanges'));
        $buttonarray[] = &$mform->createElement('reset', 'resetbutton', get_string('revert'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

        if ($checkmark->flexiblenaming) {
            $jsdata = array( intval($checkmark->flexiblenaming), $examplenames, $examplegrades,
                             intval($checkmark->grade));
        } else {
            $jsdata = array(intval($checkmark->flexiblenaming), intval($checkmark->examplecount),
            intval($checkmark->examplestart), intval($checkmark->grade));
        }

        $jsmodule = array(
                    'name'     =>   'mod_checkmark',
                    'fullpath' =>   '/mod/checkmark/yui/checkmark/checkmark.js',
                    'requires' =>   array('base', 'io', 'node', 'json'),
                    'strings'  =>   array(
        array('yes', 'moodle'),
        array('no', 'moodle')
        )
        );

        $PAGE->requires->js_init_call('M.mod_checkmark.init_submission', $jsdata, false,
                                      $jsmodule);

        // Set data from last submission and hidden fields!
        $this->set_data($this->_customdata);

    }

}