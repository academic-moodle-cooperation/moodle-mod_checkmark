<?php
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

    if (! $checkmark = $DB->get_record('checkmark', array('id'=>$cm->instance))) {
        print_error('invalidid', 'checkmark');
    }

    if (! $course = $DB->get_record('course', array('id'=>$checkmark->course))) {
        print_error('coursemisconf', 'checkmark');
    }
    $url->param('id', $id);
} else {
    if (!$checkmark = $DB->get_record('checkmark', array('id'=>$a))) {
        print_error('invalidid', 'checkmark');
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
require_login($course, true, $cm);

$PAGE->requires->js('/mod/checkmark/yui/checkmark/checkmark.js');

require_once($CFG->dirroot.'/mod/checkmark/lib.php');
$checkmarkinstance = new checkmark($cm->id, $checkmark, $cm, $course);

// Mark as viewed!
$completion=new completion_info($course);
$completion->set_module_viewed($cm);

$checkmarkinstance->view();   // Actually display the checkmark!
