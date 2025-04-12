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
 * @author    Philipp Hager, Daniel Binder
 * @copyright 2020 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_checkmark;

/**
 * This class contains the grading form for checkmark-submissions
 *
 * @package   mod_checkmark
 * @author    Philipp Hager, Daniel Binder
 * @copyright 2020 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class exportform extends \moodleform {
    /** available export formats */
    const FORMATS = [
        MTablePDF::OUTPUT_FORMAT_PDF        => 'PDF',
        MTablePDF::OUTPUT_FORMAT_XLSX       => 'XLSX',
        MTablePDF::OUTPUT_FORMAT_ODS        => 'ODS',
        MTablePDF::OUTPUT_FORMAT_CSV_COMMA  => 'CSV (;)',
        MTablePDF::OUTPUT_FORMAT_CSV_TAB    => 'CSV (tab)',
    ];

    /** @var  \stdClass course module object */
    protected $cm;
    /** @var  \context_module object */
    protected $context;
    /** @var int amount of examples present in this checkmark instance */
    protected $examplescount = 0;
    /** @var string HTML snippet representing data table */
    protected $table = '';

    /**
     * Definition of the grading form.
     */
    public function definition() {
        global $OUTPUT;
        $mform =& $this->_form;

        $formattr = $mform->getAttributes();
        $formattr['id'] = 'submitform';
        $mform->setAttributes($formattr);

        $this->cm = $this->_customdata['cm'];
        $this->context = $this->_customdata['context'];
        $this->examplescount = $this->_customdata['examplescount'];
        $this->table = $this->_customdata['table'];
        $filters = $this->_customdata['filters'];
        $filter = get_user_preferences('checkmark_filter_export', \checkmark::FILTER_ALL);

        // Here come the hidden params!
        $mform->addElement('hidden', 'updatepref');
        $mform->setDefault('updatepref', 1);
        $mform->setType('updatepref', PARAM_BOOL);

        $mform->addElement('header', 'data_settings_header', get_string('datasettingstitle', 'checkmark'));
        $mform->addElement('select', 'datafilter', get_string('filter'),  $filters);
        $mform->setDefault('datafilter', $filter);

        $this->add_groupselect();

        $mform->addElement('checkbox', 'seperatenamecolumns', get_string('seperatenamecolumns', 'checkmark'));
        $mform->addHelpButton('seperatenamecolumns', 'seperatenamecolumns', 'checkmark');

        $summarygrp = [
            $mform->createElement('advcheckbox', 'sumabs', '', get_string('summary_abs', 'checkmark'), ['group' => 3]),
            $mform->createElement('advcheckbox', 'sumrel', '', get_string('summary_rel', 'checkmark'), ['group' => 3]),
        ];
        $mform->addGroup($summarygrp, 'summarygrp', get_string('checksummary', 'checkmark'), ['<br />'], false);

        $mform->addElement('submit', 'submitdataview', get_string('strrefreshdata', 'checkmark'));

        $mform->addElement('header', 'print_settings_header', get_string('printsettingstitle', 'checkmark'));
        $mform->setExpanded('print_settings_header');

        $warninglvl = get_config('checkmark', 'pdfexampleswarning');
        if (!empty($warninglvl) && ($this->examplescount >= $warninglvl)) {
            /* TODO maybe we could replace this fixed value someday with some sort of calculation
               how much space would be needed and the info to hide some columns */
            $mform->addElement('html', $OUTPUT->notification(get_string('manycolumnsinpdfwarning', 'checkmark'), 'notifymessage'));
        }

        // Format?
        $mform->addElement('select', 'format', get_string('format', 'checkmark'), self::FORMATS);

        $mform->addElement('advcheckbox', 'zipped', '', get_string('zippedgrouppdfs', 'checkmark'), null, [
                MTablePDF::UNCOMPRESSED,
                MTablePDF::ZIPPED,
        ]);
        $mform->hideIf('zipped', 'format', 'neq', MTablePDF::OUTPUT_FORMAT_PDF);
        $mform->hideIf('zipped', 'group', 'neq', 0);

        // Select course title as long name or short name.
        $mform->addElement('select', 'coursetitle', get_string('coursetitle', 'checkmark'), [
            'courseshortname' => get_string('courseshortname', 'checkmark'),
            'courselongname' => get_string('coursefullname', 'checkmark'),
        ]);
        $mform->addHelpButton('coursetitle', 'coursetitle', 'checkmark');
        $mform->setDefault('coursetitle', 'courseshortname');

        $templatenames = self::get_templates();
        $templates = [];
        foreach ($templatenames as $cur) {
            $templates[$cur] = get_string('exporttemplate_'.$cur, 'checkmark');
        }
        $templates[''] = get_string('custom_settings', 'checkmark');
        $mform->addElement('select', 'template', get_string('exporttemplates', 'checkmark'), $templates);
        $mform->addHelpButton('template', 'exporttemplates', 'checkmark');
        $mform->setDefault('template', '');

        // How many submissions per page?
        $pppgroup = [
            $mform->createElement('text', 'printperpage', get_string('pdfpagesize', 'checkmark'), ['size' => 3]),
            $mform->createElement('advcheckbox', 'printoptimum', '', get_string('optimum', 'checkmark'), ['group' => 2]),
        ];
        $mform->addGroup($pppgroup, 'printperpagegrp', get_string('pdfpagesize', 'checkmark'), ' ', false);
        $mform->addHelpButton('printperpagegrp', 'pdfpagesize', 'checkmark');
        $mform->setType('printperpage', PARAM_INT);
        $mform->disabledIf('printperpage', 'printoptimum', 'checked');
        $mform->hideIf('printperpagegrp', 'format', 'neq', MTablePDF::OUTPUT_FORMAT_PDF);

        $textsizes = [
            MTablePDF::FONTSIZE_SMALL => get_string('strsmall', 'checkmark'),
            MTablePDF::FONTSIZE_MEDIUM => get_string('strmedium', 'checkmark'),
            MTablePDF::FONTSIZE_LARGE => get_string('strlarge', 'checkmark'),
        ];
        $mform->addElement('select', 'textsize', get_string('pdftextsize', 'checkmark'), $textsizes);
        $mform->hideIf('textsize', 'format', 'neq', MTablePDF::OUTPUT_FORMAT_PDF);
        $mform->hideIf('textsize', 'template', 'neq', '');

        $pageorientations = [
            MTablePDF::LANDSCAPE => get_string('strlandscape', 'checkmark'),
            MTablePDF::PORTRAIT => get_string('strportrait', 'checkmark'),
        ];
        $mform->addElement('select', 'pageorientation', get_string('pdfpageorientation', 'checkmark'), $pageorientations);
        $mform->hideIf('pageorientation', 'format', 'neq', MTablePDF::OUTPUT_FORMAT_PDF);
        $mform->hideIf('pageorientation', 'template', 'neq', '');

        $mform->addElement('checkbox', 'printheader', get_string('pdfprintheader', 'checkmark'));
        $mform->addHelpButton('printheader', 'pdfprintheader', 'checkmark');
        $mform->hideIf('printheader', 'format', 'neq', MTablePDF::OUTPUT_FORMAT_PDF);
        $mform->hideIf('printheader', 'template', 'neq', '');

        $mform->addElement('checkbox', 'forcesinglelinenames', get_string('forcesinglelinenames', 'checkmark'));
        $mform->addHelpButton('forcesinglelinenames', 'forcesinglelinenames', 'checkmark');
        $mform->hideIf('forcesinglelinenames', 'format', 'neq', MTablePDF::OUTPUT_FORMAT_PDF);
        $mform->hideIf('forcesinglelinenames', 'template', 'neq', '');

        $mform->addElement('checkbox', 'sequentialnumbering', get_string('sequentialnumbering', 'checkmark'));
        $mform->addHelpButton('sequentialnumbering', 'sequentialnumbering', 'checkmark');
        $mform->hideIf('sequentialnumbering', 'format', 'neq', MTablePDF::OUTPUT_FORMAT_PDF);
        $mform->hideIf('sequentialnumbering', 'template', 'neq', '');

        $mform->addElement('submit', 'export', get_string('export', 'checkmark'));

        $mform->addElement('header', 'data_preview_header', get_string('data_preview', 'checkmark'));
        $mform->addHelpButton('data_preview_header', 'data_preview', 'checkmark');
        $mform->setExpanded('data_preview_header');
        $mform->addElement('html', $this->table);

    }

    /**
     * Adds the group select element (according to group mode)!
     */
    protected function add_groupselect() {
        global $USER;

        $mform = $this->_form;

        $groupmode = groups_get_activity_groupmode($this->cm);
        if ($groupmode == NOGROUPS) {
            return;
        }

        $aag = has_capability('moodle/site:accessallgroups', $this->context);
        if ($groupmode == VISIBLEGROUPS || $aag) {
            // Any group in grouping!
            $allowedgroups = groups_get_all_groups($this->cm->course, 0, $this->cm->groupingid);
        } else {
            // Only assigned groups!
            $allowedgroups = groups_get_all_groups($this->cm->course, $USER->id, $this->cm->groupingid);
        }

        $activegroup = groups_get_activity_group($this->cm, true, $allowedgroups);

        $groupsmenu = [];
        if ((!$allowedgroups || $groupmode == VISIBLEGROUPS || $aag)) {
            $groupsmenu[0] = get_string('allparticipants');
        }

        if ($allowedgroups) {
            foreach ($allowedgroups as $group) {
                $groupsmenu[$group->id] = format_string($group->name);
            }
        }

        if ($groupmode == VISIBLEGROUPS) {
            $grouplabel = get_string('groupsvisible');
        } else {
            $grouplabel = get_string('groupsseparate');
        }

        if ($aag && $this->cm->groupingid) {
            if ($grouping = groups_get_grouping($this->cm->groupingid)) {
                $grouplabel = $grouplabel . ' (' . format_string($grouping->name) . ')';
            }
        }

        if (count($groupsmenu) == 1) {
            $groupname = reset($groupsmenu);
            $mform->addElement('static', 'group', $grouplabel, $groupname);
        } else {
            $mform->addElement('select', 'group', $grouplabel, $groupsmenu);
            $mform->setDefault('group', $activegroup);
        }
    }

    /**
     * Returns the available export templates sorted in alphabetical order.
     *
     * @return string[] available template-names
     */
    public static function get_templates() {
        global $CFG;

        $dir = scandir($CFG->dirroot.'/mod/checkmark/classes/local/exporttemplates');
        $templates = [];
        foreach ($dir as $cur) {
            if (in_array($cur, ['.', '..'])) {
                continue;
            }
            $cur = str_replace('.php', '', $cur);
            $classname = '\\mod_checkmark\\local\\exporttemplates\\'.$cur;
            if (class_exists($classname)) {
                $templates[] = $cur;
            }
        }
        sort($templates);

        return $templates;
    }


    /**
     * Validates current checkmark settings
     *
     * TODO use this form correctly to enable usage of validation etc, currently we use only submitted values via optional_param()!
     *
     * @param array $data data from the module form
     * @param array $files data about files transmitted by the module form
     * @return string[] array of error messages, to be displayed at the form fields
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }
}
