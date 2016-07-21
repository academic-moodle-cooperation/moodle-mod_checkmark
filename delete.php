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
 * delete.php deletes an checkmark instance
 *
 * @package       mod_checkmark
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('lib.php');

$id = optional_param('id', 0, PARAM_INT);  // Course module ID?
$c  = optional_param('c', 0, PARAM_INT);   // Checkmark ID?

// Sets url with params and performs require_login!
$url = new moodle_url('/mod/checkmark/delete.php');
list($cm, $checkmark, $course) = \checkmark::init_checks($id, $c, $url);

$modinfo = get_fast_modinfo($course);
$cminfo = $modinfo->get_cm($cm->id);
if (empty($cminfo->uservisible)) {
    if ($cminfo->availableinfo) {
        // User cannot access the activity, but on the course page they will
        // see a link to it, greyed-out, with information (HTML format) from
        // $cm->availableinfo about why they can't access it.
        $text = "<br />".format_text($cminfo->availableinfo, FORMAT_HTML);
    } else {
        // User cannot access the activity and they will not see it at all.
        $text = '';
    }
    $notification = $OUTPUT->notification(get_string('conditions_prevent_access', 'checkmark').$text, 'notifyproblem');
    echo $OUTPUT->box($notification, 'generalbox centered');
    die;
}

// Load up the required checkmark code!
require($CFG->dirroot.'/mod/checkmark/lib.php');
checkmark_delete_instance($checkmark->id);
