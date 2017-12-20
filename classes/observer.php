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
 * Contains all of mod_checkmark's event observers/handlers
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2017 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_checkmark;
use \core\event\course_module_updated;
use \grade_item;

defined('MOODLE_INTERNAL') || die;

/**
 * mod_checkmark\observer handles events due to changes in moodle core which affect grouptool
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2017 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * Handles updated course modules - we have to take care of our additional grade items - they have to be updated if the
     * modules name changes!
     *
     * @param course_module_updated $event Event object containing useful data
     * @return bool true if success
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public static function course_module_updated(course_module_updated $event) {
        global $DB;

        // First keep the method fast! We don't want to waste everyone's time!
        if ($event->other['modulename'] !== 'checkmark') {
            return true;
        }

        $sql = "SELECT id, course, name, trackattendance, attendancegradebook, presentationgrading, presentationgradebook
                  FROM {checkmark}
                  WHERE id = ?
                        AND ((trackattendance = 1 AND attendancegradebook = 1)
                             OR (presentationgrading = 1 AND presentationgradebook = 1))";
        if (!$checkmark = $DB->get_record_sql($sql, [$event->other['instanceid']])) {
            return true;
        }

        // Update presentation-grade-item's and attendance-grade-item's names only if necessary!

        $attupdate = GRADE_UPDATE_OK;
        if ($checkmark->trackattendance && $checkmark->attendancegradebook) {
            $params = ['itemname' => get_string('attendance', 'checkmark').' '.$event->other['name']];
            $attupdate = grade_update('mod/checkmark', $checkmark->course, 'mod', 'checkmark', $checkmark->id,
                    CHECKMARK_ATTENDANCE_ITEM, null, $params);
            // Move attendance item directly after grade item, if it exists in the same category!
            $params = ['courseid'     => $checkmark->course,
                       'itemtype'     => 'mod',
                       'itemmodule'   => 'checkmark',
                       'iteminstance' => $checkmark->id];
            if ($attendanceitem = grade_item::fetch($params + ['itemnumber' => CHECKMARK_ATTENDANCE_ITEM])) {
                if ($gradeitem = grade_item::fetch($params + ['itemnumber' => CHECKMARK_GRADE_ITEM])) {
                    if ($gradeitem->categoryid == $attendanceitem->categoryid) {
                        $attendanceitem->move_after_sortorder($gradeitem->get_sortorder());
                    }
                }
            }
        }

        $presupdate = GRADE_UPDATE_OK;
        if ($checkmark->presentationgrading && $checkmark->presentationgradebook) {
            $params = ['itemname' => get_string('presentationgrade_short', 'checkmark').' '.$event->other['name']];
            $presupdate = grade_update('mod/checkmark', $checkmark->course, 'mod', 'checkmark', $checkmark->id,
                    CHECKMARK_PRESENTATION_ITEM, null, $params);
            // Move presentation item attendance item directly after attendance or grade item, if one of them exists!
            $params = ['courseid'     => $checkmark->course,
                       'itemtype'     => 'mod',
                       'itemmodule'   => 'checkmark',
                       'iteminstance' => $checkmark->id];
            if ($presentationitem = grade_item::fetch($params + ['itemnumber' => CHECKMARK_PRESENTATION_ITEM])) {
                if ($attendanceitem = grade_item::fetch($params + ['itemnumber' => CHECKMARK_ATTENDANCE_ITEM])) {
                    if ($attendanceitem->categoryid == $presentationitem->categoryid) {
                        $presentationitem->move_after_sortorder($attendanceitem->get_sortorder());
                    }
                } else if ($gradeitem = grade_item::fetch($params + ['itemnumber' => CHECKMARK_GRADE_ITEM])) {
                    if ($presentationitem->categoryid == $gradeitem->categoryid) {
                        $presentationitem->move_after_sortorder($gradeitem->get_sortorder());
                    }
                }
            }
        }

        return (($attupdate === GRADE_UPDATE_OK) && ($presupdate === GRADE_UPDATE_OK));
    }
}
