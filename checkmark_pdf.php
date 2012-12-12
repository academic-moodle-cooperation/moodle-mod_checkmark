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
 * PDF-functionality for component checkmark, branch 'MOODLE_22_STABLE'
 *
 * This class extends the moodle pdf class with a custom header and some helperfunctions for
 * proper submissions-output.
 *
 * @package       mod_checkmark
 * @author        Philipp Hager
 * @copyright     2012 Philipp Hager
 * @since         Moodle 2.2
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir.'/pdflib.php');

class checkmark_pdf extends pdf {
    /** @var string[] $header1 defines what's in the upper row of page-header **/
    protected $header1 = null;

    /** @var string[] $header2 defines what's in the lower row of page-header **/
    protected $header2 = null;

    /**
     * @var string[] $header numerical array of strings, used for storage of column-headers
     * each index corresponds to the index in {@see $data}, {@see $width} and {@see $align}
     */
    protected $header = array();

    /**
     * @var float[] $width numerical array of floats, used for storage of column-header-widths
     * each index corresponds to the index in {@see $data}, {@see $width} and {@see $align}
     * If index in $width is set to null the corresponding column-width gets calculated
     * automatically (the same calculated width is used for each of those columns)
     */
    protected $width = array();

    /**
     * @var char[] $align numerical array of chars, used for storage of column-header-alignment
     * each index corresponds to the index in {@see $data}, {@see $width} and {@see $align}
     * use 'C' for center, 'L' for left and 'R' for right
     */
    protected $align = array();

    /**
     * @var string|boolean false if no groupmode or the current groupname as string
     */
    protected $groups = false;

    /**
     * setHeaderData() helper method to set the right texts for page header
     *
     * @param string $coursename the name of the course
     * @param timestamp $timeavailable time since the checkmark is available
     * @param timemstamp $timedue time due to which students can submit
     * @param string $viewname the checkmark-modulename to view
     */
    public function setHeaderData($coursename, $checkmarkname, $timeavailable, $timedue,
                                  $viewname) {
        $this->header1 = array();
        $this->header1[0] = get_string('course').":";
        $this->header1[1] = $coursename;
        $this->header1[2] = get_string('availabledate', 'checkmark').":";
        $this->header1[3] = userdate($timeavailable);
        $this->header1[4] = get_string('strprintpreview', 'checkmark');
        $this->header1[5] = $viewname;

        $this->header2 = array();
        $this->header2[0] = get_string('strassignment', 'checkmark').":";
        $this->header2[1] = $checkmarkname;
        $this->header2[2] = get_string('duedate', 'checkmark').":";
        $this->header2[3] = userdate($timedue);
        $this->header2[4] = get_string("groups") . ":";
    }

    public function setGroups($selection) {
        $this->groups = $selection;
    }

