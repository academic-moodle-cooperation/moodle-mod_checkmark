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
 * This is the file containing the object class for an example in checkmark
 *
 * @package   mod_checkmark
 * @author    Philipp Hager, extended and maintained by Daniel Binder
 * @copyright 2019 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_checkmark;

defined('MOODLE_INTERNAL') || die();

/**
 * This is the object class for an example in checkmark
 *
 * @package   mod_checkmark
 * @author    Philipp Hager, extended and maintained by Daniel Binder
 * @copyright 2019 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class example {
    /**
     * Bitmask user
     */
    const BITMASK_USER = 0x0001;
    /**
     * Bitmask teacher
     */
    const BITMASK_TEACHER = 0x0002;
    /**
     * Bitmask forced
     */
    const BITMASK_FORCED = 0x0004;

    /**
     * Checked checkbox value
     */
    const CHECKEDBOX = 'X';
    /**
     * Unchecked checkbox value
     */
    const EMPTYBOX = '';
    /**
     * Forced unchecked checkbox value
     */
    const FORCED_EMPTYBOX = '()';
    /**
     * Forced Checked checkbox value
     */
    const FORCED_CHECKEDBOX = '(X)';

    /**
     * Equals: 0b000000000000000
     */
    const UNCHECKED = 0x0000;
    /**
     * Equals: 0b000000000000001
     */
    const CHECKED = 0x0001;
    /**
     * Equals: 0b000000000000110
     */
    const UNCHECKED_OVERWRITTEN = 0x0006;
    /**
     * Equals: 0b000000000000101
     */
    const CHECKED_OVERWRITTEN = 0x0005;

    /**
     * @var int $id: Id of the present example
     */
    protected $id = 0;
    /**
     * @var string $name: Tailing name of the present example
     */
    public $name = '';
    /**
     * @var int $grade: Amount of points awarded for checking the example
     */
    public $grade = 0;
    /**
     * @var string $prefix: Prefix (leading name) of the present example
     */
    protected $prefix = '';
    /**
     * @var int|null $state: State of the present example
     * UNCHECKED: The example has not been checked
     * CHECLED: The example has been checked by the student
     * UNCHECKED_OVERWRITTEN: The example has been checked by the student and was overwritten as unchecked by the teacher
     * CHECKED_OVERWRITTEN: The example has not been checked by the student and was overwritten as checked by the teacher
     */
    public $state = 0x0000;

    /**
     * example constructor.
     *
     * @param int $id Id of the example to create
     * @param string $name Name of the example to create
     * @param float $grade Grade of the example to create
     * @param string $prefix Prefix of the example to create
     * @param null|int $state State of the example to create
     */
    public function __construct($id, $name, $grade, $prefix, $state=null) {
        $this->id = $id;
        $this->name = $name;
        $this->grade = $grade;
        $this->prefix = $prefix;

        if ($state !== null) {
            $this->state = $state;
        }
    }

    /**
     * Compatibility function with legacy parts of checkmark. Returns a namestring based on $name
     * 'id','grade','state','prefix','shortname' will return $this->name (e.g. 3.5)
     * 'name' will return $this->prefix . $this->name (e.g. Example 3.5)
     * 'pointstring will return the appropriate expression for 'points' (singular or plural)
     *
     * @param string $name Name of the string that should be returned
     *
     * @return string Requested name string
     * @throws \coding_exception
     */
    public function __get($name) {
        switch($name) {
            case 'id':
            case 'grade':
            case 'state':
            case 'prefix':
                return $this->$name;
                break;
            case 'name':
                return $this->prefix . $this->name;
                break;
            case 'shortname':
                return $this->name;
                break;
            case 'pointsstring':
                switch ($this->grade) {
                    case '1':
                        return get_string('strpoint', 'checkmark');
                        break;
                    case '2':
                    default:
                        return get_string('strpoints', 'checkmark');
                        break;
                }
                break;
        }
        return null;
    }
    /**
     * Returns a stdCass consisting of all dynamic values of the present example
     *
     * @return \stdClass Snapshot of the present example
     */
    public function export_for_snapshot() {
        $record = new \stdClass;
        $record->id = $this->id;
        $record->name = $this->name;
        $record->grade = $this->grade;
        $record->prefix = $this->prefix;
        $record->state = $this->state;
        return $record;
    }
    /**
     * Sets the state of the present example
     *
     * @param int $state Needs to be either UNCHECKED, CHECKED, UNCHECKED_OVERWRITTEN or CHECKED_OVERWRITTEN
     * @throws \coding_exception
     */
    public function set_state($state) {
        if ($state != self::UNCHECKED && $state != self::CHECKED && $state != self::UNCHECKED_OVERWRITTEN &&
                $state != self::CHECKED_OVERWRITTEN) {
            throw new \coding_exception('State can only be UNCHECKED, CHECKED, UNCHECKED_OVERWRITTEN or CHECKED_OVERWRITTEN');
        }
        $this->state = $state;
    }

    /**
     * Returns the full name of the present example
     *
     * @return string Full name of the present example
     */
    public function get_name() {
        return $this->prefix . $this->name;
    }

    /**
     * Returns the grade of the present example
     *
     * @return int|string Grade of the present example
     */
    public function get_grade() {
        return $this->grade;
    }

    /**
     * Returns the appropriate expression for 'points' (singular or plural)
     *
     * @return string Language string for 'Point' or 'Points'
     * @throws \coding_exception
     */
    public function get_pointsstring() {
        switch ($this->grade) {
            case '1':
                return get_string('strpoint', 'checkmark');
                break;
            case '2':
            default:
                return get_string('strpoints', 'checkmark');
                break;
        }
    }

    /**
     * Returns the localized string for 'overwritten by teacher'
     *
     * @return string Language string for 'overwritten by teacher'
     * @throws \coding_exception
     */
    public function get_forcedstring() {
        if ($this->is_forced()) {
            return $this->get_forcedstring_unconditionally();
        }
        return '';
    }

    /**
     * Always returns forcedstring regardless of state
     *
     * @return string String indicating an overwrite
     * @throws \coding_exception
     */
    public function get_forcedstring_unconditionally() {
        return '[' . get_string('forced', 'checkmark') . ']';
    }

    /**
     * Queries the DB for an example with a given id and returns a new example instance with its data
     *
     * @param int $id Id of the requested example
     * @param bool $userid Id of the user the example is requested for
     *
     * @return example
     * @throws \dml_exception
     */
    public static function from_id($id, $userid=false) {
        global $DB;

        if ($userid > 0) {
            $checkfields = ", state";
            $checkjoin = "LEFT JOIN {checkmark_submissions} s ON cc.submissionid = s.id AND s.userid = :userid
                          LEFT JOIN {checkmark_checks} cc ON ex.id = cc.exampleid";
            $checkparams = ['userid' => $userid];
        } else {
            $checkfields = ", NULL as state";
            $checkjoin = "";
            $checkparams = [];
        }

        $sql = "SELECT ex.id, ex.checkmarkid, ex.name AS shortname, ex.grade,
                       ".$DB->sql_concat('c.exampleprefix', 'ex.name')." AS name
                       $checkfields
                  FROM {checkmark_examples} ex
                  JOIN {checkmark} c ON ex.checkmarkid = c.id
                  $checkjoin
                 WHERE ex.id = :id
        ";

        $example = $DB->get_record_sql($sql, ['id' => $id] + $checkparams);

        return new self($example->shortname, $example->grade, $example->prefix, $example->state);
    }

    /**
     * Returns the html output for the corresponding check for the example without any text
     * @return mixed
     */
    public function print_examplestate() {
        global $OUTPUT;

        return $OUTPUT->render_from_template('mod_checkmark/examplestate', $this);
    }

    /**
     * Returns the html output for the full example display including text
     * @return mixed
     */
    public function print_example() {
        global $OUTPUT;

        return $OUTPUT->render_from_template('mod_checkmark/example', $this);
    }

    /**
     * Returns the html indication for an overwrite used in student view
     *
     * @return string HTML string indicating an overwrite
     */
    public function render_forced_hint() {
        global $OUTPUT;

        return $OUTPUT->render_from_template('mod_checkmark/overwriteinfo', $this);
    }

    /**
     * Method used for overwriting an example. It ensures that state is valid after overwrite
     *
     * @param int $overwrittenexamplestate State the example should be overwritten to
     */
    public function overwrite_example($overwrittenexamplestate) {
        if ($this->state == self::CHECKED && $overwrittenexamplestate == self::UNCHECKED) {
            $this->state = self::CHECKED_OVERWRITTEN;
        } else if ($this->state == self::UNCHECKED && $overwrittenexamplestate == self::CHECKED) {
            $this->state = self::UNCHECKED_OVERWRITTEN;
        } else if ( $this->state == self::CHECKED_OVERWRITTEN && $overwrittenexamplestate == self::CHECKED) {
            $this->state = self::CHECKED;
        } else if ($this->state == self::UNCHECKED_OVERWRITTEN && $overwrittenexamplestate == self::UNCHECKED) {
            $this->state = self::UNCHECKED;
        }
        return;
    }

    /**
     * Returns the appropriate string used in export for the present example
     * @return string
     */
    public function get_examplestate_for_export() {
        if ($this->is_forced_checked()) {
            return self::FORCED_CHECKEDBOX;
        } else if ($this->is_forced_unchecked()) {
            return self::FORCED_EMPTYBOX;
        } else if ($this->is_checked()) {
            return self::CHECKEDBOX;
        } else {
            return self::EMPTYBOX;
        }
    }

    /**
     * Checks if a given state results in a checked example (CHECKED or OVERWRITTEN_CHECKED)
     *
     * @param int $state
     *
     * @return bool: TRUE if checked, FALSE if unchecked
     */
    public static function static_is_checked($state) {
        return (bool)(($state & self::BITMASK_FORCED) ?
                ($state & self::BITMASK_TEACHER) :
                ($state & self::BITMASK_USER));
    }

    /**
     * Checks if a given state was overwritten by teacher (OVERWRITTEN_UNCHECKED or OVERWRITTEN_CHECKED)
     *
     * @param int $state
     *
     * @return bool: TRUE if overwritten, FALSE if not overwritten
     */
    public static function static_is_forced($state) {
        return (bool)($state & self::BITMASK_FORCED);
    }

    /**
     * Checks if a given state was overwritten by teacher to checked (OVERWRITTEN_CHECKED)
     *
     * @param int $state
     *
     * @return bool: TRUE if OVERWRITTEN_CHECKED, FALSE if not OVERWRITTEN_CHECKED
     */
    public static function static_is_forced_checked($state) {
        return (bool)($state & self::BITMASK_FORCED) && ($state & self::BITMASK_TEACHER);
    }

    /**
     * Checks if a given state was overwritten by teacher to unchecked (OVERWRITTEN_UNCHECKED)
     *
     * @param int $state
     *
     * @return bool: TRUE if OVERWRITTEN_UNCHECKED, FALSE if not OVERWRITTEN_UNCHECKED
     */
    public function static_is_forced_unchecked($state) {
        return (bool)($state & self::BITMASK_FORCED) & !($state & self::BITMASK_TEACHER);
    }

    /**
     * Checks if the present example is checked
     * @return bool: TRUE if checked, FALSE if unchecked
     */
    public function is_checked() {
        return self::static_is_checked($this->state);
    }

    /**
     * Checks if the present example was overwritten by teacher (OVERWRITTEN_UNCHECKED or OVERWRITTEN_CHECKED)
     * @return bool: TRUE if overwritten, FALSE if not overwritte
     */
    public function is_forced() {
        return self::static_is_forced($this->state);
    }

    /**
     * Checks if the present example was overwritten by teacher to checked (OVERWRITTEN_CHECKED)
     * @return bool: TRUE if OVERWRITTEN_CHECKED, FALSE if not OVERWRITTEN_CHECKED
     */
    public function is_forced_checked() {
        return self::static_is_forced_checked($this->state);
    }

    /**
     * Checks if the present example was overwritten by teacher to unchecked (OVERWRITTEN_UNCHECKED)
     * @return bool: TRUE if OVERWRITTEN_UNCHECKED, FALSE if not OVERWRITTEN_UNCHECKED
     */
    public function is_forced_unchecked() {
        return self::static_is_forced_unchecked($this->state);
    }

    /**
     * Returns the appropriate expression for 'points' (singular or plural)
     *
     * @param float $grade Amount of points for determining weather to use singular or plural
     * @return string
     * @throws \coding_exception
     */
    public static function get_static_pointstring($grade) {
        switch ($grade) {
            case '1':
                return get_string('strpoint', 'checkmark');
                break;
            case '2':
            default:
                return get_string('strpoints', 'checkmark');
                break;
        }
    }
}
