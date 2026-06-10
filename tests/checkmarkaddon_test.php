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
 * Tests for Checkmark add-on subplugin support.
 *
 * @package   mod_checkmark
 * @category  test
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_checkmark;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/checkmark/adminlib.php');

/**
 * Tests for Checkmark add-on subplugin support.
 *
 * @package   mod_checkmark
 * @category  test
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_checkmark\plugininfo\checkmarkaddon::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\checkmark_plugin_manager::class)]
final class checkmarkaddon_test extends \advanced_testcase {
    /**
     * Checkmark declares the add-on subplugin type.
     */
    public function test_checkmarkaddon_type_is_registered(): void {
        $plugintypes = \core_component::get_plugin_types();
        $subplugins = \core_component::get_subplugins('mod_checkmark');

        $this->assertArrayHasKey('checkmarkaddon', $plugintypes);
        $this->assertStringEndsWith('/mod/checkmark/addon', $plugintypes['checkmarkaddon']);
        $this->assertArrayHasKey('checkmarkaddon', $subplugins);
        $this->assertIsArray($subplugins['checkmarkaddon']);
    }

    /**
     * Checkmark can discover and manage a test fixture add-on.
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function test_fixture_addon_can_be_discovered_and_managed(): void {
        $this->resetAfterTest(true);

        $this->add_simple_fixture_addon();

        $plugins = \core_component::get_plugin_list('checkmarkaddon');
        $this->assertArrayHasKey('simple', $plugins);
        $subplugins = \core_component::get_subplugins('mod_checkmark');
        $this->assertContains('simple', $subplugins['checkmarkaddon']);

        $plugininfo = \core_plugin_manager::instance()->get_plugin_info('checkmarkaddon_simple');
        $this->assertInstanceOf(\mod_checkmark\plugininfo\checkmarkaddon::class, $plugininfo);
        $this->assertSame('checkmarkaddon_simple', $plugininfo->get_settings_section_name());
        $this->assertSame('Simple Checkmark add-on', $plugininfo->displayname);
        $this->assertTrue($plugininfo->is_installed_and_upgraded());

        $manager = new \checkmark_plugin_manager('checkmarkaddon');
        $this->assertSame(['simple'], array_values($manager->get_sorted_plugins_list()));

        $manager->hide_plugin('simple');
        $this->assertSame(1, (int) get_config('checkmarkaddon_simple', 'disabled'));

        $manager->show_plugin('simple');
        $this->assertSame(0, (int) get_config('checkmarkaddon_simple', 'disabled'));
    }

    /**
     * Add the simple test fixture as an installed checkmark add-on.
     */
    private function add_simple_fixture_addon(): void {
        global $CFG;

        $plugindir = $CFG->dirroot . '/mod/checkmark/tests/fixtures/addon/simple';
        $this->add_mocked_plugin('checkmarkaddon', 'simple', $plugindir);

        $mockedcomponent = new \ReflectionClass(\core_component::class);
        $subplugins = $mockedcomponent->getStaticPropertyValue('subplugins');
        $subplugins['mod_checkmark']['checkmarkaddon'][] = 'simple';
        $mockedcomponent->setStaticPropertyValue('subplugins', $subplugins);

        $plugin = new \stdClass();
        require($plugindir . '/version.php');
        set_config('version', $plugin->version, $plugin->component);

        \core_plugin_manager::reset_caches(true);
    }
}
