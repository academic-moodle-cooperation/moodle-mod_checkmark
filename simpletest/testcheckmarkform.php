<?php
/**
 * Unit tests for (some of) mod_checkmark's methods.
 *
 * @todo update to new test-framework!
 *
 * @package       mod_checkmark
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page!
}

// Make sure the code being tested is accessible.
require_once($CFG->dirroot . '/mod/checkmark/lib.php'); // Include the code to test!

/** This class contains the test cases for the formular validation. */
class checkmark_formvalidation_test extends UnitTestCase {
    public function test_countmismatch() {
        // Setup fixture!
        $checkmark = new checkmark();
        $data['examplegrades'] = '1,2,3';
        $data['examplenames'] = '1,2';
        $data['grade'] = '6';
        $data['flexiblenaming'] = 1;

        // Exercise SUT!
        $errors = $checkmark->form_validation($data, null);

        // Validate outcome!
        $a->namecount = 2;
        $a->gradecount = 3;
        $this->assertEqual($errors['examplenames'], get_string('count_individuals_mismatch',
                                                               'checkmark', $a));
        $this->assertEqual($errors['examplegrades'], get_string('count_individuals_mismatch',
                                                                'checkmark', $a));

        // Teardown fixture!
        $data = null;
        $checkmark = null;
    }

    public function test_summismatch() {
        // Setup fixture!
        $checkmark = new checkmark();
        $data['examplegrades'] = '1,2,3';
        $data['examplenames'] = '1,2,3';
        $data['grade'] = '5';
        $data['flexiblenaming'] = 1;

        // Exercise SUT!
        $errors = $checkmark->form_validation($data, null);

        // Validate outcome!
        $a->gradesum = 6;
        $a->maxgrade = $data['grade'];
        $this->assertEqual($errors['grade'], get_string('gradesum_mismatch', 'checkmark', $a));
        $this->assertEqual($errors['examplegrades'], get_string('gradesum_mismatch', 'checkmark',
                                                                $a));

        // Teardown fixture!
        $data = null;
        $checkmark = null;
    }

    public function test_both_errors() {
        // Setup fixture!
        $checkmark = new checkmark();
        $data['examplegrades'] = '1,2,3';
        $data['examplenames'] = '1,2';
        $data['grade'] = '5';
        $data['flexiblenaming'] = 1;

        // Exercise SUT!
        $errors = $checkmark->form_validation($data, null);

        // Validate outcome!
        $a->gradesum = 6;
        $a->maxgrade = $data['grade'];
        $a->gradecount = 3;
        $a->namecount = 2;
        $this->assertEqual($errors['grade'], get_string('gradesum_mismatch', 'checkmark', $a));
        $this->assertEqual($errors['examplenames'], get_string('count_individuals_mismatch',
                                                               'checkmark', $a));
        $this->assertEqual($errors['examplegrades'], get_string('count_individuals_mismatch',
                                                                'checkmark', $a).
                                                     html_writer::empty_tag('br').
                                                     get_string('gradesum_mismatch', 'checkmark',
                                                                $a));

        // Teardown fixture!
        $data = null;
        $checkmark = null;
    }

    public function test_noflexiblenaming() {
        // Setup fixture!
        $checkmark = new checkmark();
        $data['examplegrades'] = '1,2,3';
        $data['examplenames'] = '1,2';
        $data['grade'] = '5';
        $data['flexiblenaming'] = 0;

        // Exercise SUT!
        $errors = $checkmark->form_validation($data, null);

        // Validate outcome!
        $this->assertEqual(isset($errors['grade']), false);
        $this->assertEqual(isset($errors['examplenames']), false);
        $this->assertEqual(isset($errors['examplegrades']), false);

        // Teardown fixture!
        $data = null;
        $checkmark = null;
    }

    public function test_noerror() {
        // Setup fixture!
        $checkmark = new checkmark();
        $data['examplegrades'] = '1,2,3';
        $data['examplenames'] = '1,2,3';
        $data['grade'] = '6';
        $data['flexiblenaming'] = 1;

        // Exercise SUT!
        $errors = $checkmark->form_validation($data, null);

        // Validate outcome!
        $this->assertEqual(isset($errors['grade']), false);
        $this->assertEqual(isset($errors['examplenames']), false);
        $this->assertEqual(isset($errors['examplegrades']), false);

        // Teardown fixture!
        $data = null;
        $checkmark = null;
    }
}

