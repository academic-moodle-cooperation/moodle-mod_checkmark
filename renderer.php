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