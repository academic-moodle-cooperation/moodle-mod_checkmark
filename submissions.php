<?php
// This file is part of Moodle - http://moodle.org/
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

/**
 * Lists all checkmark-submissions in course-module.
 *
 * @package       mod
 * @subpackage    checkmark
 * @author        Philipp Hager
 * @copyright     2011 Philipp Hager
 * @since         Moodle 2.1
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once($CFG->dirroot . "/mod/checkmark/locallib.php");
require_once($CFG->libdir.'/plagiarismlib.php');

$id   = optional_param('id', 0, PARAM_INT);          // Course module ID
$a    = optional_param('a', 0, PARAM_INT);           // checkmark ID
$mode = optional_param('mode', 'all', PARAM_ALPHA);  // What mode are we in?
$download = optional_param('download' , 'none', PARAM_ALPHA); //ZIP download asked for?

$url = new moodle_url('/mod/checkmark/submissions.php');
if ($id) {
    if (! $cm = get_coursemodule_from_id('checkmark', $id)) {
        print_error('invalidcoursemodule');
    }

    if (! $checkmark = $DB->get_record("checkmark", array("id"=>$cm->instance))) {
        print_error('invalidid', 'checkmark');
    }

    if (! $course = $DB->get_record("course", array("id"=>$checkmark->course))) {
        print_error('coursemisconf', 'checkmark');
    }
    $url->param('id', $id);
} else {
    if (!$checkmark = $DB->get_record("checkmark", array("id"=>$a))) {
        print_error('invalidcoursemodule');
    }
    if (! $course = $DB->get_record("course", array("id"=>$checkmark->course))) {
        print_error('coursemisconf', 'checkmark');
    }
    if (! $cm = get_coursemodule_from_instance("checkmark", $checkmark->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
    $url->param('a', $a);
}

$PAGE->set_url($url);
require_login($course->id, false, $cm);

require_capability('mod/checkmark:grade', get_context_instance(CONTEXT_MODULE, $cm->id));

$PAGE->requires->js('/mod/checkmark/checkmark.js');

/// Load up the required checkmark code
$checkmarkinstance = new checkmark($cm->id, $checkmark, $cm, $course);

if ($mode !== 'all') {
    $url->param('mode', $mode);
}

if (isset($_POST['autograde_all_submit'])) {
    $mode = 'all';
    $_POST['autograde'] = checkmark::FILTER_ALL;
} else if (isset($_POST['autograde_req_submit'])) {
    $mode = 'all';
    $_POST['autograde'] = checkmark::FILTER_REQUIRE_GRADING;
} else if (isset($_POST['autograde_custom_submit'])) {
    $mode = 'all';
    $_POST['autograde'] = checkmark::FILTER_SELECTED;
}


if ($download == "zip") {
    $checkmarkinstance->download_submissions();
} else if (isset($_POST['submittoprint'])) {
    $PAGE->set_pagelayout('popup'); //remove navbars, etc
    $checkmarkinstance->submissions_print();
} else {
    $checkmarkinstance->submissions($mode);   // Display or process the submissions
}