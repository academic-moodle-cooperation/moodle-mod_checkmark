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
 * Allows the admin to manage checkmark subplugins.
 *
 * @package   mod_checkmark
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/checkmark/adminlib.php');

require_login();

$subtype = optional_param('subtype', 'checkmarkaddon', PARAM_PLUGIN);
$action = optional_param('action', null, PARAM_PLUGIN);
$plugin = optional_param('plugin', null, PARAM_PLUGIN);

checkmark_plugin_manager::require_valid_subtype($subtype);

if (!empty($plugin)) {
    require_sesskey();
}

$pluginmanager = new checkmark_plugin_manager($subtype);

$PAGE->set_context(context_system::instance());

$pluginmanager->execute($action, $plugin);
