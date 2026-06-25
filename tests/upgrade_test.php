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

namespace mod_checkmark;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/upgradelib.php');
require_once($CFG->dirroot . '/mod/checkmark/db/upgrade.php');
require_once($CFG->dirroot . '/mod/checkmark/lib.php');

/**
 * Tests for checkmark database upgrades.
 *
 * @package     mod_checkmark
 * @copyright   2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class upgrade_test extends \advanced_testcase {
    /**
     * Ensure fresh installations contain the fields added by the combined upgrade step.
     */
    public function test_feedback_schema_contains_separate_modified_fields(): void {
        global $DB;

        $columns = $DB->get_columns('checkmark_feedbacks');

        $this->assertArrayHasKey('presentationstatus', $columns);
        $this->assertTrue($columns['presentationstatus']->not_null);
        $this->assertEquals(0, $columns['presentationstatus']->default_value);

        $this->assertArrayHasKey('gradetimemodified', $columns);
        $this->assertTrue($columns['gradetimemodified']->not_null);
        $this->assertEquals(0, $columns['gradetimemodified']->default_value);
    }

    /**
     * Ensure the upgrade copies legacy modification times without changing other feedback data.
     */
    public function test_upgrade_backfills_grade_modified_time(): void {
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

        $firststudent = $generator->create_user();
        $secondstudent = $generator->create_user();
        $firstid = $this->create_legacy_feedback(
            $checkmark->id,
            $firststudent->id,
            $USER->id,
            111111111,
            CHECKMARK_PRESENTATION_STATUS_MARKED
        );
        $secondid = $this->create_legacy_feedback(
            $checkmark->id,
            $secondstudent->id,
            $USER->id,
            222222222,
            CHECKMARK_PRESENTATION_STATUS_YES
        );

        set_config('version', 2026061400, 'mod_checkmark');

        $this->assertTrue(\xmldb_checkmark_upgrade(2026061400));

        $firstfeedback = $DB->get_record('checkmark_feedbacks', ['id' => $firstid], '*', MUST_EXIST);
        $secondfeedback = $DB->get_record('checkmark_feedbacks', ['id' => $secondid], '*', MUST_EXIST);

        $this->assertEquals(111111111, $firstfeedback->timemodified);
        $this->assertEquals(111111111, $firstfeedback->gradetimemodified);
        $this->assertEquals(CHECKMARK_PRESENTATION_STATUS_MARKED, $firstfeedback->presentationstatus);
        $this->assertEquals(333333333, $firstfeedback->presentationtimemodified);
        $this->assertEquals(50, $firstfeedback->grade);
        $this->assertSame('Existing grade feedback', $firstfeedback->feedback);

        $this->assertEquals(222222222, $secondfeedback->timemodified);
        $this->assertEquals(222222222, $secondfeedback->gradetimemodified);
        $this->assertEquals(CHECKMARK_PRESENTATION_STATUS_YES, $secondfeedback->presentationstatus);
        $this->assertEquals(333333333, $secondfeedback->presentationtimemodified);
    }

    /**
     * Ensure legacy presentation grading data gets an explicit done status.
     */
    public function test_upgrade_backfills_legacy_presentation_status(): void {
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

        $gradedid = $this->create_legacy_feedback(
            $checkmark->id,
            $gradedstudent->id,
            $USER->id,
            111111111,
            CHECKMARK_PRESENTATION_STATUS_NO,
            0,
            null
        );
        $feedbackid = $this->create_legacy_feedback(
            $checkmark->id,
            $feedbackstudent->id,
            $USER->id,
            222222222,
            CHECKMARK_PRESENTATION_STATUS_NO,
            null,
            'Existing presentation feedback'
        );
        $whitespaceid = $this->create_legacy_feedback(
            $checkmark->id,
            $whitespacestudent->id,
            $USER->id,
            333333333,
            CHECKMARK_PRESENTATION_STATUS_NO,
            null,
            '   '
        );
        $nogradeid = $this->create_legacy_feedback(
            $checkmark->id,
            $nogradestudent->id,
            $USER->id,
            444444444,
            CHECKMARK_PRESENTATION_STATUS_NO,
            -1,
            null
        );
        $emptyid = $this->create_legacy_feedback(
            $checkmark->id,
            $emptystudent->id,
            $USER->id,
            555555555,
            CHECKMARK_PRESENTATION_STATUS_NO,
            null,
            ''
        );
        $markedid = $this->create_legacy_feedback(
            $checkmark->id,
            $markedstudent->id,
            $USER->id,
            666666666,
            CHECKMARK_PRESENTATION_STATUS_MARKED,
            75,
            null
        );

        set_config('version', 2026062200, 'mod_checkmark');

        $this->assertTrue(\xmldb_checkmark_upgrade(2026062200));

        $gradedfeedback = $DB->get_record('checkmark_feedbacks', ['id' => $gradedid], '*', MUST_EXIST);
        $feedbackonly = $DB->get_record('checkmark_feedbacks', ['id' => $feedbackid], '*', MUST_EXIST);
        $whitespacefeedback = $DB->get_record('checkmark_feedbacks', ['id' => $whitespaceid], '*', MUST_EXIST);
        $nogradefeedback = $DB->get_record('checkmark_feedbacks', ['id' => $nogradeid], '*', MUST_EXIST);
        $emptyfeedback = $DB->get_record('checkmark_feedbacks', ['id' => $emptyid], '*', MUST_EXIST);
        $markedfeedback = $DB->get_record('checkmark_feedbacks', ['id' => $markedid], '*', MUST_EXIST);

        $this->assertEquals(CHECKMARK_PRESENTATION_STATUS_YES, $gradedfeedback->presentationstatus);
        $this->assertEquals(CHECKMARK_PRESENTATION_STATUS_YES, $feedbackonly->presentationstatus);
        $this->assertEquals(CHECKMARK_PRESENTATION_STATUS_NO, $whitespacefeedback->presentationstatus);
        $this->assertEquals(CHECKMARK_PRESENTATION_STATUS_NO, $nogradefeedback->presentationstatus);
        $this->assertEquals(CHECKMARK_PRESENTATION_STATUS_NO, $emptyfeedback->presentationstatus);
        $this->assertEquals(CHECKMARK_PRESENTATION_STATUS_MARKED, $markedfeedback->presentationstatus);
    }

    /**
     * Create feedback as it would exist before gradetimemodified was populated.
     *
     * @param int $checkmarkid Checkmark instance id.
     * @param int $userid Student id.
     * @param int $graderid Grader id.
     * @param int $timemodified Legacy modification time.
     * @param int $presentationstatus Presentation status.
     * @param int|null $presentationgrade Presentation grade.
     * @param string|null $presentationfeedback Presentation feedback.
     * @return int Feedback record id.
     */
    private function create_legacy_feedback(
        int $checkmarkid,
        int $userid,
        int $graderid,
        int $timemodified,
        int $presentationstatus,
        ?int $presentationgrade = 75,
        ?string $presentationfeedback = 'Existing presentation feedback'
    ): int {
        global $DB;

        return $DB->insert_record('checkmark_feedbacks', (object) [
            'checkmarkid' => $checkmarkid,
            'userid' => $userid,
            'grade' => 50,
            'feedback' => 'Existing grade feedback',
            'format' => FORMAT_HTML,
            'attendance' => null,
            'presentationstatus' => $presentationstatus,
            'presentationgrade' => $presentationgrade,
            'presentationfeedback' => $presentationfeedback,
            'presentationformat' => FORMAT_HTML,
            'graderid' => $graderid,
            'mailed' => 1,
            'timecreated' => 100000000,
            'timemodified' => $timemodified,
            'gradetimemodified' => 0,
            'presentationtimemodified' => 333333333,
        ]);
    }
}
