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
 * submissions.php
 * Lists all checkmark-submissions in course-module.
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
require_once($CFG->libdir.'/plagiarismlib.php');

$id   = optional_param('id', 0, PARAM_INT);          // Course module ID
$a    = optional_param('a', 0, PARAM_INT);           // checkmark ID
$mode = optional_param('mode', 'all', PARAM_ALPHANUM);  // What mode are we in?
$download = optional_param('download' , 'none', PARAM_ALPHA); // ZIP download asked for?

$url = new moodle_url('/mod/checkmark/submissions.php');
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
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record('course', array('id' => $checkmark->course))) {
        print_error('coursemisconf', 'checkmark');
    }
    if (!$cm = get_coursemodule_from_instance('checkmark', $checkmark->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
    $url->param('a', $a);
}

$PAGE->set_url($url);
require_login($course->id, false, $cm);

require_capability('mod/checkmark:grade', context_module::instance($cm->id));

$PAGE->requires->js('/mod/checkmark/yui/checkmark/checkmark.js');

// Load up the required checkmark code!
$checkmarkinstance = new checkmark($cm->id, $checkmark, $cm, $course);

if ($mode !== 'all') {
    $url->param('mode', $mode);
}

if ($download == 'zip') {
    $checkmarkinstance->download_submissions();
} else if (optional_param('submittoprint', false, PARAM_BOOL) !== false) {
    $PAGE->set_pagelayout('popup'); // Remove navbars, etc!
    $checkmarkinstance->submissions_print();
} else {
    $checkmarkinstance->submissions($mode);   // Display or process the submissions!
}