    /**
     * Header() helper method to actually print the page header in the PDF
     */
    public function Header() {
        // Set font
        $this->SetFont('', '');

        // Title

        $header = $this->header1;

        if ($header != false) {

            $pagewidth = $this->getPageWidth();
            $scale = $pagewidth / 200;
            $oldfontsize = $this->getFontSize();
            $this->setFontSize('12');

            // first row
            $border = 0;
            $height = 4;
            $this->SetFont('', 'B');
            $this->MultiCell(15*$scale, $height, $header[0], $border, 'L', 0, 0, null, null, true,
                             1, false, false, $height, 'M', true);

            $this->SetFont('', '');
            $this->MultiCell(31*$scale, $height, $header[1], $border, 'R', 0, 0, null, null, true,
                             1, false, false, $height, 'M', true);

            //spacer
            $this->MultiCell(15*$scale, $height, "", $border, 'C', 0, 0, null, null, true, 1,
                             false, false, $height, 'M', true);

            $this->SetFont('', 'B');
            $this->MultiCell(21*$scale, $height, $header[2], $border, 'L', 0, 0, null, null, true,
                             1, false, false, $height, 'M', true);
            $this->SetFont('', '');

            $this->SetFont('', '');
            $this->MultiCell(41*$scale, $height, $header[3], $border, 'R', 0, 0, null, null, true,
                             1, false, false, $height, 'M', true);

            //spacer
            $this->MultiCell(15*$scale, $height, "", $border, 'C', 0, 0, null, null, true, 1, false,
                             false, $height, 'M', true);

            $this->SetFont('', 'B');
            $this->MultiCell(15*$scale, $height, $header[4], $border, 'R', 0, 0, null, null, true,
                             1, false, false, $height, 'M', true);

            $this->SetFont('', '');
            $this->MultiCell(0 /* 31*$scale*/, $height, $header[5], $border, 'R', 0, 0, null, null,
                             true, 1, false, false, $height, 'M', true);

            $this->Ln();

            // second row
            $height = 4;
            $header = $this->header2;

            $this->SetFont('', 'B');
            $this->MultiCell(15*$scale, $height, $header[0], $border, 'L', 0, 0, null, null, true,
                             1, false, false, $height, 'M', true);

            $this->SetFont('', '');
            $this->MultiCell(31*$scale, $height, $header[1], $border, 'R', 0, 0, null, null, true,
                             1, false, false, $height, 'M', true);

            //spacer
            $this->MultiCell(15*$scale, $height, "", $border, 'C', 0, 0, null, null, true, 1, false,
                             false, $height, 'M', true);

            $this->SetFont('', 'B');
            $this->MultiCell(21*$scale, $height, $header[2], $border, 'L', 0, 0, null, null, true,
                             1, false, false, $height, 'M', true);

            $this->SetFont('', '');
            $this->MultiCell(41*$scale, $height, $header[3], $border, 'R', 0, 0, null, null, true,
                             1, false, false, $height, 'M', true);

            if ($this->groups) {
                //spacer
                $this->MultiCell(15*$scale, $height, "", $border, 'C', 0, 0, null, null, true, 1,
                                 false, false, $height, 'M', true);

                $this->SetFont('', 'B');
                $this->MultiCell(15 * $scale, $height, $header[4], $border, 'L', 0, 0, null, null,
                                 true, 1, false, false, $height, 'M', true);
                $this->SetFont('', '');
                $this->MultiCell(0/*31 * $scale*/, $height, $this->groups, $border, 'R', 0, 0, null,
                                 null, true, 1, false, false, $height, 'M', true);
            } else {
                $this->SetFont('', 'B');
                $this->MultiCell(15 * $scale, $height, $header[4], $border, 'L', 0, 0, null, null,
                                 true, 1, false, false, $height, 'M', true);
                $this->SetFont('', '');
                $this->MultiCell(0/*31 * $scale*/, $height, "-", $border, 'R', 0, 0, null, null,
                                 true, 1, false, false, $height, 'M', true);
            }

            $this->Ln();
            $this->SetFontSize($oldfontsize);
        }
    }

    /**
     * prints the tableheader as first row of the table
     */
    protected function setTableHeader() {

        if (isset($this->theadMargins['top'])) {
            // restore the original top-margin
            $this->tMargin = $this->theadMargins['top'];
            $this->pagedim[$this->page]['tm'] = $this->tMargin;
            $this->y = $this->tMargin;
        }
        if (!empty($this->header)) {
            // set margins
            $prev_lMargin = $this->lMargin;
            $prev_rMargin = $this->rMargin;
            $this->lMargin = $this->pagedim[$this->page]['olm'];
            $this->rMargin = $this->pagedim[$this->page]['orm'];
            // Colors, line width and bold font
            $this->SetFillColor(0xc0, 0xc0, 0xc0);
            $this->SetTextColor(0);
            $this->setDrawColor(0);
            $this->SetLineWidth(0.3);
            $this->SetFont('', 'B');
            // Header
            $width = $this->width;
            $w = $width;
            $nullelementcount = 0;
            foreach ($width as $key => $value) {
                if (($value == null) && isset($this->header[$key])) {
                    $nullelementcount++;
                } else if (($value != null) && !isset($this->header[$key])) {
                    //set to null to use whole space
                    //otherwise the space for this fixed-width-column would be unused
                    unset($width[$key]);
                }
            }
            foreach ($width as $key => $value) {
                if ($value == null) {
                    $w[$key] = ($this->getPageWidth()-20-array_sum($width))/$nullelementcount;
                }
            }
            $header = $this->header;
            $num_headers = count($header);
            //for ($i = 0; $i < $num_headers; ++$i) {
            foreach ($header as $key => $value) {
                if (!isset($this->align[$key])) {
                    $this->align[$key] = 'C';
                }
                $this->MultiCell($w[$key], 7, $value, 1, $this->align[$key], 1, 0, null, null, true,
                                 1, false, false, 7, 'M', true);
            }
            $this->Ln();
        }
        // set new top margin to skip the table headers
        if (!isset($this->theadMargins['top'])) {
            $this->theadMargins['top'] = $this->tMargin;
        }
        $this->tMargin = $this->y;
        $this->pagedim[$this->page]['tm'] = $this->tMargin;
        $this->lasth = 0;

        // Color and font restoration
        $this->SetFillColor(0xe8, 0xe8, 0xe8);
        $this->SetTextColor(0);
        $this->SetFont('');
    }

