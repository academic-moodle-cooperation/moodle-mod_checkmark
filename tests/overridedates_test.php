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
 * Unit tests for methods related to override_dates in locallib.php
 *
 * @package   mod_checkmark
 * @author    Daniel Binder
 * @copyright 2020 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_checkmark;

defined('MOODLE_INTERNAL') || die();

// Make sure the code being tested is accessible.
global $CFG;
use core\event\{calendar_event_created, calendar_event_deleted, calendar_event_updated};
use mod_checkmark\event\{group_override_deleted,
        group_override_priority_changed,
        group_override_updated,
        user_override_created,
        group_override_created,
        user_override_deleted,
        user_override_updated};
require_once($CFG->dirroot . '/mod/checkmark/locallib.php'); // Include the code to test!

/**
 * This class contains the test cases for the override_dates method.
 * @group mod_checkmark
 *
 * @package   mod_checkmark
 * @author    Daniel Binder
 * @copyright 2020 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \checkmark::override_dates
 */
final class overridedates_test extends \advanced_testcase {

    /**
     * @var \checkmark Checkmark object used for testing
     */
    private $checkmark;
    /**
     * @var \stdClass User object used for testing
     */
    private $testuser;
    /**
     * @var \stdClass Group object used for testing
     */
    private $testgroup1;
    /**
     * @var \stdClass Another group object used for testing
     */
    private $testgroup2;

    /**
     * Set up a checkmark instance, a user and a group
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->testuser = $this->getDataGenerator()->create_user(['email' => 'test@example.com', 'username' => 'test']);
        $course1 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($this->testuser->id, $course1->id);
        $checkmark = $this->getDataGenerator()->create_module('checkmark', ['course' => $course1->id]);
        $this->testgroup1 = $this->getDataGenerator()->create_group(['courseid' => $course1->id]);
        $this->testgroup2 = $this->getDataGenerator()->create_group(['courseid' => $course1->id]);
        $this->getDataGenerator()->create_group_member(['userid' => $this->testuser->id, 'groupid' => $this->testgroup1->id]);
        $this->getDataGenerator()->create_group_member(['userid' => $this->testuser->id, 'groupid' => $this->testgroup2->id]);
        $this->checkmark = new \checkmark($checkmark->cmid);
    }

    /**
     * Test the creation of a single user override
     *
     * @throws \dml_exception
     */
    public function test_add_user_override(): void {
        global $DB;
        $timedueoverride = time() + 1209600; // 2 weeks after now.
        $sink = $this->redirectEvents();
        $this->checkmark->override_dates([$this->testuser->id], $this->checkmark->checkmark->timeavailable,
                $timedueoverride, $this->checkmark->checkmark->cutoffdate);
        $this->assertEquals(1, $DB->count_records('checkmark_overrides'));
        $expect = ['timeavailable' => null, 'timedue' => $timedueoverride, 'cutoffdate' => null, 'groupid' => null,
                'grouppriority' => null, ];
        $result = $DB->get_record('checkmark_overrides', ['userid' => $this->testuser->id],
                'timeavailable, timedue, cutoffdate, groupid, grouppriority');
        $result = ['timeavailable' => $result->timeavailable, 'timedue' => (int)($result->timedue),
                'cutoffdate' => $result->cutoffdate, 'groupid' => $result->groupid, 'grouppriority' => $result->grouppriority, ];
        $this->assertTrue(self::arrays_are_similar($expect, $result));

        // Assert calendar and log event.
        $events = $sink->get_events();
        $this->check_events($events, user_override_created::class, calendar_event_created::class);
        $sink->close();
    }

