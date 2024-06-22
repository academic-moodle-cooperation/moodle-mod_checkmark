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
 * /classes/local/exporttemplate/grades_extended.php
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2017 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_checkmark\local\exporttemplates;
use mod_checkmark\basetemplate as basetemplate;
use mod_checkmark\MTablePDF as MTablePDF;

/**
 * Template table-class exported with specific settings!
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2017 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grades_extended extends basetemplate {
    /**
     * Creates and returns a grades object!
     *
     * @param string $uniqueid a string identifying this table.Used as a key in
     *                          session  vars. It gets set automatically with the helper methods!
     * @param \checkmark|int $checkmarkorcmid checkmark object or course module id of checkmark instance
     * @return grades_extended
     */
    public static function get_table_instance($uniqueid, $checkmarkorcmid = null) {
        return new grades_extended($uniqueid, $checkmarkorcmid);
    }

    /**
     * Returns by the template predefined export settings
     *
     * @return array [sumabs, rumrel, orientation, textsize, printheader, forcesinglenames]
     */
    public static function get_export_settings() {
        return [1, 1, MTablePDF::LANDSCAPE, MTablePDF::FONTSIZE_SMALL, true, true];
    }

    /**
     * Sets up all the columns, header, formats, etc.
     */
    public function setup_columns() {
        // Adapt table for export view (columns, etc.)!
        $this->setup_name_colums();
        // Add id number.
        $this->tableheaders[] = get_string('idnumber');
        $this->tablecolumns[] = 'idnumber';
        $this->cellwidth[] = ['mode' => 'Fixed', 'value' => '20'];
        $this->columnformat['idnumber'] = ['align' => 'L'];

        // Dynamically add examples!
        foreach ($this->checkmark->checkmark->examples as $key => $example) {
            $width = strlen($example->shortname) + strlen($example->grade) + 4;
            $this->tableheaders[] = $example->shortname." (".$example->grade.'P)';
            $this->tablecolumns[] = 'example'.$key;
            $this->cellwidth[] = ['mode' => 'Fixed', 'value' => $width];
            $this->columnformat['example'.$key] = ['align' => 'C'];
        }

        $this->tableheaders[] = get_string('checkmarks', 'checkmark');
        $this->tablecolumns[] = 'summary';
        $this->cellwidth[] = ['mode' => 'Fixed', 'value' => '20'];
        $this->columnformat['summary'] = ['align' => 'L'];

        if ($this->checkmark->checkmark->grade != 0) {
            $this->tableheaders[] = get_string('modgrade', 'grades');
            $this->tablecolumns[] = 'grade';
            $this->cellwidth[] = ['mode' => 'Fixed', 'value' => '15'];
            $this->columnformat['grade'] = ['align' => 'R'];
        }
    }
}
