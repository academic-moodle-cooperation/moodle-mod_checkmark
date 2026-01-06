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

namespace mod_checkmark;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

/**
 * Table to display detailed information about overwritten grades/feedback.
 *
 * @package   mod_checkmark
 * @author    Clemens Marx
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overwrite_confirmation_table extends \table_sql {
    /** @var \checkmark The checkmark instance */
    protected $checkmark;

    /** @var \context The context of the module */
    protected $context;

    /**
     * Constructor.
     *
     * @param string $uniqueid Unique ID for the table
     * @param \checkmark $checkmark The checkmark instance
     */
    public function __construct($uniqueid, \checkmark $checkmark) {
        parent::__construct($uniqueid);
        $this->checkmark = $checkmark;
        $this->context = \context_module::instance($checkmark->cm->id);
    }

    /**
     * Formatting for the fullname column.
     *
     * @param \stdClass $row The row data
     * @return string The formatted output
     */
    public function col_fullname($row) {
        global $OUTPUT;

        // We assume $row contains user fields.
        $profileurl = new \moodle_url('/user/view.php', ['id' => $row->id, 'course' => $this->checkmark->course->id]);
        $o = $OUTPUT->user_picture($row, ['courseid' => $this->checkmark->course->id, 'link' => true])
            . ' ' . \html_writer::link($profileurl, fullname($row));
        return $o;
    }

    /**
     * Formatting for the grade column.
     *
     * @param \stdClass $row The row data
     * @return string The formatted output
     */
    public function col_grade($row) {
        return $this->checkmark->display_grade($row->grade);
    }

    /**
     * Formatting for the feedback column.
     *
     * @param \stdClass $row The row data
     * @return string The formatted output
     */
    public function col_feedback($row) {
        return format_text($row->feedback, $row->feedbackformat, ['context' => $this->context]);
    }

    /**
     * Formatting for the grade modified column.
     *
     * @param \stdClass $row The row data
     * @return string The formatted output
     */
    public function col_grademodified($row) {
        if (empty($row->grademodified)) {
            return '-';
        }
        return userdate($row->grademodified);
    }
}
