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
 * Extension of activity_custom_completion for enabling activity completion on checkmark submit
 *
 * @package   mod_checkmark
 * @author    Daniel Binder, based on the work of Simey Lameze
 * @copyright 2021 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_checkmark\completion;

use core_completion\activity_custom_completion;

/**
 * Extension of activity_custom_completion for enabling activity completion on checkmark submit
 *
 * @package   mod_checkmark
 * @author    Daniel Binder, based on the work of Simey Lameze
 * @copyright 2021 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {

    /**
     * Fetches the completion state for a given completion rule.
     *
     * @param string $rule The completion rule.
     * @return int The completion state.
     */
    public function get_state(string $rule): int {
        global $CFG;

        $this->validate_rule($rule);

        $userid = $this->userid;
        $cm = $this->cm;

        require_once($CFG->dirroot . '/mod/checkmark/locallib.php');

        $checkmark = new \checkmark($cm->id, null, $cm);

        // If completion option is enabled, evaluate it and return true/false.
        if ($checkmark->checkmark->completionsubmit) {
            $submission = $checkmark->get_submission($userid, false);
            $status = $submission && $submission->timecreated && $submission->timemodified;
            return $status ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
        } else {
            // Completion option is not enabled so just return $type.
            return COMPLETION_INCOMPLETE;
        }
    }

    /**
     * Fetch the list of custom completion rules that this module defines.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return ['completionsubmit'];
    }

    /**
     * Returns an associative array of the descriptions of custom completion rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        return [
            'completionsubmit' => get_string('completiondetail:submit', 'checkmark'),
        ];
    }

    /**
     * Returns an array of all completion rules, in the order they should be displayed to users.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'completionsubmit',
            'completionusegrade',
        ];
    }
}
