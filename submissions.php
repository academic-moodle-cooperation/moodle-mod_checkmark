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
 * submissions.php lists all checkmark-submissions in this course-module
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/checkmark/locallib.php');
require_once($CFG->libdir.'/plagiarismlib.php');

$id   = optional_param('id', 0, PARAM_INT);          // Course module ID
$c    = optional_param('c', 0, PARAM_INT);           // checkmark ID
$mode = optional_param('mode', 'all', PARAM_ALPHANUM);  // What mode are we in?
$download = optional_param('download' , 'none', PARAM_ALPHA); // ZIP download asked for?

// Sets url with params and performs require_login!
$url = new moodle_url('/mod/checkmark/submissions.php');
list($cm, $checkmark, $course) = \checkmark::init_checks($id, $c, $url);

require_capability('mod/checkmark:grade', context_module::instance($cm->id));

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
    $pagetitle = $course->shortname.': '.get_string('modulename', 'checkmark').': '.format_string($checkmark->name, true);
    $PAGE->set_title(strip_tags($pagetitle));
    $PAGE->set_heading($course->fullname);

    $checkmarkinstance->submissions($mode);   // Display or process the submissions!
}
