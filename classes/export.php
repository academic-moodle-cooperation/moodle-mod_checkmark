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
 * Special class holding all the export settings. Used to streamline event creation, etc.
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2018 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_checkmark;

/**
 * Special class holding all the export settings. Used to streamline event creation, etc.
 *
 * TODO: move every logic used for export settings here?!?
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2018 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class export {
    /** Portrait orientation in PDF */
    const PORTRAIT = MTablePDF::PORTRAIT;
    /** Landscape orientation in PDF */
    const LANDSCAPE = MTablePDF::PORTRAIT;

    /** Small sized font in PDF */
    const FONTSIZE_SMALL = MTablePDF::FONTSIZE_SMALL;
    /** Medium sized font in PDF */
    const FONTSIZE_MEDIUM = MTablePDF::FONTSIZE_MEDIUM;
    /** Large sized font in PDF */
    const FONTSIZE_LARGE = MTablePDF::FONTSIZE_LARGE;

    /** Output as PDF */
    const OUTPUT_FORMAT_PDF = MTablePDF::OUTPUT_FORMAT_PDF;
    /** Output as XLSX */
    const OUTPUT_FORMAT_XLSX = MTablePDF::OUTPUT_FORMAT_XLSX;
    /** Output as ODS */
    const OUTPUT_FORMAT_ODS = MTablePDF::OUTPUT_FORMAT_ODS;
    /** Output as comma separated CSV (separator is a semicolon, but nevermind) */
    const OUTPUT_FORMAT_CSV_COMMA = MTablePDF::OUTPUT_FORMAT_CSV_COMMA;
    /** Output as tab separated CSV */
    const OUTPUT_FORMAT_CSV_TAB = MTablePDF::OUTPUT_FORMAT_CSV_TAB;

    /** Output as per-group-PDFs zipped in an archive */
    const ZIPPED = MTablePDF::ZIPPED;
    /** Output as single uncompressed file */
    const UNCOMPRESSED = MTablePDF::UNCOMPRESSED;

    /** @var int[] */
    private $filters = [];
    /** @var int[] */
    private $formats = [];

    /** @var $groupmode int which groupmode is currently active */
    private $groupmode = null;
    /** @var $groupid int which group to export */
    private $groupid = null;
    /** @var $usrlst int[] further limitation of exported results */
    private $usrlst = null;
    /** @var $filter int which filter used for the export */
    private $filter = null;
    /** @var $filterreadable string Human readable filter name */
    private $filterreadable = null;
    /** @var $outputformat int which format to export into */
    private $format = null;
    /** @var $formatreadable string Human readable output format */
    private $formatreadable = null;
    /** @var $sumabs bool whether to export absolute values */
    private $sumabs = null;
    /** @var $sumrel bool whether to export relative values */
    private $sumrel = null;
    /** @var $seperatenamecolumns bool whether or not name fragments are exported in separate columns */
    private $seperatenamecolumns = false;

    // Additional PDF export settings!
    /** @var $orientation string page orientation to use for PDF output */
    private $orientation = self::PORTRAIT;
    /** @var $headerfooter bool if we should show header and footer in PDF */
    private $headerfooter = true;
    /** @var $fontsize int which fontsize to use (8 pt, 10 pt or 12 pt) in PDF */
    private $fontsize = self::FONTSIZE_MEDIUM;
    /** @var $perpage int how many rows a PDF page contains at max (0 = auto pagination) */
    private $perpage = 0;
    /** @var $singlelinenames bool whether or not names should be restricted on a single line */
    private $singlelinenames = false;


    /** @var $template string Template used for the export */
    private $template = '';

    /**
     * Constructor
     *
     */
    public function __construct() {
        $this->filters = \checkmark::get_possible_filters(true, true);
        $this->formats = \checkmark::get_formats();
    }

    /**
     * Set general data.
     *
     * @param int $groupmode either VISIBLEGROUPS, SEPARATEGROUPS or NOGROUPS
     * @param int $groupid groupid the export is limited to
     * @param int[] $selected selected users to export
     * @param int $filter one of the checkmark filter constants
     *            \\checkmark::FILTER\_(ALL|SUBMITTED|REQUIRE\_GRADING|ATTENDANT|ABSENT|UNKNOWN)
     * @param int $format one of checkmark's format constants
     *            \\mod_checkmark\\MTablePDF::OUTPUT\_FORMAT\_(PDF|XLSX|ODS|CSV\_(COMMA|TAB))
     * @param bool $sumabs
     * @param bool $sumrel
     * @param bool $seperatenamecolumns Indicates if name fragment should be printed in seperate columns
     */
    public function set_general_data($groupmode, $groupid, $selected, $filter, $format, $sumabs, $sumrel, $seperatenamecolumns) {
        $this->groupmode = $groupmode;
        $this->groupid = $groupid;
        $this->usrlst = $selected;
        $this->filter = $filter;
        $this->filterreadable = $this->filters[$filter];
        $this->format = $format;
        $this->formatreadable = $this->formats[$format];
        $this->sumabs = $sumabs;
        $this->sumrel = $sumrel;
        $this->seperatenamecolumns = $seperatenamecolumns;
    }

    /**
     * Set pdf-data.
     *
     * @param string $orientation either \mod_checkmark\MTablePDF::LANDSCAPE or \mod_checkmark\MTablePDF::PORTRAIT
     * @param bool $headerfooter
     * @param int $fontsize one of the constants: \\mod_checkmark\\MTablePDF::FONTSIZE\_(SMALL|MEDIUM|LARGE)
     * @param int $perpage
     * @param bool $singlelinenames
     * @param bool $sequentialnumbering
     */
    public function set_pdf_data($orientation, $headerfooter, $fontsize, $perpage,
                                 $singlelinenames, $sequentialnumbering) {
        $this->orientation = $orientation;
        $this->headerfooter = $headerfooter;
        $this->fontsize = $fontsize;
        $this->perpage = $perpage;
        $this->singlelinenames = $singlelinenames;
        $this->sequentialnumbering = $sequentialnumbering;
    }

    /**
     * Set used template.
     *
     * @param string $template
     */
    public function set_used_template($template) {
        $this->template = $template;
    }

    /**
     * Get event data.
     *
     * @return mixed[] The settings ready to be used for an export event!
     */
    public function get_event_data() {
        $data = [
            'groupmode' => $this->groupmode,
            'groupid' => $this->groupid,
            'selected' => $this->usrlst,
            'filter' => $this->filter,
            'filter_readable' => $this->filterreadable,
            'format' => $this->format,
            'format_readable' => $this->formatreadable,
            'sumabs' => $this->sumabs,
            'sumrel' => $this->sumrel,
        ];

        if ($this->format === self::OUTPUT_FORMAT_PDF) {
            $data += [
                'orientation' => $this->orientation,
                'printheader' => $this->headerfooter,
                'textsize' => $this->fontsize,
                'printperpage' => $this->perpage,
                'forcesinglelinenames' => $this->singlelinenames,
            ];
        }

        if ($this->template) {
            $data['template'] = $this->template;
        }

        return $data;
    }
}
