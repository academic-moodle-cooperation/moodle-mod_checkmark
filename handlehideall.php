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
 * A simple function to hide or unhide multiple columns and set the respective user preferences
 *
 * @package       mod_checkmark
 * @author        Simeon Naydenov (moniNaydenov@gmail.com)
 * @copyright 2019 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');

require_login();

$columns = optional_param_array('columns', false, PARAM_ALPHANUMEXT);
$hide = optional_param('hide', false, PARAM_BOOL);

$uniqueid = 'mod-checkmark-submissions';

$prefs = json_decode(get_user_preferences('flextable_' . $uniqueid), true);

foreach ($columns as $col) {
    if ($hide) {
        $prefs['collapse'][$col] = true;
        if (property_exists($col, $prefs['sortby'])) {
            unset($prefs['sortby'][$col]);
        }
    } else {
        $prefs['collapse'][$col] = false;
    }
}


set_user_preference('flextable_' . $uniqueid, json_encode($prefs));
