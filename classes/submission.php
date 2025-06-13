<?php
// This file is part of mtablepdf for Moodle - http://moodle.org/
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
 * submission.php
 *
 * @package   mod_checkmark
 * @author    Philipp Hager, extended and maintained by Daniel Binder
 * @copyright 2019 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_checkmark;

/**
 * This class contains a submission definition
 *
 * @package   mod_checkmark
 * @author    Philipp Hager, extended and maintained by Daniel Binder
 * @copyright 2019 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submission {
    /** @var int */
    public $id = 0;
    /** @var int */
    public $checkmarkid = 0;
    /** @var int */
    public $userid = null;
    /** @var example[] */
    public $examples = [];
    /** @var int */
    public $timecreated = null;
    /** @var int */
    public $timemodified = null;
    /** @var bool */
    public $showcolumns = false;

    /**
     * submission constructor.
     *
     * @param int $id
     * @param null $submission
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function __construct($id = 0, $submission = null) {
        global $DB;

        $this->id = $id;

        if (!empty($this->id)) {
            if ($submission === null) {
                $submission = $DB->get_record('checkmark_submissions', ['id' => $this->id]);
            }
            $this->checkmarkid = $submission->checkmarkid;
            $this->userid = $submission->userid;
            $this->timecreated = $submission->timecreated;
            $this->timemodified = $submission->timemodified;

            $this->examples = \checkmark::get_examples_static($this->checkmarkid);

            if ($submission) {
                if ($submission->examples = $DB->get_records_sql('
                    SELECT exampleid AS id, state
                      FROM {checkmark_checks}
                     WHERE submissionid = :subid', ['subid' => $submission->id])) {
                    foreach ($submission->examples as $key => $ex) {
                        $this->examples[$ex->id]->set_state($ex->state);
                    }
                    $submission->examples = $this->examples;
                }
            }
        }
    }

    /**
     * Returns a single submission for a given $checkmarkid and $userid
     *
     * @param int $checkmarkid
     * @param int $userid
     *
     * @return false|self
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_submission($checkmarkid, $userid) {
        global $DB;

        if (!$submission = $DB->get_record('checkmark_submissions', ['checkmarkid' => $checkmarkid, 'userid' => $userid])) {
            return false;
        }

        return new self($submission->id, $submission);
    }

    /**
     * Creates an empty submission for a given $checkmarkid and $userid using the current date
     *
     * @param int $checkmarkid
     * @param int $userid
     *
     * @return submission
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_mock_submission($checkmarkid, $userid = null) {
        $submission = new Submission(0, 0);
        $submission->checkmarkid = $checkmarkid;
        if (isset($userid)) {
            $submission->userid = $userid;
        }
        $submission->timecreated = time();
        $submission->timemodified = $submission->timecreated;
        return $submission;
    }

    /**
     * Returns the present object of submission as stdClass
     *
     * @return \stdClass
     */
    public function export_for_snapshot() {
        $record = new \stdClass;
        $record->id = $this->id;
        $record->checkmarkid = $this->checkmarkid;
        $record->userid = $this->userid;
        $record->examples = $this->examples;
        $record->timecreated = $this->timecreated;
        $record->timemodified = $this->timemodified;
        return $record;
    }

    /**
     * Gets the example for a given $name or false if none was found
     *
     * @param string $name
     * @return false|example
     */
    public function get_example($name) {
        if (!key_exists($name, $this->examples)) {
            return false;
        }

        return $this->examples[$name];
    }


    /**
     * Gets all examples from the present submission
     *
     * @return example[]
     */
    public function get_examples() {
        return $this->examples;
    }

    /**
     * Gets all examples from the present submission or unchecked examples if there are no examples present in the submission yet
     *
     * @return example[]
     * @throws \dml_exception
     */
    public function get_examples_or_example_template() {
        if (empty($this->examples)) {
            return \checkmark::get_examples_static($this->checkmarkid);
        }
        return $this->examples;
    }

    /**
     * Gets the most recent modification time of the present example
     *
     * @return int
     */
    public function get_timemodified() {
        return $this->timemodified;
    }

    /**
     * Returns the id of the present submission
     *
     * @return int
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Returns the userid of the present submission
     *
     * @return int
     */
    public function get_userid() {
        return $this->userid;
    }

    /**
     * Returns the Id of the respective Checkmark exercise
     *
     * @return int Id of the respective Checkmark exercise
     */
    public function get_checkmarkid() {
        return $this->checkmarkid;
    }

    /**
     * Returns html output for displaying all examples of the present submission
     *
     * @return string
     * @throws \dml_exception
     */
    public function render() {
        global $OUTPUT;

        $context = clone $this;
        $context->examples = array_values($this->get_examples_or_example_template());
        $context->showcolumns = count($context->examples) > MAXCHECKMARKS_FOR_ONECOLUMN;

        return $OUTPUT->render_from_template('mod_checkmark/submission', $context);
    }
}
