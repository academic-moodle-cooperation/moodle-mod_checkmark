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
 * submissionstable.php
 *
 * @package   mod_checkmark
 * @author    Philipp Hager, extended and maintained by Daniel Binder
 * @copyright 2019 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_checkmark;
use dml_exception;
use checkmark;
use coding_exception;
use moodle_exception;
use moodle_recordset;
use core\dml\recordset_walk;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->libdir.'/gradelib.php');

/**
 * submissionstable class handles display of submissions for print preview and submissions view...
 *
 * @package   mod_checkmark
 * @author    Philipp Hager, extended and maintained by Daniel Binder
 * @copyright 2019 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submissionstable extends \table_sql {
    /** formated cells contain tags for colors */
    const FORMAT_COLORS = 2;
    /** formated cells contain HTML */
    const FORMAT_HTML = 1;
    /** formated cells won't contain HTML */
    const FORMAT_DOWNLOAD = 0;

    /** select none */
    const SEL_NONE = 0;
    /** select all */
    const SEL_ALL = 1;
    /** select submitted */
    const SEL_SUBMITTED = 2;
    /** select submissions which require grading */
    const SEL_REQ_GRADING = 3;

    /** @var \checkmark protected checkmark instance */
    protected $checkmark;

    /** @var \context_module protected context instance */
    protected $context;

    /** @var \bool protected if submission should be shown in timesubmitted column */
    protected $showsubmission = false;

    /** @var \int protected if formated cells should contain html */
    protected $format = self::FORMAT_HTML;

    /** @var \bool protected suppressinitials shows whether or not the intials bars should be printed
     * @deprecated since Moodle 3.3 TODO remove in Moodle 3.7
     */
    protected $suppressinitials = false;

    /** @var \bool protected defaultselectstate whether or not the select checkboxes should be checked or not checked by default */
    protected $defaultselectstate = false;

    /**
     * @var \array private For storing user-customised table properties in the user_preferences db table.
     */
    private $prefs = [];

    /** @var array */
    protected $cellwidth = [];

    /** @var array */
    protected $columnformat = [];

    /** @var int */
    protected $groupmode = null;

    /** @var int */
    protected $currentgroup = null;

    /** @var \stdClass */
    protected $gradinginfo = null;

    /** @var bool */
    protected $usesoutcomes = null;

    /** @var int  */
    protected $examplecount = 0;
    /** @var int  */
    protected $sumabs = 0;
    /** @var int  */
    protected $sumrel = 0;
    /** @var null  */
    protected $filter = null;
    /** @var bool  */
    protected $quickgrade = false;
    /** @var int  */
    protected $tabindex = 0;
    /** @var array  */
    protected $colgroups = [];
    /** @var array  */
    protected $grademenu = [];
    /** @var array  */
    protected $presentationgrademenu = [];
    /** @var string  */
    protected $strupdate = '';
    /** @var string  */
    protected $strgrade = '';

    /** @var bool */
    protected $hasoverrides = false;

    /**
     * constructor
     * @param string $uniqueid a string identifying this table.Used as a key in
     *                          session  vars. It gets set automatically with the helper methods!
     * @param checkmark|int $checkmarkorcmid checkmark object or course module id of checkmark instance
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function __construct($uniqueid, $checkmarkorcmid = null) {
        global $CFG, $DB, $PAGE;
        $PAGE->requires->js_call_amd('mod_checkmark/utils', 'init');

        parent::__construct($uniqueid);

        $this->attributes['id'] = $uniqueid;

        if ($checkmarkorcmid instanceof \checkmark) {
            $this->checkmark = $checkmarkorcmid;
        } else if (is_numeric($checkmarkorcmid)) {
            $this->checkmark = new \checkmark($checkmarkorcmid);
        } else {
            print_error('invalidcoursemodule');
        }

        $this->context = \context_module::instance($this->checkmark->cm->id);
        $this->groupmode = groups_get_activity_groupmode($this->checkmark->cm);
        $this->currentgroup = groups_get_activity_group($this->checkmark->cm, true);
        $this->gradinginfo = grade_get_grades($this->checkmark->course->id, 'mod', 'checkmark', $this->checkmark->checkmark->id);
        if (!empty($CFG->enableoutcomes) && !empty($this->gradinginfo->outcomes)) {
            $this->usesoutcomes = true;
        } else {
            $this->usesoutcomes = false;
        }

        // Some sensible defaults!
        $this->set_attribute('cellspacing', '0');
        $this->set_attribute('class', 'submissions generaltable generalbox');

        $this->set_attribute('width', '100%');

        // Save status of table(s) persistent as user preference!
        $this->is_persistent(true);

        if ($DB->record_exists('checkmark_overrides', ['checkmarkid' => $this->checkmark->checkmark->id])) {
            $this->hasoverrides = true;
        }
    }

    /**
     * Query the db. Store results in the table object for use by build_table.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar. Bar
     * will only be used if there is a fullname column defined for the table.
     * @throws dml_exception
     */
    public function query_db($pagesize, $useinitialsbar=true) {
        global $DB;
        if (!$this->is_downloading()) {
            if ($this->countsql === null) {
                $this->countsql = 'SELECT COUNT(1) FROM '.$this->sql->from.' WHERE '.$this->sql->where;
                $this->countparams = $this->sql->params;
            }
            $grandtotal = $DB->count_records_sql($this->countsql, $this->countparams);
            if ($useinitialsbar && !$this->is_downloading()) {
                $this->initialbars(($grandtotal > $pagesize) || empty($pagesize));
            }

            list($wsql, $wparams) = $this->get_sql_where();
            if ($wsql) {
                $this->countsql .= ' AND '.$wsql;
                $this->countparams = array_merge($this->countparams, $wparams);

                $this->sql->where .= ' AND '.$wsql;
                $this->sql->params = array_merge($this->sql->params, $wparams);

                $total  = $DB->count_records_sql($this->countsql, $this->countparams);
            } else {
                $total = $grandtotal;
            }
            if ($pagesize) {
                $this->pagesize($pagesize, $total);
            }
        }

        // Fetch the attempts!
        $sort = $this->get_sql_sort();
        if ($sort) {
            $sort = "ORDER BY $sort";
        }
        if (isset($this->sql->groupby)) {
            $groupby = "GROUP BY {$this->sql->groupby}";
        } else {
            $groupby = "";
        }
        $sql = "SELECT
                {$this->sql->fields}
                FROM {$this->sql->from}
                WHERE {$this->sql->where}
                {$groupby}
                {$sort}";

        if (!$this->is_downloading()) {
            $this->rawdata = $DB->get_records_sql($sql, $this->sql->params, $this->get_page_start(), $this->get_page_size());
        } else {
            $this->rawdata = $DB->get_records_sql($sql, $this->sql->params);
        }
    }

    /**
     * Convenience method to call a number of methods for you to get the
     * table data. TODO: replace array-using methods with streaming download (like dataformat).
     *
     * @param int $type Type (with or without color information) that should be used for print
     * @return array[] array of arrays containing data in legacy format (compatible with mtablepdf class)
     * @throws coding_exception ;
     * @throws dml_exception
     */
    public function get_data($type = self::FORMAT_DOWNLOAD) {
        global $SESSION;

        if (!$this->setup) {
            $this->setup();
        }

        // We need to access (read only) the collapsed columns!
        if ($this->is_persistent()) {
            $this->prefs = json_decode(get_user_preferences('flextable_' . $this->uniqueid), true);
        } else if (isset($SESSION->flextable[$this->uniqueid])) {
            $this->prefs = $SESSION->flextable[$this->uniqueid];
        }

        // Remove selection column not used for download!
        foreach ($this->columns as $col => $num) {
            if ($col == 'selection') {
                unset($this->headers[$num]);
                unset($this->columns[$col]);
                unset($this->columnformat[$col]);
            }
        }
        $this->columns = array_flip(array_keys($this->columns));
        $this->headers = array_values($this->headers);
        $this->cellwidth = array_values($this->cellwidth);
        foreach ($this->columns as $col => $num) {
            if (isset($this->prefs['collapse'][$col]) && ($this->prefs['collapse'][$col] == true)) {
                    unset($this->headers[$num]);
                    unset($this->columns[$col]);
                    unset($this->columnformat[$col]);
                    unset($this->cellwidth[$num]);
            }
        }
        $this->columns = array_flip(array_keys($this->columns));
        $this->headers = array_values($this->headers);
        $this->cellwidth = array_values($this->cellwidth);

        // Now we simulate a download (at least for query_db) so it will fetch everything!
        $this->download = true;
        $this->query_db(30, false);
        $this->download = '';

        if ($this->rawdata instanceof \Traversable && !$this->rawdata->valid()) {
            return [[], [], [], [], []];
        }
        if (!$this->rawdata) {
            return [[], [], [], [], []];
        }

        $returndata = [];
        $this->format = $type;
        foreach ($this->rawdata as $key => $row) {
            $returndata[$key] = $this->format_row($row);
        }

        $this->format = self::FORMAT_HTML;

        if ($this->rawdata instanceof recordset_walk ||
                $this->rawdata instanceof moodle_recordset) {
            $this->rawdata->close();
        }

        return [$this->columns, $this->headers, $returndata, $this->columnformat, $this->cellwidth];
    }

    /**
     * Dynamically adds the example's columns to the arrays!
     *
     * @param string[] $tablecolumns Table's columns identifieres
     * @param string[] $tableheaders Table's header texts as strings
     * @param mixed[] $helpicons array of help icons and nulls
     */
    private function addexamplecolumns(&$tablecolumns, &$tableheaders, &$helpicons) {
        // Dynamically add examples!
        $middle = count($this->checkmark->checkmark->examples) / 2;
        $count = 0;
        foreach ($this->checkmark->checkmark->examples as $key => $example) {
            $width = strlen($example->shortname) + strlen($example->grade) + 4;
            $tableheaders[] = $example->shortname." (".$example->grade.'P)';
            $tablecolumns[] = 'example'.$key;
            $this->cellwidth[] = ['mode' => 'Fixed', 'value' => $width];
            $this->columnformat['example'.$key] = ['align' => 'C'];
            $helpicons[] = null;
        }

        $this->add_colgroup(count($this->checkmark->checkmark->examples), 'examples');

    }

    /**
     * Helper method to create the table for submissions view!
     *
     * @param checkmark|int $checkmarkorcmid checkmark object or course module id of checkmark instance
     * @param int $filter Which filter to use (FILTER_ALL, FILTER_REQUIRE_GRADING, FILTER_SUBMITTED, ...)
     * @return submissionstable object
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    static public function create_submissions_table($checkmarkorcmid = null, $filter = \checkmark::FILTER_ALL) {
        global $CFG, $DB;
        // We need to have the same ID to ensure the columns are collapsed if their collapsed in the other table!
        $table = new submissionstable('mod-checkmark-submissions', $checkmarkorcmid);

        $table->quickgrade = get_user_preferences('checkmark_quickgrade', 0);
        $table->filter = $filter;
        $table->showsubmission = true;
        $table->tabindex = 0;
        $table->defaultselectstate = false;

        // Adapt table for submissions view (columns, etc.)!
        $tablecolumns = ['selection', 'fullname'];
        $tableheaders = ['', get_string('fullnameuser')];
        $helpicons = [null, null];
        $table->add_colgroup(1, 'sel');

        $useridentity = get_extra_user_fields($table->context);
        foreach ($useridentity as $cur) {
            $tablecolumns[] = $cur;
            $tableheaders[] = ($cur == 'phone1') ? get_string('phone') : get_string($cur);
            $helpicons[] = null;
        }
        $table->add_colgroup(count($useridentity) + 1, 'user');
        if ($table->groupmode != NOGROUPS) {
            $tableheaders[] = get_string('group');
            $tablecolumns[] = 'groups';
            $helpicons[] = null;
            $table->add_colgroup(1, 'group');
        }

        $tableheaders[] = get_string('lastmodified').' ('.get_string('submission', 'checkmark').')';
        $tablecolumns[] = 'timesubmitted';
        $helpicons[] = null;
        $table->add_colgroup(1, 'timesubmitted');

        $table->addexamplecolumns($tablecolumns, $tableheaders, $helpicons);

        if ($table->checkmark->checkmark->grade != 0) {
            $tableheaders[] = get_string('grade');
            $tablecolumns[] = 'grade';
            $helpicons[] = null;
            $feedbackcols = 3;
        } else {
            $feedbackcols = 2;
        }
        $tableheaders[] = get_string('comment', 'checkmark');
        $tablecolumns[] = 'feedback';
        $helpicons[] = null;
        $tableheaders[] = get_string('lastmodified').' ('.get_string('grade').')';
        $tablecolumns[] = 'timemarked';
        $helpicons[] = null;
        $table->add_colgroup($feedbackcols, 'feedback');

        if ($table->checkmark->checkmark->trackattendance) {
            $tableheaders[] = get_string('attendance', 'checkmark');
            $tablecolumns[] = 'attendance';
            $helpicons[] = new \help_icon('attendance', 'checkmark');
            $table->add_colgroup(1, 'attendance');
        }

        $tableheaders[] = get_string('status');
        $tablecolumns[] = 'status';
        $helpicons[] = null;
        $tableheaders[] = get_string('finalgrade', 'grades');
        $tablecolumns[] = 'finalgrade';
        $helpicons[] = null;
        if ($table->usesoutcomes) {
            $tableheaders[] = get_string('outcome', 'grades');
            $tablecolumns[] = 'outcome'; // No sorting based on outcomes column!
            $helpicons[] = null;
            $table->add_colgroup(3, 'status_and_gradebook');
        } else {
            $table->add_colgroup(2, 'status_and_gradebook');
        }
        if ($table->checkmark->checkmark->presentationgrading) {
            $span = 1;
            if ($table->checkmark->checkmark->presentationgrade) {
                $tableheaders[] = get_string('presentationgrade_table', 'checkmark');
                $tablecolumns[] = 'presentationgrade';
                $helpicons[] = null;
                $span++;
            }
            $tableheaders[] = get_string('presentationfeedback_table', 'checkmark');
            $tablecolumns[] = 'presentationfeedback';
            $helpicons[] = null;
            $table->add_colgroup($span, 'presentationgrade');
        }

        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $table->define_help_for_headers($helpicons);
        $table->define_baseurl($CFG->wwwroot.'/mod/checkmark/submissions.php?id='.$table->checkmark->cm->id.
                               '&amp;currentgroup='.$table->currentgroup);

        $table->sortable(true, 'lastname'); // Sorted by lastname by default!
        $table->collapsible(true);
        $table->initialbars(true);

        $table->column_suppress('fullname');

        $table->column_class('fullname', 'fullname');
        $table->column_class('idnumber', 'idnumber');
        if ($table->groupmode != NOGROUPS) {
            $table->column_class('groups', 'groups');
            $table->no_sorting('groups');
        }
        $table->column_class('grade', 'grade');
        $table->column_class('feedback', 'feedback');
        $table->column_class('timesubmitted', 'timesubmitted');

        foreach ($table->checkmark->checkmark->examples as $key => $example) {
            $table->column_class('example'.$key, 'example'.$key . ' colexample');
            $table->no_sorting('example'.$key);
        }

        $table->column_class('timemarked', 'timemarked');
        $table->column_class('status', 'status');
        $table->column_class('finalgrade', 'finalgrade');
        if ($table->usesoutcomes) {
            $table->column_class('outcome', 'outcome');
        }
        if ($table->checkmark->checkmark->presentationgrading) {
            if ($table->checkmark->checkmark->presentationgrade) {
                $table->column_class('presentationgrade', 'presentationgrade');
            }
            $table->column_class('presentationfeedback', 'presentationfeedback');
        }

        $table->no_sorting('finalgrade');
        $table->no_sorting('outcome');
        $table->no_sorting('status');

        // Create and set the SQL!
        $params = [];
        $useridentityfields = get_extra_user_fields_sql($table->context, 'u');
        $ufields = \user_picture::fields('u');
        $examplecount = count($table->checkmark->checkmark->examples);
        $params['examplecount'] = $examplecount;

        if ($table->groupmode != NOGROUPS) {
            $getgroupsql = "SELECT MAX(grps.courseid) AS courseid, MIN(grps.name) AS groups, grpm.userid AS userid
                         FROM {groups_members} grpm
                    LEFT JOIN {groups} grps ON grps.id = grpm.groupid
                        WHERE grps.courseid = :courseid
                     GROUP BY grpm.userid
                     ORDER BY MIN(grps.name) ASC";
            $params['courseid'] = $table->checkmark->course->id;
            $groupssql = ' LEFT JOIN ('.$getgroupsql.') AS grpq ON u.id = grpq.userid ';
        } else {
            $groupssql = '';
        }
        $fields = "u.id, ' ' AS selection, ' ' AS picture, ".$ufields." ".$useridentityfields.",
                  s.id AS submissionid, f.id AS feedbackid, f.grade, f.feedback,
                  s.timemodified AS timesubmitted, f.timemodified AS timemarked";
        if ($table->checkmark->checkmark->trackattendance) {
            $fields .= ", f.attendance AS attendance";
        }
        if ($table->checkmark->checkmark->presentationgrading) {
            if ($table->checkmark->checkmark->presentationgrade) {
                $fields .= ", f.presentationgrade AS presentationgrade";
            }
            $fields .= ", f.presentationfeedback AS presentationfeedback";
        }
        if ($table->groupmode != NOGROUPS) {
            $fields .= ", groups";
        }
        $params['checkmarkid'] = $table->checkmark->checkmark->id;
        $params['checkmarkid2'] = $table->checkmark->checkmark->id;

        $users = $table->get_userids($filter);
        list($sqluserids, $userparams) = $DB->get_in_or_equal($users, SQL_PARAMS_NAMED, 'user');
        $params = array_merge_recursive($params, $userparams);

        $from = "{user} u ".
                "LEFT JOIN {checkmark_submissions} s ON (u.id = s.userid) AND s.checkmarkid = :checkmarkid
                 LEFT JOIN {checkmark_feedbacks} f ON (u.id = f.userid) AND f.checkmarkid = :checkmarkid2
                 ".
                $groupssql;

        $where = '';
        if ($filter == \checkmark::FILTER_SUBMITTED) {
            $where .= 's.timemodified > 0 AND ';
        } else if ($filter == \checkmark::FILTER_REQUIRE_GRADING) {
            $where .= 'COALESCE(f.timemodified,0) < COALESCE(s.timemodified,0) AND ';
        } else if ($filter == \checkmark::FILTER_ATTENDANT) {
            $where .= 'f.attendance = 1 AND ';
        } else if ($filter == \checkmark::FILTER_ABSENT) {
            $where .= 'f.attendance = 0 AND ';
        } else if ($filter == \checkmark::FILTER_UNKNOWN) {
            $where .= 'f.attendance IS NULL AND ';
        }
        $where .= "u.id ".$sqluserids;
        $groupby = " u.id, s.id, f.id, ".$ufields." ".$useridentityfields;
        if ($table->groupmode != NOGROUPS) {
            $groupby .= ", grpq.groups";
        }

        $table->set_sql($fields, $from, $where, $params, $groupby);
        $table->set_count_sql("SELECT COUNT(u.id) FROM ".$from." WHERE ".$where, $params);

        $table->gradinginfo = grade_get_grades($table->checkmark->course->id, 'mod', 'checkmark', $table->checkmark->checkmark->id,
                                               $users);
        $table->strupdate = get_string('update');
        $table->strgrade = get_string('grade');
        $table->grademenu = make_grades_menu($table->checkmark->checkmark->grade);
        if ($table->checkmark->checkmark->presentationgrading && $table->checkmark->checkmark->presentationgrade) {
            $table->presentationgrademenu = make_grades_menu($table->checkmark->checkmark->presentationgrade);
        }

        return $table;
    }

    /**
     * Returns the previous, current and next userid (to navigate the list) as array
     *
     * @param int $userid User to get surrounding entries for
     * @return array|bool false if userid is not found, else triple previous userid, userid and next userid (or false if some of
     *                    those don't exist)
     * @throws dml_exception
     */
    public function get_triple($userid) {
        if (!$this->setup) {
            $this->setup();
        }
        if (!$this->rawdata) {
            $this->query_db(0);
        }

        if (!key_exists($userid, $this->rawdata)) {
            return false;
        }

        $userids = array_keys($this->rawdata);
        $pos = array_search($userid, $userids);

        if (count($userids) == 1) {
            return [false, $userids[$pos], false];
        } else if ($pos > 0 && $pos < count($userids) - 1) {
            return [$userids[$pos - 1], $userids[$pos], $userids[$pos + 1]];
        } else if ($pos == 0) {
            return [false, $userids[$pos], $userids[$pos + 1]];
        } else if ($pos == count($userids) - 1) {
            return [$userids[$pos - 1], $userids[$pos], false];
        }

        return false;
    }

    /**
     * Set the sql to query the db. Query will be :
     *      SELECT $fields FROM $from WHERE $where
     * Of course you can use sub-queries, JOINS etc. by putting them in the
     * appropriate clause of the query.
     *
     * @param string $fields fields to fetch (SQL snippet)
     * @param string $from from where to fetch (SQL snippet)
     * @param string $where where conditions for SQL query (SQL snippet)
     * @param array $params (optional) params for query
     * @param string $groupby (optional) groupby clause (SQL snippet)
     */
    public function set_sql($fields, $from, $where, array $params = null, $groupby = '') {
        parent::set_sql($fields, $from, $where, $params);
        $this->sql->groupby = $groupby;
    }

    /**
     * Helper method to create the table for export view!
     *
     * @param checkmark|int $checkmarkorcmid checkmark object or course module id of checkmark instance
     * @param int $filter which filter to use
     * @param int[] $ids for which user ids to filter
     * @return submissionstable object
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    static public function create_export_table($checkmarkorcmid = null, $filter = \checkmark::FILTER_ALL, $ids = []) {
        global $CFG, $DB;
        // We need to have the same ID to ensure the columns are collapsed if their collapsed in the other table!
        $table = new submissionstable('mod-checkmark-submissions', $checkmarkorcmid);

        $table->sumabs     = get_user_preferences('checkmark_sumabs', 1);
        $table->sumrel     = get_user_preferences('checkmark_sumrel', 1);
        $forcesinglelinenames = get_user_preferences('checkmark_forcesinglelinenames', 0);
        $table->quickgrade = 0;
        $table->filter = $filter;
        $table->defaultselectstate = true; // Select all checkboxes by default!

        // Adapt table for export view (columns, etc.)!
        $tableheaders = [''];
        $tablecolumns = ['selection'];
        $helpicons = [null];
        $table->cellwidth = [];
        $table->columnformat = [];
        $namefieldcount = 1;
        if ($table->is_downloading()){
            $table->cellwidth[] = ['mode' => 'Fixed', 'value' => '25'];
            $table->columnformat['fullname'] = ['align' => 'L'];
            $tablecolumns[]= 'fullname';
            $tableheaders[]= get_string('name');
            $helpicons[] = NULL;
            $table->column_suppress('fullname');
            $table->column_class('fullname', 'fullname');
            if ($forcesinglelinenames) {
                $table->columnformat['fullname']['stretch'] = MTablePDF::STRETCH_SCALING;
            }
        } else {
            // Find name fields used in nameformat and create columns in the same order.

            if (has_capability('moodle/site:viewfullnames', $table->context)) {
                $nameformat = $CFG->alternativefullnameformat;
            } else {
                $nameformat = $CFG->fullnamedisplay;
            }
            // Use default setting from language if no other format is defined.
            if ($nameformat == 'language') {
                $nameformat = get_string('fullnamedisplay');
            }
            $allnamefields = get_all_user_name_fields();
            $usednamefields = [];
            foreach ($allnamefields as $name) {
                if (($position = strpos($nameformat, $name)) !== false) {
                    $usednamefields[$position] = $name;
                }
            }
            // Sort names in the order stated in $nameformat
            ksort($usednamefields);

            foreach ($usednamefields as $name) {
                $tablecolumns[]= $name;
                $tableheaders[]= get_string($name);
                $helpicons[] = NULL;
                $table->column_suppress($name);
                $table->column_class($name, $name);
                $table->cellwidth[] = ['mode' => 'Fixed', 'value' => '25'];
                $table->columnformat[$name] = ['align' => 'L'];
            }
            $namefieldcount = count($usednamefields);

        }
        $table->add_colgroup(1, 'sel');

        $useridentity = get_extra_user_fields($table->context);
        foreach ($useridentity as $cur) {
            $tableheaders[] = ($cur == 'phone1') ? get_string('phone') : get_string($cur);
            $tablecolumns[] = $cur;
            $table->cellwidth[] = ['mode' => 'Fixed', 'value' => '20'];
            $table->columnformat[$cur] = ['align' => 'L'];
            $helpicons[] = null;
        }
        $table->add_colgroup(count($useridentity) + $namefieldcount, 'user');
        if ($table->groupmode != NOGROUPS) {
            $tableheaders[] = get_string('group');
            $tablecolumns[] = 'groups';
            $table->cellwidth[] = ['mode' => 'Fixed', 'value' => '20'];
            $table->columnformat['groups'] = ['align' => 'L'];
            $helpicons[] = null;
            $table->add_colgroup(1, 'group');
        }

        $tablecolumns[] = 'timesubmitted';
        $tableheaders[] = get_string('lastmodified').' ('.get_string('submission', 'checkmark').')';
        $table->cellwidth[] = ['mode' => 'Fixed', 'value' => '30'];
        $table->columnformat['timesubmitted'] = ['align' => 'L'];
        $helpicons[] = null;
        $table->add_colgroup(1, 'timesubmitted');

        $table->addexamplecolumns($tablecolumns, $tableheaders, $helpicons);

        if ((!empty($table->sumabs) || !empty($table->sumrel))) {
            $tableheaders[] = get_string('checkmarks', 'checkmark');
            $tablecolumns[] = 'summary';
            $table->cellwidth[] = ['mode' => 'Fixed', 'value' => '20'];
            $table->columnformat['summary'] = ['align' => 'L'];
            $helpicons[] = null;
            $table->add_colgroup(1, 'summary');
        }

        if ($table->checkmark->checkmark->grade != 0) {
            $tableheaders[] = get_string('grade');
            $tablecolumns[] = 'grade';
            $table->cellwidth[] = ['mode' => 'Fixed', 'value' => '15'];
            $table->columnformat['grade'] = ['align' => 'R'];
            $helpicons[] = null;
        }

        $tableheaders[] = get_string('comment', 'checkmark');
        $tablecolumns[] = 'feedback';
        $table->cellwidth[] = ['mode' => 'Fixed', 'value' => '50'];
        $table->columnformat['feedback'] = ['align' => 'L'];
        $helpicons[] = null;

        if ($table->usesoutcomes) {
            $tableheaders[] = get_string('outcome', 'grades');
            $tablecolumns[] = 'outcome';
            $table->cellwidth[] = ['mode' => 'Fixed', 'value' => '50'];
            $table->columnformat['outcome'] = ['align' => 'L'];
            $helpicons[] = null;
            $table->add_colgroup(3, 'feedback outcomes');
        } else {
            $table->add_colgroup(2, 'feedback outcomes');
        }

        if ($table->checkmark->checkmark->trackattendance) {
            $tableheaders[] = get_string('attendance', 'checkmark');
            $helpicons[] = new \help_icon('attendance', 'checkmark');
            $tablecolumns[] = 'attendance';
            $table->cellwidth[] = ['mode' => 'Fixed', 'value' => '20'];
            $table->columnformat['attendance'] = ['align' => 'R'];
            $table->add_colgroup(1, 'attendance');
        }
        if ($table->checkmark->checkmark->presentationgrading) {
            $span = 1;
            if ($table->checkmark->checkmark->presentationgrade) {
                $tableheaders[] = get_string('presentationgrade_table', 'checkmark');
                $helpicons[] = null;
                $tablecolumns[] = 'presentationgrade';
                $table->cellwidth[] = ['mode' => 'Fixed', 'value' => '20'];
                $table->columnformat['presentationgrade'] = ['align' => 'R'];
                $span++;
            }
            $tableheaders[] = get_string('presentationfeedback_table', 'checkmark');
            $helpicons[] = null;
            $tablecolumns[] = 'presentationfeedback';
            $table->cellwidth[] = ['mode' => 'Fixed', 'value' => '50'];
            $table->columnformat['presentationfeedback'] = ['align' => 'L'];
            $table->add_colgroup($span, 'presentationgrade');
        }

        $tableheaders[] = get_string('signature', 'checkmark');
        $tablecolumns[] = 'signature';
        $table->cellwidth[] = ['mode' => 'Fixed', 'value' => '30'];
        $table->columnformat['signature'] = ['align' => 'L'];
        $helpicons[] = null;
        $table->add_colgroup(1, 'signature');

        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $table->define_help_for_headers($helpicons);
        $table->define_baseurl($CFG->wwwroot.'/mod/checkmark/export.php?id='.$table->checkmark->cm->id.
                               '&amp;currentgroup='.$table->currentgroup);

        $table->sortable(true, 'lastname'); // Sorted by lastname by default!
        $table->collapsible(true);
        $table->initialbars(true);
        if ($table->is_downloading()){

        } else {
            $table->column_suppress('lastname');
            $table->column_class('lastname', 'lastname');
            $table->column_suppress('firstname');
            $table->column_class('firstname', 'firstname');
        }
        foreach ($useridentity as $cur) {
            $table->column_class($cur, $cur == 'phone1' ? 'phone' : $cur);
        }
        if ($table->groupmode != NOGROUPS) {
            $table->column_class('groups', 'groups');
            $table->no_sorting('groups');
        }
        $table->column_class('timesubmitted', 'timesubmitted');

        foreach ($table->checkmark->checkmark->examples as $key => $example) {
            $table->column_class('example'.$key, 'example'.$key . ' colexample');
            $table->no_sorting('example'.$key);
        }

        if (!empty($table->sumabs) || !empty($table->sumrel)) {
            $table->column_class('summary', 'summary');
        }
        $table->column_class('grade', 'grade');
        $table->column_class('feedback', 'feedback');
        if ($table->usesoutcomes) {
            $table->column_class('outcome', 'outcome');
            $table->no_sorting('outcome');
        }
        if ($table->checkmark->checkmark->presentationgrading) {
            if ($table->checkmark->checkmark->presentationgrade) {
                $table->column_class('presentationgrade', 'presentationgrade');
            }
            $table->column_class('presentationfeedback', 'presentationfeedback');
        }

        $table->column_class('signature', 'signature');
        $table->no_sorting('signature');

        // Create and set the SQL!
        $params = [];
        $useridentityfields = get_extra_user_fields_sql($table->checkmark->context, 'u');
        $ufields = \user_picture::fields('u');
        $table->examplecount = count($table->checkmark->checkmark->examples);
        $params['examplecount'] = $table->examplecount;

        if ($table->groupmode != NOGROUPS) {
            $getgroupsql = "SELECT MAX(grps.courseid) AS courseid, MIN(grps.name) AS groups";
            $params['courseid'] = $table->checkmark->course->id;
            $getgroupsql .= ", grpm.userid AS userid
                         FROM {groups_members} grpm
                    LEFT JOIN {groups} grps ON grps.id = grpm.groupid
                        WHERE grps.courseid = :courseid
                     GROUP BY grpm.userid
                     ORDER BY MIN(grps.name) ASC";
            $params['courseid'] = $table->checkmark->course->id;
            $groupssql = ' LEFT JOIN ('.$getgroupsql.') AS grpq ON u.id = grpq.userid ';
        } else {
            $groupssql = '';
        }
        $fields = "u.id, ' ' AS selection, ".$ufields." ".$useridentityfields.",
                  MAX(s.id) AS submissionid, MAX(f.id) AS feedbackid, MAX(f.grade) AS grade,
                  MAX(f.feedback) AS feedback, MAX(s.timemodified) AS timesubmitted,
                  MAX(f.timemodified) AS timemarked, 100 * COUNT( DISTINCT cchks.id ) / :examplecount AS summary,
                  COUNT( DISTINCT cchks.id ) AS checks, f.attendance AS attendance";
        if ($table->checkmark->checkmark->presentationgrading) {
            if ($table->checkmark->checkmark->presentationgrade) {
                $fields .= ", f.presentationgrade AS presentationgrade";
            }
            $fields .= ", f.presentationfeedback AS presentationfeedback";
        }
        if ($table->groupmode != NOGROUPS) {
            $fields .= ", MAX(groups) AS groups";
        }
        $params['checkmarkid'] = $table->checkmark->checkmark->id;
        $params['checkmarkid2'] = $table->checkmark->checkmark->id;

        $users = $table->get_userids($filter, $ids);
        list($sqluserids, $userparams) = $DB->get_in_or_equal($users, SQL_PARAMS_NAMED, 'user');
        $params = array_merge_recursive($params, $userparams);

        $from = "{user} u ".
                "LEFT JOIN {checkmark_submissions} s ON u.id = s.userid AND s.checkmarkid = :checkmarkid
                 LEFT JOIN {checkmark_feedbacks} f ON u.id = f.userid AND f.checkmarkid = :checkmarkid2
                 LEFT JOIN {checkmark_checks} gchks ON gchks.submissionid = s.id
                 LEFT JOIN {checkmark_checks} cchks ON cchks.submissionid = s.id AND cchks.state = 1 ".
                $groupssql;

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
        }

        $groupby = " u.id, s.id, f.id, ".$ufields." ".$useridentityfields.", f.attendance";

        $table->set_sql($fields, $from, $where, $params, $groupby);
        $table->set_count_sql("SELECT COUNT(DISTINCT u.id) FROM ".$from." WHERE ".$where, $params);

        $table->gradinginfo = grade_get_grades($table->checkmark->course->id, 'mod', 'checkmark', $table->checkmark->checkmark->id,
                                               $users);

        return $table;
    }

    /**
     * Adds a new colgroup!
     *
     * @param int $span how many cols the group should span
     * @param string $class the col groups css class(es)
     */
    protected function add_colgroup($span = 1, $class) {
        $colgrp = new \stdClass();
        $colgrp->span = $span;
        $colgrp->class = $class;

        $this->colgroups[] = $colgrp;
    }

    /**
     * Here we extend the moodle sql_table with the ability to output colgroups!
     */
    public function start_html() {
        global $PAGE;

        if ($this->hasoverrides) {
            $params = new \stdClass();
            $params->id = $this->attributes['id'];
            $PAGE->requires->js_call_amd('mod_checkmark/overrides', 'initializer', [$params]);
        }
        if ($this->quickgrade && !$this->is_downloading() && ($this->format != self::FORMAT_DOWNLOAD)) {
            $PAGE->requires->js_call_amd('mod_checkmark/quickgrade', 'init');
        }

        parent::start_html();
        if (!empty($this->colgroups)) {
            foreach ($this->colgroups as $colgrp) {
                echo \html_writer::start_tag('colgroup', ['class' => $colgrp->class,
                                                          'span'  => $colgrp->span]);
                for ($i = 0; $i < $colgrp->span; $i++) {
                    echo \html_writer::empty_tag('col');
                }
                echo \html_writer::end_tag('colgroup');
            }
        }
    }

    /**
     * Set or determine if initials bar output should be suppressed
     *
     * @return bool false
     *
     * @deprecated since Moodle 3.3 TODO remove in Moodle 3.7
     */
    public function suppress_initials_output() {
        debugging('Suppression of initials output has been deprecated, due to changes in Moodle core!', DEBUG_DEVELOPER);

        return false;
    }

    /**
     * Helpermethod to get the enrolled and filtered userids!
     *
     * @param int $filter Currently active filter (FILTER_ALL, FILTER_REQUIRE_GRADING, FILTER_SUBMITTED...)
     * @param int[] $ids (optional) Array of userids to filter for
     * @return int[] the filtered userids!
     * @throws dml_exception
     * @throws coding_exception
     */
    public function get_userids($filter, $ids = []) {
        global $DB;

        // Get all ppl that are allowed to submit checkmarks!
        list($esql, $params) = get_enrolled_sql($this->context, 'mod/checkmark:submit', $this->currentgroup);
        if (!empty($ids) && is_array($ids)) {
            $usrlst = $ids;
        }
        if (!empty($usrlst)) {
            list($sqluserids, $userparams) = $DB->get_in_or_equal($usrlst, SQL_PARAMS_NAMED, 'user');
            $params = array_merge_recursive($params, $userparams);
            $sqluserids = " AND u.id ".$sqluserids;
        } else {
            $sqluserids = "";
        }

        if (($filter == \checkmark::FILTER_SELECTED) || ($filter == \checkmark::FILTER_ALL)) {
            $sql = "SELECT u.id FROM {user} u
                      JOIN (".$esql.") eu ON eu.id=u.id
                     WHERE 1=1".$sqluserids;
        } else {
            $wherefilter = '';
            if ($filter == \checkmark::FILTER_SUBMITTED) {
                $wherefilter = " AND s.timemodified > 0";
            } else if ($filter == \checkmark::FILTER_NOT_SUBMITTED) {
                $wherefilter = " AND (s.timemodified <= 0 OR s.timemodified IS NULL)";
            } else if ($filter == \checkmark::FILTER_REQUIRE_GRADING) {
                $wherefilter = " AND COALESCE(f.timemodified,0) < COALESCE(s.timemodified,0) ";
            } else if ($filter == \checkmark::FILTER_EXTENSION) {
                $wherefilter = " AND o.id IS NOT NULL";
            }
            $params['checkmarkid'] = $this->checkmark->checkmark->id;
            $params['checkmarkid2'] = $this->checkmark->checkmark->id;
            $params['checkmarkid3'] = $this->checkmark->checkmark->id;
            $sql = "SELECT DISTINCT u.id FROM {user} u
                 LEFT JOIN (".$esql.") eu ON eu.id=u.id
                 LEFT JOIN {checkmark_submissions} s ON (u.id = s.userid) AND s.checkmarkid = :checkmarkid
                 LEFT JOIN {checkmark_feedbacks} f ON (u.id = f.userid) AND f.checkmarkid = :checkmarkid2
                 LEFT JOIN {groups_members} g ON (g.userid = u.id)
                 LEFT JOIN {checkmark_overrides} o ON (u.id = o.userid OR g.groupid = o.groupid) AND o.checkmarkid = :checkmarkid3
                     WHERE u.deleted = 0
                           AND eu.id = u.id ".$sqluserids."
                           ".$wherefilter;
        }
        $users = $DB->get_records_sql($sql, $params);

        if (!empty($users)) {
            $users = array_keys($users);
        }

        if (empty($users)) {
            return [-1];
        }

        return $users;
    }

    /**
     * Renders links to select all/ungraded/submitted/none entries
     *
     * @param bool $returnonlylinks if true only the links will be returned without title!
     * @return string HTML snippet to output in page
     * @throws moodle_exception
     */
    public function checkbox_controller($returnonlylinks = true) {
        global $PAGE;

        $baseurl = $PAGE->url;

        $allurl = new \moodle_url($baseurl, ['select' => self::SEL_ALL]);
        $noneurl = new \moodle_url($baseurl, ['select' => self::SEL_NONE]);
        $reqgradingurl = new \moodle_url($baseurl, ['select' => self::SEL_REQ_GRADING]);
        $submittedurl = new \moodle_url($baseurl, ['select' => self::SEL_SUBMITTED]);

        $randomid = \html_writer::random_id('checkboxcontroller');
        if (!$returnonlylinks) {
            $title = get_string('select', 'checkmark').':&nbsp;';
        } else {
            $title = '';
        }

        $params = new \stdClass();
        $params->table = '.usersubmissions table.submissions';
        $params->id = $randomid;
        $PAGE->requires->js_call_amd('mod_checkmark/checkboxcontroller', 'initializer', [$params]);

        return \html_writer::tag('div', $title.
                                        \html_writer::link($allurl, get_string('all'), ['class' => 'all']).' / '.
                                        \html_writer::link($noneurl, get_string('none'), ['class' => 'none']).' / '.
                                        \html_writer::link($reqgradingurl, get_string('ungraded', 'checkmark'),
                                                           ['class' => 'ungraded']).' / '.
                                        \html_writer::link($submittedurl, get_string('submitted', 'checkmark'),
                                                           ['class' => 'submitted']),
                                 ['id' => $randomid]);
    }

    /***************************************************************
     *** COLUMN OUTPUT METHODS *************************************
     **************************************************************/

    /**
     * This function is called for each data row to allow processing of the
     * XXX value.
     *
     * @param object $values Contains object with all the values of record.
     * @return string Return XXX.
     * @throws coding_exception
     */
    public function col_selection($values) {
        // If the data is being downloaded than we don't want to show HTML.
        if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
            return '';
        } else {
            $select = optional_param('select', null, PARAM_INT);
            // All get selected by default in export table, not selected in submissions table!
            $selectstate = $this->defaultselectstate;

            $attr = ['class' => 'checkboxgroup1'];
            if ($select == self::SEL_ALL) {
                $selectstate = true;
            } else if ($select === self::SEL_NONE) {
                $selectstate = false;
            }
            if (empty($values->timemarked) || ($values->timesubmitted > $values->timemarked)) {
                if ($select == self::SEL_REQ_GRADING) {
                    $selectstate = true;
                }
                $attr['data-ungraded'] = 1;
            } else {
                if ($select == self::SEL_REQ_GRADING) {
                    $selectstate = false;
                }
                $attr['data-ungraded'] = 0;
            }
            if (!empty($values->submissionid)) {
                if ($select == self::SEL_SUBMITTED) {
                    $selectstate = true;
                }
                $attr['data-submitted'] = 1;
            } else {
                if ($select == self::SEL_SUBMITTED) {
                    $selectstate = false;
                }
                $attr['data-submitted'] = 0;
            }

            return \html_writer::checkbox('selected[]', $values->id, $selectstate, null, $attr);
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * user name.
     *
     * @param object $values Contains object with all the values of record.
     * @return string Return user fullname.
     */
    public function col_fullname($values) {
        if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
            return strip_tags(parent::col_fullname($values));
        } else {
            return parent::col_fullname($values);
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * user picture.
     * \
     * @param object $values Contains object with all the values of record.
     * @return string Return user picture markup.
     */
    public function col_picture($values) {
        global $OUTPUT;
        // If the data is being downloaded than we don't want to show HTML.
        if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
            return '';
        } else {
            return $OUTPUT->user_picture($values);
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * user's groups.
     *
     * @param object $values Contains object with all the values of record.
     * @return string Return user groups.
     */
    public function col_groups($values) {
        if (isset($values->groups)) {
            $groups = groups_get_all_groups($this->checkmark->course->id, $values->id, 0, 'g.name');
            $values->groups = '';
            foreach ($groups as $group) {
                if ($values->groups != '') {
                    $values->groups .= ', ';
                }
                $values->groups .= $group->name;
            }
            if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                return $values->groups;
            } else {
                return \html_writer::tag('div', $values->groups, ['id' => 'gr'.$values->id]);
            }
        } else if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
            return '';
        } else {
            return \html_writer::tag('div', '-', ['id' => 'gr'.$values->id]);
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * user's grade.
     *
     * @param object $values Contains object with all the values of record.
     * @return string Return user's grade.
     * @throws coding_exception
     */
    public function col_grade($values) {
        $finalgrade = $this->gradinginfo->items[CHECKMARK_GRADE_ITEM]->grades[$values->id];
        $finalgrade->formatted_grade = $this->checkmark->display_grade($finalgrade->grade, CHECKMARK_GRADE_ITEM);
        $lockedoroverridden = 'locked';
        if ($finalgrade->overridden) {
            $lockedoroverridden = 'overridden';
        }
        if ($values->feedbackid) {
            // Print grade, dropdown or text!
            if ($finalgrade->locked || $finalgrade->overridden) {
                $gradeattr = ['id'    => 'g'.$values->id,
                              'class' => $lockedoroverridden];
                if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                    return $finalgrade->formatted_grade;
                } else {
                    return \html_writer::tag('div', $finalgrade->formatted_grade, $gradeattr);
                }
            } else if ($this->quickgrade && !$this->is_downloading() && ($this->format != self::FORMAT_DOWNLOAD)) {
                $attributes = [];
                $attributes['tabindex'] = $this->tabindex++;
                $menu = \html_writer::select($this->grademenu,
                                            'menu['.$values->id.']',
                                            (int)$values->grade,
                                            [-1 => get_string('nograde')],
                                            $attributes);
                $oldgradeattr = ['type'  => 'hidden',
                                 'name'  => 'oldgrade['.$values->id.']',
                                 'value' => $values->grade];
                $oldgrade = \html_writer::empty_tag('input', $oldgradeattr);
                return \html_writer::tag('div', $menu.$oldgrade, ['id' => 'g'.$values->id]);
            } else {
                if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                    return $this->checkmark->display_grade($values->grade);
                } else {
                    return \html_writer::tag('div', $this->checkmark->display_grade($values->grade),
                                             ['id' => 'g'.$values->id]);
                }
            }
        } else {
            if ($finalgrade->locked || $finalgrade->overridden) {
                if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                    return $finalgrade->formatted_grade;
                } else {
                    return \html_writer::tag('div', $finalgrade->formatted_grade, ['id' => 'g'.$values->id]);
                }
            } else if ($this->quickgrade && !$this->is_downloading() && ($this->format != self::FORMAT_DOWNLOAD)) {
                // Allow editing!
                $attributes = [];
                $attributes['tabindex'] = $this->tabindex++;
                $menu = \html_writer::select($this->grademenu,
                                            'menu['.$values->id.']',
                                            $values->grade,
                                            [-1 => get_string('nograde')],
                                            $attributes);
                $oldgradearr = ['type'  => 'hidden',
                                'name'  => 'oldgrade'.$values->id,
                                'value' => $values->grade];
                $oldgrade = \html_writer::empty_tag('input', $oldgradearr);
                return \html_writer::tag('div', $menu.$oldgrade, ['id' => 'g'.$values->id]);
            } else {
                if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                    return '-';
                } else {
                    return \html_writer::tag('div', '-', ['id' => 'g'.$values->id]);
                }
            }
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * user's feedback.
     *
     * @param object $values Contains object with all the values of record.
     * @return string Return user's feedback.
     */
    public function col_feedback($values) {
        $finalgrade = $this->gradinginfo->items[CHECKMARK_GRADE_ITEM]->grades[$values->id];
        if ($values->feedbackid) {
            // Print Comment!
            if ($finalgrade->locked || $finalgrade->overridden) {
                if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                    return $finalgrade->str_feedback;
                } else {
                    return \html_writer::tag('div', $finalgrade->str_feedback, ['id' => 'com'.$values->id]);
                }
            } else if ($this->quickgrade && !$this->is_downloading() && ($this->format != self::FORMAT_DOWNLOAD)) {
                $feedbackclean = self::convert_html_to_text($values->feedback);
                $inputarr = ['type'  => 'hidden',
                             'name'  => 'oldfeedback['.$values->id.']',
                             'value' => $feedbackclean];
                $oldfeedback = \html_writer::empty_tag('input', $inputarr);
                $content = \html_writer::tag('textarea', $feedbackclean, ['tabindex' => $this->tabindex++,
                                                                             'name'     => 'feedback['.$values->id.']',
                                                                             'id'       => 'feedback'.$values->id,
                                                                             'rows'     => 2,
                                                                             'cols'     => 20]);
                return \html_writer::tag('div', $content.$oldfeedback, ['id' => 'com'.$values->id]);
            } else {
                if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                    return $values->feedback;
                } else {
                    return \html_writer::tag('div', $values->feedback, ['id' => 'com'.$values->id]);
                }
            }
        } else {
            if ($finalgrade->locked || $finalgrade->overridden) {
                if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                    return $finalgrade->str_feedback;
                } else {
                    return \html_writer::tag('div', $finalgrade->str_feedback, ['id' => 'com'.$values->id]);
                }
            } else if ($this->quickgrade && !$this->is_downloading() && ($this->format != self::FORMAT_DOWNLOAD)) {
                $inputarr = ['type'  => 'hidden',
                             'name'  => 'oldfeedback'.$values->id,
                             'value' => trim($values->feedback)];
                $oldfeedback = \html_writer::empty_tag('input', $inputarr);

                $content = \html_writer::tag('textarea', $values->feedback, ['tabindex'  => $this->tabindex++,
                                                                             'name'      => 'feedback['.$values->id.']',
                                                                             'id'        => 'feedback'.$values->id,
                                                                             'rows'      => '2',
                                                                             'cols'      => '20']);
                return \html_writer::tag('div', $content.$oldfeedback, ['id' => 'com'.$values->id]);
            } else {
                if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                    return '';
                } else {
                    return \html_writer::tag('div', '&nbsp;', ['id' => 'com'.$values->id]);
                }
            }
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * user's submission time.
     *
     * @param object $values Contains object with all the values of record.
     * @return string Return user time of submission.
     * @throws coding_exception
     */
    public function col_timesubmitted($values) {
        if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
            $timeformat = get_string('strftimedatetimeshort');
        } else {
            $timeformat = get_string('strftimedatetime');
        }
        // Prints student answer and student modified date!
        if ($values->timesubmitted > 0) {
            $content = userdate($values->timesubmitted, $timeformat);

            $overrides = checkmark_get_overridden_dates($this->checkmark->checkmark->id, $values->id, $this->checkmark->course->id);
            if ($overrides && $overrides->timedue !== null) {
                $timedue = $overrides->timedue;
            } else {
                $timedue = $this->checkmark->checkmark->timedue;
            }
            if ($values->timesubmitted >= $timedue) {
                $content .= $this->checkmark->display_lateness($values->timesubmitted, $values->id);
            }
            if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                return strip_tags($content);
            } else {
                return \html_writer::tag('div', $content, ['id' => 'ts'.$values->id]);
            }
        } else {
            if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                return '-';
            } else {
                return \html_writer::tag('div', '-', ['id' => 'ts'.$values->id]);
            }
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * user's time of grading.
     *
     * @param object $values Contains object with all the values of record.
     * @return string Return user's time of grading.
     * @throws coding_exception
     */
    public function col_timemarked($values) {
        if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
            $timeformat = get_string('strftimedatetimeshort');
        } else {
            $timeformat = get_string('strftimedatetime');
        }
        $finalgrade = $this->gradinginfo->items[CHECKMARK_GRADE_ITEM]->grades[$values->id];
        if ($finalgrade->locked || $finalgrade->overridden) {
            $date = userdate($finalgrade->dategraded, $timeformat);
            if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                return $date;
            } else {
                return \html_writer::tag('div', $date, ['id' => 'tt'.$values->id]);
            }
        } else if ($values->feedbackid && $values->timemarked > 0) {
            $date = userdate($values->timemarked, $timeformat);
            if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                return $date;
            } else {
                return \html_writer::tag('div', $date, ['id' => 'tt'.$values->id]);
            }
        } else {
            if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                return \html_writer::tag('div', '-', ['id' => 'tt'.$values->id]);
            } else {
                return '-';
            }
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * user's grading button.
     *
     * @param object $values Contains object with all the values of record.
     * @return string Return user's grading button.
     * @throws coding_exception
     */
    public function col_status($values) {
        global $OUTPUT;

        // TODO: enhance with AJAX grading!
        $status = ($values->timemarked > 0) && ($values->timemarked >= $values->timesubmitted);
        $text = $status ? $this->strupdate : $this->strgrade;
        if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
            return $text;
        } else {
            // No more buttons, we use popups!
            $popupurl = '/mod/checkmark/submissions.php?id=' . $this->checkmark->cm->id .
                    '&amp;userid=' . $values->id . '&amp;mode=single' .
                    '&amp;filter=' . $this->filter;

            $button = $OUTPUT->action_link($popupurl, $text);

            // If overridden dates are present for this user, we display an icon with popup!
            if ($this->hasoverrides && $overrides = checkmark_get_overridden_dates($this->checkmark->cm->instance,
                            $values->id, $this->checkmark->course->id)) {
                $context = new stdClass();
                $overrideediturl = new moodle_url('/mod/checkmark/extend.php');
                $returnurl = new moodle_url('/mod/checkmark/submissions.php');
                $returnurl = $returnurl->out(true, array('id' => $this->checkmark->cm->id));
                if (!empty($overrides->userid)) {
                    $context->isgroupoverride = false;
                    $context->editurlstr = $overrideediturl->out(true, array('id' => $this->checkmark->cm->id,
                            'type' => \mod_checkmark\overrideform::USER, 'mode' => \mod_checkmark\overrideform::EDIT,
                            'users' => $overrides->userid, 'return' => $returnurl));
                } else if (!empty($overrides->groupid)) {
                    $context->isgroupoverride = true;
                    $context->groupname = groups_get_group_name($overrides->groupid);
                    $context->addurlstr = $overrideediturl->out(true, array('id' => $this->checkmark->cm->id,
                            'type' => \mod_checkmark\overrideform::USER, 'mode' => \mod_checkmark\overrideform::ADD,
                            'users' => $values->id, 'return' => $returnurl));
                    $context->editurlstr = $overrideediturl->out(true, array('id' => $this->checkmark->cm->id,
                            'type' => \mod_checkmark\overrideform::GROUP, 'mode' => \mod_checkmark\overrideform::EDIT,
                            'users' => $overrides->groupid, 'return' => $returnurl));
                }

                if ($overrides->timeavailable === null) {
                    $context->timeavailable = false;
                } else if ($overrides->timeavailable == 0) {
                    $context->timeavailable = get_string('noopen', 'checkmark');
                } else {
                    $context->timeavailable = userdate($overrides->timeavailable, get_string('strftimerecentfull'));
                }

                if ($overrides->timedue === null) {
                    $context->timedue = false;
                } else if ($overrides->timedue == 0) {
                    $context->timedue = get_string('noclose', 'checkmark');
                } else {
                    $context->timedue = userdate($overrides->timedue, get_string('strftimerecentfull'));
                }

                if ($overrides->cutoffdate === null) {
                    $context->cutoffdate = false;
                } else if ($overrides->cutoffdate == 0) {
                    $context->cutoffdate = get_string('noclose', 'checkmark');
                } else {
                    $context->cutoffdate = userdate($overrides->cutoffdate, get_string('strftimerecentfull'));
                }
                $button .= $OUTPUT->render_from_template('mod_checkmark/overridetooltip', $context);
            }

            return \html_writer::tag('div', $button, ['id'    => 'up'.$values->id,
                                                      'class' => 's'.$status]);
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * user's final grade.
     *
     * @param object $values Contains object with all the values of record.
     * @return string Return user's final grade.
     */
    public function col_finalgrade($values) {
        $finalgrade = $this->gradinginfo->items[CHECKMARK_GRADE_ITEM]->grades[$values->id];
        if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
            return $finalgrade->str_grade;
        } else {
            return \html_writer::tag('span', $finalgrade->str_grade, ['id' => 'finalgrade_'.$values->id]);
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * user's outcomes.
     *
     * @param object $values Contains object with all the values of record.
     * @return string Return user's outcomes.
     * @throws coding_exception
     */
    public function col_outcome($values) {
        $outcomes = '';
        foreach ($this->gradinginfo->outcomes as $n => $outcome) {
            $options = make_grades_menu(-$outcome->scaleid);
            $index = $outcome->grades[$values->id]->grade;
            if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                $outcomes .= $outcome->name.': '.$options[$index]."\n";
            } else {
                $outcomes .= \html_writer::start_tag('div', ['class' => 'outcome']);
                $outcomes .= \html_writer::tag('label', $outcome->name);
                if ($outcome->grades[$values->id]->locked or !$this->quickgrade) {
                    $options[0] = get_string('nooutcome', 'grades');
                    $outcomes .= ': '.\html_writer::tag('span', $options[$index], ['id' => 'outcome_'.$n.'_'. $values->id]);
                } else {
                    $attributes = [];
                    $attributes['id'] = 'outcome_'.$n.'_'.$values->id;
                    $usr = $values->id;
                    $outcomes .= ' '.\html_writer::select($options, 'outcome_'.$n.'['.$usr.']', $outcome->grades[$usr]->grade,
                                                          [get_string('nooutcome', 'grades')], $attributes);
                }
                $outcomes .= \html_writer::end_tag('div');
            }
        }
        return $outcomes;
    }

    /**
     * This function is called for each data row to allow processing of the
     * user's summary.
     *
     * @param object $values Contains object with all the values of record.
     * @return string Return user's summary.
     */
    public function col_summary($values) {
        if (!empty($this->sumabs) && !empty($this->sumrel)) {
            // Both values!
            $summary = $values->checks.'/'.$this->examplecount.' ('.round($values->summary, 2).'%)';
        } else if (!empty($this->sumabs)) {
            // Summary abs!
            $summary = $values->checks.'/'.$this->examplecount;
        } else {
            // Summary rel!
            $summary = round($values->summary, 2).'%';
        }
        if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
            return $summary;
        } else {
            return \html_writer::tag('div', $summary, ['id' => 'sum'.$values->id]);
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * user's attendance column.
     *
     * @param object $values Contains object with all the values of record.
     * @return string Return user's signature column (empty).
     * @throws coding_exception
     */
    public function col_attendance($values) {
        // Print attendance symbol or quickgrading checkboxes!
        if (!empty($this->checkmark->checkmark->attendancegradebook)) {
            $finalgrade = $this->gradinginfo->items[CHECKMARK_ATTENDANCE_ITEM]->grades[$values->id];
        } else {
            $finalgrade = new \stdClass();
            $finalgrade->locked = 0;
            $finalgrade->overridden = 0;
        }

        if ($finalgrade->locked || $finalgrade->overridden) {
            if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                return $finalgrade->grade;
            } else {
                $symbol = checkmark_get_attendance_symbol($finalgrade->grade);
                return \html_writer::tag('div', $symbol, ['id' => 'com'.$values->id]);
            }
        } else if (has_capability('mod/checkmark:trackattendance', $this->context)
                    && $this->quickgrade && !$this->is_downloading() && ($this->format != self::FORMAT_DOWNLOAD)) {
            if ($values->attendance === null) {
                $values->attendance = -1;
            }
            $inputarr = ['type'  => 'hidden',
                         'name'  => 'oldattendance['.$values->id.']',
                         'value' => $values->attendance];
            $oldattendance = \html_writer::empty_tag('input', $inputarr);
            $attr = ['tabindex' => $this->tabindex++,
                     'id'       => 'attendance'.$values->id];
            $options = [-1 => '? '.strtolower(get_string('unknown', 'checkmark')),
                        1  => '✓ '.strtolower(get_string('attendant', 'checkmark')),
                        0  => '✗ '.strtolower(get_string('absent', 'checkmark'))];
            if ($values->attendance === null) {
                $content = \html_writer::select($options, 'attendance['.$values->id.']', -1, false, $attr);
            } else {
                $content = \html_writer::select($options, 'attendance['.$values->id.']', $values->attendance, false, $attr);
            }

            return \html_writer::tag('div', $content.$oldattendance, ['id' => 'att'.$values->id]);
        } else {
            if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                if ($values->attendance == null) {
                    $values->attendance = '?';
                }
                return $values->attendance;
            } else {
                $symbol = checkmark_get_attendance_symbol($values->attendance);
                return \html_writer::tag('div', $symbol, ['id' => 'com'.$values->id]);
            }
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * user's presentationgrade.
     *
     * @param object $values Contains object with all the values of record.
     * @return string Return user's grade.
     * @throws coding_exception
     * @throws dml_exception
     */
    public function col_presentationgrade($values) {
        if (!$this->checkmark->checkmark->presentationgrading || !$this->checkmark->checkmark->presentationgrade) {
            return '';
        }
        $presgradebook = $this->checkmark->checkmark->presentationgradebook;
        $lockedoroverridden = '';
        if ($presgradebook) {
            $finalgrade = $this->gradinginfo->items[CHECKMARK_PRESENTATION_ITEM]->grades[$values->id];
            $finalgrade->formatted_grade = $this->checkmark->display_grade($finalgrade->grade, CHECKMARK_PRESENTATION_ITEM);
            $lockedoroverridden = 'locked';
            if ($finalgrade->overridden) {
                $lockedoroverridden = 'overridden';
            }
        } else {
            $finalgrade = false;
        }

        // Print grade, dropdown or text!
        if ($presgradebook && ($finalgrade->locked || $finalgrade->overridden)) {
            $gradeattr = ['id'    => 'pg'.$values->id,
                          'class' => $lockedoroverridden];
            if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                return $finalgrade->formatted_grade;
            } else {
                return \html_writer::tag('div', $finalgrade->formatted_grade, $gradeattr);
            }
        } else if ($this->quickgrade && !$this->is_downloading() && ($this->format != self::FORMAT_DOWNLOAD)) {
            if ($values->presentationgrade === null) {
                $values->presentationgrade = -1;
            }
            $attributes = [];
            $attributes['tabindex'] = $this->tabindex++;
            $menu = \html_writer::select($this->presentationgrademenu,
                                        'presentationgrade['.$values->id.']',
                                        (int)$values->presentationgrade,
                                        [-1 => get_string('nograde')],
                                        $attributes);
            $oldgradeattr = ['type'  => 'hidden',
                             'name'  => 'oldpresentationgrade['.$values->id.']',
                             'value' => (int)$values->presentationgrade];
            $oldgrade = \html_writer::empty_tag('input', $oldgradeattr);
            return \html_writer::tag('div', $menu.$oldgrade, ['id' => 'pg'.$values->id]);
        } else {
            if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                if ($values->feedbackid) {
                    return $this->checkmark->display_grade($values->presentationgrade, CHECKMARK_PRESENTATION_ITEM);
                } else {
                    return '-';
                }
            } else {
                if ($values->feedbackid) {
                    return \html_writer::tag('div', $this->checkmark->display_grade($values->presentationgrade,
                                                                                    CHECKMARK_PRESENTATION_ITEM),
                                             ['id' => 'pg'.$values->id]);
                } else {
                    return \html_writer::tag('div', '-', ['id' => 'pg'.$values->id]);
                }
            }
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * user's presentation feedback.
     *
     * @param object $values Contains object with all the values of record.
     * @return string Return user's feedback.
     */
    public function col_presentationfeedback($values) {
        if (!$this->checkmark->checkmark->presentationgrading) {
            return '';
        }
        $presgradebook = $this->checkmark->checkmark->presentationgradebook;
        if ($presgradebook) {
            $finalgrade = $this->gradinginfo->items[CHECKMARK_PRESENTATION_ITEM]->grades[$values->id];
        } else {
            $finalgrade = false;
        }

        // Print Comment!
        if ($presgradebook && ($finalgrade->locked || $finalgrade->overridden)) {
            if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                return $finalgrade->str_feedback;
            } else {
                return \html_writer::tag('div', $finalgrade->str_feedback, ['id' => 'pcom'.$values->id]);
            }
        } else if ($this->quickgrade && !$this->is_downloading() && ($this->format != self::FORMAT_DOWNLOAD)) {
            $feedbackclean = self::convert_html_to_text($values->presentationfeedback);
            $inputarr = ['type'  => 'hidden',
                         'name'  => 'oldpresentationfeedback['.$values->id.']',
                         'value' => trim(str_replace('<br />', '<br />\n', $feedbackclean))];
            $oldfeedback = \html_writer::empty_tag('input', $inputarr);
            $attr = ['tabindex' => $this->tabindex++,
                     'name'     => 'presentationfeedback['.$values->id.']',
                     'id'       => 'presentationfeedback'.$values->id,
                     'rows'     => 2,
                     'cols'     => 20];
            $content = \html_writer::tag('textarea', strip_tags(trim(str_replace('<br />', '<br />\n', $feedbackclean))), $attr);
            return \html_writer::tag('div', $content.$oldfeedback, ['id' => 'pcom'.$values->id]);
        } else {
            if ($values->feedbackid) {
                if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                    return ($values->presentationfeedback === null) ? '' : $values->presentationfeedback;
                } else {
                    return \html_writer::tag('div', $values->presentationfeedback, ['id' => 'pcom'.$values->id]);
                }
            } else {
                if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                    return '';
                } else {
                    return \html_writer::tag('div', '&nbsp;', ['id' => 'com'.$values->id]);
                }
            }
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * user's signature column.
     *
     * @return string Return user's signature column (empty).
     */
    public function col_signature() {
        // Print empty signature-cell!
        return '';
    }

    /**
     * This function is called for each data row to allow processing of
     * columns which do not have a *_cols function.
     *
     * @param string $colname Name of current column
     * @param stdClass $values Values of the current row
     * @return string return processed value. Return NULL if no change has been made.
     * @throws dml_exception
     */
    public function other_cols($colname, $values) {
        // Process examples!
        if (preg_match("/example([0-9]+)/i", $colname, $match)) {
            if (!empty($values->submissionid)) {
                $submission = $this->checkmark->get_submission($values->id);
                $example = $submission->get_example($match[1]);
            } else {
                $mockexample = $this->checkmark->get_examples()[$match[1]];
                $example = new example('', 1, $mockexample->grade, example::UNCHECKED);
            }
            $test = $this->is_downloading();
            if ($this->is_downloading() == 'xlsx' || $this->is_downloading() == 'ods' || $this->format == self::FORMAT_COLORS) {
                return $example->get_examplestate_for_export_with_colors();
            } else if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                return $example->get_examplestate_for_export();
            } else if ($this->quickgrade && !$this->is_downloading() && ($this->format != self::FORMAT_DOWNLOAD)) {
                $attributes = ['class' => 'examplecheck checkline' . $values->id . ' $' . $example->grade,
                        'id' => 'ex'.$values->id.'_'.$match[1]];
                if ($example->is_forced()) {
                    $attributes['title'] = get_string('forced', 'checkmark');
                }
                $cbhidden = \html_writer::tag('input' , '', ['type' => 'hidden',
                        'name' => 'ex['.$values->id.'_'.$match[1].']', 'value' => '0']);
                $cb = $cbhidden . \html_writer::checkbox('ex['.$values->id.'_'.$match[1].']',
                                $values->id, $example->is_checked(), null, $attributes);
                $oldcb = \html_writer::tag('input', '', ['type' => 'hidden',
                        'name' => 'oldex['.$values->id.'_'.$match[1].']', 'value' => $example->is_checked()]);
                if ($example->is_forced()) {
                    return $oldcb . $cb . \html_writer::tag('div', '',
                                    ['id' => 'ex'.$values->id.'_'.$match[1], 'class' => 'excontainer exborder']);
                } else {
                    return $oldcb . $cb . \html_writer::tag('div', '',
                                    ['id' => 'ex'.$values->id.'_'.$match[1], 'class' => 'excontainer']);
                }
            } else {
                return \html_writer::tag('div', $example->print_examplestate(), ['id' => 'ex'.$values->id.'_'.$match[1]]);
            }
        }
        // Process user identity fields and name fields!
        $useridentity = get_extra_user_fields($this->context);
        $allnamefields = get_all_user_name_fields();
        if ($colname === 'phone') {
            $colname = 'phone1';
        }
        if (in_array($colname, $useridentity) || in_array($colname, $allnamefields)) {
            if (!empty($values->$colname)) {
                if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                    return $values->$colname;
                } else {
                    return \html_writer::tag('div', $values->$colname, ['id' => 'u'.$colname.$values->id]);
                }
            } else {
                if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                    return '-';
                } else {
                    return \html_writer::tag('div', '-', ['id' => 'u'.$colname.$values->id]);
                }
            }
        }
        return '';
    }

    /**
     * Converts <br> and </p> tags to line breaks and removes all other html tags
     *
     * @param string|null $html Text to convert
     * @return string|null Converted text
     */
    public static function convert_html_to_text ($html) {
        if (empty($html)) {
            return null;
        }
        $text = str_replace(array('<br />', '<br>', '</p>'), "\n", $html);
        return strip_tags(trim($text));
    }

    /**
     * Converts line breaks to <br> tags
     *
     * @param string|null $text Text to convert
     * @return string|null Converted text
     */
    public static function convert_text_to_html ($text) {
        if (empty($text)) {
            return null;
        }
        return str_replace(array("\r\n", "\n"), '<br>', $text);
    }
}
