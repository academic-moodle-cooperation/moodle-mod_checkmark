<?php
/**
 * db/events.php
 *
 * @package       mod_checkmark
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$handlers = array();

/* List of events thrown from checkmark module

checkmark_finalize_sent - object course, object user, object cm, object checkmark, fileareaname
checkmark_file_sent     - object course, object user, object cm, object checkmark, object file

*/
