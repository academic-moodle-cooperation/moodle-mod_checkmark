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
 * settings.php
 * Admin-Settings for checkmark
 *
 * @package       mod_checkmark
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_configcheckbox('checkmark/requiremodintro',
                                                    get_string('requiremodintro', 'admin'),
                                                    get_string('configrequiremodintro', 'admin'), 1));

    $settings->add(new admin_setting_configtext('checkmark/stdexamplecount',
                                                get_string('strstdexamplecount', 'checkmark'),
                                                get_string('strstdexamplecountdesc', 'checkmark'),
                                                '10'));
    $settings->add(new admin_setting_configtext('checkmark/stdexamplestart',
                                                get_string('strstdexamplestart', 'checkmark'),
                                                get_string('strstdexamplestartdesc', 'checkmark'),
                                                '1'));
    /*
     * TODO tscpr: instead of having the default values hardcoded, you can "calculate" them with
     * the delimiter set in the checkmark class.. just in case :)
     */
    $settings->add(new admin_setting_configtext('checkmark/stdnames',
                                                get_string('strstdnames', 'checkmark'),
                                                get_string('strstdnamesdesc', 'checkmark'),
                                                'a,b,c,d,e,f'));
    $settings->add(new admin_setting_configtext('checkmark/stdgrades',
                                                get_string('strstdgrades', 'checkmark'),
                                                get_string('strstdgradesdesc', 'checkmark'),
                                                '10,10,20,20,20,20'));
    $settings->add(new admin_setting_configtext('checkmark/validmsgtime',
                                                get_string('strvalidmsgtime', 'checkmark'),
                                                get_string('strvalidmsgtimedesc', 'checkmark'),
                                                '2'));

    $settings->add(new admin_setting_configcheckbox('checkmark/showrecentsubmissions',
                                                    get_string('showrecentsubmissions', 'checkmark'),
                                                    get_string('configshowrecentsubmissions', 'checkmark'), 0));
}
