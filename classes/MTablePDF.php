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
 * mtablepdf.php
 *
 * TODO maybe we should replace this library with a specialized export class, also improving design & co?
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_checkmark;

use context_course;
use context_system;

defined('MOODLE_INTERNAL') || die();

if (isset($CFG)) {
    require_once($CFG->libdir . '/pdflib.php');
}

/**
 * MTablePDF class handles exports to PDF, XLSX, ODS, CSV...
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class MTablePDF extends \pdf {
    /** Portrait orientation in PDF */
    const PORTRAIT = 'P';
    /** Landscape orientation in PDF */
    const LANDSCAPE = 'L';

    /** Small sized font in PDF */
    const FONTSIZE_SMALL = 8;
    /** Medium sized font in PDF */
    const FONTSIZE_MEDIUM = 10;
    /** Large sized font in PDF */
    const FONTSIZE_LARGE = 12;

    /** Output as PDF */
    const OUTPUT_FORMAT_PDF = 0;
    /** Output as XLSX */
    const OUTPUT_FORMAT_XLSX = 1;
    /** Output as XLS
      * @deprecated since 2.8
      */
    const OUTPUT_FORMAT_XLS = 2;
    /** Output as ODS */
    const OUTPUT_FORMAT_ODS = 3;
    /** Output as comma separated CSV (separator is a semicolon, but nevermind) */
    const OUTPUT_FORMAT_CSV_COMMA = 4;
    /** Output as tab separated CSV */
    const OUTPUT_FORMAT_CSV_TAB = 5;

    /** Output as per-group-PDFs zipped in an archive */
    const ZIPPED = 'zipped';
    /** Output as single uncompressed file */
    const UNCOMPRESSED = 'uncompressed';

    /** Disable font-stretching */
    const STRETCH_DISABLED = 0;
    /** Horizontal scaling if text is larger than cell */
    const STRETCH_SCALING = 1;
    /** Horizontal scaling is always applied */
    const STRETCH_FORCED_SCALING = 2;
    /** Horizontal spacing if text is larger than cell */
    const STRETCH_SPACING = 3;
    /** Horizontal spacing is always applied */
    const STRETCH_FORCED_SPACING = 4;

    /** @var $outputformat int which format to export into */
    private $outputformat = self::OUTPUT_FORMAT_PDF;

    /** @var $orientation string page orientation to use for PDF output */
    private $orientation = self::PORTRAIT;
    /** @var $rowsperpage int how many rows a PDF page contains at max (0 = auto pagination) */
    private $rowsperpage = 0;
    /** @var $fontsize int which fontsize to use (8 pt, 10 pt or 12 pt) in PDF */
    private $fontsize = self::FONTSIZE_MEDIUM;
    /** @var $showheaderfooter bool if we should show header and footer in PDF */
    private $showheaderfooter = true;

    /** @var $columnwidths array columns widths */
    private $columnwidths = [];
    /** @var $titles array|null columns titles */
    private $titles = null;
    /** @var $columnformat array columns formats */
    private $columnformat;
    /** @var $headerformat */
    private $headerformat = ['title' => [], 'desc' => []];
    /** @var string[] */
    private $align = [];
    /** @var int[] Calculated widths of tables */
    protected $cw = [];
    /** @var string[] Texts to use for the header */
    private $header = [];
    /** @var $data array tables data */
    private $data = [];



    /**
     * Constructor
     *
     * @param string $orientation Orientation to use for PDF export
     * @param object[] $columnwidths Width management for columns
     */
    public function __construct($orientation, $columnwidths) {
        parent::__construct($orientation);

        // Set default configuration.
        $this->SetCreator('TUWEL');
        $this->SetMargins(10, 20, 10, true);
        $this->setHeaderMargin(7);
        $this->SetFont('freesans', '');
        $this->columnwidths = $columnwidths;

        $this->orientation = $orientation;
        $this->columnformat = [];
        for ($i = 0; $i < count($columnwidths); $i++) {
            $this->columnformat[] = [];
            $this->columnformat[$i][] = ["fill" => 0, "align" => "L", "stretch" => self::STRETCH_DISABLED];
            $this->columnformat[$i][] = ["fill" => 1, "align" => "L", "stretch" => self::STRETCH_DISABLED];
        }
    }

    /**
     * Sets format for columns
     *
     * @param object[] $columnformat The columns formats
     */
    public function setcolumnformat($columnformat) {
        if (count($columnformat) != count($this->columnwidths)) {
            print_error("Columnformat (" . count($columnformat) . ") count doesnt match " .
                "column count (" . count($this->columnwidths) . ")");
        }

        $columnformat = array_values($columnformat);

        foreach ($this->columnformat as $id => &$cur) {
            $cur[0] = array_merge($cur[0], $columnformat[$id]);
            $cur[1] = array_merge($cur[1], $columnformat[$id]);
        }
    }

    /**
     * Set the texts for the header of the pdf
     *
     * TODO: Replace this with proper class properties, setter-methods and fixed texts...
     *
     * @param string ...$header [$title1, $desc1, $title2, $desc2, $title3, $desc3,
     *                          $title4, $desc4, $title5, $desc5, $title6, $desc6]
     */
    public function setheadertext(string ...$header) {
        list($title1, $desc1, $title2, $desc2, $title3, $desc3,
                $title4, $desc4, $title5, $desc5, $title6, $desc6) = $header;
        // We know this makes no sense, but it's just to visualize how they will be used!
        $this->header = [$title1, $desc1, $title2, $desc2, $title3, $desc3,
                         $title4, $desc4, $title5, $desc5, $title6, $desc6];
    }

    /**
     * Print the header in the PDF
     */
    public function header() {
        // Set font.
        $this->SetFont('', '');
        // Title.

        $header = $this->header;

        if ($this->showheaderfooter) {

            $pagewidth = $this->getPageWidth();
            $scale = $pagewidth / 200;
            $oldfontsize = (int)$this->getFontSize();
            $this->setfontsize('12');
            // First row.
            $border = 0;
            $height = 4;
            $this->SetFont('', 'B');
            $this->Cell(15 * $scale, $height, $header[0], $border, false, 'L', 0, '', 1, false);
            $this->SetFont('', '');
            $this->Cell(31 * $scale, $height, $header[1], $border, false, 'R', 0, '', 1, false);
            $this->Cell(15 * $scale, $height, "", $border, false, 'C', 0, '', 1, false);

            $this->SetFont('', 'B');
            $this->Cell(21 * $scale, $height, $header[2], $border, false, 'L', 0, '', 1, false);
            $this->SetFont('', '');

            $this->SetFont('', '');
            $this->Cell(41 * $scale, $height, $header[3], $border, false, 'R', 0, '', 1, false);
            $this->Cell(15 * $scale, $height, "", $border, false, 'C', 0, '', 1, false);

            $this->SetFont('', 'B');
            $this->Cell(15 * $scale, $height, $header[4], $border, false, 'L', 0, '', 1, false);
            $this->SetFont('', '');
            $this->Cell(31 * $scale, $height, $header[5], $border, false, 'R', 0, '', 1, false);

            $this->Ln();

            // Second row.
            $height = 4;

            $this->SetFont('', 'B');
            $this->Cell(15 * $scale, $height, $header[6], $border, false, 'L', 0, '', 1, false);

            $this->SetFont('', '');
            $this->Cell(31 * $scale, $height, $header[7], $border, false, 'R', 0, '', 1, false);
            $this->Cell(15 * $scale, $height, "", $border, false, 'C', 0, '', 1, false);

            $this->SetFont('', 'B');
            $this->Cell(21 * $scale, $height, $header[8], $border, false, 'L', 0, '', 1, false);
            $this->SetFont('', '');

            $this->SetFont('', '');
            $this->Cell(41 * $scale, $height, $header[9], $border, false, 'R', 0, '', 1, false);

            $this->Cell(15 * $scale, $height, "", $border, false, 'C', 0, '', 1, false);

            $this->SetFont('', 'B');
            $this->Cell(15 * $scale, $height, $header[10], $border, false, 'L', 0, '', 1, false);
            $this->SetFont('', '');
            $this->Cell(31 * $scale, $height, $header[11], $border, false, 'R', 0, '', 1, false);

            $this->Ln();
            $this->setfontsize($oldfontsize);
        }
    }

    /**
     * Displays the number and total number of pages in the footer, if showheaderfooter is true
     */
    public function footer() {
        if ($this->showheaderfooter) {
            // Set font.
            $this->SetFont('', '');

            // Position at 15 mm from bottom.
            $this->SetY(-15);

            // Page number.
            $this->Cell(0, 10, $this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
        }
    }

    /**
     * Sets the titles for the columns in the pdf
     *
     * @param string[] $titles
     */
    public function settitles($titles) {
        if (count($titles) != count($this->columnwidths)) {
            print_error("Error: Title count doesnt match column count");
        }

        $this->titles = $titles;
    }

    /**
     * Sets the PDFs page orientation ('P' = Portrait, 'L' = Landscape)
     *
     * @param string $orientation
     * @return bool true if ok
     */
    public function setorientation($orientation) {
        if ($orientation == 'P' || $orientation == 'L') {
            $this->orientation = $orientation;
            return true;
        }

        return false;
    }

    /**
     * Set outputformat
     *
     * @param int $format Exports outputformat
     */
    public function setoutputformat($format) {
        $this->outputformat = $format;
    }

    /**
     * Defines how many rows are printed on each page
     *
     * @param int $rowsperpage positive integer defining maximum rows per page or 0 for auto pagination
     * @return true if ok
     */
    public function setrowsperpage($rowsperpage) {
        if (is_number($rowsperpage) && $rowsperpage > 0) {
            $this->rowsperpage = $rowsperpage;
            return true;
        }

        return false;
    }

    /**
     * Adds a row to the pdf
     *
     * @param array $row
     * @return boolean
     */
    public function addrow($row) {
        if (count($row) != count($this->columnwidths)) {
            print_error("number of columns from row (" . count($row) . ") doenst match " .
                "the number defined (" . count($this->columnwidths) . ")");
            return false;
        }

        $fastmode = false;
        foreach ($row as $r) {
            if (!is_null($r) && !is_array($r)) {
                $fastmode = true;
            }
        }

        if ($fastmode) {
            // Fast mode.
            $tmp = [];

            foreach ($row as $idx => $value) {
                if (is_array($value)) {
                    print_error("Error: if you want to add a row using the fast mode, you cannot pass me an array");
                }

                $tmp[] = ["rowspan" => 0, "data" => $value];
            }

            $row = $tmp;
        } else {
            foreach ($row as $idx => $value) {
                if (!is_array($value)) {
                    $row[$idx] = ["rowspan" => 0, "data" => $value];
                } else if (!isset($value["data"])) {
                    print_error("Error: you need to set a value for [\"data\"]");
                    return false;
                } else {
                    if (!isset($value["rowspan"])) {
                        $row[$idx]["rowspan"] = 0;
                    }
                }
            }
        }

        $this->data[] = $row;

        return true;
    }

    /**
     * Sets the font size
     *
     * @param int $fontsize
     * @param bool $out (optional)
     */
    public function setfontsize($fontsize, $out=true) {
        if ($fontsize <= self::FONTSIZE_SMALL) {
            $fontsize = self::FONTSIZE_SMALL;
        } else if ($fontsize > self::FONTSIZE_SMALL && $fontsize < self::FONTSIZE_LARGE) {
            $fontsize = self::FONTSIZE_MEDIUM;
        } else if ($fontsize >= self::FONTSIZE_LARGE) {
            $fontsize = self::FONTSIZE_LARGE;
        }

        $this->fontsize = $fontsize;

        parent::SetFontSize($fontsize, $out);
    }

    /**
     * Define if the header and footer should be printed
     *
     * @param bool $showheaderfooter
     */
    public function showheaderfooter($showheaderfooter) {
        $this->showheaderfooter = $showheaderfooter;
    }

    /**
     * Generate the file.
     *
     * @param string $filename Name of the exported file
     */
    public function generate($filename = '') {

        if ($filename == '') {
            $filename = userdate(time());
        }

        $filename = clean_filename($filename);

        switch($this->outputformat) {
            case self::OUTPUT_FORMAT_XLSX:
                $this->get_xlsx($filename);
                break;
            case self::OUTPUT_FORMAT_ODS:
                $this->get_ods($filename);
                break;
            case self::OUTPUT_FORMAT_CSV_COMMA:
                $this->get_csv($filename, ';');
                break;
            case self::OUTPUT_FORMAT_CSV_TAB:
                $this->get_csv($filename);
                break;
            default:
                $this->get_pdf($filename);
        }
    }

    /**
     * Prepares the PDF in a standardized way to be filled with data!
     */
    private function prepare_pdf() {
        $pdf = $this;

        // Add a page.
        $pdf->SetDrawColor(0);
        $pdf->AddPage();

        // Calcuate column widths.
        $sumfix = 0;
        $sumrelativ = 0;

        $rowspans = [];
        $allfixed = true;
        $sum = 0;

        foreach ($this->columnwidths as $idx => $width) {
            $rowspans[] = 0;

            $sum += $width['value'];

            if ($width["mode"] == "Fixed") {
                $sumfix += $width['value'];
            } else if ($width["mode"] == "Relativ") {
                $sumrelativ += $width['value'];
                $allfixed = false;
            } else {
                print_error("ERROR: unvalid columnwidth format");
                die();
            }
        }

        $this->cw = [];
        foreach ($this->columnwidths as $idx => $width) {
            if ($allfixed) {
                $this->cw[$idx] = round(($pdf->getPageWidth() - 20) / $sum * $width['value']);
            } else if ($width["mode"] == "Fixed") {
                $this->cw[$idx] = $width['value'];
            } else {
                $this->cw[$idx] = round(($pdf->getPageWidth() - 20 - $sumfix) / $sumrelativ * $width['value']);
            }
        }

        // Print table header.
        if (isset($this->theadMargins['top'])) {
            // Restore the original top-margin.
            $this->tMargin = $this->theadMargins['top'];
            $this->pagedim[$this->page]['tm'] = $this->tMargin;
            $this->y = $this->tMargin;
        }

        $header = $this->titles;

        if (!empty($header)) {
            // Set margins.
            $this->lMargin = $this->pagedim[$this->page]['olm'];
            $this->rMargin = $this->pagedim[$this->page]['orm'];

            // Colors, line width and bold font.
            $this->SetFillColor(0xc0, 0xc0, 0xc0);
            $this->SetTextColor(0);
            $this->SetDrawColor(0);
            $this->SetLineWidth(0.3);
            $this->SetFont('', 'B');

            // Header.
            foreach ($header as $key => $value) {
                if (!isset($this->align[$key])) {
                    $this->align[$key] = 'C';
                }
                $this->Cell($this->cw[$key], 7, $value, 1, 0, $this->align[$key], 1, null, '1', 0);
            }
            $this->Ln();
        }
        // Set new top margin to skip the table headers.
        if (!isset($this->theadMargins['top'])) {
            $this->theadMargins['top'] = $this->tMargin;
        }
        $this->tMargin = $this->y;
        $this->pagedim[$this->page]['tm'] = $this->tMargin;
        $this->lasth = 0;

        // Color and font restoration.
        $this->SetFillColor(0xe8, 0xe8, 0xe8);
        $this->SetTextColor(0);
        $this->SetFont('');

        // Color and font restoration.
        $pdf->SetFillColor(0xe8, 0xe8, 0xe8);
        $pdf->SetTextColor(0);
        $pdf->SetFont('');
    }

    /**
     * Writes the table-data to the PDF-file.
     */
    private function write_data() {
        $pdf = $this;

        $fill = 0;

        $rowheights = [];

        // Calculate line heights for not rowspanned fields.
        foreach ($this->data as $rownum => $row) {
            $maxnumlines = 1;

            foreach ($row as $key => $value) {
                $cf = $this->columnformat[$key];
                $cf = $cf[$rownum % count($cf)];
                if ($value['rowspan'] == 0 && !is_null($value['data'])) {
                    if ($cf['stretch'] != self::STRETCH_DISABLED) {
                        $this->data[$rownum][$key]['numlines'] = 1;
                    } else {
                        $this->data[$rownum][$key]['numlines'] = $this->getNumLines($value['data'], $this->cw[$key]);
                    }
                    $maxnumlines = max($maxnumlines, $this->data[$rownum][$key]['numlines']);
                }
            }

            $rowheights[$rownum] = $maxnumlines;
        }

        // Add heights to rows for fields wich are rowspanned but still need more space.
        foreach ($this->data as $rownum => $row) {
            foreach ($row as $key => $value) {
                $cf = $this->columnformat[$key];
                $cf = $cf[$rownum % count($cf)];
                if ($value['rowspan'] != 0 && !is_null($value['data'])) {
                    if ($cf['stretch'] != self::STRETCH_DISABLED) {
                        $lineheight = 1;
                    } else {
                        $lineheight = $this->getNumLines($value['data'], $this->cw[$key]);
                    }

                    $lines = 0;
                    for ($i = $rownum; $i <= $rownum + $value['rowspan']; $i++) {
                        $lines += $rowheights[$i];
                    }

                    if ($lineheight > $lines) {
                        $rowheights[$rownum] += $lineheight - $lines - 1;
                    }
                }
            }
        }

        $cellsize = $pdf->FontSizePt / 2;
        $fullrows = 0;

        // Calculate space on pages.
        $spaceonpage = [];
        if ($this->fontsize == self::FONTSIZE_SMALL) {
            if ($this->orientation == self::PORTRAIT) {
                $spaceonpage[0] = 62;
                $spaceonpage[1] = 64;
            } else {
                $spaceonpage[0] = 40;
                $spaceonpage[1] = 42;
            }
        } else if ($this->fontsize == self::FONTSIZE_MEDIUM) {
            if ($this->orientation == self::PORTRAIT) {
                $spaceonpage[0] = 49;
                $spaceonpage[1] = 51;
            } else {
                $spaceonpage[0] = 32;
                $spaceonpage[1] = 33;
            }
        } else if ($this->fontsize == self::FONTSIZE_LARGE) {
            if ($this->orientation == self::PORTRAIT) {
                $spaceonpage[0] = 41;
                $spaceonpage[1] = 42;
            } else {
                $spaceonpage[0] = 27;
                $spaceonpage[1] = 28;
            }
        } else {
            print_error("an unexpected error occured. Please report this to your administrator.");
            die();
        }

        $forcebreakonnextpage = false;

        // Now the generating of the page.
        foreach ($this->data as $rownum => $row) {
            $spanned = 0;
            $dontbreak = false;
            foreach ($row as $key => $value) {
                if ($value['rowspan'] > $spanned) {
                    $spanned = $value['rowspan'];
                }

                if (is_null($value['data'])) {
                    $dontbreak = true;
                }
            }

            $fullrows += $rowheights[$rownum];

            $spannedheight = 0;
            for ($i = $rownum + 1; $i < $rownum + $spanned; $i++) {
                $spannedheight = $rowheights[$i];
            }

            if ($this->getPage() == 1) {
                $spaceleft = $spaceonpage[0];
            } else {
                $spaceleft = $spaceonpage[1];
            }

            if ($forcebreakonnextpage) {
                // Break because there had to be allready a break but we couldnt.
                if (!$dontbreak) {
                    $pdf->AddPage();
                    $fullrows = $rowheights[$rownum];
                    $forcebreakonnextpage = false;

                }
                // Break because of fixed rows per page.
            } else if ($this->rowsperpage && $this->rowsperpage > 0 && $rownum != 0 && $rownum % $this->rowsperpage == 0) {
                if (!$dontbreak) {
                    $pdf->AddPage();
                    $fullrows = $rowheights[$rownum];
                } else {
                    $forcebreakonnextpage = true;
                }
                // Break because there is no more space on current page.
            } else if ($this->rowsperpage && $this->rowsperpage > 0 && $fullrows + $spannedheight > $spaceleft) {
                if (!$dontbreak) {
                    $pdf->AddPage();
                    $fullrows = $rowheights[$rownum];
                } else {
                    $forcebreakonnextpage = true;
                }
                // Make optimal page breaks.
            } else {
                if ($fullrows + $spannedheight > $spaceleft) {
                    if (!$dontbreak) {
                        $pdf->AddPage();
                        $fullrows = $rowheights[$rownum];
                    } else {
                        $forcebreakonnextpage = true;
                    }
                }
            }

            $debug = false;

            foreach ($row as $key => $value) {
                $cf = $this->columnformat[$key];
                $cf = $cf[$rownum % count($cf)];
                if ($value['rowspan'] > 0) {
                    $rowspans[$key] = $value['rowspan'];
                } else {
                    $rowspans[$key] = 0;
                }

                if (!is_null($value['data'])) {
                    $numlines = 0;
                    for ($i = $rownum; $i <= $rownum + $value['rowspan']; $i++) {
                        $numlines += $rowheights[$i];
                    }

                    if ($debug) {
                        $debuginfo = $spanned . '/' . $value['rowspan'] . '/' . $numlines . '/';
                        $value['data'] = $debuginfo . substr($value['data'], 0, strlen($value['data']) - (strlen($debuginfo)));
                    }

                    if ($cf['stretch'] != self::STRETCH_DISABLED) {
                        $pdf->Cell($this->cw[$key], $numlines * $cellsize, $value['data'], 'LRTB', 0, $cf['align'],
                                $cf['fill'], '', $cf['stretch'], false, 'T', 'M');
                    } else {
                        $pdf->MultiCell($this->cw[$key], $numlines * $cellsize, $value['data'], 'LRTB',
                                $cf['align'], $cf['fill'], 0);
                    }

                } else if ($rowspans[$key] > 0) {
                    if ($debug) {
                        $value['data'] = $value['rowspan'] . "/_";
                    }

                    $numlines = $rowheights[$rownum];

                    $pdf->Cell($this->cw[$key], $numlines * $cellsize, $value['data'], 'LR', 0, false, 0, '', '', true, '0');
                    $rowspans[$key] = $rowspans[$key] - 1;
                }
            }
            $pdf->Ln();
            $fill = !$fill;
        }
    }
    /**
     * Generate pdf
     *
     * @param string $filename Name of the exported file
     */
    private function get_pdf($filename) {
        $this->prepare_pdf();

        // Data.
        $this->write_data();

        // Output the PDF!
        if ($filename != '') {
            if (substr($filename, strlen($filename) - 4) != ".pdf") {
                $filename .= '.pdf';
            }

            $filename = clean_filename($filename);
            $this->Output($filename, 'D');
        } else {
            $this->Output();
        }
    }

    /**
     * Generate temporary pdf saved in server filespace to be pocessed further
     *
     * @param string|bool $filename Filename to use. If omitted a random name is used.
     * @return bool|string Full filepath + filename or false if something happened
     */
    public function get_temp_pdf($filename = false) {
        static $tmpdir = false;

        $this->prepare_pdf();

        // Data.
        $this->write_data();

        if (!$tmpdir) {
            $tmpdir = make_request_directory();
        }
        if (!$filename) {
            $filename = microtime();
        }

        // Output the PDF!
        try {
            $this->Output($tmpdir.$filename, 'F');
            return $tmpdir . $filename;
        } catch (\Exception $e) {
            // TODO proper error handling and error localized strings!
            \core\notification::add('Problem during PDF-export.<br/>\n'.$e->getMessage().'<br/>\n'.$e->getTraceAsString(),
                    'error');
        }

        return false;
    }

    /**
     * Fills workbook (either XLS or ODS) with data
     *
     * @param \MoodleExcelWorkbook|\MoodleODSWorkbook $workbook workbook to put data into
     */
    public function fill_workbook(&$workbook) {
        $time = time();
        $time = userdate($time);
        $worksheet = $workbook->add_worksheet($time);
        //Get system context in order to retrieve user fields
        $systemcontext = context_system::instance();
        // Codereview SN: comments must start with an empty space between // and the first word, and must end with a .?!
        //Get all user fields
        // Get all user fields.
        $textonlycolumns = get_extra_user_fields($systemcontext);

        array_push($textonlycolumns, "fullname");

        // Codereview SN: comment formatting applicable here too!
        //Translate all user fields keys to the local language used in the moodle instance for comparison with headers
        //todo: Find an approach which directly works via keys
        // Codereview SN: here you can use foreach instead of for($i = 0; $i < sizeof(..)..
        foreach ($textonlycolumns as $key => $value) {
            $textonlycolumns[$key] = get_string($value,'moodle');
            // Codereview SN: just in case you can make sure that the string you are trying to fetch really exists.
            // $stringmanager = get_string_manager(); // this comes before the foreach loop to avoid unnecessary calls
            // if ($stringmanager->string_exists($identifier, $component)) ...
        }
        /*
        for ($i = 0;$i<sizeof($textonlycolumns);$i++) {
            $textonlycolumns[$i] = get_string($textonlycolumns[$i],'moodle');
        }*/



        // Codereview SN: here you can use a handy function array_flip. It flips the keys and the values of an array like that:
        // $a = [0 => 'a', 1 => 'b', 2 => 'c'];
        // $a = array_flip($a);
        // $a now equals ['a' => 1, 'b' => 2, 'c' => 3]
        // this allows to use the faster function isset($a['a']) instead of in_array($a, 'a') ;)
        // so you have to can add here: $textonlycolumns = array_flip($textonlycolumns);

        $headlineprop = [
            'size' => 12,
            'bold' => 1,
            'bottom' => 1,
            'align' => 'center',
            'v_align' => 'vcenter'
        ];
        $headlineformat = $workbook->add_format($headlineprop);
        $headlineformat->set_left(1);
        $headlinefirst = $workbook->add_format($headlineprop);
        unset($headlineprop['bottom']);
        if (!empty($this->headerformat['title'])) {
            $hdrleft = $workbook->add_format($this->headerformat['title']);
        } else {
            $hdrleft = $workbook->add_format($headlineprop);
            $hdrleft->set_align('right');
        }
        unset($headlineprop['bold']);
        if (!empty($this->headerformat['desc'])) {
            $hdrright = $workbook->add_format($this->headerformat['desc']);
        } else {
            $hdrright = $workbook->add_format($headlineprop);
            $hdrright->set_align('left');
        }

        $textprop = [
            'size' => 10,
            'align' => 'left',
            'v_align' => 'vcenter'
        ];
        $text = $workbook->add_format($textprop);
        $text->set_num_format(1);
        $text->set_left(1);
        $textfirst = $workbook->add_format($textprop);

        $line = 0;
        // Codereview SN: you can change the variable name here to be plural, cause it holds more than one id.
        // $textonlyids = array();
        // Also, you can use the short form for array creation cause it simply looks nicer :)
        // $textonlyids = [];
        $textonlyid = array();
        // Write header.
        for ($i = 0; $i < count($this->header); $i += 2) {
            $worksheet->write_string($line, 0, $this->header[$i], $hdrleft);
            $worksheet->write_string($line, 1, $this->header[$i + 1], $hdrright);
            $line++;
        }
        $line++;


        // Table header.
        $i = 0;
        $first = true;
        foreach ($this->titles as $header) {
            if ($first) {
                $worksheet->write_string($line, $i, $header, $headlinefirst);
                $first = false;
            } else {
                $worksheet->write_string($line, $i, $header, $headlineformat);
                $first = false;
            }
            // Codereview SN: comment format.
            //Check if the header string is a text only column and write its index to $textonlyid
            // Codereview SN: here, instead of pushing to the array values, you can simply add keys and then use isset($array[$key]) to check if a key exists
            // same principle as with array_flip
            // So if you used array_flip with $textonlycolumns, then this code becomes
            // if (isset($textonlycolumns[$header])) {
            //      $textonlyids[$i] = true;
            // }
            if(in_array($header, $textonlycolumns)) {
                array_push($textonlyid,$i);

            }
            $i++;
        }

        // Data.
        $prev = $this->data[0];
        foreach ($this->data as $row) {
            $first = true;
            $line++;
            $i = 0;
            foreach ($row as $idx => $cell) {
                if (is_null($cell['data'])) {
                    $cell['data'] = $prev[$idx]['data'];
                }


                // Codereview SN: array_key_exists($key, $array) is equivalent to isset($array[$key]).
                if (array_key_exists('format', $cell)) {
                    $worksheet->write_string($line, $i, $cell['data'], $workbook->add_format($cell['format']));
                } else {
                    // Codereview SN: again comments :)
                    //Only write numeric values via write_number if the current column is not text only ($i not in $textonlyid)
                    // Codereview SN: out of curiosity, is there a special reason why the first column is strictly string?
                    if ($first) {
                        $worksheet->write_string($line, $i, $cell['data'], $textfirst);
                        $first = false;
                    // Codereview SN: if you've set the ids as keys beforehand, here you can replace the !in_array(..) with !isset
                    // so it becomes:
                    // } else if (is_numeric($cell['data']) && !isset($textonlyid[$i])) {
                    // the main advantage of this function is that it is generally faster for larger arrays than in_array
                    } else if (is_numeric($cell['data']) && (!in_array($i, $textonlyid))) {
                        $worksheet->write_number($line, $i, $cell['data'], $text);
                    }  else {
                        $worksheet->write_string($line, $i, $cell['data'], $text);
                    }
                }

                $prev[$idx] = $cell;
                $i++;
            }
        }
    }

    /**
     * Set headerformat
     *
     * @param array $headertitleformat Headertitleformats for workbooks
     * @param array $headerdescformat Headerdescriptionformats for workbooks
     */
    public function set_headerformat($headertitleformat, $headerdescformat) {
             $this->headerformat['title'] = $headertitleformat;
             $this->headerformat['desc'] = $headerdescformat;
    }

    /**
     * Generate XLSX
     *
     * @param string $filename Name of the exported file
     */
    public function get_xlsx($filename) {
        global $CFG;

        require_once($CFG->libdir . "/excellib.class.php");

        $workbook = new \MoodleExcelWorkbook("-", 'Excel2007');

        $this->fill_workbook($workbook);

        $workbook->send($filename);
        $workbook->close();
    }

    /**
     * Generate ODS
     *
     * @param string $filename Name of the exported file
     */
    public function get_ods($filename) {
        global $CFG;

        require_once($CFG->libdir . "/odslib.class.php");

        $workbook = new \MoodleODSWorkbook("-");

        $this->fill_workbook($workbook);

        $workbook->send($filename.'.ods');
        $workbook->close();
    }

    /**
     * Generate CSV
     *
     * @param string $filename Name of the exported file
     * @param string $sep Character used to separate the data (usually tab or semicolon)
     */
    public function get_csv($filename, $sep = "\t") {
        $lines = [];

        // Course information.
        for ($i = 0; $i < count($this->header); $i += 2) {
            $lines[] = $this->header[$i] . $sep . $this->header[$i + 1];
        }

        // Table header.
        $lines[] = join($sep, $this->titles);

        $prev = $this->data[0];

        // Data.
        foreach ($this->data as $row) {
            $r = [];
            foreach ($row as $idx => $cell) {
                if (is_null($cell['data'])) {
                    $cell['data'] = $prev[$idx]['data'];
                }

                $r[] = $cell['data'];
                $prev[$idx] = $cell;
            }

            $lines[] = join($sep, $r);
        }

        $filecontent = implode("\n", $lines);

        if ($filename != '') {
            if (substr($filename, strlen($filename) - 4) != ".csv") {
                $filename .= '.csv';
            }

            $filename = clean_filename($filename);
        }

        ob_clean();
        header('Content-Type: text/plain');
        header('Content-Length: ' . strlen($filecontent));
        header('Content-Disposition: attachment; filename="'.$filename.'"; filename*="'.rawurlencode($filename));
        header('Content-Transfer-Encoding: binary');
        header('Content-Encoding: utf-8');
        echo $filecontent;
        die();
    }
}
