<?php
/**
 * manual_cron.php
 * Portfolio-Class based upon checkmark
 *
 * @package       mod_checkmark
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

echo 'Includes...';
require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->dirroot . '/mod/checkmark/locallib.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/checkmark/submission_form.php');
require_once($CFG->dirroot.'/mod/checkmark/checkmark_pdf.php');
echo 'Cron';
if (checkmark_cron()) {
    echo 'Cron finished OK';
} else {
    echo 'Cron finished not OK';
}
