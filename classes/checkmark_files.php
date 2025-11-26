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
 * Renderable checkmark files class.
 *
 * @package   mod_checkmark
 * @author    Daniel Binder (Based on the work of NetSpot {@link http://www.netspot.com.au})
 * @copyright 2020 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_checkmark;

use renderable;

/**
 * A class that extends rendererable class and is used by the checkmark module.
 *
 * @package   mod_checkmark
 * @author    Daniel Binder (Based on the work of NetSpot {@link http://www.netspot.com.au})
 * @copyright 2020 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class checkmark_files implements renderable {
    /** @var \context $context */
    public $context;
    /** @var array $dir */
    public $dir;
    /** @var \stdClass $cm course module */
    public $cm;
    /** @var \stdClass $course */
    public $course;
    /** @var string $filearea */
    public $filearea;
    /** @var string $component */
    public $component;

    /**
     * The constructor.
     *
     * @param \context $context
     * @param int $sid
     * @param string $filearea
     * @param string $component
     */
    public function __construct(\context $context, $sid, string $filearea, string $component) {
        $this->context = $context;
        $this->filearea = $filearea;
        $this->component = $component;
        [$context, $course, $cm] = \get_context_info_array($context->id);
        $this->cm = $cm;
        $this->course = $course;
        $fs = \get_file_storage();
        $this->dir = $fs->get_area_tree($context->id, $component, $filearea, $sid);
    }
}
