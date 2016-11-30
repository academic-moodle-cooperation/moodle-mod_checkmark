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
 * view.php Prints the main view of a particular instance of checkmark
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/checkmark/locallib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/plagiarismlib.php');
$id = optional_param('id', 0, PARAM_INT);  // Course Module ID?
$c  = optional_param('c', 0, PARAM_INT);   // Checkmark ID?

// Sets url with params and performs require_login!
$url = new moodle_url('/mod/checkmark/view.php');
list($cm, $checkmark, $course) = \checkmark::init_checks($id, $c, $url);

$checkmarkinstance = new checkmark($cm->id, $checkmark, $cm, $course);

// Mark as viewed!
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$checkmarkinstance->view();   // Actually display the checkmark!
