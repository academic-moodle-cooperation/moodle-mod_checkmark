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
 * This file contains a renderer for the checkmark class
 *
 * @package   mod_checkmark
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * renderer.php
 * This file contains a renderer for the checkmark class
 *
 * @package   mod_checkmark
 * @author    Daniel Binder (Based on the work of NetSpot {@link http://www.netspot.com.au})
 * @copyright 2020 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_checkmark\output\override_actionmenu;
use mod_checkmark\output\checkmark_header;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/checkmark/locallib.php');


/**
 * A custom renderer class that extends the plugin_renderer_base and is used by the checkmark module.
 *
 * @package   mod_checkmark
 * @author    Daniel Binder (Based on the work of NetSpot {@link http://www.netspot.com.au})
 * @copyright 2020 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_checkmark_renderer extends plugin_renderer_base {

    /** @var string a unique ID. */
    public $htmlid;

    /**
     * Rendering checkmark files
     *
     * @param context $context
     * @param int $userid
     * @param string $filearea
     * @param string $component
     * @return string
     */
    public function checkmark_files(context $context, $userid, $filearea, $component) {
        return $this->render(new checkmark_files($context, $userid, $filearea, $component));
    }

    /**
     * Rendering checkmark files
     *
     * @param checkmark_files $tree
     * @return string
     */
    public function render_checkmark_files(checkmark_files $tree) {
        $this->htmlid = html_writer::random_id('checkmark_files_tree');
        $this->page->requires->js_init_call('M.mod_assign.init_tree', [true, $this->htmlid]);
        $html = '<div id="' . $this->htmlid . '">';
        $html .= $this->htmllize_tree($tree, $tree->dir);
        $html .= '</div>';

        return $html;
    }

    /**
     * Internal function - creates htmls structure suitable for YUI tree.
     *
     * @param checkmark_files $tree
     * @param array $dir
     * @return string
     */
    protected function htmllize_tree(checkmark_files $tree, $dir) {
        global $CFG;
        $yuiconfig = [];
        $yuiconfig['type'] = 'html';

        if (empty($dir['subdirs']) && empty($dir['files'])) {
            return '';
        }

        $result = '<ul>';
        foreach ($dir['subdirs'] as $subdir) {
            $image = $this->output->pix_icon(file_folder_icon(),
                    $subdir['dirname'],
                    'moodle',
                    ['class' => 'icon']);
            $result .= '<li yuiConfig=\'' . json_encode($yuiconfig) . '\'>' .
                    '<div>' . $image . ' ' . s($subdir['dirname']) . '</div> ' .
                    $this->htmllize_tree($tree, $subdir) .
                    '</li>';
        }

        foreach ($dir['files'] as $file) {
            $filename = $file->get_filename();
            $image = $this->output->pix_icon(file_file_icon($file),
                    $filename,
                    'moodle',
                    ['class' => 'icon']);
            // Preprocess the date displayed.
            $timemodified = userdate(
                $file->get_timemodified(),
                get_string('strftimedatetime', 'langconfig')
            );
            // Create the URL for the file.
            $url = moodle_url::make_pluginfile_url($tree->context->id, $tree->component, $tree->filearea, $file->get_itemid(),
                    $file->get_filepath(), $filename, true);
            $fileurl = html_writer::link($url, $filename, [
                    'target' => '_blank',
            ]);
            $result .= '<li yuiConfig=\'' . json_encode($yuiconfig) . '\'>' .
                '<div>' .
                    '<div class="fileuploadsubmission">' . $image . ' ' .
                    $fileurl . ' ' .
                    '</div>' .
                    '<div class="fileuploadsubmissiontime">' . $timemodified . '</div>' .
                '</div>' .
            '</li>';
        }

        $result .= '</ul>';

        return $result;
    }

    /**
     * Render the header.
     *
     * @param checkmark_header $header
     * @return string
     */
    public function render_checkmark_header(checkmark_header $header) {
        $o = '';
        $description = '';

        // Manually edit the activity header by adding the description.
        $activityheader = $this->page->activityheader;
        if ($header->showintro) {
            $description .= format_module_intro('checkmark', $header->checkmark, $header->coursemoduleid);
        }
        $description .= $header->postfix;
        $activityheader->set_attrs([
            'description' => $description,
        ]);

        $o .= $this->output->header();
        return $o;
    }


    /**
     * Utility function to add a row of data to a table with 2 columns where the first column is the table's header.
     * Modified the table param and does not return a value.
     *
     * @param html_table $table The table to append the row of data to
     * @param string $first The first column text
     * @param string $second The second column text
     * @param array $firstattributes The first column attributes (optional)
     * @param array $secondattributes The second column attributes (optional)
     * @return void
     */
    private function add_table_row_tuple(html_table $table, $first, $second, $firstattributes = [],
            $secondattributes = []) {
        $row = new html_table_row();
        $cell1 = new html_table_cell($first);
        $cell1->header = true;
        if (!empty($firstattributes)) {
            $cell1->attributes = $firstattributes;
        }
        $cell2 = new html_table_cell($second);
        if (!empty($secondattributes)) {
            $cell2->attributes = $secondattributes;
        }
        $row->cells = [$cell1, $cell2];
        $table->data[] = $row;
    }

    /**
     * Render a table containing the current status of the grading process and attendance.
     *
     * @param \mod_checkmark\gradingsummary $summary Information that should be displayed in the grading summary
     * @param object $cm Course module object of the calling activity
     * @return string
     * @throws coding_exception
     */
    public function render_checkmark_grading_summary($summary, $cm) {
        // Create a table for the data.
        global $CFG;

        $o = '';
        $o .= $this->output->container_start('header-maxwidth', 'gradingsummary');
        $o .= $this->output->heading(get_string('gradingsummary', 'checkmark'), 3);
        $o .= groups_print_activity_menu($cm,
            $CFG->wwwroot . '/mod/checkmark/view.php?id=' . $cm->id, true);
        $o .= $this->output->box_start('boxaligncenter gradingsummarytable');
        $t = new html_table();

        // Visibility Status.
        $cell1content = get_string('hiddenfromstudents');
        $cell2content = (!$summary->isvisible) ? get_string('yes') : get_string('no');
        $this->add_table_row_tuple($t, $cell1content, $cell2content);

        // Status.
        $cell1content = get_string('numberofparticipants', 'checkmark');
        $cell2content = $summary->participantcount;
        $this->add_table_row_tuple($t, $cell1content, $cell2content);

        $urlbase = $CFG->wwwroot . '/mod/checkmark/submissions.php?id=';
        // Submitted for grading.
        if (time() > $summary->timeavailable) {
            $cell1content = get_string('numberofsubmittedassignments', 'checkmark');
            $cell2content = $summary->submissionssubmittedcount;
            $linkcell2 = html_writer::tag('a', $cell2content, [
                'class' => 'link',
                'href' => $urlbase . $cm->id. '&updatepref=1' . '&filter=2',
            ]);
            $this->add_table_row_tuple($t, $cell1content, $linkcell2);
            $cell1content = get_string('numberofsubmissionsneedgrading', 'checkmark');
            $cell2content = $summary->submissionsneedgradingcount;
            $linkcell2 = html_writer::tag('a', $cell2content, [
                'class' => 'link',
                'href' => $urlbase . $cm->id . '&updatepref=1' . '&filter=3',
            ]);
            $this->add_table_row_tuple($t, $cell1content, $linkcell2);
        }

        $time = time();
        $duedate = null;
        if ($summary->duedate) {
            // Due date.
            $cell1content = get_string('duedate', 'checkmark');
            $duedate = $summary->duedate;

            // Time remaining.
            $cell1content = get_string('timeremaining', 'checkmark');
            if ($duedate - $time <= 0) {
                $cell2content = get_string('checkmarkisdue', 'checkmark');
            } else {
                $cell2content = format_time($duedate - $time);
            }
            $this->add_table_row_tuple($t, $cell1content, $cell2content);
        }

        // Show late submissions info if regular due date was reached or is not present.
        if ($duedate < $time || !$duedate) {
            $cell1content = get_string('latesubmissions', 'checkmark');
            $cutoffdate = $summary->cutoffdate;
            if ($cutoffdate) {
                if ($cutoffdate > $time) {
                    $cell2content = get_string('latesubmissionsaccepted', 'checkmark', userdate($summary->cutoffdate));
                } else {
                    $cell2content = get_string('nomoresubmissionsaccepted', 'checkmark');
                }

                $this->add_table_row_tuple($t, $cell1content, $cell2content);
            }
            $this->print_attandance_info($t, $summary, $cm);
        } else {
            $this->print_attandance_info($t, $summary, $cm);
        }

        // Show count of presentationgradings if presenationgrading is active.
        if ($summary->presentationgradingcount > 0) {
            $cell1content = get_string('presentationgradingcount', 'checkmark');
            $cell2content = $summary->presentationgradingcount;
            $this->add_table_row_tuple($t, $cell1content, $cell2content);
        }

        // All done - write the table.
        $o .= html_writer::table($t);
        $o .= $this->output->box_end();
        $o .= $this->output->container_end();
        return $o;
    }

    /**
     * Adds attendance/absence columns to the gradingsummary table if attendance is tracked
     *
     * @param html_table $table  Table to add rows to
     * @param \mod_checkmark\gradingsummary $summary Information that should be displayed in the grading summary
     * @param stdClass $cm
     * @throws coding_exception
     */
    private function print_attandance_info($table, $summary, $cm) {
        global $CFG;
        if ($summary->attendantcount > 0) {
            $cell1content = get_string('attendance', 'checkmark');
            $cell2content = $summary->attendantcount;
            $this->add_table_row_tuple($table, $cell1content, $cell2content);
        }
        if ($summary->absencecount > 0) {
            $cell1content = get_string('absent', 'checkmark');
            $cell2content = $summary->absencecount;
            $this->add_table_row_tuple($table, $cell1content, $cell2content);
        }
        if ($summary->needattendanceentrycount > 0) {
            $cell1content = get_string('needattendanceentrycount', 'checkmark');
            $cell2content = $summary->needattendanceentrycount;
            $linkcell2 = html_writer::tag('a', $cell2content, [
                'class' => 'link',
                'href' => $CFG->wwwroot . '/mod/checkmark/submissions.php?id=' . $cm->id. '&updatepref=1' . '&filter=7',
            ]);
            $this->add_table_row_tuple($table, $cell1content, $linkcell2);
        }
    }
    /**
     * Renders the override action menu.
     *
     * @param \mod_checkmark\output\override_actionmenu $actionmenu The actionmenu
     * @return string The rendered override action menu.
     */
    public function override_actionmenu(\mod_checkmark\output\override_actionmenu $actionmenu): string {
        $context = $actionmenu->export_for_template($this);
        return $this->render_from_template('mod_checkmark/override_actionmenu', $context);
    }

    /**
     * Render a summary of the number of group and user overrides, with corresponding links.
     *
     * @param stdClass $checkmark the checkmark settings.
     * @param stdClass $cm the cm object.
     * @param int $currentgroup currently selected group, if there is one.
     * @return string HTML fragment for the link.
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function checkmark_override_summary_links(stdClass $checkmark, stdClass $cm, $currentgroup = 0): string {
        $baseurl = new moodle_url('/mod/checkmark/overrides.php', ['id' => $cm->id]);
        $counts = checkmark_override_summary($checkmark, $cm);

        $links = [];
        if ($counts['group']) {
            $links[] = html_writer::link(new moodle_url($baseurl, ['mode' => 'group']),
                get_string('overridessummarygroup', 'checkmark', $counts['group']));
        }
        if ($counts['user']) {
            $links[] = html_writer::link(new moodle_url($baseurl, ['mode' => 'user']),
                get_string('overridessummaryuser', 'checkmark', $counts['user']));
        }

        if (!$links) {
            return '';
        }

        $links = implode(', ', $links);
        switch ($counts['mode']) {
            case 'onegroup':
                return get_string('overridessummarythisgroup', 'checkmark', $links);
            case 'somegroups':
                return get_string('overridessummaryyourgroups', 'checkmark', $links);
            case 'allgroups':
                return get_string('overridessummary', 'checkmark', $links);
            default:
                throw new coding_exception('Unexpected mode ' . $counts['mode']);
        }
    }

    /**
     * Render a table containing the current status of the submission.
     *
     * @param array $status
     * @return string
     */
    public function render_checkmark_submission_status(array $status) {
        $o = '';
        $o .= $this->output->container_start('header-maxwidth', 'submissionstatustable');
        $o .= $this->output->heading(get_string('submissionstatusheading', 'checkmark'), 3);
        $time = time();

        $o .= $this->output->box_start('boxaligncenter submissionsummarytable');

        $t = new \html_table();
        $t->attributes['class'] = 'generaltable table-bordered';

        $warningmsg = '';

        // Submission Status TODO: classes.
        $cell1content = get_string('submissionstatus', 'checkmark');
        $cell2attributes = [];

        $cell2content = get_string('nosubmissionyet', 'checkmark');
        if ($status['submissionstatus'] == 'submitted') {
            $cell2content = get_string('submissionstatus_submitted', 'checkmark');
            $cell2attributes = ['class' => 'submissionstatus' . $status['submissionstatus']];
        }
        $this->add_table_row_tuple($t, $cell1content, $cell2content, [], $cell2attributes);

        // Grading status.
        $cell1content = get_string('gradingstatus', 'checkmark');
        if ($status['gradingstatus'] == "graded" || $status['gradingstatus'] == "notgraded") {
            $cell2content = get_string($status['gradingstatus'], 'checkmark');
        }

        if ($status['gradingstatus'] == "graded") {
                $cell2attributes = ['class' => 'submissiongraded'];
        } else {
            $cell2attributes = ['class' => 'submissionnotgraded'];
        }
        $this->add_table_row_tuple($t, $cell1content, $cell2content, [], $cell2attributes);

        // Time remaining.
        // Only add the row if there is a due date, or a countdown.
        $time = time();
        if ($status['timedue'] > 0 && !empty($status['timecreated'])) {
            $cell1content = get_string('timeremaining', 'checkmark');
            if ($status['timedue'] - $status['timemodified'] < 0) {
                [$cell2content, $cell2attributes] =
                    [
                        get_string('submittedlate', 'checkmark', format_time($status['timedue'] - $status['timemodified'])),
                        'latesubmission',
                    ];
            } else {
                [$cell2content, $cell2attributes] =
                    [
                        get_string('submittedearly', 'checkmark', format_time($status['timedue'] - $status['timemodified'])),
                        'earlysubmission',
                    ];
            }
            $this->add_table_row_tuple($t, $cell1content, $cell2content, [], ['class' => $cell2attributes]);
        } else if ($status['timedue'] - $time > 0) {
            $cell1content = get_string('timeremaining', 'checkmark');
            $cell2content = get_string('paramtimeremaining', 'checkmark', format_time($status['timedue'] - $time));
            $this->add_table_row_tuple($t, $cell1content, $cell2content);
        } else if ($status['timedue'] > 0) {
            $cell1content = get_string('timeremaining', 'checkmark');
            $cell2content = get_string('late', 'checkmark', format_time($time - $status['timedue']));
            $this->add_table_row_tuple($t, $cell1content, $cell2content);
        }

        // Last modified.
        $cell1content = get_string('timemodified', 'checkmark');

        if (!empty($status['timecreated'])) {
            $cell2content = userdate($status['timemodified']);
        } else {
            $cell2content = "-";
        }

        $this->add_table_row_tuple($t, $cell1content, $cell2content);

        // Checkmark info.
        $cell1content = get_string('checkmarks', 'checkmark');
        if (!empty($status['checkmarkinfo'])) {
            $cell2content = get_string('submissionstatus_checkmark_summary', 'checkmark', $status['checkmarkinfo']);
        } else {
            $cell2content = "-";
        }
        $this->add_table_row_tuple($t, $cell1content, $cell2content);

        $o .= $warningmsg;
        $o .= \html_writer::table($t);
        $o .= $this->output->box_end();

        $o .= $this->output->container_end();
        return $o;
    }

    /**
     * Render a table containing all the current grades and feedback.
     *
     * @param array $status
     * @return string
     */
    public function render_checkmark_feedback_status(array $status) {
        $o = '';

        $o .= $this->output->container_start('header-maxwidth', 'feedback');
        $o .= $this->output->heading(get_string('feedback', 'checkmark'), 3);
        $o .= $this->output->box_start('boxaligncenter feedbacktable');
        $t = new \html_table();
        $t->attributes['class'] = 'generaltable table-bordered';

        // Grade.
        if ($status['gradefordisplay']) {
            $cell1content = get_string('gradenoun');
            $cell2content = $status['gradefordisplay'];
            $this->add_table_row_tuple($t, $cell1content, $cell2content);

            // Grade date.
            $cell1content = get_string('gradedon', 'checkmark');
            $cell2content = userdate($status['dategraded']);
            $this->add_table_row_tuple($t, $cell1content, $cell2content);
        }

        // Feedback.
        $cell1content = get_string('feedback', 'checkmark');
        $cell2content = $status['feedback'];
        $this->add_table_row_tuple($t, $cell1content, $cell2content);

        $o .= \html_writer::table($t);
        $o .= $this->output->box_end();

        $o .= $this->output->container_end();
        return $o;
    }
}
/**
 * A class that extends rendererable class and is used by the checkmark module.
 *
 * @package   mod_checkmark
 * @author    Daniel Binder (Based on the work of NetSpot {@link http://www.netspot.com.au})
 * @copyright 2020 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class checkmark_files implements renderable {
    /** @var context $context */
    public $context;
    /** @var string $context */
    public $dir;
    /** @var stdClass $cm course module */
    public $cm;
    /** @var stdClass $course */
    public $course;
    /** @var string $filearea */
    public $filearea;
    /** @var string $component */
    public $component;

    /**
     * The constructor
     *
     * @param context $context
     * @param int $sid
     * @param string $filearea
     * @param string $component
     */
    public function __construct(context $context, $sid, $filearea, $component) {
        global $CFG;
        $this->context = $context;
        $this->filearea = $filearea;
        $this->component = $component;
        list($context, $course, $cm) = get_context_info_array($context->id);
        $this->cm = $cm;
        $this->course = $course;
        $fs = get_file_storage();
        $this->dir = $fs->get_area_tree($context->id, $component, $filearea, $sid);
    }
}