    /**
     * Test the creation of a single group override
     *
     * @throws \dml_exception
     */
    public function test_add_group_override(): void {
        global $DB;
        $timedueoverride = time() + 1209600; // 2 weeks after now.
        $sink = $this->redirectEvents();
        $this->checkmark->override_dates([$this->testgroup1->id], $this->checkmark->checkmark->timeavailable,
                $timedueoverride, $this->checkmark->checkmark->cutoffdate, \mod_checkmark\overrideform::GROUP);
        $this->assertEquals(1, $DB->count_records('checkmark_overrides'));
        $expect = ['timeavailable' => null, 'timedue' => $timedueoverride, 'cutoffdate' => null,
                'userid' => null, 'grouppriority' => 1, ];
        $result = $DB->get_record('checkmark_overrides', ['groupid' => $this->testgroup1->id],
                'timeavailable, timedue, cutoffdate, userid, grouppriority');
        $result = ['timeavailable' => $result->timeavailable, 'timedue' => (int)($result->timedue),
                'cutoffdate' => $result->cutoffdate, 'userid' => $result->userid,
                'grouppriority' => (int)($result->grouppriority),
        ];
        $this->assertTrue(self::arrays_are_similar($expect, $result));

        // Assert calendar and log event.
        $this->check_events($sink->get_events(), group_override_created::class, calendar_event_created::class);
        $sink->close();
    }


    public function test_update_user_override(): void {
        global $DB;

        // Create a user override as tested above.
        $timedueoverride = time() + 1209600; // 2 weeks after now.
        $this->checkmark->override_dates([$this->testuser->id], $this->checkmark->checkmark->timeavailable,
                $timedueoverride, $this->checkmark->checkmark->cutoffdate);

        $sink = $this->redirectEvents();
        $timedueoverride = time() + 2419200; // 4 weeks after now.
        $timeavaliableoverride = time() + 1209600;
        $cutoffoverride = time() + 2419200;
        $this->checkmark->override_dates([$this->testuser->id], $timeavaliableoverride,
                $timedueoverride, $cutoffoverride);
        $expect = ['timeavailable' => $timeavaliableoverride, 'timedue' => $timedueoverride, 'cutoffdate' => $cutoffoverride,
                'groupid' => null, 'grouppriority' => null, ];
        $result = $DB->get_record('checkmark_overrides', ['userid' => $this->testuser->id],
                'timeavailable, timedue, cutoffdate, groupid, grouppriority');
        $result = ['timeavailable' => (int)$result->timeavailable, 'timedue' => (int)($result->timedue),
                'cutoffdate' => (int)$result->cutoffdate, 'groupid' => $result->groupid,
                'grouppriority' => $result->grouppriority,
                ];
        $this->assertTrue(self::arrays_are_similar($expect, $result));

        // Assert calendar and log event.
        $events = $sink->get_events();
        $this->check_events($events, user_override_updated::class, calendar_event_updated::class);
        $sink->close();
    }

    public function test_update_group_override(): void {
        global $DB;

        // Create a group override as tested above.
        $timedueoverride = time() + 1209600; // 2 weeks after now.
        $this->checkmark->override_dates([$this->testgroup1->id], $this->checkmark->checkmark->timeavailable,
                $timedueoverride, $this->checkmark->checkmark->cutoffdate, \mod_checkmark\overrideform::GROUP);

        $sink = $this->redirectEvents();
        $timedueoverride = time() + 2419200; // 4 weeks after now.
        $timeavaliableoverride = time() + 1209600;
        $cutoffoverride = time() + 2419200;
        $this->checkmark->override_dates([$this->testgroup1->id], $timeavaliableoverride,
                $timedueoverride, $cutoffoverride, \mod_checkmark\overrideform::GROUP);
        $expect = ['timeavailable' => $timeavaliableoverride, 'timedue' => $timedueoverride, 'cutoffdate' => $cutoffoverride,
                'userid' => null, 'grouppriority' => 1, ];
        $result = $DB->get_record('checkmark_overrides', ['groupid' => $this->testgroup1->id],
                'timeavailable, timedue, cutoffdate, userid, grouppriority');
        $result = ['timeavailable' => (int)$result->timeavailable, 'timedue' => (int)($result->timedue),
                'cutoffdate' => (int)$result->cutoffdate, 'userid' => $result->userid,
                'grouppriority' => (int)($result->grouppriority), ];
        $this->assertTrue(self::arrays_are_similar($expect, $result));

        // Assert calendar and log event.
        $this->check_events($sink->get_events(), group_override_updated::class,
                calendar_event_updated::class);
        $sink->close();
    }



