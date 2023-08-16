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
 * preview.php Prints the preview of a particular instance of checkmark
 *
 * @package   mod_checkmark
 * @copyright 2023 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

// We check that in detail afterwards!
require_login();

require_once($CFG->dirroot . '/mod/checkmark/locallib.php');

$id = optional_param('id', 0, PARAM_INT);  // Course Module ID?
$c  = optional_param('c', 0, PARAM_INT);   // Checkmark ID?

// Sets url with params and performs require_login!
$url = new moodle_url('/mod/checkmark/preview.php');
list($cm, $checkmark, $course) = \checkmark::init_checks($id, $c, $url);
$PAGE->activityheader->disable();
$checkmarkinstance = new checkmark($cm->id, $checkmark, $cm, $course);

echo $OUTPUT->header();

$checkmarkinstance->preview();   // Actually display the checkmark!