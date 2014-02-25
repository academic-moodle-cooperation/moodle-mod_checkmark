<?php
/**
 * delete.php
 * This file deletes an checkmark-coursemodule
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
$a  = optional_param('a', 0, PARAM_INT);   // Checkmark ID?

$url = new moodle_url('/mod/checkmark/delete.php');
if ($id) {
    if (! $cm = get_coursemodule_from_id('checkmark', $id)) {
        print_error('invalidcoursemodule');
    }

    if (! $checkmark = $DB->get_record('checkmark', array('id'=>$cm->instance))) {
        print_error('invalidid', 'checkmark');
    }

    if (! $course = $DB->get_record('course', array('id'=>$checkmark->course))) {
        print_error('coursemisconf', 'checkmark');
    }
    $url->param('id', $id);
} else {
    if (!$checkmark = $DB->get_record('checkmark', array('id'=>$a))) {
        print_error('invalidcoursemodule');
    }
    if (! $course = $DB->get_record('course', array('id'=>$checkmark->course))) {
        print_error('coursemisconf', 'checkmark');
    }
    if (! $cm = get_coursemodule_from_instance('checkmark', $checkmark->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
    $url->param('a', $a);
}

$PAGE->set_url($url);
require_login($course->id, false, $cm);

// Load up the required checkmark code!
require($CFG->dirroot.'/mod/checkmark/lib.php');
checkmark_delete_instance($checkmark->id);
