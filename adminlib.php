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
 * Admin helpers for checkmark subplugins.
 *
 * @package   mod_checkmark
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_admin\admin_search;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/mod/checkmark/adminmanager.php');

/**
 * Admin external page that displays a list of installed checkmark subplugins.
 *
 * @package   mod_checkmark
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class checkmark_admin_page_manage_checkmark_plugins extends admin_externalpage {
    /** @var string The checkmark subplugin type. */
    private $subtype = '';

    /**
     * Constructor.
     *
     * @param string $subtype The checkmark subplugin type.
     */
    public function __construct($subtype) {
        checkmark_plugin_manager::require_valid_subtype($subtype);

        $this->subtype = $subtype;
        $url = new moodle_url('/mod/checkmark/adminmanageplugins.php', ['subtype' => $subtype]);

        parent::__construct(
            'manage' . $subtype . 'plugins',
            get_string('manage' . $subtype . 'plugins', 'checkmark'),
            $url
        );
    }

    /**
     * Search subplugins for the specified string.
     *
     * @param string $query The string to search for.
     * @return array
     */
    public function search($query) {
        if ($result = parent::search($query)) {
            return $result;
        }

        $found = false;
        foreach (core_component::get_plugin_list($this->subtype) as $name => $notused) {
            $pluginname = get_string('pluginname', $this->subtype . '_' . $name);
            if (strpos(core_text::strtolower($pluginname), $query) !== false) {
                $type = admin_search::SEARCH_MATCH_SETTING_DISPLAY_NAME;
                $found = true;
                break;
            }
        }

        if ($found) {
            $result = new stdClass();
            $result->page = $this;
            $result->settings = [];
            $result->searchmatchtype = $type;

            return [$this->name => $result];
        }

        return [];
    }
}
