<?php
// This file is part of mod_checkmark for Moodle - http://moodle.org/
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
 * Generator file for mod_checkmark's PHPUnit tests
 *
 * @package   mod_checkmark
 * @category  test
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * checkmark module data generator class
 *
 * @package   mod_checkmark
 * @category  test
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_checkmark_generator extends testing_module_generator {

    /**
     * Generator method creating a mod_checkmark instance.
     *
     * @param array|stdClass $record (optional) Named array containing instance settings
     * @param array $options (optional) general options for course module. Can be merged into $record
     * @return stdClass record from module-defined table with additional field cmid (corresponding id in course_modules table)
     */
    public function create_instance($record = null, array $options = null) {
        $record = (object)(array)$record;

        $timecreated = time();

        $defaultsettings = array(
            'name' => 'Checkmark',
            'intro' => 'Introtext',
            'introformat' => 1,
            'alwaysshowdescription' => 1,
            'timecreated' => $timecreated,
            'timemodified' => $timecreated,
            'timedue' => $timecreated + 604800, // 1 week later!
            'gradingdue' => $timecreated + 1209600, // 2 weeks later!
            'timeavailable' => $timecreated,
            'cutoffdate' => 0,
            'resubmit' => 1,
            'emailteachers' => 1,
            'examplecount' => 10,
            'examplestart' => 1,
            'exampleprefix' => 'Example ',
            'flexiblenaming' => 0,
            'examplenames' => '1,2,3,4,5,6,7,8,9,10',
            'examplegrades' => '10,10,10,10,10,10,10,10,10,10',
            'grade' => 100,
            'trackattendance' => 0,
            'attendancegradelink' => 0,
            'attendancegradebook' => 0,
            'presentationgrading' => 0,
            'presentationgrade' => 0,
            'presentationgradebook' => 0,
            'already_submit' => 0,
        );

        foreach ($defaultsettings as $name => $value) {
            if (!isset($record->{$name})) {
                $record->{$name} = $value;
            }
        }

        return parent::create_instance($record, (array)$options);
    }
}
