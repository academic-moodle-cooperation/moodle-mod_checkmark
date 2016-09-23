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
 * @package       mod_checkmark
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Andreas Windbichler
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_checkmark;

require_once('../../config.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->libdir.'/gradelib.php');

/**
 * submissionstable class handles display of submissions for print preview and submissions view...
 *
 * @package       mod_checkmark
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Andreas Windbichler
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submissionstable extends \table_sql {
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

    /** @var protected checkmark instance */
    protected $checkmark;

    /** @var protected context instance */
    protected $context;

    /** @var protected if submission should be shown in timesubmitted column */
    protected $showsubmission = false;

    /** @var protected if formated cells should contain html */
    protected $format = self::FORMAT_HTML;

    /** @var protected suppressinitials shows whether or not the intials bars should be printed */
    protected $suppressinitials = false;

    /** @var protected defaultselectstate whether or not the select checkboxes should be checked or not checked by default */
    protected $defaultselectstate = false;

    /**
     * constructor
     * @param string $uniqueid a string identifying this table.Used as a key in
     *                          session  vars. It gets set automatically with the helper methods!
     * @param checkmark|int $checkmarkorcmid checkmark object or course module id of checkmark instance
     */
    public function __construct($uniqueid, $checkmarkorcmid = null) {
        global $CFG;

        parent::__construct($uniqueid);

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
    }

    /**
     * Query the db. Store results in the table object for use by build_table.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar. Bar
     * will only be used if there is a fullname column defined for the table.
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
                $this->initialbars($grandtotal > $pagesize);
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
            $this->pagesize($pagesize, $total);
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
     * @return array[] array of arrays containing data in legacy format (compatible with mtablepdf class)
     */
    public function get_data() {
        global $SESSION;

        $this->setup();

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
            return array(array(), array(), array(), array(), array());
        }
        if (!$this->rawdata) {
            return array(array(), array(), array(), array(), array());
        }

        $returndata = array();
        $this->format = self::FORMAT_DOWNLOAD;
        foreach ($this->rawdata as $key => $row) {
            $returndata[$key] = $this->format_row($row);
        }

        $this->format = self::FORMAT_HTML;

        if ($this->rawdata instanceof \core\dml\recordset_walk ||
                $this->rawdata instanceof moodle_recordset) {
            $this->rawdata->close();
        }

        return array($this->columns, $this->headers, $returndata, $this->columnformat, $this->cellwidth);
    }

    /**
     * Helper method to create the table for submissions view!
     *
     * @param checkmark|int $checkmarkorcmid checkmark object or course module id of checkmark instance
     * @param int $filter Which filter to use (FILTER_ALL, FILTER_REQUIRE_GRADING, FILTER_SUBMITTED, ...)
     * @return submissionstable object
     */
    static public function create_submissions_table($checkmarkorcmid = null, $filter = \checkmark::FILTER_ALL) {
        global $CFG, $DB, $OUTPUT;
        // We need to have the same ID to ensure the columns are collapsed if their collapsed in the other table!
        $table = new submissionstable('mod-checkmark-submissions', $checkmarkorcmid);

        $table->quickgrade = get_user_preferences('checkmark_quickgrade', 0);
        $table->filter = $filter;
        $table->showsubmission = true;
        $table->tabindex = 0;
        $table->defaultselectstate = false;

        // Adapt table for submissions view (columns, etc.)!
        $tablecolumns = array('selection', 'picture', 'fullname');
        $tableheaders = array('', '', get_string('fullnameuser'));
        $helpicons = array(null, null, null);

        $useridentity = get_extra_user_fields($table->context);
        foreach ($useridentity as $cur) {
            $tablecolumns[] = $cur;
            $tableheaders[] = ($cur == 'phone1') ? get_string('phone') : get_string($cur);
            $helpicons[] = null;
        }
        if ($table->groupmode != NOGROUPS) {
            $tableheaders[] = get_string('group');
            $tablecolumns[] = 'groups';
            $helpicons[] = null;
        }
        $tableheaders[] = get_string('grade');
        $tablecolumns[] = 'grade';
        $helpicons[] = null;
        $tableheaders[] = get_string('comment', 'checkmark');
        $tablecolumns[] = 'feedback';
        $helpicons[] = null;
        $tableheaders[] = get_string('lastmodified').' ('.get_string('submission', 'checkmark').')';
        $tablecolumns[] = 'timesubmitted';
        $helpicons[] = null;
        $tableheaders[] = get_string('lastmodified').' ('.get_string('grade').')';
        $tablecolumns[] = 'timemarked';
        $helpicons[] = null;
        if ($table->checkmark->checkmark->trackattendance) {
            $tableheaders[] = get_string('attendance', 'checkmark');
            $tablecolumns[] = 'attendance';
            $helpicons[] = new \help_icon('attendance', 'checkmark');
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
        }

        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $table->define_help_for_headers($helpicons);
        $table->define_baseurl($CFG->wwwroot.'/mod/checkmark/submissions.php?id='.$table->checkmark->cm->id.
                               '&amp;currentgroup='.$table->currentgroup);

        $table->sortable(true, 'lastname'); // Sorted by lastname by default!
        $table->collapsible(true);
        $table->initialbars(true);

        $table->column_suppress('picture');
        $table->column_suppress('fullname');

        $table->column_class('picture', 'picture');
        $table->column_class('fullname', 'fullname');
        $table->column_class('idnumber', 'idnumber');
        if ($table->groupmode != NOGROUPS) {
            $table->column_class('groups', 'groups');
            $table->no_sorting('groups');
        }
        $table->column_class('grade', 'grade');
        $table->column_class('feedback', 'feedback');
        $table->column_class('timesubmitted', 'timesubmitted');
        $table->column_class('timemarked', 'timemarked');
        $table->column_class('status', 'status');
        $table->column_class('finalgrade', 'finalgrade');
        if ($table->usesoutcomes) {
            $table->column_class('outcome', 'outcome');
        }

        $table->no_sorting('finalgrade');
        $table->no_sorting('outcome');
        $table->no_sorting('status');

        // Create and set the SQL!
        $params = array();
        $useridentityfields = get_extra_user_fields_sql($table->context, 'u');
        $ufields = \user_picture::fields('u');
        $examplecount = count($table->checkmark->checkmark->examples);
        $params['examplecount'] = $examplecount;

        if ($table->groupmode != NOGROUPS) {
            $getgroupsql = "SELECT MAX(grps.courseid), MIN(grps.name)";
            $params['courseid'] = $table->checkmark->course->id;
            $getgroupsql .= " AS groups, grpm.userid AS userid
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
        if ($table->groupmode != NOGROUPS) {
            $fields .= ", groups";
        }
        $params['checkmarkid'] = $table->checkmark->checkmark->id;
        $params['checkmarkid2'] = $table->checkmark->checkmark->id;

        $users = $table->get_userids($filter);
        list($sqluserids, $userparams) = $DB->get_in_or_equal($users, SQL_PARAMS_NAMED, 'user');
        $params = array_merge_recursive($params, $userparams);

        $from = "{user} u ".
                "LEFT JOIN {checkmark_submissions} s ON u.id = s.userid AND s.checkmarkid = :checkmarkid
                 LEFT JOIN {checkmark_feedbacks} f ON u.id = f.userid AND f.checkmarkid = :checkmarkid2 ".
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

        $table->set_sql($fields, $from, $where, $params, $groupby);
        $table->set_count_sql("SELECT COUNT(u.id) FROM ".$from." WHERE ".$where, $params);

        $table->gradinginfo = grade_get_grades($table->checkmark->course->id, 'mod', 'checkmark', $table->checkmark->checkmark->id,
                                               $users);
        $table->strupdate = get_string('update');
        $table->strgrade = get_string('grade');
        $table->grademenu = make_grades_menu($table->checkmark->checkmark->grade);

        return $table;
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
     */
    static public function create_export_table($checkmarkorcmid = null, $filter = \checkmark::FILTER_ALL, $ids = array()) {
        global $CFG, $DB;
        // We need to have the same ID to ensure the columns are collapsed if their collapsed in the other table!
        $table = new submissionstable('mod-checkmark-submissions', $checkmarkorcmid);

        $table->suppress_initials_output(true);

        $table->sumabs     = get_user_preferences('checkmark_sumabs', 1);
        $table->sumrel     = get_user_preferences('checkmark_sumrel', 1);
        $table->quickgrade = 0;
        $table->filter = $table;
        $table->defaultselectstate = true; // Select all checkboxes by default!

        // Adapt table for export view (columns, etc.)!
        $tableheaders = array('', get_string('name'));
        $tablecolumns = array('selection', 'fullname');
        $table->cellwidth = array(array('mode' => 'Fixed', 'value' => '25'));
        $table->columnformat = array('fullname' => array('align' => 'L'));
        $helpicons = array(null, null);

        $useridentity = get_extra_user_fields($table->context);
        foreach ($useridentity as $cur) {
            $tableheaders[] = ($cur == 'phone1') ? get_string('phone') : get_string($cur);
            $tablecolumns[] = $cur;
            $table->cellwidth[] = array('mode' => 'Relativ', 'value' => '20');
            $table->columnformat[$cur] = array('align' => 'L');
            $helpicons[] = null;
        }
        if ($table->groupmode != NOGROUPS) {
            $tableheaders[] = get_string('group');
            $tablecolumns[] = 'groups';
            $table->cellwidth[] = array('mode' => 'Relativ', 'value' => '20');
            $table->columnformat['groups'] = array('align' => 'L');
            $helpicons[] = null;
        }

        $tablecolumns[] = 'timesubmitted';
        $tableheaders[] = get_string('lastmodified').' ('.get_string('submission', 'checkmark').')';
        $table->cellwidth[] = array('mode' => 'Fixed', 'value' => '30');
        $table->columnformat['timesubmitted'] = array('align' => 'L');
        $helpicons[] = null;

        // Dynamically add examples!
        foreach ($table->checkmark->checkmark->examples as $key => $example) {
            $width = strlen($example->shortname) + strlen($example->grade) + 4;
            $tableheaders[] = $example->shortname." (".$example->grade.'P)';
            $tablecolumns[] = 'example'.$key;
            $table->cellwidth[] = array('mode' => 'Fixed', 'value' => $width);
            $table->columnformat['example'.$key] = array('align' => 'C');
            $helpicons[] = null;
        }

        if ((!empty($table->sumabs) || !empty($table->sumrel))) {
            $tableheaders[] = get_string('checkmarks', 'checkmark');
            $tablecolumns[] = 'summary';
            $table->cellwidth[] = array('mode' => 'Fixed', 'value' => '20');
            $table->columnformat['summary'] = array('align' => 'L');
            $helpicons[] = null;
        }

        $tableheaders[] = get_string('grade');
        $tablecolumns[] = 'grade';
        $table->cellwidth[] = array('mode' => 'Fixed', 'value' => '15');
        $table->columnformat['grade'] = array('align' => 'R');
        $helpicons[] = null;

        $tableheaders[] = get_string('comment', 'checkmark');
        $tablecolumns[] = 'feedback';
        $table->cellwidth[] = array('mode' => 'Relativ', 'value' => '50');
        $table->columnformat['feedback'] = array('align' => 'L');
        $helpicons[] = null;

        if ($table->usesoutcomes) {
            $tableheaders[] = get_string('outcome', 'grades');
            $tablecolumns[] = 'outcome';
            $table->cellwidth[] = array('mode' => 'Relativ', 'value' => '50');
            $table->columnformat['outcome'] = array('align' => 'L');
            $helpicons[] = null;
        }

        if ($table->checkmark->checkmark->trackattendance) {
            $tableheaders[] = get_string('attendance', 'checkmark');
            $helpicons[] = new \help_icon('attendance', 'checkmark');
            $tablecolumns[] = 'attendance';
            $table->cellwidth[] = array('mode' => 'Fixed', 'value' => '20');
            $table->columnformat['attendance'] = array('align' => 'R');
        }

        $tableheaders[] = get_string('signature', 'checkmark');
        $tablecolumns[] = 'signature';
        $table->cellwidth[] = array('mode' => 'Fixed', 'value' => '30');
        $table->columnformat['signature'] = array('align' => 'L');
        $helpicons[] = null;

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
        foreach ($useridentity as $cur) {
            $table->column_class($cur, $cur == 'phone1' ? 'phone' : $cur);
        }
        if ($table->groupmode != NOGROUPS) {
            $table->column_class('groups', 'groups');
            $table->no_sorting('groups');
        }
        $table->column_class('timesubmitted', 'timesubmitted');

        foreach ($table->checkmark->checkmark->examples as $key => $example) {
            $table->column_class('example'.$key, 'example'.$key);
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

        $table->column_class('signature', 'signature');
        $table->no_sorting('signature');

        // Create and set the SQL!
        $params = array();
        $useridentityfields = get_extra_user_fields_sql($table->checkmark->context, 'u');
        $ufields = \user_picture::fields('u');
        $table->examplecount = count($table->checkmark->checkmark->examples);
        $params['examplecount'] = $table->examplecount;

        if ($table->groupmode != NOGROUPS) {
            $getgroupsql = "SELECT MAX(grps.courseid), MIN(grps.name)";
            $params['courseid'] = $table->checkmark->course->id;
            $getgroupsql .= " AS groups, grpm.userid AS userid
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
     * Used to suppress initials bar if flag is set!
     */
    public function print_initials_bar() {
        if (!$this->suppress_initials_output()) {
            parent::print_initials_bar();
        }
    }

    /**
     * Set or determine if initials bar output should be suppressed
     *
     * @param bool $value (optional) Set flag or remove flag! Must be true or false to set/remove!
     * @return bool current state of flag
     */
    public function suppress_initials_output($value = null) {
        if ($value === true) {
            $this->suppressinitials = true;
        } else if ($value === false) {
            $this->suppressinitials = false;
        }

        return $this->suppressinitials;
    }

    /**
     * Helpermethod to get the enrolled and filtered userids!
     *
     * @param int $filter Currently active filter (FILTER_ALL, FILTER_REQUIRE_GRADING, FILTER_SUBMITTED...)
     * @param int[] $ids (optional) Array of userids to filter for
     * @return $string Return user's outcomes.
     */
    public function get_userids($filter, $ids = array()) {
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
            } else if ($filter == \checkmark::FILTER_REQUIRE_GRADING) {
                $wherefilter = " AND COALESCE(f.timemodified,0) < COALESCE(s.timemodified,0) ";
            }
            $params['checkmarkid'] = $this->checkmark->checkmark->id;
            $params['checkmarkid2'] = $this->checkmark->checkmark->id;
            $sql = "SELECT u.id FROM {user} u
                 LEFT JOIN (".$esql.") eu ON eu.id=u.id
                 LEFT JOIN {checkmark_submissions} s ON (u.id = s.userid) AND s.checkmarkid = :checkmarkid
                 LEFT JOIN {checkmark_feedbacks} f ON (u.id = f.userid) AND f.checkmarkid = :checkmarkid2
                     WHERE u.deleted = 0
                           AND eu.id = u.id ".$sqluserids."
                           ".$wherefilter;
        }

        $users = $DB->get_records_sql($sql, $params);

        if (!empty($users)) {
            $users = array_keys($users);
        }

        if (empty($users)) {
            return array(-1);
        }

        return $users;
    }

    /**
     * Renders links to select all/ungraded/submitted/none entries
     *
     * @param bool $returnonlylinks if true only the links will be returned without title!
     */
    public function checkbox_controller($returnonlylinks = true) {
        global $PAGE;

        $baseurl = $PAGE->url;

        $allurl = new \moodle_url($baseurl, array('select' => self::SEL_ALL));
        $noneurl = new \moodle_url($baseurl, array('select' => self::SEL_NONE));
        $reqgradingurl = new \moodle_url($baseurl, array('select' => self::SEL_REQ_GRADING));
        $submittedurl = new \moodle_url($baseurl, array('select' => self::SEL_SUBMITTED));

        $randomid = \html_writer::random_id('checkboxcontroller');
        if (!$returnonlylinks) {
            $title = get_string('select', 'checkmark').':&nbsp;';
        } else {
            $title = '';
        }

        // TODO: Initialize JS!
        $params = new \stdClass();
        $params->table = '#mform1 .submissions';
        $params->id = $randomid;
        $PAGE->requires->js_call_amd('mod_checkmark/checkboxcontroller', 'initializer', array($params));

        return \html_writer::tag('div', $title.
                                        \html_writer::link($allurl, get_string('all'), array('class' => 'all')).' / '.
                                        \html_writer::link($noneurl, get_string('none'), array('class' => 'none')).' / '.
                                        \html_writer::link($reqgradingurl, get_string('ungraded', 'checkmark'),
                                                           array('class' => 'ungraded')).' / '.
                                        \html_writer::link($submittedurl, get_string('submitted', 'checkmark'),
                                                           array('class' => 'submitted')),
                                 array('id' => $randomid));
    }

    /***************************************************************
     *** COLUMN OUTPUT METHODS *************************************
     **************************************************************/

    /**
     * This function is called for each data row to allow processing of the
     * XXX value.
     *
     * @param object $values Contains object with all the values of record.
     * @return $string Return XXX.
     */
    public function col_selection($values) {
        // If the data is being downloaded than we don't want to show HTML.
        if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
            return '';
        } else {
            $select = optional_param('select', null, PARAM_INT);
            // All get selected by default in export table, not selected in submissions table!
            $selectstate = $this->defaultselectstate;

            $attr = array('class' => 'checkboxgroup1');
            if ($select == self::SEL_ALL) {
                $selectstate = true;
            } else if ($select === self::SEL_NONE) {
                $selectstate = false;
            }
            if ($values->timesubmitted > $values->timemarked) {
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
     * user picture.
     *
     * @param object $values Contains object with all the values of record.
     * @return $string Return user fullname.
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
     *
     * @param object $values Contains object with all the values of record.
     * @return $string Return user picture markup.
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
     * @return $string Return user groups.
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
                return \html_writer::tag('div', $values->groups, array('id' => 'gr'.$values->id));
            }
        } else if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
            return '';
        } else {
            return \html_writer::tag('div', '-', array('id' => 'gr'.$values->id));
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * user's grade.
     *
     * @param object $values Contains object with all the values of record.
     * @return $string Return user's grade.
     */
    public function col_grade($values) {
        $finalgrade = $this->gradinginfo->items[CHECKMARK_GRADE_ITEM]->grades[$values->id];
        $grademax = $this->gradinginfo->items[CHECKMARK_GRADE_ITEM]->grademax;
        $finalgrade->formatted_grade = round($finalgrade->grade, 2).' / '.round($grademax, 2);
        $lockedoroverridden = 'locked';
        if ($finalgrade->overridden) {
            $lockedoroverridden = 'overridden';
        }
        if ($values->feedbackid) {
            // Print grade, dropdown or text!
            if ($finalgrade->locked || $finalgrade->overridden) {
                $gradeattr = array('id'    => 'g'.$values->id,
                                   'class' => $lockedoroverridden);
                if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                    return $finalgrade->formatted_grade;
                } else {
                    return \html_writer::tag('div', $finalgrade->formatted_grade, $gradeattr);
                }
            } else if ($this->quickgrade && !$this->is_downloading() && ($this->format != self::FORMAT_DOWNLOAD)) {
                $attributes = array();
                $attributes['tabindex'] = $this->tabindex++;
                $menu = \html_writer::select($this->grademenu,
                                            'menu['.$values->id.']',
                                            (int)$values->grade,
                                            array(-1 => get_string('nograde')),
                                            $attributes);
                $oldgradeattr = array('type'  => 'hidden',
                                      'name'  => 'oldgrade['.$values->id.']',
                                      'value' => $values->grade);
                $oldgrade = \html_writer::empty_tag('input', $oldgradeattr);
                return \html_writer::tag('div', $menu.$oldgrade, array('id' => 'g'.$values->id));
            } else {
                if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                    return $this->checkmark->display_grade($values->grade);
                } else {
                    return \html_writer::tag('div', $this->checkmark->display_grade($values->grade),
                                             array('id' => 'g'.$values->id));
                }
            }
        } else {
            if ($finalgrade->locked || $finalgrade->overridden) {
                if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                    return $finalgrade->formatted_grade;
                } else {
                    return \html_writer::tag('div', $finalgrade->formatted_grade, array('id' => 'g'.$values->id));
                }
            } else if ($this->quickgrade && !$this->is_downloading() && ($this->format != self::FORMAT_DOWNLOAD)) {
                // Allow editing!
                $attributes = array();
                $attributes['tabindex'] = $this->tabindex++;
                $menu = \html_writer::select($this->grademenu,
                                            'menu['.$values->id.']',
                                            $values->grade,
                                            array(-1 => get_string('nograde')),
                                            $attributes);
                $oldgradearr = array('type'  => 'hidden',
                                     'name'  => 'oldgrade'.$values->id,
                                     'value' => $values->grade);
                $oldgrade = \html_writer::empty_tag('input', $oldgradearr);
                return \html_writer::tag('div', $menu.$oldgrade, array('id' => 'g'.$values->id));
            } else {
                if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                    return '-';
                } else {
                    return \html_writer::tag('div', '-', array('id' => 'g'.$values->id));
                }
            }
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * user's feedback.
     *
     * @param object $values Contains object with all the values of record.
     * @return $string Return user's feedback.
     */
    public function col_feedback($values) {
        $finalgrade = $this->gradinginfo->items[CHECKMARK_GRADE_ITEM]->grades[$values->id];
        if ($values->feedbackid) {
            // Print Comment!
            if ($finalgrade->locked || $finalgrade->overridden) {
                if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                    return $finalgrade->str_feedback;
                } else {
                    return \html_writer::tag('div', $finalgrade->str_feedback, array('id' => 'com'.$values->id));
                }
            } else if ($this->quickgrade && !$this->is_downloading() && ($this->format != self::FORMAT_DOWNLOAD)) {
                $inputarr = array('type'  => 'hidden',
                                  'name'  => 'oldfeedback['.$values->id.']',
                                  'value' => trim($values->feedback));
                $oldfeedback = \html_writer::empty_tag('input', $inputarr);
                $content = \html_writer::tag('textarea', $values->feedback, array('tabindex' => $this->tabindex++,
                                                                                  'name'     => 'feedback['.$values->id.']',
                                                                                  'id'       => 'feedback'.$values->id,
                                                                                  'rows'     => 2,
                                                                                  'cols'     => 20));
                return \html_writer::tag('div', $content.$oldfeedback, array('id' => 'com'.$values->id));
            } else {
                if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                    return $values->feedback;
                } else {
                    return \html_writer::tag('div', $values->feedback, array('id' => 'com'.$values->id));
                }
            }
        } else {
            if ($finalgrade->locked || $finalgrade->overridden) {
                if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                    return $finalgrade->str_feedback;
                } else {
                    return \html_writer::tag('div', $finalgrade->str_feedback, array('id' => 'com'.$values->id));
                }
            } else if ($this->quickgrade && !$this->is_downloading() && ($this->format != self::FORMAT_DOWNLOAD)) {
                $inputarr = array('type'  => 'hidden',
                                  'name'  => 'oldfeedback'.$values->id,
                                  'value' => trim($values->feedback));
                $oldfeedback = \html_writer::empty_tag('input', $inputarr);

                $content = \html_writer::tag('textarea', $values->feedback, array('tabindex'  => $this->tabindex++,
                                                                                  'name'      => 'feedback['.$values->id.']',
                                                                                  'id'        => 'feedback'.$values->id,
                                                                                  'rows'      => '2',
                                                                                  'cols'      => '20'));
                return \html_writer::tag('div', $content.$oldfeedback, array('id' => 'com'.$values->id));
            } else {
                if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                    return '';
                } else {
                    return \html_writer::tag('div', '&nbsp;', array('id' => 'com'.$values->id));
                }
            }
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * user's submission time.
     *
     * @param object $values Contains object with all the values of record.
     * @return $string Return user time of submission.
     */
    public function col_timesubmitted($values) {
        $late = 0;
        // Prints student answer and student modified date!
        if ($values->timesubmitted > 0) {
            if ($this->showsubmission) {
                $content = $this->checkmark->print_student_answer($values->id).userdate($values->timesubmitted,
                                                                                        get_string('strftimedatetime'));
            } else {
                $content = userdate($values->timesubmitted, get_string('strftimedatetime'));
            }
            if ($values->timesubmitted >= $this->checkmark->checkmark->timedue) {
                $content .= $this->checkmark->display_lateness($values->timesubmitted);
            }
            $time = $this->checkmark->checkmark->timedue - $values->timesubmitted;
            if (!empty($this->checkmark->checkmark->timedue) && $time < 0) {
                $late = 1;
            }
            if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                return strip_tags($content);
            } else {
                return \html_writer::tag('div', $content, array('id' => 'ts'.$values->id));
            }
        } else {
            if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                return '-';
            } else {
                return \html_writer::tag('div', '-', array('id' => 'ts'.$values->id));
            }
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * user's time of grading.
     *
     * @param object $values Contains object with all the values of record.
     * @return $string Return user's time of grading.
     */
    public function col_timemarked($values) {
        $finalgrade = $this->gradinginfo->items[CHECKMARK_GRADE_ITEM]->grades[$values->id];
        if ($finalgrade->locked || $finalgrade->overridden) {
            $date = userdate($finalgrade->dategraded, get_string('strftimedatetime'));
            if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                return $date;
            } else {
                return \html_writer::tag('div', $date, array('id' => 'tt'.$values->id));
            }
        } else if ($values->feedbackid && $values->timemarked > 0) {
            $date = userdate($values->timemarked, get_string('strftimedatetime'));
            if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                return $date;
            } else {
                return \html_writer::tag('div', $date, array('id' => 'tt'.$values->id));
            }
        } else {
            if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                return \html_writer::tag('div', '-', array('id' => 'tt'.$values->id));
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
     * @return $string Return user's grading button.
     */
    public function col_status($values) {
        global $OUTPUT;
        if (!isset($this->offset)) {
            $this->offset = optional_param('page', 0, PARAM_INT) * $this->pagesize;
        }
        // TODO: enhance with AJAX grading!
        $status = ($values->timemarked > 0) && ($values->timemarked >= $values->timesubmitted);
        $text = $status ? $this->strupdate : $this->strgrade;
        if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
            return $text;
        } else {
            // No more buttons, we use popups!
            $popupurl = '/mod/checkmark/submissions.php?id='.$this->checkmark->cm->id.
                        '&amp;userid='.$values->id.'&amp;mode=single'.
                        '&amp;filter='.$this->filter.'&amp;offset='.$this->offset++;

            $button = $OUTPUT->action_link($popupurl, $text);

            return \html_writer::tag('div', $button, array('id'    => 'up'.$values->id,
                                                           'class' => 's'.$status));
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * user's final grade.
     *
     * @param object $values Contains object with all the values of record.
     * @return $string Return user's final grade.
     */
    public function col_finalgrade($values) {
        $finalgrade = $this->gradinginfo->items[CHECKMARK_GRADE_ITEM]->grades[$values->id];
        if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
            return $finalgrade->str_grade;
        } else {
            return \html_writer::tag('span', $finalgrade->str_grade, array('id' => 'finalgrade_'.$values->id));
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * user's outcomes.
     *
     * @return $string Return user's outcomes.
     */
    public function col_outcome() {
        $outcomes = '';
        foreach ($this->gradinginfo->outcomes as $n => $outcome) {
            $options = make_grades_menu(-$outcome->scaleid);
            if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                $outcomes .= $outcome->name.': '.$options[$index]."\n";
            } else {
                $outcomes .= \html_writer::start_tag('div', array('class' => 'outcome'));
                $outcomes .= \html_writer::tag('label', $outcome->name);
                if ($outcome->grades[$auser->id]->locked or !$this->quickgrade) {
                    $options[0] = get_string('nooutcome', 'grades');
                    $index = $outcome->grades[$auser->id]->grade;
                    $outcomes .= ': '.\html_writer::tag('span', $options[$index], array('id' => 'outcome_'.$n.'_'. $auser->id));
                } else {
                    $attributes = array();
                    $attributes['id'] = 'outcome_'.$n.'_'.$auser->id;
                    $usr = $auser->id;
                    $outcomes .= ' '.\html_writer::select($options, 'outcome_'.$n.'['.$usr.']', $outcome->grades[$usr]->grade,
                                                          array(get_string('nooutcome', 'grades')), $attributes);
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
     * @return $string Return user's summary.
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
            return \html_writer::tag('div', $summary, array('id' => 'sum'.$values->id));
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * user's attendance column.
     *
     * @param object $values Contains object with all the values of record.
     * @return $string Return user's signature column (empty).
     */
    public function col_attendance($values) {
        // Print attendance symbol or quickgrading checkboxes!
        if (!empty($this->checkmark->attendancegradebook)) {
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
                return \html_writer::tag('div', $symbol, array('id' => 'com'.$values->id));
            }
        } else if (has_capability('mod/checkmark:trackattendance', $this->context)
                    && $this->quickgrade && !$this->is_downloading() && ($this->format != self::FORMAT_DOWNLOAD)) {
            if ($values->attendance === null) {
                $values->attendance = -1;
            }
            $inputarr = array('type'  => 'hidden',
                              'name'  => 'oldattendance['.$values->id.']',
                              'value' => $values->attendance);
            $oldattendance = \html_writer::empty_tag('input', $inputarr);
            $attr = array('tabindex' => $this->tabindex++,
                          'id'       => 'attendance'.$values->id);
            $options = array(-1 => '? '.strtolower(get_string('unknown', 'checkmark')),
                             1  => '✓ '.strtolower(get_string('attendant', 'checkmark')),
                             0  => '✗ '.strtolower(get_string('absent', 'checkmark')));
            if ($values->attendance === null) {
                $content = \html_writer::select($options, 'attendance['.$values->id.']', -1, false, $attr);
            } else {
                $content = \html_writer::select($options, 'attendance['.$values->id.']', $values->attendance, false, $attr);
            }

            return \html_writer::tag('div', $content.$oldattendance, array('id' => 'att'.$values->id));
        } else {
            if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                if ($values->attendance == null) {
                    $values->attendance = '?';
                }
                return $values->attendance;
            } else {
                $symbol = checkmark_get_attendance_symbol($values->attendance);
                return \html_writer::tag('div', $symbol, array('id' => 'com'.$values->id));
            }
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * user's signature column.
     *
     * @return $string Return user's signature column (empty).
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
     * @param mixed[] $values Values of the current row
     * @return string return processed value. Return NULL if no change has
     *     been made.
     */
    public function other_cols($colname, $values) {
        // Process examples!
        if (preg_match("/example([0-9]+)/i", $colname, $match)) {
            if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                $checked = 'X';
                $unchecked = ' ';
            } else {
                $checked = \checkmark::CHECKEDBOX;
                $unchecked = \checkmark::EMPTYBOX;
            }
            if (!empty($values->submissionid)) {
                $submission = $this->checkmark->get_submission($values->id);
                $state = $submission->examples[$match[1]]->state ? $checked : $unchecked;
            } else {
                $state = $unchecked;
            }
            if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                return $state;
            } else {
                return \html_writer::tag('div', $state, array('id' => 'ex'.$values->id.'_'.$match[1]));
            }
        }

        // Process user identity fields!
        $useridentity = get_extra_user_fields($this->context);
        if ($colname === 'phone') {
            $colname = 'phone1';
        }
        if (in_array($colname, $useridentity)) {
            if (!empty($values->$colname)) {
                if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                    return $values->$colname;
                } else {
                    return \html_writer::tag('div', $values->$colname, array('id' => 'u'.$colname.$values->id));
                }
            } else {
                if ($this->is_downloading() || $this->format == self::FORMAT_DOWNLOAD) {
                    return '-';
                } else {
                    return \html_writer::tag('div', '-', array('id' => 'u'.$colname.$values->id));
                }
            }
        }
    }
}