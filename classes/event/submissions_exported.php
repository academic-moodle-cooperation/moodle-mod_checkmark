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
 * The mod_checkmark_submissions_exported event.
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_checkmark\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Submissions have been exported in this event.
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submissions_exported extends \core\event\base {
    /**
     * Init method.
     *
     * Please override this in extending class and specify objecttable.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'checkmark';
    }

    /**
     * Create event object and return it.
     *
     *
     * @param \stdClass $cm course module object
     * @param \mod_checkmark\export $exportsettings Export settings object
     * @return \core\event\base
     */
    public static function exported(\stdClass $cm, \mod_checkmark\export $exportsettings) {
        $event = static::create([
            'objectid' => $cm->instance,
            'context'  => \context_module::instance($cm->id),
            'other'    => $exportsettings->get_event_data(),
        ]);

        return $event;
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        if ($this->data['other']['groupmode'] != NOGROUPS) {
            $group = ' for group with id \''.$this->data['other']['groupid'].'\'';
        } else {
            $group = '';
        }

        if (key_exists('template', $this->data['other'])) {
            $templatestr = " using template '".
                get_string('exporttemplate_'.$this->data['other']['template'], 'checkmark')."'";
        } else {
            $templatestr = '';
        }

        return "The user with id '$this->userid' exported the submissions for '{$this->objecttable}' with the ".
            "course module id '$this->contextinstanceid'".$group.$templatestr." as '".
            $this->data['other']['format_readable']."'.";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventsubmissionsexported', 'checkmark');
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        $params = [
            'id' => $this->contextinstanceid,
            'format' => $this->data['other']['format'],
            'groupmode' => $this->data['other']['groupmode'],
            'groupid' => $this->data['other']['groupid'],
            'datafilter' => $this->data['other']['filter']
        ];
        foreach ($this->data['other']['selected'] as $cur) {
            $params['selected['.$cur.']'] = $cur;
        }
        if ($this->data['other']['format'] == \mod_checkmark\MTablePDF::OUTPUT_FORMAT_PDF) {
            $params['pageorientation'] = $this->data['other']['orientation'];
            $params['printheader'] = $this->data['other']['printheader'];
            $params['printperpage'] = $this->data['other']['printperpage'];
            $params['textsize'] = $this->data['other']['textsize'];
        }
        return new \moodle_url("/mod/$this->objecttable/export.php", $params);
    }

    /**
     * Return the legacy event log data.
     *
     * @return array|null
     */
    protected function get_legacy_logdata() {
        return [
            $this->courseid,
            $this->objecttable,
            'export '.$this->data['other']['format_readable'],
            (new \moodle_url("/mod/checkmark/export.php", [
                'id' => $this->contextinstanceid,
                'groupid' => $this->data['other']['groupid'],
                'format' => $this->data['other']['format']
            ]))->out(),
            '',
            $this->contextinstanceid
        ];
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();
        // Make sure this class is never used without proper object details.
        if (empty($this->objectid) || empty($this->objecttable)) {
            throw new \coding_exception('The submissions_exported event must define objectid and object table.');
        }
        // Make sure the context level is set to module.
        if ($this->contextlevel != CONTEXT_MODULE) {
            throw new \coding_exception('Context level must be CONTEXT_MODULE.');
        }

        if (!key_exists('format', $this->data['other'])) {
            throw new \coding_exception('Format has to be specified!');
        } else if ($this->data['other']['format'] == \mod_checkmark\MTablePDF::OUTPUT_FORMAT_PDF) {
            if (!key_exists('printperpage', $this->data['other'])) {
                throw new \coding_exception('Entries per page have to be specified!');
            }

            if (!key_exists('orientation', $this->data['other'])) {
                throw new \coding_exception('PDF-Orientation has to be specified!');
            }

            if (!key_exists('textsize', $this->data['other'])) {
                throw new \coding_exception('Textsize has to be specified!');
            }

            if (!key_exists('printheader', $this->data['other'])) {
                throw new \coding_exception('Print-header-key is missing!');
            }
        }

        if (!key_exists('format_readable', $this->data['other'])) {
            throw new \coding_exception('Format (readable) has to be specified!');
        }

        if (!key_exists('groupmode', $this->data['other'])) {
            throw new \coding_exception('Groupmode-Key missing!');
        }

        if (!key_exists('groupid', $this->data['other'])) {
            throw new \coding_exception('Group-ID-Key missing!');
        }

        if (!key_exists('selected', $this->data['other'])) {
            throw new \coding_exception('Selected users have to be specified!');
        }

        if (!key_exists('filter', $this->data['other'])) {
            throw new \coding_exception('Filter has to be specified!');
        }

        if (!key_exists('filter_readable', $this->data['other'])) {
            throw new \coding_exception('Filter (readable) has to be specified!');
        }

        if (!key_exists('sumabs', $this->data['other'])) {
            throw new \coding_exception('Summary (absolute) key is missing!');
        }
    }
}
