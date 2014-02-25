<?php
/**
 * db/install.php
 *
 * @package       mod_checkmark
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

define('CHECKMARK_UPGRADE_FROM_OLD', 0);
define('CHECKMARK_UPGRADE_DELETE_OLD', 0);

function xmldb_checkmark_install() {
    global $DB, $CFG, $OUTPUT;

    if (CHECKMARK_UPGRADE_FROM_OLD) {
        require_once($CFG->dirroot.'/mod/checkmark/db/upgradeoldcheckmarks.php');
    }
}
