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
require_once($CFG->dirroot . '/mod/checkmark/locallib.php');

/**
 * Unit tests for locallib.php
 *
 * @package     mod_checkmark
 * @author      Clemens Marx
 * @copyright   2025 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class locallib_test extends \advanced_testcase {
    /**
     * Ensure defaults are returned when no preferences are set and no form submission occurs.
     */
    public function test_print_preferences_defaults(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Ensure request is clean.
        $oldpost = $_POST;
        $oldget = $_GET;
        $_POST = [];
        $_GET = [];

        // Ensure no leftovers from other tests in user preferences.
        unset_user_preference('checkmark_filter_export');
        unset_user_preference('checkmark_sumabs');
        unset_user_preference('checkmark_sumrel');
        unset_user_preference('checkmark_seperatenamecolumns');
        unset_user_preference('checkmark_format');
        unset_user_preference('checkmark_coursetitle');
        unset_user_preference('checkmark_exporttemplate');
        unset_user_preference('checkmark_pdfprintperpage');
        unset_user_preference('checkmark_textsize');
        unset_user_preference('checkmark_pageorientation');
        unset_user_preference('checkmark_printheader');
        unset_user_preference('checkmark_forcesinglelinenames');
        unset_user_preference('checkmark_sequentialnumbering');
        unset_user_preference('checkmark_zipped');

        $instance = new \checkmark('staticonly');

        [
            $filter,
            $sumabs,
            $sumrel,
            $seperatenamecolumns,
            $format,
            $printperpage,
            $printoptimum,
            $textsize,
            $pageorientation,
            $printheader,
            $forcesinglelinenames,
            $zipped,
            $sequentialnumbering,
            $coursetitle,
            $template
        ] = $instance->print_preferences();

        $this->assertEquals(\checkmark::FILTER_ALL, $filter);
        $this->assertEquals(1, $sumabs);
        $this->assertEquals(1, $sumrel);
        $this->assertEquals(0, $seperatenamecolumns);
        $this->assertEquals(\mod_checkmark\MTablePDF::OUTPUT_FORMAT_PDF, $format);
        $this->assertEquals(0, $printperpage);
        $this->assertEquals(1, $printoptimum, 'When printperpage=0, printoptimum should default to 1');
        $this->assertEquals(\mod_checkmark\MTablePDF::FONTSIZE_SMALL, $textsize);
        $this->assertEquals(\mod_checkmark\MTablePDF::LANDSCAPE, $pageorientation);
        $this->assertEquals(1, $printheader);
        $this->assertEquals(0, $forcesinglelinenames);
        $this->assertEquals(\mod_checkmark\MTablePDF::UNCOMPRESSED, $zipped);
        $this->assertEquals(0, $sequentialnumbering);
        $this->assertSame('', $coursetitle);
        $this->assertSame('', $template);

        // Restore globals.
        $_POST = $oldpost;
        $_GET = $oldget;
    }

    /**
     * Ensure submitted preferences are written and returned when updatepref and sesskey are valid (PDF format path).
     */
    public function test_print_preferences_update_pref_pdf(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $oldpost = $_POST;
        $oldget = $_GET;
        $_POST = [];
        $_GET = [];

        // Simulate form submission with valid sesskey.
        $_POST['updatepref'] = 1;
        $_POST['sesskey'] = sesskey();
        $_POST['datafilter'] = \checkmark::FILTER_ALL; // Keep simple.
        $_POST['format'] = \mod_checkmark\MTablePDF::OUTPUT_FORMAT_PDF;
        $_POST['sumabs'] = 0;
        $_POST['sumrel'] = 0;
        $_POST['seperatenamecolumns'] = 1;
        $_POST['coursetitle'] = 'Course 101';
        $_POST['template'] = 'MyTemplate';
        $_POST['printperpage'] = 42; // Explicit per-page value.
        $_POST['printoptimum'] = 0; // So printperpage stays 42.
        $_POST['textsize'] = \mod_checkmark\MTablePDF::FONTSIZE_SMALL;
        $_POST['pageorientation'] = \mod_checkmark\MTablePDF::LANDSCAPE;
        $_POST['printheader'] = 0;
        $_POST['forcesinglelinenames'] = 1;
        $_POST['sequentialnumbering'] = 1;
        $_POST['zipped'] = \mod_checkmark\MTablePDF::UNCOMPRESSED;

        $instance = new \checkmark('staticonly');

        [
            $filter,
            $sumabs,
            $sumrel,
            $seperatenamecolumns,
            $format,
            $printperpage,
            $printoptimum,
            $textsize,
            $pageorientation,
            $printheader,
            $forcesinglelinenames,
            $zipped,
            $sequentialnumbering,
            $coursetitle,
            $template
        ] = $instance->print_preferences();

        $this->assertEquals(\checkmark::FILTER_ALL, $filter);
        $this->assertEquals(0, $sumabs);
        $this->assertEquals(0, $sumrel);
        $this->assertEquals(1, $seperatenamecolumns);
        $this->assertEquals(\mod_checkmark\MTablePDF::OUTPUT_FORMAT_PDF, $format);
        $this->assertEquals(42, $printperpage);
        $this->assertEquals(0, $printoptimum);
        $this->assertEquals(\mod_checkmark\MTablePDF::FONTSIZE_SMALL, $textsize);
        $this->assertEquals(\mod_checkmark\MTablePDF::LANDSCAPE, $pageorientation);
        $this->assertEquals(0, $printheader);
        $this->assertEquals(1, $forcesinglelinenames);
        $this->assertEquals(\mod_checkmark\MTablePDF::UNCOMPRESSED, $zipped);
        $this->assertEquals(1, $sequentialnumbering);
        $this->assertSame('Course 101', $coursetitle);
        $this->assertSame('MyTemplate', $template);

        // Restore globals.
        $_POST = $oldpost;
        $_GET = $oldget;
    }

    /**
     * Ensure presentation modification time is only updated when presentation fields change.
     */
    public function test_process_feedback_tracks_presentation_modified_time(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $checkmark = $this->getDataGenerator()->create_module('checkmark', [
            'course' => $course->id,
            'presentationgrading' => 1,
            'presentationgrade' => 100,
            'presentationgradebook' => 0,
        ]);
        $instance = new \checkmark($checkmark->cmid);

        $oldpost = $_POST;
        $oldget = $_GET;
        $_GET = [];

        $this->submit_feedback_form($instance, $student->id, 25, 'Initial presentation feedback', 10);

        $feedback = $DB->get_record('checkmark_feedbacks', [
            'checkmarkid' => $checkmark->id,
            'userid' => $student->id,
        ], '*', MUST_EXIST);
        $this->assertGreaterThan(0, $feedback->presentationtimemodified);

        $fixedtime = 123456789;
        $feedback->presentationtimemodified = $fixedtime;
        $feedback->timemodified = $fixedtime;
        $DB->update_record('checkmark_feedbacks', $feedback);

        $this->submit_feedback_form($instance, $student->id, 25, 'Initial presentation feedback', 20);

        $feedback = $DB->get_record('checkmark_feedbacks', ['id' => $feedback->id], '*', MUST_EXIST);
        $this->assertEquals($fixedtime, $feedback->presentationtimemodified);
        $this->assertGreaterThan($fixedtime, $feedback->timemodified);

        $this->submit_feedback_form($instance, $student->id, 30, 'Updated presentation feedback', 20);

        $feedback = $DB->get_record('checkmark_feedbacks', ['id' => $feedback->id], '*', MUST_EXIST);
        $this->assertGreaterThan($fixedtime, $feedback->presentationtimemodified);

        $_POST = $oldpost;
        $_GET = $oldget;
    }

    /**
     * Submit feedback form data to process_feedback().
     *
     * @param \checkmark $instance Checkmark instance.
     * @param int $userid User id.
     * @param int $presentationgrade Presentation grade.
     * @param string $presentationfeedback Presentation feedback text.
     * @param int $grade Submission grade.
     */
    private function submit_feedback_form(
        \checkmark $instance,
        int $userid,
        int $presentationgrade,
        string $presentationfeedback,
        int $grade
    ): void {
        $_POST = [
            'sesskey' => sesskey(),
            'saveuserid' => -1,
            'userid' => $userid,
            'mailinfo' => 0,
            'xgrade' => $grade,
            'feedback_editor' => [
                'text' => '',
                'format' => FORMAT_HTML,
            ],
            'presentationgrade' => $presentationgrade,
            'presentationfeedback_editor' => [
                'text' => $presentationfeedback,
                'format' => FORMAT_HTML,
            ],
        ];

        $instance->process_feedback();
    }
}
