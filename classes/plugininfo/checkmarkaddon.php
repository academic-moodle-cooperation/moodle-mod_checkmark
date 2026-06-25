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
 * Checkmark add-on subplugin info class.
 *
 * @package   mod_checkmark
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_checkmark\plugininfo;

use core\plugininfo\base;
use core_plugin_manager;
use moodle_url;

/**
 * Checkmark add-on subplugin info class.
 *
 * @package   mod_checkmark
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class checkmarkaddon extends base {
    /**
     * Whether Checkmark add-on plugins can be disabled.
     *
     * @return bool
     */
    public static function plugintype_supports_disabling(): bool {
        return true;
    }

    /**
     * Finds all enabled plugins, the result may include missing plugins.
     *
     * @return array|null Enabled plugins as pluginname => pluginname, null means unknown.
     */
    public static function get_enabled_plugins() {
        global $DB;

        $plugins = core_plugin_manager::instance()->get_installed_plugins('checkmarkaddon');
        if (!$plugins) {
            return [];
        }

        $installed = [];
        foreach ($plugins as $plugin => $version) {
            $installed[] = 'checkmarkaddon_' . $plugin;
        }

        [$installedsql, $params] = $DB->get_in_or_equal($installed, SQL_PARAMS_NAMED);
        $disabled = $DB->get_records_select(
            'config_plugins',
            "plugin {$installedsql} AND name = 'disabled'",
            $params,
            'plugin ASC'
        );
        foreach ($disabled as $conf) {
            if (empty($conf->value)) {
                continue;
            }
            [, $name] = explode('_', $conf->plugin, 2);
            unset($plugins[$name]);
        }

        $enabled = [];
        foreach ($plugins as $plugin => $version) {
            $enabled[$plugin] = $plugin;
        }

        return $enabled;
    }

    /**
     * Enable or disable a Checkmark add-on plugin.
     *
     * @param string $pluginname The plugin name.
     * @param int $enabled Whether the plugin should be enabled.
     * @return bool Whether the enabled state changed.
     */
    public static function enable_plugin(string $pluginname, int $enabled): bool {
        $haschanged = false;

        $plugin = 'checkmarkaddon_' . $pluginname;
        $oldvalue = get_config($plugin, 'disabled');
        $disabled = !$enabled;

        if ($oldvalue === false || ((bool) $oldvalue !== $disabled)) {
            set_config('disabled', $disabled, $plugin);
            $haschanged = true;

            add_to_config_log('disabled', $oldvalue, $disabled, $plugin);
            core_plugin_manager::reset_caches();
        }

        return $haschanged;
    }

    /**
     * Whether this Checkmark add-on plugin can be uninstalled.
     *
     * @return bool
     */
    public function is_uninstall_allowed() {
        return true;
    }

    /**
     * Return URL used for management of plugins of this type.
     *
     * @return moodle_url
     */
    public static function get_manage_url() {
        return new moodle_url('/mod/checkmark/adminmanageplugins.php', ['subtype' => 'checkmarkaddon']);
    }

    /**
     * Return the settings section name for this plugin.
     *
     * @return string
     */
    public function get_settings_section_name() {
        return $this->type . '_' . $this->name;
    }

    /**
     * Loads plugin settings into the settings tree.
     *
     * @param \part_of_admin_tree $adminroot The admin tree.
     * @param string $parentnodename Parent node name.
     * @param bool $hassiteconfig Whether the current user has moodle/site:config capability.
     */
    public function load_settings(\part_of_admin_tree $adminroot, $parentnodename, $hassiteconfig) {
        global $CFG, $USER, $DB, $OUTPUT, $PAGE; // In case settings.php wants to refer to them.

        $ADMIN = $adminroot;
        $plugininfo = $this;

        if (!$this->is_installed_and_upgraded()) {
            return;
        }

        if (!$hassiteconfig || !file_exists($this->full_path('settings.php'))) {
            return;
        }

        $section = $this->get_settings_section_name();
        $settings = new \admin_settingpage($section, $this->displayname, 'moodle/site:config', $this->is_enabled() === false);

        if ($adminroot->fulltree) {
            include($this->full_path('settings.php'));
        }

        $adminroot->add($parentnodename, $settings);
    }
}
