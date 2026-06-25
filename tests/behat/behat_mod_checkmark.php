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
 * Behat custom steps and hooks for mod_checkmark.
 *
 * @package   mod_checkmark
 * @category  test
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Behat custom steps and hooks for mod_checkmark.
 *
 * @package   mod_checkmark
 * @category  test
 * @copyright 2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_checkmark extends behat_base {
    /**
     * Make the simple checkmark add-on fixture available for this scenario.
     *
     * @BeforeScenario @with_checkmarkaddon_simple
     */
    public function setup_simple_checkmarkaddon(): void {
        global $CFG;

        require_once($CFG->libdir . '/upgradelib.php');

        $pluginname = 'simple';
        $fixturepath = $CFG->dirroot . '/mod/checkmark/tests/fixtures/addon';
        $plugindir = $fixturepath . '/' . $pluginname;

        $mockedcomponent = new ReflectionClass(core_component::class);

        $plugins = $mockedcomponent->getStaticPropertyValue('plugins');
        $plugins['checkmarkaddon'][$pluginname] = $plugindir;
        $mockedcomponent->setStaticPropertyValue('plugins', $plugins);

        $plugintypes = $mockedcomponent->getStaticPropertyValue('plugintypes');
        $plugintypes['checkmarkaddon'] = $fixturepath;
        $mockedcomponent->setStaticPropertyValue('plugintypes', $plugintypes);

        $mockedcomponent->getMethod('fill_classmap_cache')->invoke(null);
        $mockedcomponent->getMethod('fill_filemap_cache')->invoke(null);

        $subplugins = $mockedcomponent->getStaticPropertyValue('subplugins');
        if (!in_array($pluginname, $subplugins['mod_checkmark']['checkmarkaddon'], true)) {
            $subplugins['mod_checkmark']['checkmarkaddon'][] = $pluginname;
        }
        $mockedcomponent->setStaticPropertyValue('subplugins', $subplugins);

        $content = core_component::get_cache_content();
        $this->write_fake_component_cache($content);

        ob_start();
        upgrade_noncore(false);
        upgrade_finished();
        ob_end_clean();

        // The upgrade clears caches, so write the fake component cache again.
        $this->write_fake_component_cache($content);

        $mockedcomponent->setStaticPropertyValue('plugintypes', null);
        $mockedcomponent->getMethod('init')->invoke(null);

        $manager = core_plugin_manager::resolve_plugininfo_class('checkmarkaddon');
        $manager::enable_plugin($pluginname, true);
    }

    /**
     * Remove the simple checkmark add-on fixture after this scenario.
     *
     * @AfterScenario @with_checkmarkaddon_simple
     */
    public function teardown_simple_checkmarkaddon(): void {
        global $CFG;

        unset_config('version', 'checkmarkaddon_simple');
        unset_config('disabled', 'checkmarkaddon_simple');
        unset_config('sortorder', 'checkmarkaddon_simple');
        unset_config('configtext', 'checkmarkaddon_simple');

        $cachefile = $CFG->cachedir . '/core_component.php';
        if (file_exists($cachefile)) {
            unlink($cachefile);
        }

        $mockedcomponent = new ReflectionClass(core_component::class);
        $mockedcomponent->setStaticPropertyValue('plugintypes', null);
        $mockedcomponent->getMethod('init')->invoke(null);

        core_plugin_manager::reset_caches();
    }

    /**
     * Write a fake component cache for browser requests in this Behat scenario.
     *
     * @param string $content The cache content.
     */
    private function write_fake_component_cache(string $content): void {
        global $CFG;

        $cachefile = $CFG->cachedir . '/core_component.php';
        if (file_exists($cachefile)) {
            unlink($cachefile);
        }

        $dirpermissions = $CFG->directorypermissions ?? 02777;
        $filepermissions = $CFG->filepermissions ?? ($dirpermissions & 0666);

        clearstatcache();
        $cachedir = dirname($cachefile);
        if (!is_dir($cachedir)) {
            mkdir($cachedir, $dirpermissions, true);
        }

        if ($fp = @fopen($cachefile . '.tmp', 'xb')) {
            fwrite($fp, $content);
            fclose($fp);
            @rename($cachefile . '.tmp', $cachefile);
            @chmod($cachefile, $filepermissions);
        }
        @unlink($cachefile . '.tmp');
        core_component::invalidate_opcode_php_cache($cachefile);
    }
}
