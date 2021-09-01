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

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/mod/checkmark/externallib.php');

/**
 * External mod checkmark functions unit tests
 */
class mod_checkmark_external_testcase extends externallib_advanced_testcase {

    private $_course;
    private $_checkmark;

    /**
     * Test if the user only gets checkmarks for enrolled courses
     */
    public function test_get_checkmarks_by_courses() {
        global $CFG, $DB, $USER;

        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();

        $course1 = $this->getDataGenerator()->create_course([
            'fullname' => 'PHPUnitTestCourse1',
            'summary' => 'Test course for automated php unit tests',
            'summaryformat' => FORMAT_HTML
        ]);

        $this->getDataGenerator()->enrol_user($user->id, $course1->id);

        $course2 = $this->getDataGenerator()->create_course([
            'fullname' => 'PHPUnitTestCourse2',
            'summary' => 'Test course for automated php unit tests',
            'summaryformat' => FORMAT_HTML
        ]);

        $this->getDataGenerator()->enrol_user($user->id, $course2->id);

        $course3 = $this->getDataGenerator()->create_course([
            'fullname' => 'PHPUnitTestCourse3',
            'summary' => 'Test course for automated php unit tests',
            'summaryformat' => FORMAT_HTML
        ]);

        $checkmark1 = self::getDataGenerator()->create_module('checkmark', [
            'course' => $course1->id,
            'name' => 'Checkmark Module 1',
            'intro' => 'Checkmark module for automated php unit tests',
            'introformat' => FORMAT_HTML,
        ]);

        $checkmark2 = self::getDataGenerator()->create_module('checkmark', [
            'course' => $course2->id,
            'name' => 'Checkmark Module 2',
            'intro' => 'Checkmark module for automated php unit tests',
            'introformat' => FORMAT_HTML,
        ]);

        $checkmark3 = self::getDataGenerator()->create_module('checkmark', [
            'course' => $course3->id,
            'name' => 'Checkmark Module 3',
            'intro' => 'Checkmark module for automated php unit tests',
            'introformat' => FORMAT_HTML,
        ]);

        $this->setUser($user);

        $result = mod_checkmark_external::get_checkmarks_by_courses([]);

        // User is enrolled only in course1 and course2, so the third checkmark module in course3 should not be included.
        $this->assertEquals(2, count($result->checkmarks));
    }

    /**
     * Test if the user gets a valid checkmark from the endpoint
     */
    public function test_get_checkmark() {
        global $CFG, $DB, $USER;

        $result = $this->init_test_suite_one_course();

        // Checkmark name should be equal to 'Checkmark Module'.
        $this->assertEquals('Checkmark Module', $result->checkmark->name);

        // Course id in checkmark should be equal to the id of the course.
        $this->assertEquals($this->_course->id, $result->checkmark->course);
    }

    /**
     * Test if the user gets an exception when the checkmark is hidden in the course
     */
    public function test_get_checkmark_hidden() {
        global $CFG, $DB, $USER;

        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course([
            'fullname' => 'PHPUnitTestCourse',
            'summary' => 'Test course for automated php unit tests',
            'summaryformat' => FORMAT_HTML
        ]);

        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $checkmark = self::getDataGenerator()->create_module('checkmark', [
            'course' => $course->id,
            'name' => 'Hidden Checkmark Module',
            'intro' => 'Checkmark module for automated php unit tests',
            'introformat' => FORMAT_HTML,
            'visible' => 0,
        ]);

        $this->setUser($user);

        // Test should throw require_login_exception!
        $this->expectException(require_login_exception::class);

        $result = mod_checkmark_external::get_checkmark($checkmark->cmid);

    }

    /**
     * Test the submission of a checkmark module
     */
    public function test_get_submit() {
        global $CFG, $DB, $USER;

        $result = $this->init_test_suite_one_course();

        $submissionexamples = [];
        foreach ($result->checkmark->examples as $example) {
            $submissionexamples[] = ['id' => $example->id, 'checked' => $example->id % 2];
        }

        $result = mod_checkmark_external::submit($this->_checkmark->cmid, $submissionexamples);

        // Checkmark name should be equal to 'Checkmark Module'!
        $this->assertEquals('Checkmark Module', $result->checkmark->name);

        // Course id in checkmark should be equal to the id of the course!
        $this->assertEquals($this->_course->id, $result->checkmark->course);

        // Check the examples checked status of the result object!
        for ($i = 0; $i < 10; $i++) {
            $this->assertEquals($result->checkmark->examples[$i]->id % 2, $result->checkmark->examples[$i]->checked);
        }

        $result = mod_checkmark_external::get_checkmark($this->_checkmark->cmid);

        // Checkmark name should be equal to 'Checkmark Module'!
        $this->assertEquals('Checkmark Module', $result->checkmark->name);

        // Course id in checkmark should be equal to the id of the course!
        $this->assertEquals($this->_course->id, $result->checkmark->course);

        // Check the examples checked status was correctly saved!
        for ($i = 0; $i < 10; $i++) {
            $this->assertEquals($result->checkmark->examples[$i]->id % 2, $result->checkmark->examples[$i]->checked);
        }
    }

    /**
     * Test if the user gets an exception if the submission is already closed ('cutofdate' was yesterday)
     */
    public function test_get_submit_negative() {
        global $CFG, $DB, $USER;

        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course([
            'fullname' => 'PHPUnitTestCourse',
            'summary' => 'Test course for automated php unit tests',
            'summaryformat' => FORMAT_HTML
        ]);

        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $checkmark = self::getDataGenerator()->create_module('checkmark', [
            'course' => $course->id,
            'name' => 'Checkmark Module',
            'intro' => 'Checkmark module for automated php unit tests',
            'introformat' => FORMAT_HTML,
            'cutoffdate' => time() - 60 * 60 * 24 // Yesterday.
        ]);

        $this->setUser($user);

        $result = mod_checkmark_external::get_checkmark($checkmark->cmid);

        $submissionexamples = [];
        foreach ($result->checkmark->examples as $example) {
            $submissionexamples[] = ['id' => $example->id, 'checked' => $example->id % 2];
        }

        // Test should throw moodle_exception because the 'cutofdate' was yesterday.
        $this->expectException(moodle_exception::class);

        $result = mod_checkmark_external::submit($checkmark->cmid, $submissionexamples);

    }

    public function init_test_suite_one_course() {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course([
            'fullname' => 'PHPUnitTestCourse',
            'summary' => 'Test course for automated php unit tests',
            'summaryformat' => FORMAT_HTML
        ]);

        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $checkmark = self::getDataGenerator()->create_module('checkmark', [
            'course' => $course->id,
            'name' => 'Checkmark Module',
            'intro' => 'Checkmark module for automated php unit tests',
            'introformat' => FORMAT_HTML,
        ]);

        $this->setUser($user);

        $result = mod_checkmark_external::get_checkmark($checkmark->cmid);
        $this->_course = $course;
        $this->_checkmark = $checkmark;
        return $result;
    }
}