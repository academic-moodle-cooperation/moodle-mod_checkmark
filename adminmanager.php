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
 * Checkmark subplugin management controller.
 *
 * @package   mod_checkmark
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/adminlib.php');

/**
 * Handles the display and configuration of installed checkmark subplugins.
 *
 * @package   mod_checkmark
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class checkmark_plugin_manager {
    /** @var array Supported checkmark subplugin types and their directories. */
    private const SUBPLUGIN_TYPES = [
        'checkmarkaddon' => 'addon',
    ];

    /** @var moodle_url The current management page URL. */
    private $pageurl;

    /** @var string The checkmark subplugin type. */
    private $subtype = '';

    /**
     * Constructor.
     *
     * @param string $subtype The checkmark subplugin type.
     */
    public function __construct($subtype) {
        self::require_valid_subtype($subtype);

        $this->pageurl = new moodle_url('/mod/checkmark/adminmanageplugins.php', ['subtype' => $subtype]);
        $this->subtype = $subtype;
    }

    /**
     * Returns supported checkmark subplugin types.
     *
     * @return array
     */
    public static function get_subplugin_types() {
        return self::SUBPLUGIN_TYPES;
    }

    /**
     * Checks whether the given subtype is supported by checkmark.
     *
     * @param string $subtype The subplugin type.
     * @return bool
     */
    public static function is_valid_subtype($subtype) {
        return array_key_exists($subtype, self::SUBPLUGIN_TYPES);
    }

    /**
     * Require a supported checkmark subplugin type.
     *
     * @param string $subtype The subplugin type.
     * @throws moodle_exception
     */
    public static function require_valid_subtype($subtype) {
        if (!self::is_valid_subtype($subtype)) {
            throw new moodle_exception('invalidsubplugintype', 'checkmark', '', $subtype);
        }
    }

    /**
     * Return a list of plugins sorted by the order defined in the admin interface.
     *
     * @return array
     */
    public function get_sorted_plugins_list() {
        $names = core_component::get_plugin_list($this->subtype);
        $result = [];

        foreach ($names as $name => $path) {
            $idx = get_config($this->subtype . '_' . $name, 'sortorder');
            if (!$idx) {
                $idx = 0;
            }
            while (array_key_exists($idx, $result)) {
                $idx++;
            }
            $result[$idx] = $name;
        }

        ksort($result);

        return $result;
    }

    /**
     * Write an action icon link.
     *
     * @param string $action URL parameter to include in the link.
     * @param string $plugin Plugin name.
     * @param string $icon The key of the icon to use.
     * @param string $alt The string description of the link.
     * @return string
     */
    private function format_icon_link($action, $plugin, $icon, $alt) {
        global $OUTPUT;

        $url = $this->pageurl;

        if ($action === 'delete') {
            $url = core_plugin_manager::instance()->get_uninstall_url($this->subtype . '_' . $plugin, 'manage');
            if (!$url) {
                return '&nbsp;';
            }

            return html_writer::link($url, get_string('uninstallplugin', 'core_admin'));
        }

        return $OUTPUT->action_icon(
            new moodle_url($url, ['action' => $action, 'plugin' => $plugin, 'sesskey' => sesskey()]),
            new pix_icon($icon, $alt, 'moodle', ['title' => $alt]),
            null,
            ['title' => $alt]
        ) . ' ';
    }

    /**
     * Write the HTML for the subplugins table.
     */
    private function view_plugins_table() {
        global $OUTPUT, $CFG;

        require_once($CFG->libdir . '/tablelib.php');

        $this->view_header();

        $table = new flexible_table($this->subtype . 'pluginsadmintable');
        $table->define_baseurl($this->pageurl);
        $table->define_columns(['pluginname', 'version', 'hideshow', 'order', 'settings', 'uninstall']);
        $table->define_headers([
            get_string($this->subtype . 'pluginname', 'checkmark'),
            get_string('version'),
            get_string('hideshow', 'checkmark'),
            get_string('order'),
            get_string('settings'),
            get_string('uninstallplugin', 'core_admin'),
        ]);
        $table->set_attribute('id', $this->subtype . 'plugins');
        $table->set_attribute('class', 'admintable table generaltable');
        $table->setup();

        $plugins = array_values($this->get_sorted_plugins_list());

        foreach ($plugins as $idx => $plugin) {
            $component = $this->subtype . '_' . $plugin;
            $plugininfo = core_plugin_manager::instance()->get_plugin_info($component);
            $row = [];
            $class = '';

            $row[] = $plugininfo ? $plugininfo->displayname : get_string('pluginname', $component);
            $row[] = get_config($component, 'version') ?: '';

            $visible = !get_config($component, 'disabled');
            if ($visible) {
                $row[] = $this->format_icon_link('hide', $plugin, 't/hide', get_string('disable'));
            } else {
                $row[] = $this->format_icon_link('show', $plugin, 't/show', get_string('enable'));
                $class = 'dimmed_text';
            }

            $movelinks = '';
            if ($idx > 0) {
                $movelinks .= $this->format_icon_link('moveup', $plugin, 't/up', get_string('up'));
            } else {
                $movelinks .= $OUTPUT->spacer(['width' => 16]);
            }
            if ($idx < count($plugins) - 1) {
                $movelinks .= $this->format_icon_link('movedown', $plugin, 't/down', get_string('down'));
            }
            $row[] = $movelinks;

            $plugindir = core_component::get_plugin_directory($this->subtype, $plugin);
            if ($row[1] !== '' && $plugindir && file_exists($plugindir . '/settings.php')) {
                $row[] = html_writer::link(
                    new moodle_url('/admin/settings.php', ['section' => $component]),
                    get_string('settings')
                );
            } else {
                $row[] = '&nbsp;';
            }

            $row[] = $this->format_icon_link('delete', $plugin, 't/delete', get_string('uninstallplugin', 'core_admin'));

            $table->add_data($row, $class);
        }

        $table->finish_output();
        $this->view_footer();
    }

    /**
     * Write the page header.
     */
    private function view_header() {
        global $OUTPUT;

        admin_externalpage_setup('manage' . $this->subtype . 'plugins');

        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('manage' . $this->subtype . 'plugins', 'checkmark'));
    }

    /**
     * Write the page footer.
     */
    private function view_footer() {
        global $OUTPUT;

        echo $OUTPUT->footer();
    }

    /**
     * Check this user has permission to edit the installed plugin list.
     */
    private function check_permissions() {
        require_login();
        require_capability('moodle/site:config', context_system::instance());
    }

    /**
     * Hide this plugin.
     *
     * @param string $plugin The plugin to hide.
     * @return string
     */
    public function hide_plugin($plugin) {
        $class = core_plugin_manager::resolve_plugininfo_class($this->subtype);
        $class::enable_plugin($plugin, false);

        return 'view';
    }

    /**
     * Change this plugin's order.
     *
     * @param string $plugintomove The plugin to move.
     * @param string $dir The direction, up or down.
     * @return string
     */
    public function move_plugin($plugintomove, $dir) {
        $plugins = array_values($this->get_sorted_plugins_list());
        $currentindex = array_search($plugintomove, $plugins);

        if ($currentindex === false) {
            return 'view';
        }

        if ($dir === 'up' && $currentindex > 0) {
            $tempplugin = $plugins[$currentindex - 1];
            $plugins[$currentindex - 1] = $plugins[$currentindex];
            $plugins[$currentindex] = $tempplugin;
        } else if ($dir === 'down' && $currentindex < count($plugins) - 1) {
            $tempplugin = $plugins[$currentindex + 1];
            $plugins[$currentindex + 1] = $plugins[$currentindex];
            $plugins[$currentindex] = $tempplugin;
        }

        foreach ($plugins as $key => $plugin) {
            set_config('sortorder', $key, $this->subtype . '_' . $plugin);
        }

        return 'view';
    }

    /**
     * Show this plugin.
     *
     * @param string $plugin The plugin to show.
     * @return string
     */
    public function show_plugin($plugin) {
        $class = core_plugin_manager::resolve_plugininfo_class($this->subtype);
        $class::enable_plugin($plugin, true);

        return 'view';
    }

    /**
     * Entry point for this controller.
     *
     * @param string|null $action The action to perform.
     * @param string|null $plugin Optional plugin name to perform the action on.
     */
    public function execute($action, $plugin) {
        if ($action === null) {
            $action = 'view';
        }

        $this->check_permissions();

        if ($action === 'hide' && $plugin !== null) {
            $action = $this->hide_plugin($plugin);
        } else if ($action === 'show' && $plugin !== null) {
            $action = $this->show_plugin($plugin);
        } else if ($action === 'moveup' && $plugin !== null) {
            $action = $this->move_plugin($plugin, 'up');
        } else if ($action === 'movedown' && $plugin !== null) {
            $action = $this->move_plugin($plugin, 'down');
        }

        if ($action === 'view') {
            $this->view_plugins_table();
        }
    }
}
