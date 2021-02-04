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
 * This file contains the definition for the renderable classes for the assignment
 *
 * @package   mod_checkmark
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_checkmark;

use renderable;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderable checkmark grading summary
 * @package   mod_checkmark
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradingsummary implements renderable {
    /** @var int participantcount - The number of users who can submit to this assignment */
    public $participantcount = 0;
    /** @var int timeavailable - Allow submissions */
    public $timeavailable = 0;
    /** @var int submissionssubmittedcount - The number of submissions in submitted status */
    public $submissionssubmittedcount = 0;
    /** @var int submissionsneedgradingcount - The number of submissions that need grading */
    public $submissionsneedgradingcount = 0;
    /** @var int duedate - The assignment due date (if one is set) */
    public $duedate = 0;
    /** @var int cutoffdate - The assignment cut off date (if one is set) */
    public $cutoffdate = 0;
    /** @var int coursemoduleid - The assignment course module id */
    public $coursemoduleid = 0;
    /** @var int coursestartdate - start date of the course as a unix timestamp*/
    public $coursestartdate;
    /** @var boolean cangrade - Can the current user grade students? */
    public $cangrade = false;
    /** @var boolean isvisible - Is the assignment's context module visible to students? */
    public $isvisible = true;
    /** @var int attendantcount - Is the course a relative dates mode course or not */
    public $attendantcount = -1;
    /** @var int absencecount - Is the course a relative dates mode course or not */
    public $absencecount = -1;

    /**
     * assign_grading_summary constructor.
     *
     * @param int $participantcount
     * @param int $timeavailable
     * @param int $submissionssubmittedcount
     * @param int $submissionsneedgradingcount
     * @param int $duedate
     * @param int $cutoffdate
     * @param int $coursemoduleid
     * @param int $coursestartdate
     * @param bool $cangrade
     * @param bool $isvisible
     * @param int $attendantcount
     * @param int $absencecount
     */
    public function __construct(int $participantcount, int $timeavailable, int $submissionssubmittedcount,
            int $submissionsneedgradingcount, int $duedate, int $cutoffdate, int $coursemoduleid, int $coursestartdate,
            bool $cangrade, bool $isvisible, int $attendantcount, int $absencecount) {
        $this->participantcount = $participantcount;
        $this->timeavailable = $timeavailable;
        $this->submissionssubmittedcount = $submissionssubmittedcount;
        $this->submissionsneedgradingcount = $submissionsneedgradingcount;
        $this->duedate = $duedate;
        $this->cutoffdate = $cutoffdate;
        $this->coursemoduleid = $coursemoduleid;
        $this->coursestartdate = $coursestartdate;
        $this->cangrade = $cangrade;
        $this->isvisible = $isvisible;
        $this->attendantcount = $attendantcount;
        $this->absencecount = $absencecount;
    }

}