    /**
     * Test if no overwrite is created if dates identical to the checkmark's dates are passed.
     *
     * @throws \dml_exception
     */
    public function test_add_identical_overwrite(): void {
        global $DB;
        $sink = $this->redirectEvents();
        $this->checkmark->override_dates([$this->testgroup1->id], $this->checkmark->checkmark->timeavailable,
                $this->checkmark->checkmark->timedue, $this->checkmark->checkmark->cutoffdate,
                \mod_checkmark\overrideform::GROUP);
        $this->assertEquals(0, $DB->count_records('checkmark_overrides'));
        $events = $sink->get_events();
        $this->assertCount(0, $events);
        $sink->close();
    }

    /**
     * Test if delete_override deletes an existing user override from the database and fires the correct events
     *
     * @throws \dml_exception
     */
    public function test_delete_user_override(): void {
        global $DB;

        // Create a user override as tested above.
        $timedueoverride = time() + 1209600; // 2 weeks after now.
        $this->checkmark->override_dates([$this->testuser->id], $this->checkmark->checkmark->timeavailable,
                $timedueoverride, $this->checkmark->checkmark->cutoffdate);
        $this->assertEquals(1, $DB->count_records('checkmark_overrides'));

        $sink = $this->redirectEvents();
        $this->checkmark->delete_override($this->testuser->id);
        $this->assertEquals(0, $DB->count_records('checkmark_overrides'));
        $this->check_events($sink->get_events(), user_override_deleted::class, calendar_event_deleted::class);

    }

    /**
     * Test if delete_override deletes an existing group override from the database and fires the correct events
     *
     * @throws \dml_exception
     */
    public function test_delete_group_override(): void {
        global $DB;

        // Create a group override as tested above.
        $timedueoverride = time() + 1209600; // 2 weeks after now.
        $this->checkmark->override_dates([$this->testgroup1->id], $this->checkmark->checkmark->timeavailable,
                $timedueoverride, $this->checkmark->checkmark->cutoffdate, \mod_checkmark\overrideform::GROUP);
        $this->assertEquals(1, $DB->count_records('checkmark_overrides'));

        $sink = $this->redirectEvents();
        $this->checkmark->delete_override($this->testgroup1->id, \mod_checkmark\overrideform::GROUP);
        $this->assertEquals(0, $DB->count_records('checkmark_overrides'));
        $this->check_events($sink->get_events(), group_override_deleted::class, calendar_event_deleted::class);
    }

