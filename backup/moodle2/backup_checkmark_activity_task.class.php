<?php
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
        $search='/('.$base.'\/mod\/checkmark\/index.php\?id\=)([0-9]+)/';
        $content= preg_replace($search, '$@CHECKMARKINDEX*$2@$', $content);
        // Link to checkmark view by moduleid!
        $search='/('.$base.'\/mod\/checkmark\/view.php\?id\=)([0-9]+)/';
        $content= preg_replace($search, '$@CHECKMARKVIEWBYID*$2@$', $content);

        return $content;
    }
}
