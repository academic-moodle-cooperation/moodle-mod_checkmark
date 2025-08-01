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
 * submission_form.php Extends the moodleform class for checkmark submission form
 *
 * @package   mod_checkmark
 * @author    Philipp Hager, extended and maintained by Daniel Binder
 * @copyright 2019 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot.'/mod/checkmark/locallib.php');

/**
 * checkmark_submission_form extends moodleform and defines checkmarks' submission form
 *
 * contains a checkbox for each example and some standard form buttons
 * additionally it invokes some JS to display the amount of currently checked examples and points
 *
 * @package   mod_checkmark
 * @author    Philipp Hager, xtended and maintained by Daniel Binder
 * @copyright 2019 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class checkmark_submission_form extends moodleform {

    /**
     * Defines the submission form
     */
    public function definition() {
        global $PAGE;

        $mform =& $this->_form; // Don't forget the underscore!
        $attr = $mform->getAttributes();
        $attr['class'] = 'mform submissionform header-maxwidth';
        $mform->setAttributes($attr);

        $buttonarray = [];
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton',
            get_string('savesubmission', 'checkmark'));
        $buttonarray[] = &$mform->createElement('reset', 'resetbutton', get_string('revert'),
            ['class' => 'btn btn-secondary mr-1']);
        $mform->addGroup($buttonarray, 'buttonar', '', ' ', false, ['class' => 'w-100 buttonar-leftalign']);
        $mform->closeHeaderBefore('buttonar');

        $examples = $this->_customdata->examples;
        $usecolumns = count($examples) > MAXCHECKMARKS_FOR_ONECOLUMN;

        if ($usecolumns) {
            $mform->addElement('html', '<div class="responsive-columns">');
        } else {
            $mform->addElement('html', '<div>');
        }

        foreach ($this->_customdata->examples as $key => $example) {
            $pointsstring = ($example->grade === '1')
                ? get_string('strpoint', 'checkmark')
                : get_string('strpoints', 'checkmark');

            $attr = [
                'id' => 'example' . $key,
                'group' => '1',
                'data-grade' => $example->grade,
                'data-name' => $example->shortname,
                'data-example' => $example->id,
            ];

            $mform->addElement('html', '<div class="example-checkmark">');
            $mform->addElement(
                'advcheckbox',
                $key,
                '',
                $example->prefix . $example->name . ' (' . $example->grade . ' ' . $pointsstring . ')',
                $attr,
                [0, 1]
            );
            $mform->addElement('html', '</div>');

            if (property_exists($this->_customdata, 'example' . $key)) {
                $mform->setDefault($key, $this->_customdata->{'example' . $key});
            }
        }
        $mform->addElement('html', '</div>');

        // Here come the hidden params!
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'edit');
        $mform->setType('edit', PARAM_INT);

        // Only preload js if the form is editable.
        if ($this->_customdata->editable) {
            $PAGE->requires->js_call_amd('mod_checkmark/submission', 'initializer');
        }

        // Set data from last submission and hidden fields!
        $this->_customdata->examples = [];
        $this->set_data($this->_customdata);
    }
}
