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
 * Tests for checkmark restore handling.
 *
 * @package     mod_checkmark
 * @copyright   2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_checkmark;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/mod/checkmark/backup/moodle2/restore_checkmark_stepslib.php');
require_once($CFG->dirroot . '/mod/checkmark/tests/fixtures/testable_restore_checkmark_activity_structure_step.php');
require_once($CFG->dirroot . '/mod/checkmark/lib.php');

/**
 * Tests for checkmark restore handling.
 *
 * @package     mod_checkmark
 * @copyright   2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\restore_checkmark_activity_structure_step::class)]
final class restore_test extends \advanced_testcase {
    /**
     * Ensure legacy backups without explicit presentation status restore saved presentation grading as done.
     */
    public function test_restore_infers_legacy_presentation_status(): void {
        global $DB, $USER;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $checkmark = $generator->create_module('checkmark', [
            'course' => $course->id,
            'presentationgrading' => 1,
            'presentationgrade' => 100,
        ]);

        $gradedstudent = $generator->create_user();
        $feedbackstudent = $generator->create_user();
        $whitespacestudent = $generator->create_user();
        $nogradestudent = $generator->create_user();
        $emptystudent = $generator->create_user();
        $markedstudent = $generator->create_user();

        $restorestep = new testable_restore_checkmark_activity_structure_step($checkmark->id);
        $restorestep->restore_feedback($this->get_restore_feedback_data($gradedstudent->id, $USER->id, [
            'presentationgrade' => 0,
        ]));
        $restorestep->restore_feedback($this->get_restore_feedback_data($feedbackstudent->id, $USER->id, [
            'presentationgrade' => null,
            'presentationfeedback' => 'Existing presentation feedback',
        ]));
        $restorestep->restore_feedback($this->get_restore_feedback_data($whitespacestudent->id, $USER->id, [
            'presentationgrade' => null,
            'presentationfeedback' => '   ',
        ]));
        $restorestep->restore_feedback($this->get_restore_feedback_data($nogradestudent->id, $USER->id, [
            'presentationgrade' => -1,
        ]));
        $restorestep->restore_feedback($this->get_restore_feedback_data($emptystudent->id, $USER->id, [
            'presentationgrade' => null,
            'presentationfeedback' => '',
        ]));
        $restorestep->restore_feedback($this->get_restore_feedback_data($markedstudent->id, $USER->id, [
            'presentationstatus' => CHECKMARK_PRESENTATION_STATUS_MARKED,
            'presentationgrade' => 75,
        ]));

        $gradedfeedback = $DB->get_record('checkmark_feedbacks', [
            'checkmarkid' => $checkmark->id,
            'userid' => $gradedstudent->id,
        ], '*', MUST_EXIST);
        $feedbackonly = $DB->get_record('checkmark_feedbacks', [
            'checkmarkid' => $checkmark->id,
            'userid' => $feedbackstudent->id,
        ], '*', MUST_EXIST);
        $whitespacefeedback = $DB->get_record('checkmark_feedbacks', [
            'checkmarkid' => $checkmark->id,
            'userid' => $whitespacestudent->id,
        ], '*', MUST_EXIST);
        $nogradefeedback = $DB->get_record('checkmark_feedbacks', [
            'checkmarkid' => $checkmark->id,
            'userid' => $nogradestudent->id,
        ], '*', MUST_EXIST);
        $emptyfeedback = $DB->get_record('checkmark_feedbacks', [
            'checkmarkid' => $checkmark->id,
            'userid' => $emptystudent->id,
        ], '*', MUST_EXIST);
        $markedfeedback = $DB->get_record('checkmark_feedbacks', [
            'checkmarkid' => $checkmark->id,
            'userid' => $markedstudent->id,
        ], '*', MUST_EXIST);

        $this->assertEquals(CHECKMARK_PRESENTATION_STATUS_YES, $gradedfeedback->presentationstatus);
        $this->assertEquals(CHECKMARK_PRESENTATION_STATUS_YES, $feedbackonly->presentationstatus);
        $this->assertEquals(CHECKMARK_PRESENTATION_STATUS_NO, $whitespacefeedback->presentationstatus);
        $this->assertEquals(CHECKMARK_PRESENTATION_STATUS_NO, $nogradefeedback->presentationstatus);
        $this->assertEquals(CHECKMARK_PRESENTATION_STATUS_NO, $emptyfeedback->presentationstatus);
        $this->assertEquals(CHECKMARK_PRESENTATION_STATUS_MARKED, $markedfeedback->presentationstatus);
    }

    /**
     * Get restore feedback data.
     *
     * @param int $userid User id.
     * @param int $graderid Grader id.
     * @param array $overrides Record overrides.
     * @return object Restore feedback data.
     */
    private function get_restore_feedback_data(int $userid, int $graderid, array $overrides = []): object {
        static $id = 1;

        return (object) array_merge([
            'id' => $id++,
            'userid' => $userid,
            'grade' => 50,
            'feedback' => 'Existing grade feedback',
            'format' => FORMAT_HTML,
            'attendance' => null,
            'presentationgrade' => null,
            'presentationfeedback' => null,
            'presentationformat' => FORMAT_HTML,
            'graderid' => $graderid,
            'mailed' => 1,
            'timecreated' => 100000000,
            'timemodified' => 111111111,
            'gradetimemodified' => 111111111,
            'presentationtimemodified' => 222222222,
        ], $overrides);
    }
}
