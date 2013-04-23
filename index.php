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
 * Lists all checkmarks in course.
 *
 * @package       mod_checkmark
 * @author        Philipp Hager
 * @copyright     2012 Philipp Hager
 * @since         Moodle 2.2
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once($CFG->dirroot . '/mod/checkmark/locallib.php');
require_once($CFG->libdir.'/gradelib.php');

$id = required_param('id', PARAM_INT);   // We need a course!

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('invalidcourseid');
}

require_course_login($course);
$PAGE->set_pagelayout('incourse');

add_to_log($course->id, 'checkmark', 'view all', 'index.php?id=$course->id', '');

$strcheckmarks = get_string('modulenameplural', 'checkmark');
$strcheckmark = get_string('modulename', 'checkmark');
$strsectionname  = get_string('sectionname', 'format_'.$course->format);
$strname = get_string('name');
$strduedate = get_string('duedate', 'checkmark');
$strsubmitted = get_string('submitted', 'checkmark');
$strgrade = get_string('grade');


$PAGE->set_url('/mod/checkmark/index.php', array('id'=>$course->id));
$PAGE->navbar->add($strcheckmarks);
$PAGE->set_title($strcheckmarks);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

if (!$cms = get_coursemodules_in_course('checkmark', $course->id, 'cm.idnumber, m.timedue')) {
    notice(get_string('nocheckmarks', 'checkmark'), '../../course/view.php?id=$course->id');
    die;
}

$usesections = course_format_uses_sections($course->format);
if ($usesections) {
    //tscpr:
        // get_all_sections is deprecated. you can replace it with get_fast_modinfo($course->id)->get_section_info_all();
    $sections = get_all_sections($course->id);
}

$timenow = time();

$table = new html_table();

if ($usesections) {
    $table->head  = array ($strsectionname, $strname, $strduedate, $strsubmitted, $strgrade);
} else {
    $table->head  = array ($strname, $strduedate, $strsubmitted, $strgrade);
}

$currentsection = '';

$modinfo = get_fast_modinfo($course);
foreach ($modinfo->instances['checkmark'] as $cm) {
    if (!$cm->uservisible) {
        continue;
    }

    $cm->timedue        = $cms[$cm->id]->timedue;
    $cm->idnumber       = $cms[$cm->id]->idnumber;

    // Show dimmed if the mod is hidden!
    $class = $cm->visible ? '' : 'dimmed';

    $link = html_writer::tag('a', format_string($cm->name),
                             array('href'=>'view.php?id='.$cm->id,
                                   'class'=>$class));

    $printsection = '';
    if ($usesections) {
        if ($cm->sectionnum !== $currentsection) {
            if ($cm->sectionnum) {
                $printsection = get_section_name($course, $sections[$cm->sectionnum]);
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $cm->sectionnum;
        }
    }

    //tscpr:
        //this is repeated for each mod instance, is it really needed here inside the loop?
    require_once($CFG->dirroot.'/mod/checkmark/lib.php');

    $checkmarkinstance = new checkmark($cm->id, null, $cm, $course);

    $submitted = $checkmarkinstance->submittedlink(true);

    $grading_info = grade_get_grades($course->id, 'mod', 'checkmark', $cm->instance, $USER->id);
    if (isset($grading_info->items[0]) && !$grading_info->items[0]->grades[$USER->id]->hidden ) {
        $grade = $grading_info->items[0]->grades[$USER->id]->str_grade;
    } else {
        $grade = '-';
    }


    $due = $cm->timedue ? userdate($cm->timedue) : '-';

    if ($usesections) {
        $table->data[] = array ($printsection, $link, $due, $submitted, $grade);
    } else {
        $table->data[] = array ($link, $due, $submitted, $grade);
    }
}

echo html_writer::empty_tag('br');

echo html_writer::table($table);

echo $OUTPUT->footer();
