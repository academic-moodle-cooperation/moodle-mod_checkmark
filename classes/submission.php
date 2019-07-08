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
 * @author    Philipp Hager
 * @copyright 2018 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_checkmark;

defined('MOODLE_INTERNAL') || die();

/**
 * This class contains a submission definition
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2018 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
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

    /**
     * submission constructor.
     *
     * @param int $id
     * @param null $submission
     * @throws \dml_exception
     */
    public function __construct($id = 0, $submission=null) {
        global $DB;

        $this->id = $id;

        if (!empty($this->id)) {
            if ($submission === null) {
                $submission = $DB->get_record('checkmark_submission', ['id' => $this->id]);
            }
            $this->checkmarkid = $submission->checkmarkid;
            $this->userid = $submission->userid;
            $this->timecreated = $submission->timecreated;
            $this->timemodified = $submission->timemodified;

            $this->examples = \checkmark::get_examples_static($this->checkmarkid);

            if ($submission) {
                if (!$submission->examples = $DB->get_records_sql('
                    SELECT exampleid AS id, state
                      FROM {checkmark_checks}
                     WHERE submissionid = :subid', ['subid' => $submission->id])) {
                    // Empty submission!
                    foreach ($this->examples as $key => $example) {
                        $submission->examples[$key] = $this->examples[$key];
                        $DB->insert_record('checkmark_checks', (object)['exampleid'    => $key,
                                                                        'submissionid' => $submission->id,
                                                                        'state'        => null]);
                    }
                } else {
                    foreach ($submission->examples as $key => $ex) {
                        $this->examples[$ex->id]->set_state($ex->state);
                    }
                    $submission->examples = $this->examples;
                }
            }
        }
    }

    /**
     * @param $checkmarkid
     * @param $userid
     * @return false|self
     * @throws \dml_exception
     */
    public static function get_submission($checkmarkid, $userid) {
        global $DB;

        if (!$submission = $DB->get_record('checkmark_submissions', ['checkmarkid' => $checkmarkid,
                                                                'userid'      => $userid])) {
            return false;
        }

        return new self($submission->id, $submission);
    }

    /**
     * @param $name
     * @return false|example
     */
    public function get_example($name) {
        if (!key_exists($name, $this->examples)) {
            return false;
        }

        return $this->examples[$name];
    }

    /**
     * @return example[]
     */
    public function get_examples() {
        return $this->examples;
    }

    /**
     * @return int
     */
    public function get_timemodified() {
        return $this->timemodified;
    }

    /**
     * @return int
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * @return int
     */
    public function get_userid() {
        return $this->userid;
    }

    /**
     * @return string
     */
    public function render() {
        global $OUTPUT;

        $context = clone $this;
        $context->examples = array_values($context->examples);

        return $OUTPUT->render_from_template('mod_checkmark/submission', $context);
    }
}
