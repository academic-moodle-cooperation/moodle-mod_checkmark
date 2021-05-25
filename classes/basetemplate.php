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
 * /classes/local/exporttemplate/grades_simple.php
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2017 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_checkmark;
use \mod_checkmark\submissionstable as submissionstable;
use \mod_checkmark\MTablePDF as MTablePDF;

defined('MOODLE_INTERNAL') || die();

/**
 * Template table-class exported with specific settings!
 *
 * @package   mod_checkmark
 * @author    Philipp Hager
 * @copyright 2017 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class basetemplate extends submissionstable {
    /** @var \checkmark protected checkmark instance */
    protected $checkmark;

    /** @var \context_module protected context instance */
    protected $context;

    /** @var \bool protected if submission should be shown in timesubmitted column */
    protected $showsubmission = false;

    /** @var \int protected if formated cells should contain html */
    protected $format = self::FORMAT_HTML;

    /** @var \bool protected defaultselectstate whether or not the select checkboxes should be checked or not checked by default */
    protected $defaultselectstate = false;

    /** @var array */
    protected $tableheaders = [];

    /** @var array */
    protected $tablecolumns = [];

    /** @var array */
    protected $cellwidth = [];

    /** @var array */
    protected $columnformat = [];

    /** @var bool */
    protected $quickgrade = false;

    /** @var bool */
    protected $sumabs = false;

    /** @var bool */
    protected $sumrel = false;

    /** @var int */
    protected $filter = \checkmark::FILTER_ALL;

    /**
     * Returns by the template predefined export settings
     *
     * @return array [sumabs, rumrel, orientation, textsize, printheader, forcesinglelinenames]
     */
    public static function get_export_settings() {
        return [0, 0, MTablePDF::PORTRAIT, MTablePDF::FONTSIZE_SMALL, true, true];
    }

    /**
     * Sets up just the column(s) for name. When checkmark_seperatenamecolumns is set, a seperate column is generated for each
     * name fragment
     *
     * @throws \coding_exception
     */
    public function setup_name_colums() {
        $seperatenamecolumns = get_user_preferences('checkmark_seperatenamecolumns', 0);
        $this->tableheaders = [];
        $this->tablecolumns = [];
        $this->cellwidth = [];
        $this->columnformat = [];
        if (!$seperatenamecolumns) {
            $this->tableheaders[] = get_string('name');
            $this->tablecolumns[] = 'fullname';
            $this->cellwidth[] = ['mode' => 'Fixed', 'value' => '25'];
            $this->columnformat['fullname'] = ['align' => 'L', 'stretch' => MTablePDF::STRETCH_SCALING];
        } else {
            $usednamefields = submissionstable::get_name_fields($this->context);
            foreach ($usednamefields as $name) {
                $this->tableheaders[] = get_string($name);
                $this->tablecolumns[] = $name;
                $this->cellwidth[] = ['mode' => 'Fixed', 'value' => '25'];
                $this->columnformat[$name] = ['align' => 'L', 'stretch' => MTablePDF::STRETCH_SCALING];
            }
        }
    }

    /**
     * Sets up all the columns, headers, etc.
     */
    public function setup_columns() {
        // Adapt table for export view (columns, etc.)!
        $this->setup_name_colums();

        // Dynamically add examples!
        foreach ($this->checkmark->checkmark->examples as $key => $example) {
            $width = strlen($example->shortname) + strlen($example->grade) + 4;
            $this->tableheaders[] = $example->shortname." (".$example->grade.'P)';
            $this->tablecolumns[] = 'example'.$key;
            $this->cellwidth[] = ['mode' => 'Fixed', 'value' => $width];
            $this->columnformat['example'.$key] = ['align' => 'C'];
        }
    }

    /**
     * Used by create_export_table() and wraps around the child classes constructors
     *
     * @param string $uniqueid
     * @param \checkmark|int $checkmarkorcmid
     * @return basetemplate object
     */
    static public function get_table_instance($uniqueid, $checkmarkorcmid = null) {
        throw new \coding_exception('Method has to be overridden!');
    }

    /**
     * Helper method to create the table for export view!
     *
     * @param \checkmark|int $checkmarkorcmid checkmark object or course module id of checkmark instance
     * @param int $filter which filter to use
     * @param int[] $ids for which user ids to filter
     * @return submissionstable object
     */
    static public function create_export_table($checkmarkorcmid = null, $filter = \checkmark::FILTER_ALL, $ids = array()) {
        global $CFG, $DB;
        // We need to have the same ID to ensure the columns are collapsed if their collapsed in the other table!
        $table = static::get_table_instance('mod-checkmark-submissions', $checkmarkorcmid);

        list($table->sumabs, $table->sumrel, , , , ) = static::get_export_settings();
        $table->quickgrade = 0;
        $table->filter = $filter;
        $table->defaultselectstate = true; // Select all checkboxes by default!

        $table->setup_columns();

        $table->define_columns($table->tablecolumns);
        $table->define_headers($table->tableheaders);
        $table->define_baseurl($CFG->wwwroot.'/mod/checkmark/export.php?id='.$table->checkmark->cm->id.
            '&amp;currentgroup='.$table->currentgroup);

        $table->sortable(true, 'lastname'); // Sorted by lastname by default!
        $table->collapsible(true);
        $table->initialbars(true);

        // Create and set the SQL!
        $params = array();
        $ufields = \core_user\fields::for_userpic()->get_sql('u')->selects;
        $table->examplecount = count($table->checkmark->checkmark->examples);
        $params['examplecount'] = $table->examplecount;

        $fields = "u.id ".$ufields.", u.idnumber,
                  MAX(s.id) AS submissionid, MAX(f.id) AS feedbackid, MAX(f.grade) AS grade,
                  MAX(f.feedback) AS feedback, MAX(s.timemodified) AS timesubmitted,
                  MAX(f.timemodified) AS timemarked, 100 * COUNT( DISTINCT cchks.id ) / :examplecount AS summary,
                  COUNT( DISTINCT cchks.id ) AS checks, f.attendance AS attendance";
        $params['checkmarkid'] = $table->checkmark->checkmark->id;
        $params['checkmarkid2'] = $table->checkmark->checkmark->id;

        $users = $table->get_userids($filter, $ids);
        list($sqluserids, $userparams) = $DB->get_in_or_equal($users, SQL_PARAMS_NAMED, 'user');
        $params = array_merge_recursive($params, $userparams);

        $from = "{user} u ".
            "LEFT JOIN {checkmark_submissions} s ON u.id = s.userid AND s.checkmarkid = :checkmarkid
                 LEFT JOIN {checkmark_feedbacks} f ON u.id = f.userid AND f.checkmarkid = :checkmarkid2
                 LEFT JOIN {checkmark_checks} gchks ON gchks.submissionid = s.id
                 LEFT JOIN {checkmark_checks} cchks ON cchks.submissionid = s.id AND cchks.state = 1 ";

        $where = "u.id ".$sqluserids;

        if ($filter == \checkmark::FILTER_SUBMITTED) {
            $where .= ' AND s.timemodified > 0';
        } else if ($filter == \checkmark::FILTER_REQUIRE_GRADING) {
            $where .= ' AND COALESCE(f.timemodified,0) < COALESCE(s.timemodified,0)';
        } else if ($filter == \checkmark::FILTER_ATTENDANT) {
            $where .= ' AND attendance = 1';
        } else if ($filter == \checkmark::FILTER_ABSENT) {
            $where .= ' AND attendance = 0';
        } else if ($filter == \checkmark::FILTER_UNKNOWN) {
            $where .= ' AND attendance IS NULL';
        } else if ($filter == \checkmark::FILTER_NOT_SUBMITTED) {
            $where = " AND (s.timemodified <= 0 OR s.timemodified IS NULL)";
        } else if ($filter == \checkmark::FILTER_PRESENTATIONGRADING) {
            $where .= " AND presentationgrade IS NOT NULL OR presentationfeedback IS NOT NULL";
        } else if ($filter == \checkmark::FILTER_NO_PRESENTATIONGRADING) {
            $where .= " AND presentationgrade IS NULL AND presentationfeedback IS NULL";
        }

        $groupby = " u.id, s.id, f.id ".$ufields.", u.idnumber, f.attendance";

        $table->set_sql($fields, $from, $where, $params, $groupby);
        $table->set_count_sql("SELECT COUNT(DISTINCT u.id) FROM ".$from." WHERE ".$where, $params);

        $table->gradinginfo = grade_get_grades($table->checkmark->course->id, 'mod', 'checkmark', $table->checkmark->checkmark->id,
            $users);

        return $table;
    }

    /**
     * Convenience method to call a number of methods for you to get the
     * table data. TODO: replace array-using methods with streaming download (like dataformat).
     *
     * @param int $type Format that should be exported (currently with color or without)
     * @return array[] array of arrays containing data in legacy format (compatible with mtablepdf class)
     * @throws \dml_exception
     */
    public function get_data($type = self::FORMAT_DOWNLOAD) {
        if (!$this->setup) {
            $this->setup();
        }

        $this->columns = array_flip(array_keys($this->columns));
        $this->headers = array_values($this->headers);
        $this->cellwidth = array_values($this->cellwidth);
        $this->columns = array_flip(array_keys($this->columns));

        // Now we simulate a download (at least for query_db) so it will fetch everything!
        $this->download = true;
        $this->query_db(30, false);
        $this->download = '';

        if (!$this->rawdata || ($this->rawdata instanceof \Traversable && !$this->rawdata->valid())) {
            return array(array(), array(), array(), array(), array());
        }

        $returndata = array();
        $this->format = $type;
        foreach ($this->rawdata as $key => $row) {
            $returndata[$key] = $this->format_row($row);
        }

        $this->format = self::FORMAT_HTML;

        if ($this->rawdata instanceof \core\dml\recordset_walk || $this->rawdata instanceof \moodle_recordset) {
            $this->rawdata->close();
        }

        return array($this->columns, $this->headers, $returndata, $this->columnformat, $this->cellwidth);
    }
}

