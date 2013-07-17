<?php
// This file is made for Moodle - http://moodle.org/
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
defined('MOODLE_INTERNAL') || die;

/**
 * Admin-Settings for checkmark
 *
 * @package       mod_checkmark
 * @author        Philipp Hager (e0803285@gmail.com)
 * @copyright     2011 TSC TU Vienna
 * @since         Moodle 2.1
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_configtext('checkmark_stdexamplecount',
                                                get_string('strstdexamplecount', 'checkmark'),
                                                get_string('strstdexamplecountdesc', 'checkmark'),
                                                '10'));
    $settings->add(new admin_setting_configtext('checkmark_stdexamplestart',
                                                get_string('strstdexamplestart', 'checkmark'),
                                                get_string('strstdexamplestartdesc', 'checkmark'),
                                                '1'));
    //tscpr:
        //instead of having the default values hardcoded, you can "calculate" them with the delimiter set in the checkmark class.. just in case :)
    $settings->add(new admin_setting_configtext('checkmark_stdnames',
                                                get_string('strstdnames', 'checkmark'),
                                                get_string('strstdnamesdesc', 'checkmark'),
                                                'a,b,c,d,e,f'));
    $settings->add(new admin_setting_configtext('checkmark_stdgrades',
                                                get_string('strstdgrades', 'checkmark'),
                                                get_string('strstdgradesdesc', 'checkmark'),
                                                '10,10,20,20,20,20'));
    $settings->add(new admin_setting_configtext('checkmark_validmsgtime',
                                                get_string('strvalidmsgtime', 'checkmark'),
                                                get_string('strvalidmsgtimedesc', 'checkmark'),
                                                '2'));
}
