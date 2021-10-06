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
 * db/services.php
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2021 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$functions = [

        'mod_checkmark_get_checkmarks_by_courses' => [
            'classname'     => 'mod_checkmark_external',
            'methodname'    => 'get_checkmarks_by_courses',
            'classpath'     => 'mod/checkmark/externallib.php',
            'description'   => 'Get all checkmarks in the given courses',
            'type'          => 'read',
            'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        ],

        'mod_checkmark_get_checkmark' => [
            'classname'     => 'mod_checkmark_external',
            'methodname'    => 'get_checkmark',
            'classpath'     => 'mod/checkmark/externallib.php',
            'description'   => 'Get the checkmark with the given id',
            'type'          => 'read',
            'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        ],

        'mod_checkmark_submit' => [
            'classname'     => 'mod_checkmark_external',
            'methodname'    => 'submit',
            'classpath'     => 'mod/checkmark/externallib.php',
            'description'   => 'Submit a submission for the checkmark with the given id',
            'type'          => 'write',
            'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        ],

];
