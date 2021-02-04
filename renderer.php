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
        $this->page->requires->js_init_call('M.mod_assign.init_tree', array(true, $this->htmlid));
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
        $yuiconfig = array();
        $yuiconfig['type'] = 'html';

        if (empty($dir['subdirs']) and empty($dir['files'])) {
            return '';
        }

        $result = '<ul>';
        foreach ($dir['subdirs'] as $subdir) {
            $image = $this->output->pix_icon(file_folder_icon(),
                    $subdir['dirname'],
                    'moodle',
                    array('class' => 'icon'));
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
                    array('class' => 'icon'));
            $result .= '<li yuiConfig=\'' . json_encode($yuiconfig) . '\'>' .
                    '<div>' .
                    '<div class="fileuploadsubmission">' . $image . ' ' .
                    $file->fileurl . ' ' .
                    '</div>' .
                    '<div class="fileuploadsubmissiontime">' . $file->timemodified . '</div>' .
                    '</div>' .
                    '</li>';
        }

        $result .= '</ul>';

        return $result;
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
        $row->cells = array($cell1, $cell2);
        $table->data[] = $row;
    }

    /**
     * Render a table containing the current status of the grading process and attendance.
     *
     * @param \mod_checkmark\gradingsummary $summary Information that should be displayed in the grading summary
     * @return string
     */
    public function render_checkmark_grading_summary( $summary) {
        // Create a table for the data.
        $o = '';
        $o .= $this->output->container_start('gradingsummary');
        $o .= $this->output->heading(get_string('gradingsummary', 'checkmark'), 3);
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

        // Submitted for grading.
        if (time() > $summary->timeavailable) {
            $cell1content = get_string('numberofsubmittedassignments', 'checkmark');
            $cell2content = $summary->submissionssubmittedcount;
            $this->add_table_row_tuple($t, $cell1content, $cell2content);
            $cell1content = get_string('numberofsubmissionsneedgrading', 'checkmark');
            $cell2content = $summary->submissionsneedgradingcount;
            $this->add_table_row_tuple($t, $cell1content, $cell2content);
        } else {
            $cell1content = get_string('allowsubmissionsfromdate', 'checkmark');
            $cell2content = userdate($summary->timeavailable);
            $this->add_table_row_tuple($t, $cell1content, $cell2content);
        }

        $time = time();
        if ($summary->duedate) {
            // Due date.
            $cell1content = get_string('duedate', 'checkmark');
            $duedate = $summary->duedate;
            $cell2content = userdate($duedate);
            }

            $this->add_table_row_tuple($t, $cell1content, $cell2content);

            // Time remaining.
            $cell1content = get_string('timeremaining', 'checkmark');
            if ($duedate - $time <= 0) {
                $cell2content = get_string('checkmarkisdue', 'checkmark');
            } else {
                $cell2content = format_time($duedate - $time);
            }

            $this->add_table_row_tuple($t, $cell1content, $cell2content);

            if ($duedate < $time) {
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
                $this->print_attandance_info($t, $summary);
            }
        } else {
            $this->print_attandance_info($t, $summary);
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
     * @param object $table  Table to add rows to
     * @param \mod_checkmark\gradingsummary $summary Information that should be displayed in the grading summary
     * @throws coding_exception
     */
    private function print_attandance_info ($table, $summary) {
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
        list($context, $course, $cm) = get_context_info_array($context->id);
        $this->cm = $cm;
        $this->course = $course;
        $fs = get_file_storage();
        $this->dir = $fs->get_area_tree($context->id, $component, $filearea, $sid);

        $files = $fs->get_area_files($context->id,
                $component,
                $filearea,
                $sid,
                'timemodified',
                false);

        $this->preprocess($this->dir, $filearea, $component);
    }

    /**
     * Preprocessing the file list
     *
     * @param array $dir
     * @param string $filearea
     * @param string $component
     * @return void
     */
    public function preprocess($dir, $filearea, $component) {
        global $CFG;

        foreach ($dir['subdirs'] as $subdir) {
            $this->preprocess($subdir, $filearea, $component);
        }
        foreach ($dir['files'] as $file) {

            $file->timemodified = userdate(
                    $file->get_timemodified(),
                    get_string('strftimedatetime', 'langconfig')
            );
            $url = moodle_url::make_pluginfile_url($this->context->id, $component, $filearea, $file->get_itemid(),
                    $file->get_filepath(), $file->get_filename(), true);
            $filename = $file->get_filename();
            $file->fileurl = html_writer::link($url, $filename, [
                    'target' => '_blank',
            ]);
        }
    }
}