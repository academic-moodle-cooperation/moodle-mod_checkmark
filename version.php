<?php
/**
 * version.php
 * Defines the version of checkmark
 *
 * This code fragment is called by moodle_needs_upgrading() and
 * /admin/index.php
 *
 * @package       mod_checkmark
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$module->version  = 2013112500;
$module->requires = 2013051400.03;  // Requires this Moodle version!
$module->cron     = 60;

