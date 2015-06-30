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
 * backup/moodle2/backup_checkmark_activity_task.class.php
 *
 * @package       mod_checkmark
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// Because it exists (must)!
require_once($CFG->dirroot.
             '/mod/checkmark/backup/moodle2/backup_checkmark_stepslib.php');

/**
 * checkmark backup task that provides all the settings and steps to perform one
 * complete backup of the activity
 */
class backup_checkmark_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity!
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Checkmark only has one structure step!
        $this->add_step(new backup_checkmark_activity_structure_step('checkmark_structure',
                                                                               'checkmark.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        // Link to the list of checkmarks!
        $search = '/('.$base.'\/mod\/checkmark\/index.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@CHECKMARKINDEX*$2@$', $content);
        // Link to checkmark view by moduleid!
        $search = '/('.$base.'\/mod\/checkmark\/view.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@CHECKMARKVIEWBYID*$2@$', $content);

        return $content;
    }
}
