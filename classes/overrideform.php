<?php
// This file is part of mtablepdf for Moodle - http://moodle.org/
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
 * exportform.php
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2017 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_checkmark;
use coding_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * This class contains the form for overriding deadlines
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2017 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overrideform extends \moodleform {
    /** user type overrideform */
    const USER = 'USER';
    /** group type overrideform */
    const GROUP = 'GROUP';
    /** add mode extend */
    const ADD = 'ADD';
    /** edit mode extend */
    const EDIT = 'EDIT';
    /** copy mode extend */
    const COPY = 'COPY';
    /** delete mode extend */
    const DELETE = 'DELETE';
    /** move up mode extend */
    const UP = 'UP';
    /** move down mode extend */
    const DOWN = 'DOWN';
    /** resets entries to activity defaults */
    const RESET = 'RESET';

    /** @var  \stdClass course module object */
    protected $cm;
    /** @var  \context_module object */
    protected $context;
    /** @var  int type of overrideform (USER or GROUP) */
    protected $type;
    /** @var  bool flag weather or not the current user is allowed to access all groups */
    protected $accessallgroups;

    /**
     * overrideform constructor.
     *
     * @param string $type either self::USER or self::GROUP!
     * @param string|\moodle_url|null $action
     * @param \stdClass|mixed[]|null $customdata
     * @param string $method
     * @param string $target
     * @param string[]|null $attributes
     * @param bool $editable
     * @param null $ajaxformdata
     */
    public function __construct($type, $action = null, $customdata = null, $method = 'post', $target = '', $attributes = null,
                                $editable = true, $ajaxformdata = null) {
        if (!in_array($type, [self::USER, self::GROUP])) {
            throw new coding_exception('invalidformdata');
        }
        $this->type = $type;
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $ajaxformdata);
    }

    /**
     * Definition of the grading form.
     */
    public function definition() {
        global $USER;

        $mform =& $this->_form;

        $formattr = $mform->getAttributes();
        $formattr['id'] = 'overrideform';
        $mform->setAttributes($formattr);

        $this->cm = $this->_customdata['cm'];
        $this->context = $this->_customdata['context'];
        $checkmark = $this->_customdata['checkmark'];

        // Check the user has the required capabilities to list overrides.
        require_capability('mod/checkmark:manageoverrides', $this->context);
        $cmgroupmode = groups_get_activity_groupmode($this->cm);
        $this->accessallgroups = ($cmgroupmode == NOGROUPS) ||
                has_capability('moodle/site:accessallgroups', $this->context);
        $mform =& $this->_form;

        $mform->addElement('hidden', 'orig_timeavailable', $checkmark->timeavailable);
        $mform->setType('orig_timeavailable', PARAM_INT);
        $mform->addElement('hidden', 'orig_timedue', $checkmark->timedue);
        $mform->setType('orig_timedue', PARAM_INT);
        $mform->addElement('hidden', 'orig_cutoffdate', $checkmark->cutoffdate);
        $mform->setType('orig_cutoffdate', PARAM_INT);

        $mform->addElement('header', 'override_header', get_string('override', 'checkmark'));
        $mform->setExpanded('override_header');

        $mform->addElement('hidden', 'id', $this->cm->id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'type', $this->type);
        $mform->setType('type', PARAM_ALPHA);

        $mform->addElement('hidden', 'return', $this->_customdata['return']);
        $mform->setType('return', PARAM_URL);

        switch ($this->type) {
            case self::USER:
                if ($this->accessallgroups) {
                    $users = get_enrolled_users($this->context, 'mod/checkmark:submit');
                } else {
                    $users = [];
                    foreach (groups_get_user_groups($this->cm->course) as $grouping) {
                        foreach ($grouping as $group) {
                            $users += groups_get_members($group);
                        }
                    }
                }
                foreach ($users as $userid => $cur) {
                    $users[$userid] = fullname($cur);
                }
                $userel = $mform->createElement('autocomplete', 'userids', get_string('users'), $users);
                $userel->setMultiple('userids', true);
                $mform->addElement($userel);
                $mform->addRule('userids', null, 'required', null, 'client');
                break;
            case self::GROUP:
                if ($this->accessallgroups) {
                    $groups = groups_get_all_groups($this->cm->course);
                } else {
                    $groups = groups_get_all_groups($this->cm->course, $USER->id);
                }
                foreach ($groups as $groupid => $cur) {
                    $groups[$groupid] = $cur->name;
                }
                $groupel = $mform->createElement('autocomplete', 'groups', get_string('groups'), $groups);
                $groupel->setMultiple('groups', true);
                $mform->addElement($groupel);
                $mform->addRule('groups', null, 'required', null, 'client');
                break;
        }

        $name = get_string('availabledate', 'checkmark');
        $mform->addElement('date_time_selector', 'timeavailable', $name, array('optional' => true));
        $mform->addHelpButton('timeavailable', 'availabledate', 'checkmark');
        $mform->setDefault('timeavailable', $checkmark->timeavailable);

        $name = get_string('duedate', 'checkmark');
        $mform->addElement('date_time_selector', 'timedue', $name, array('optional' => true));
        $mform->addHelpButton('timedue', 'duedate', 'checkmark');
        $mform->setDefault('timedue', $checkmark->timedue);

        $name = get_string('cutoffdate', 'checkmark');
        $mform->addElement('date_time_selector', 'cutoffdate', $name, array('optional' => true));
        $mform->addHelpButton('cutoffdate', 'cutoffdate', 'checkmark');
        $mform->setDefault('cutoffdate', $checkmark->cutoffdate);

        // Submit buttons.
        $mform->addElement('submit', 'resetbutton',
            get_string('reverttodefaults', 'checkmark'));

        $btngrp = [];
        $btngrp[] = $mform->createElement('submit', 'override', get_string('override', 'checkmark'));
        $btngrp[] = $mform->createElement('submit', 'override_and_next', get_string('override_and_next', 'checkmark'));
        $btngrp[] = $mform->createElement('cancel', 'cancel', get_string('cancel'));
        $mform->addGroup($btngrp, 'btngrp', '', ' ', false);
    }

    /**
     * Resets the present override form
     *
     * @return void
     */
    public function reset() {
        $this->_form->updateSubmission(null, null);
    }

    /**
     * Validates form content
     *
     * @param array $data data from the module form
     * @param array $files data about files transmitted by the module form
     * @return string[] array of error messages, to be displayed at the form fields
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['timeavailable'] && $data['timedue']) {
            if ($data['timeavailable'] > $data['timedue']) {
                $errors['timedue'] = get_string('duedatevalidation', 'assign');
            }
        }
        if ($data['timedue'] && $data['cutoffdate']) {
            if ($data['timedue'] > $data['cutoffdate']) {
                $errors['cutoffdate'] = get_string('cutoffdatevalidation', 'assign');
            }
        }
        if ($data['timedue'] && !$data['cutoffdate'] && $data['orig_cutoffdate']) {
            if ($data['timedue'] > $data['orig_cutoffdate']) {
                $errors['cutoffdate'] = get_string('cutoffdatevalidation', 'assign');
            }
        }
        if ($data['timeavailable'] && $data['cutoffdate']) {
            if ($data['timeavailable'] > $data['cutoffdate']) {
                $errors['cutoffdate'] = get_string('cutoffdatefromdatevalidation', 'assign');
            }
        }
        if ($data['timeavailable'] && !$data['cutoffdate'] && $data['orig_cutoffdate']) {
            if ($data['timeavailable'] > $data['orig_cutoffdate']) {
                $errors['cutoffdate'] = get_string('cutoffdatefromdatevalidation', 'assign');
            }
        }
        if ($this->_customdata['checkmark']) {
            $checkmark = $this->_customdata['checkmark'];
            if ($data['timeavailable'] == $checkmark->timeavailable &&
                    $data['timedue'] == $checkmark->timedue && $data['cutoffdate'] == $checkmark->cutoffdate) {
                $errors['timeavailable'] = get_string('nochangeviolation', 'checkmark');
            }
        }

        return $errors;
    }
}
