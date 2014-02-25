<?php
/**
 * grade.php
 * This file redirects to submissions-list if someone has grading capabilities.
 * Otherwise it redirects to standard view.
 *
 * @package       mod_checkmark
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$id   = required_param('id', PARAM_INT);          // Course module ID!

$PAGE->set_url('/mod/checkmark/grade.php', array('id'=>$id));
if (! $cm = get_coursemodule_from_id('checkmark', $id)) {
    print_error('invalidcoursemodule');
}

if (! $checkmark = $DB->get_record('checkmark', array('id'=>$cm->instance))) {
    print_error('invalidid', 'checkmark');
}

if (! $course = $DB->get_record('course', array('id'=>$checkmark->course))) {
    print_error('coursemisconf', 'checkmark');
}

require_login($course, false, $cm);

if (has_capability('mod/checkmark:grade', get_context_instance(CONTEXT_MODULE, $cm->id))) {
    redirect('submissions.php?id='.$cm->id);
} else {
    redirect('view.php?id='.$cm->id);
}
