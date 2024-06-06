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
 * Unit tests for (some of) mod_checkmark's methods.
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page!
}

// Make sure the code being tested is accessible.
global $CFG;
require_once($CFG->dirroot . '/mod/checkmark/locallib.php'); // Include the code to test!

/**
 * This class contains the test cases for the formular validation.
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class formvalidation_test extends basic_testcase {
    /**
     * Tests if there is an proper error for different amounts of examples and example-gradesum
     */
    public function test_countmismatch() {
        // Setup fixture!
        $checkmark = new checkmark();
        $data['examplegrades'] = '1,2,3';
        $data['examplenames'] = '1,2';
        $data['grade'] = '6';
        $data['flexiblenaming'] = 1;
        $data['allready_submit'] = false;

        // Exercise SUT!
        $errors = $checkmark->form_validation($data);

        // Validate outcome!
        $a = new stdClass();
        $a->namecount = 2;
        $a->gradecount = 3;
        $this->assertEquals($errors['examplenames'], get_string('count_individuals_mismatch', 'checkmark', $a));
        $this->assertEquals($errors['examplegrades'], get_string('count_individuals_mismatch', 'checkmark', $a));

        // Teardown fixture!
        $data = null;
        $checkmark = null;
    }

    /**
     * Tests if there is an proper error for when example-gradesum differs from gradesum in instance
     */
    public function test_summismatch() {
        // Setup fixture!
        $checkmark = new checkmark();
        $data['examplegrades'] = '1,2,3';
        $data['examplenames'] = '1,2,3';
        $data['grade'] = '5';
        $data['flexiblenaming'] = 1;
        $data['allready_submit'] = false;

        // Exercise SUT!
        $errors = $checkmark->form_validation($data);

        // Validate outcome!
        $a = new stdClass();
        $a->gradesum = 6;
        $a->maxgrade = $data['grade'];
        $this->assertEquals($errors['grade'], get_string('gradesum_mismatch', 'checkmark', $a));
        $this->assertEquals($errors['examplegrades'], get_string('gradesum_mismatch', 'checkmark', $a));

        // Teardown fixture!
        $data = null;
        $checkmark = null;
    }

    /**
     * Tests if both errors together will be displayed correctly
     */
    public function test_both_errors() {
        // Setup fixture!
        $checkmark = new checkmark();
        $data['examplegrades'] = '1,2,3';
        $data['examplenames'] = '1,2';
        $data['grade'] = '5';
        $data['flexiblenaming'] = 1;
        $data['allready_submit'] = false;

        // Exercise SUT!
        $errors = $checkmark->form_validation($data);

        // Validate outcome!
        $a = new stdClass();
        $a->gradesum = 6;
        $a->maxgrade = $data['grade'];
        $a->gradecount = 3;
        $a->namecount = 2;
        $this->assertEquals($errors['grade'], get_string('gradesum_mismatch', 'checkmark', $a));
        $this->assertEquals($errors['examplenames'], get_string('count_individuals_mismatch', 'checkmark', $a));
        $this->assertEquals($errors['examplegrades'], get_string('count_individuals_mismatch', 'checkmark', $a).
                                                      html_writer::empty_tag('br').
                                                      get_string('gradesum_mismatch', 'checkmark', $a));

        // Teardown fixture!
        $data = null;
        $checkmark = null;
    }

    /**
     * Tests if there are no errors if flexible naming is deactivated and amount and sum mismatch
     */
    public function test_noflexiblenaming() {
        // Setup fixture!
        $checkmark = new checkmark();
        $data['examplecount'] = '5';
        $data['examplestart'] = '1';
        $data['examplegrades'] = '1,2,3';
        $data['examplenames'] = '1,2';
        $data['grade'] = '5';
        $data['flexiblenaming'] = 0;
        $data['allready_submit'] = false;

        // Exercise SUT!
        $errors = $checkmark->form_validation($data);

        // Validate outcome!
        $this->assertEquals(isset($errors['grade']), false);
        $this->assertEquals(isset($errors['examplenames']), false);
        $this->assertEquals(isset($errors['examplegrades']), false);

        // Teardown fixture!
        $data = null;
        $checkmark = null;
    }

    /**
     * Tests if no error will be wrongly displayed if everythings correct
     */
    public function test_noerror() {
        // Setup fixture!
        $checkmark = new checkmark();
        $data['examplegrades'] = '1,2,3';
        $data['examplenames'] = '1,2,3';
        $data['grade'] = '6';
        $data['flexiblenaming'] = 1;
        $data['allready_submit'] = false;

        // Exercise SUT!
        $errors = $checkmark->form_validation($data);

        // Validate outcome!
        $this->assertEquals(isset($errors['grade']), false);
        $this->assertEquals(isset($errors['examplenames']), false);
        $this->assertEquals(isset($errors['examplegrades']), false);

        // Teardown fixture!
        $data = null;
        $checkmark = null;
    }
}