    /**
     * Test the reordering of priorities of two groups in both directions
     *
     * @throws \dml_exception
     */
    public function test_reorder_grouppriority(): void {
        global $DB;
        $timedueoverride1 = time() + 1209600; // 2 weeks after now.
        $timedueoverride2 = time() + 2419200; // 4 weeks after now.

        $this->checkmark->override_dates([$this->testgroup1->id], $this->checkmark->checkmark->timeavailable,
                $timedueoverride1, $this->checkmark->checkmark->cutoffdate, \mod_checkmark\overrideform::GROUP);
        $this->checkmark->override_dates([$this->testgroup2->id], $this->checkmark->checkmark->timeavailable,
                $timedueoverride2, $this->checkmark->checkmark->cutoffdate, \mod_checkmark\overrideform::GROUP);
        $expected1 = (object) ['groupid' => $this->testgroup1->id, 'grouppriority' => '1'];
        $expected2 = (object) ['groupid' => $this->testgroup2->id, 'grouppriority' => '2'];
        $result1 = $DB->get_record('checkmark_overrides', ['groupid' => $this->testgroup1->id],
                'groupid, grouppriority');
        $result2 = $DB->get_record('checkmark_overrides', ['groupid' => $this->testgroup2->id],
                'groupid, grouppriority');

        $this->assertEquals(2, $DB->count_records('checkmark_overrides'));
        $this->assertEquals($expected1, $result1);
        $this->assertEquals($expected2, $result2);

        $sink = $this->redirectEvents();
        $this->checkmark->reorder_group_overrides($this->testgroup1->id);
        $this->check_events($sink->get_events(), group_override_priority_changed::class,
                calendar_event_updated::class, calendar_event_updated::class);
        $sink->clear();

        $expected1 = (object) ['groupid' => $this->testgroup1->id, 'grouppriority' => '2'];
        $expected2 = (object) ['groupid' => $this->testgroup2->id, 'grouppriority' => '1'];

        $result1 = $DB->get_record('checkmark_overrides', ['groupid' => $this->testgroup1->id],
                'groupid, grouppriority');
        $result2 = $DB->get_record('checkmark_overrides', ['groupid' => $this->testgroup2->id],
                'groupid, grouppriority');

        $this->assertEquals(2, $DB->count_records('checkmark_overrides'));
        $this->assertEquals($expected1, $result1);
        $this->assertEquals($expected2, $result2);

        $this->checkmark->reorder_group_overrides($this->testgroup1->id, true);

        $this->check_events($sink->get_events(), group_override_priority_changed::class,
                calendar_event_updated::class, calendar_event_updated::class);
        $sink->close();

        $expected1 = (object) ['groupid' => $this->testgroup1->id, 'grouppriority' => '1'];
        $expected2 = (object) ['groupid' => $this->testgroup2->id, 'grouppriority' => '2'];

        $result1 = $DB->get_record('checkmark_overrides', ['groupid' => $this->testgroup1->id],
                'groupid, grouppriority');
        $result2 = $DB->get_record('checkmark_overrides', ['groupid' => $this->testgroup2->id],
                'groupid, grouppriority');

        $this->assertEquals(2, $DB->count_records('checkmark_overrides'));
        $this->assertEquals($expected1, $result1);
        $this->assertEquals($expected2, $result2);

    }

    /**
     * Helper function to check if all log and calendar events for an overwrite dates action have taken place
     *
     * @param array $events Events caught by $sink->getEvents()
     * @param string $logkind Class the log event should be an instance of
     * @param string $calendarkind Class the calendar event should be an instance of
     * @param string|null $calendarkind2 Class the second calendar event should be an instance of or null if there is none
     */
    private function check_events($events, $logkind, $calendarkind = null, $calendarkind2 = null): void {
        // TODO Eventually rewrite this method in a generic way so it can be used by other tests too.
        if ($calendarkind2) {
            $this->assertCount(3, $events);
        } else {
            $this->assertCount(2, $events);
        }
        $calendareventreceived = false;
        $calendarevent2received = false;
        $logeventreceived = false;
        foreach ($events as $event) {
            if ($event instanceof $logkind && !$logeventreceived) {
                $this->assertEquals($this->checkmark->context, $event->get_context());
                if (isset($event->other['groupid'])) {
                    $this->assertEquals($this->testgroup1->id, $event->other['groupid']);
                } else if (isset($event->relateduserid)) {
                    $this->assertEquals($this->testuser->id, $event->relateduserid);
                } else {
                    // Let test fail if no id of either a user or a group override is contained in event !
                    $this->assertTrue(false);
                }
                $logeventreceived = true;
            } else if ($event instanceof $calendarkind && !$calendareventreceived) {
                $calendareventreceived = true;
            } else if ($calendarkind2 && $event instanceof $calendarkind2 && !$calendarevent2received) {
                $calendarevent2received = true;
            } else {
                // Let test fail if events contains not exactly one log and one calendar event!
                $this->assertTrue(false);
            }
        }
    }

    /**
     * Determine if two associative arrays are similar
     *
     * Both arrays must have the same indexes with identical values
     * without respect to key ordering
     *
     * @param array $a
     * @param array $b
     * @return bool
     */
    private static function arrays_are_similar($a, $b): bool {
        // If the indexes don't match, return immediately.
        if (count(array_diff_assoc($a, $b))) {
            return false;
        }
        // We know that the indexes, but maybe not values, match.
        // Compare the values between the two arrays.
        foreach ($a as $k => $v) {
            if ($v !== $b[$k]) {
                return false;
            }
        }
        // We have identical indexes, and no unequal values.
        return true;
    }
}