    /**
     * setDataviaTBL() helper method using the PDF-classes Cell-Function to build the table
     *
     * @param array $data 2d-array of strings containing the content of the cells
     * @param array $header contents of the header-cells
     * @param array $width columns-width, numerical index
     */
    public function setDataviaTBL($data, $header = false, $width = false) {
        $scale = $this->getPageWidth() / 210;

        $this->width = $width;
        $this->header = $header;
        // add a page
        $this->setDrawColor(0);
        $this->AddPage();
        $w = $width;
        $nullelementcount = 0;
        foreach ($width as $key => $value) {
            if (($value == null) && isset($this->header[$key])) {
                $nullelementcount++;
            } else if (($value != null) && !isset($this->header[$key])) {
                //set to null to use whole space
                //otherwise the space for this fixed-width-column would be unused
                unset($width[$key]);
            }
        }
        foreach ($width as $key => $value) {
            if ($value == null) {
                $w[$key] = ($this->getPageWidth()-20-array_sum($width))/$nullelementcount;
            }
        }

        // Color and font restoration
        $this->SetFillColor(0xe8, 0xe8, 0xe8);
        $this->SetTextColor(0);
        $this->SetFont('');

        // Data
        $fill = 0;
        foreach ($data as $rownum => $row) {
            if (($this->maxrows != null) && ($rownum != 0) && (($rownum%$this->maxrows) == 0)) {
                $this->addPage();
            }
            if ($rownum == count($data)-1) {
                $bottomborder = 'B';
            } else {
                $bottomborder = '';
            }
            foreach ($row as $key => $value) {
                if (!isset($this->align[$key])) {
                    $this->align[$key] = 'C';
                }
                $this->MultiCell($w[$key], 6, $value, 'LR'.$bottomborder, $this->align[$key], $fill,
                                 0, null, null, true, 1, false, false, 6, 'M', true);
            }
            $this->Ln();
            $fill=!$fill;
        }
    }

    /**
     * setData helper method to initiate the data output
     *
     * either outputs to PDF ($useHTML=false) or dumps $data ($useHTML=true)
     *
     * @param array $data 2d-array of strings containing the content of the cells
     * @param array $header contents of the header-cells
     * @param array $width columns-width, numerical index
     * @param array $align sets the columns align (either 'C' center, 'R' right or 'L' left)
     * @param bool  $useHTML switch for dumping data to the browser for test purposes
     * @param int   $maxrows after $maxrows rows is a pagebreak inserted (false for auto-pagebreak)
     */
    public function setData($data, $header = false, $width = false, $align = false, $useHTML=false,
                            $maxrows=false) {
        $this->header = $header;
        $this->width = $width;
        $this->align = $align;
        $this->maxrows = $maxrows;
        if ($useHTML) {
            var_dump($data);
        } else {
            $this->setDataviaTBL($data, $header, $width);
        }
    }
}
