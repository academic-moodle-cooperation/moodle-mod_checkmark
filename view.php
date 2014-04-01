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
 * view.php
 * view checkmark (called by moodle-core)
 *
 * @package       mod_checkmark
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/checkmark/locallib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/plagiarismlib.php');
$id = optional_param('id', 0, PARAM_INT);  // Course Module ID?
$a  = optional_param('a', 0, PARAM_INT);   // Checkmark ID?

$url = new moodle_url('/mod/checkmark/view.php');
if ($id) {
    if (! $cm = get_coursemodule_from_id('checkmark', $id)) {
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
    if (!$checkmark = $DB->get_record('checkmark', array('id' => $a))) {
        print_error('invalidid', 'checkmark');
    }
    if (! $course = $DB->get_record('course', array('id' => $checkmark->course))) {
        print_error('coursemisconf', 'checkmark');
    }
    if (!$cm = get_coursemodule_from_instance('checkmark', $checkmark->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
    $url->param('a', $a);
}

$PAGE->set_url($url);
require_login($course, true, $cm);

$PAGE->requires->js('/mod/checkmark/yui/checkmark/checkmark.js');

require_once($CFG->dirroot.'/mod/checkmark/lib.php');
$checkmarkinstance = new checkmark($cm->id, $checkmark, $cm, $course);

// Mark as viewed!
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$checkmarkinstance->view();   // Actually display the checkmark!
