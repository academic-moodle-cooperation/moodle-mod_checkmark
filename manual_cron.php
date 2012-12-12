<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Portfolio-Class based upon checkmark
 *
 * @package       mod
 * @subpackage    checkmark
 * @author        Philipp Hager
 * @copyright     2011 Philipp Hager
 * @since         Moodle 2.1
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
echo "Includes...";
require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->dirroot . '/mod/checkmark/lib.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/checkmark/submission_form.php');
require_once($CFG->dirroot.'/mod/checkmark/checkmark_pdf.php');
echo "Cron";
if (checkmark_cron()) {
    echo "Cron finished OK";
} else {
    echo "Cron finished not OK";
}
