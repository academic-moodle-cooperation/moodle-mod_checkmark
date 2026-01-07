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
 * Overview integration for checkmark.
 *
 * @package    mod_checkmark
 * @author     Clemens Marx
 * @copyright  2026, Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_checkmark\courseformat;

use cm_info;
use core\output\action_link;
use core\output\local\properties\button;
use core\output\local\properties\text_align;
use core\output\renderer_helper;
use core_courseformat\activityoverviewbase;
use core_courseformat\local\overview\overviewitem;
use mod_checkmark\submissionstable;
use moodle_url;

/**
 * Provides data for the course overview tab.
 */
class overview extends activityoverviewbase {
    /** @var renderer_helper Helper for core renderers. */
    protected readonly renderer_helper $rendererhelper;

    /** @var \checkmark Checkmark instance wrapper. */
    protected readonly \checkmark $checkmark;

    /**
     * Constructor.
     *
     * @param cm_info $cm Course module info
     * @param renderer_helper $rendererhelper Helper for core renderers
     */
    public function __construct(
        cm_info $cm,
        renderer_helper $rendererhelper,
    ) {
        global $CFG;

        require_once($CFG->dirroot . '/mod/checkmark/locallib.php');

        $this->rendererhelper = $rendererhelper;
        parent::__construct($cm);

        // Build the checkmark instance wrapper for later reuse.
        $this->checkmark = new \checkmark($cm->id, null, $cm, $this->course);
    }

    #[\Override]
    public function get_due_date_overview(): ?overviewitem {
        [$open, $due] = $this->checkmark->get_avail_due_times();

        if (empty($due)) {
            return new overviewitem(
                name: get_string('duedate', 'checkmark'),
                value: null,
                content: '-',
            );
        }

        return new overviewitem(
            name: get_string('duedate', 'checkmark'),
            value: $due,
            content: userdate($due, get_string('strftimedaydatetime', 'langconfig')),
        );
    }

    #[\Override]
    public function get_actions_overview(): ?overviewitem {
        if (!has_capability('mod/checkmark:grade', $this->context)) {
            return null;
        }

        $groupid = $this->get_groupid_for_filter();
        $alertlabel = get_string('actions');
        $actions = [];
        $alertcount = 0;
        $renderer = $this->rendererhelper->get_core_renderer();

        $needgrading = submissionstable::count_userids(
            $this->context,
            $this->checkmark->checkmark->id,
            $groupid,
            \checkmark::FILTER_REQUIRE_GRADING,
        );
        if ($needgrading > 0) {
            $badge = $renderer->notice_badge(
                contents: $needgrading,
                title: get_string('grading', 'grades'),
            );
            $actions[] = new action_link(
                url: new moodle_url('/mod/checkmark/submissions.php', ['id' => $this->cm->id]),
                text: get_string('gradeverb') . ' ' . $badge,
                attributes: ['class' => button::BODY_OUTLINE->classes()],
            );
            $alertcount += $needgrading;
        }

        $attendanceunknown = 0;
        if ($this->checkmark->checkmark->trackattendance) {
            $attendanceunknown = submissionstable::count_userids(
                $this->context,
                $this->checkmark->checkmark->id,
                $groupid,
                \checkmark::FILTER_UNKNOWN,
            );
            if ($attendanceunknown > 0) {
                $attendancebadge = $renderer->notice_badge(
                    contents: $attendanceunknown,
                    title: get_string('recordattendance', 'checkmark'),
                );
                $actions[] = new action_link(
                    url: new moodle_url('/mod/checkmark/submissions.php', ['id' => $this->cm->id]),
                    text: get_string('recordattendance', 'checkmark') . ' ' . $attendancebadge,
                    attributes: ['class' => button::BODY_OUTLINE->classes()],
                );
                $alertcount += $attendanceunknown;
            }
        }

        if (empty($actions)) {
            $actions[] = new action_link(
                url: new moodle_url('/mod/checkmark/submissions.php', ['id' => $this->cm->id]),
                text: get_string('view'),
                attributes: ['class' => button::BODY_OUTLINE->classes()],
            );
        }

        $renderedactions = array_map(fn($a) => $renderer->render($a), $actions);
        $content = \html_writer::div(implode(' ', $renderedactions));

        return new overviewitem(
            name: get_string('actions'),
            value: $alertcount,
            content: $content,
            textalign: text_align::CENTER,
            alertcount: $alertcount,
            alertlabel: $alertlabel,
        );
    }

    #[\Override]
    public function get_extra_overview_items(): array {
        if ($this->is_teacher()) {
            return $this->get_teacher_overview_items();
        }

        if ($this->is_student()) {
            return $this->get_student_overview_items();
        }

        return [];
    }

    #[\Override]
    public function get_grades_overviews(): array {
        // Hide grade column for students; teachers already have access elsewhere.
        return [];
    }

    /**
     * Build overview items for teachers.
     *
     * @return array<string, overviewitem>
     */
    private function get_teacher_overview_items(): array {
        $groupid = $this->get_groupid_for_filter();

        $items = [];

        $submissions = checkmark_count_real_submissions($this->cm, $groupid);
        $total = submissionstable::count_userids(
            $this->context,
            $this->checkmark->checkmark->id,
            $groupid,
            \checkmark::FILTER_ALL,
        );

        $items['submissions'] = new overviewitem(
            name: get_string('submissions', 'checkmark'),
            value: $submissions,
            content: get_string('count_of_total', 'moodle', ['count' => $submissions, 'total' => $total]),
            textalign: text_align::CENTER,
        );

        return array_filter($items);
    }

    /**
     * Build overview items for students.
     *
     * @return array<string, overviewitem>
     */
    private function get_student_overview_items(): array {
        global $USER;

        $items = [];

        $submission = $this->checkmark->get_submission($USER->id, false);
        $status = $submission ? 'submissionstatus_submitted' : 'submissionstatus_';
        $statetext = get_string($status, 'checkmark');

        $items['submissionstatus'] = new overviewitem(
            name: get_string('submissionstatus', 'checkmark'),
            value: $submission ? 1 : 0,
            content: $statetext,
            textalign: text_align::CENTER,
        );

        return $items;
    }

    /**
     * Determine whether current user is in a grading/teacher role for this cm.
     */
    private function is_teacher(): bool {
        return has_capability('mod/checkmark:grade', $this->context);
    }

    /**
     * Determine whether current user is a participant (no grading capability).
     */
    private function is_student(): bool {
        return has_capability('mod/checkmark:view', $this->context) && !$this->is_teacher();
    }

    /**
     * Resolve current group filter (0 = all groups).
     */
    private function get_groupid_for_filter(): int {
        if (!$this->needs_filtering_by_groups()) {
            return 0;
        }
        return groups_get_activity_group($this->cm, true) ?? 0;
    }
}
