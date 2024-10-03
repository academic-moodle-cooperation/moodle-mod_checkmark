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
 * This file contains the definition for the renderable checkmark header.
 *
 * @package   mod_checkmark
 * @copyright 2024 Clemens Marx (AMC - Academic Moodle Cooperation)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_checkmark\output;

/**
 * This class contains the definition for the renderable checkmark header.
 *
 * @package   mod_checkmark
 * @author    Clemens Marx
 * @copyright 2024 Clemens Marx (AMC - Academic Moodle Cooperation)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class checkmark_header implements \renderable {
    /** @var \stdClass The checkmark record. */
    public $checkmark;
    /** @var bool $showintro Show or hide the intro. */
    public $showintro;
    /** @var int coursemoduleid The course module id. */
    public $coursemoduleid;
    /** @var string */
    public $postfix;

    /**
     * Constructor
     *
     * @param \stdClass $checkmark The checkmark database record.
     * @param bool $showintro Show or hide the intro.
     * @param int $coursemoduleid The course module id.
     * @param string $postfix Optional postfix (text to show after the intro).
     */
    public function __construct(\stdClass $checkmark, bool $showintro, int $coursemoduleid, string $postfix = '') {
        $this->checkmark = $checkmark;
        $this->showintro = $showintro;
        $this->coursemoduleid = $coursemoduleid;
        $this->postfix = $postfix;
    }
}
