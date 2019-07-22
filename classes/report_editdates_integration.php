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

defined('MOODLE_INTERNAL') || die;


require_once($CFG->dirroot.'/mod/checkmark/locallib.php');

class mod_checkmark_report_editdates_integration
extends report_editdates_mod_date_extractor {

    public function __construct($course) {
        parent::__construct($course, 'checkmark');
        parent::load_data();
    }

    public function get_settings(cm_info $cm) {
        $checkmark = $this->mods[$cm->instance];

        return array(
                'timeavailable' => new report_editdates_date_setting(
                        get_string('availabledate', 'checkmark'),
                        $checkmark->timeavailable,
                        self::DATETIME, true, 5),
                'timedue' => new report_editdates_date_setting(
                        get_string('duedate', 'checkmark'),
                        $checkmark->timedue,
                        self::DATETIME, true, 5),
                'cutoffdate' => new report_editdates_date_setting(
                        get_string('cutoffdate', 'checkmark'),
                        $checkmark->cutoffdate,
                        self::DATETIME, true, 5),
                'gradingduedate' => new report_editdates_date_setting(
                        get_string('gradingdue', 'checkmark'),
                        $checkmark->gradingdue,
                        self::DATETIME, true, 5),
                );
    }

    public function validate_dates(cm_info $cm, array $dates) {
        $errors = array();
        if ($dates['timeavailable'] && $dates['timedue']
                && $dates['timedue'] < $dates['timeavailable']) {
            $errors['timedue'] = get_string('duedatevalidation', 'assign');
        }
        if ($dates['timedue'] && $dates['cutoffdate']) {
            if ($dates['timedue'] > $dates['cutoffdate']) {
                $errors['cutoffdate'] = get_string('cutoffdatevalidation', 'assign');
            }
        }
        if ($dates['timeavailable'] && $dates['cutoffdate']) {
            if ($dates['timeavailable'] > $dates['cutoffdate']) {
                $errors['cutoffdate'] = get_string('cutoffdatefromdatevalidation', 'assign');
            }
        }

        if ($dates['timedue'] && $dates['gradingduedate']
                && $dates['timedue'] > $dates['gradingduedate']) {
            $errors['gradingduedate'] = get_string('gradingdueduedatevalidation', 'assign');
        }
        if ($dates['timeavailable'] && $dates['gradingduedate'] &&
            $dates['timeavailable'] > $dates['gradingduedate']) {
            $errors['gradingduedate'] = get_string('gradingduefromdatevalidation', 'assign');
        }

        return $errors;
    }

    public function save_dates(cm_info $cm, array $dates) {
        global $DB, $COURSE;

        $update = new stdClass();
        $update->id = $cm->instance;
        $update->timedue = $dates['timedue'];
        $update->timeavailable = $dates['timeavailable'];
        $update->cutoffdate = $dates['cutoffdate'];
        $update->gradingdue  = $dates['gradingduedate'];

        $result = $DB->update_record('checkmark', $update);

        checkmark_refresh_events(0, $cm->instance);
    }
}
