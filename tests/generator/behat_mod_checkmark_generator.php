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
 * Generator file for mod_checkmark's behat tests
 *
 * @package   mod_checkmark
 * @category  test
 * @author    Daniel Binder
 * @copyright 2020 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * checkmark module data generator class
 *
 * @package   mod_checkmark
 * @category  test
 * @author    Daniel Binder
 * @copyright 2020 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_checkmark_generator extends behat_generator_base {

    /**
     * Defines objects that can be generated via "and the following 'x' exists"
     * @return Structure of objects that can be generated
     */
    protected function get_creatable_entities(): array {
            return [
                    'submissions' => [
                            'singular' => 'submission',
                            'datagenerator' => 'submission',
                            'required' => ['checkmark', 'user'],
                            'switchids' => ['checkmark' => 'checkmark', 'user' => 'userid'],
                    ],
            ];
    }

    /**
     * Look up the id of a checkmark from its name.
     *
     * @param string $checkmarkname the checkmark name, for example 'Checkmark 1'.
     * @return int corresponding id.
     */
    public function get_checkmark_id(string $checkmarkname) {
        global $DB;

        if (!$id = $DB->get_field('checkmark', 'id', ['name' => $checkmarkname])) {
            throw new Exception('There is no checkmark with name "' . $checkmarkname);
        }
        return $id;
    }
}
