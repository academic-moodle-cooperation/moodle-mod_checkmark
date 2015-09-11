<?php
// This file is part of mod_checkmark for Moodle - http://moodle.org/
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
 * db/upgrade.php
 *
 * @package       mod_checkmark
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/calendar/lib.php');

/*
 * This file keeps track of upgrades to
 * the checkmark module
 *
 * Sometimes, changes between versions involve
 * alterations to database structures and other
 * major things that may break installations.
 *
 * The upgrade function in this file will attempt
 * to perform all the necessary actions to upgrade
 * your older installation to the current version.
 *
 * If there's something it cannot do itself, it
 * will tell you what you need to do.
 *
 * The commands in here will all be database-neutral,
 * using the methods of database_manager class
 *
 * Please do not forget to use upgrade_set_timeout()
 * before any action that may take longer time to finish.
 */

function xmldb_checkmark_upgrade($oldversion) {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager();

    if ($oldversion < 2011102002) {
        // Convert old data to new one!
        upgrade_mod_savepoint(true, 2011102002, 'checkmark');
    }
    if ($oldversion < 2011111500) {
        // Rename table in accordance to the moodle db guidlines!
        $table = new xmldb_table('checkmarkassignment_submissions');
        if (table_exists('checkmarkassignment_submissions')) {
            $dbman->rename_table($table, 'checkmark_submissions');
        }

        upgrade_mod_savepoint(true, 2011111500, 'checkmark');
    }

    if ($oldversion < 2011112900) {
        upgrade_mod_savepoint(true, 2011112900, 'checkmark');
    }

    if ($oldversion < 2011122100) {
        $table = new xmldb_table('checkmark');
        $fieldstodrop = array('assignmenttype', 'var3', 'var4', 'var5', 'maxbytes');
        echo '<hr />dropping fields in table: '.$table->name.'<br />';
        foreach ($fieldstodrop as $fieldname) {
            $field = new xmldb_field($fieldname);
            if ($dbman->field_exists($table, $field)) {
                echo 'drop field: '.$field->name.' in table: '.$table->name;
                $dbman->drop_field($table, $field);
                echo '...OK<br />';
            } else {
                echo 'field: '.$field->name.' in table: '.$table->name.' doesn\'t exists!<br />';
            }
        }
        $fieldstorename = array(
            'course' => 'course_id',
            'var1' => 'examplecount',
            'var2' => 'examplestart');
        echo '<hr />renaming fields in table: '.$table->name.'<br />';
        foreach ($fieldstorename as $oldname => $newname) {
            switch ($oldname) {
                case 'course':
                    $field = new xmldb_field('course', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
                                             XMLDB_NOTNULL, null, '0', 'id');
                    break;
                case 'var1':
                    $field = new xmldb_field('var1', XMLDB_TYPE_INTEGER, '10', null, null, null,
                                             '0', 'emailteachers');
                    break;
                case 'var2':
                    $field = new xmldb_field('var2', XMLDB_TYPE_INTEGER, '10', null, null, null,
                                             '0', 'var1');
                    break;
            }
            if ($dbman->field_exists($table, $field)) {
                echo 'rename field: '.$field->name.' in table: '.$table->name;
                $dbman->rename_field($table, $field, $newname);
                echo ' to '.$newname.'...OK<br />';
            } else {
                echo 'field: '.$field->name.' in table: '.$table->name.' doesn\'t exists!<br />';
            }
        }

        // Define index course_id (not unique) to be dropped form checkmark!
        $table = new xmldb_table('checkmark');
        $index = new xmldb_index('course', XMLDB_INDEX_NOTUNIQUE, array('course'));

        // Conditionally launch drop index course!
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index course_id (not unique) to be added to checkmark!
        $table = new xmldb_table('checkmark');
        $index = new xmldb_index('course_id', XMLDB_INDEX_NOTUNIQUE, array('course_id'));

        // Conditionally launch add index course_id!
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        // Now take care of checkmark_submissions!
        $table = new xmldb_table('checkmark_submissions');
        $fieldstodrop = array('numfiles', 'data2');
        echo '<hr />dropping fields in table: '.$table->name.'<br />';
        foreach ($fieldstodrop as $fieldname) {
            $field = new xmldb_field($fieldname);
            if ($dbman->field_exists($table, $field)) {
                echo 'drop field: '.$field->name.' in table: '.$table->name;
                $dbman->drop_field($table, $field);
                echo '...OK<br />';
            } else {
                echo 'field: '.$field->name.' in table: '.$table->name.' doesn\'t exists!<br />';
            }
        }
        $fieldstorename = array(
            'assignment' => 'checkmark_id',
            'userid' => 'user_id',
            'data1' => 'checked',
            'teacher' => 'teacher_id');
        echo '<hr />renaming fields in table: '.$table->name.'<br />';
        foreach ($fieldstorename as $oldname => $newname) {
            switch ($oldname) {
                case 'assignment':
                    $field = new xmldb_field('assignment', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
                                             XMLDB_NOTNULL, null, '0', 'id');
                    break;
                case 'userid':
                    $field = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
                                             XMLDB_NOTNULL, null, '0', 'asignment');
                    break;
                case 'data1':
                    $field = new xmldb_field('data1', XMLDB_TYPE_TEXT, 'small', null, null, null,
                                             null, 'timemodified');
                    break;
                case 'teacher':
                    $field = new xmldb_field('teacher', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
                                             XMLDB_NOTNULL, null, '0', 'format');
                    break;
            }
            if ($dbman->field_exists($table, $field)) {
                echo 'rename field: '.$field->name.' in table: '.$table->name;
                $dbman->rename_field($table, $field, $newname);
                echo ' to '.$newname.'...OK<br />';
            } else {
                echo 'field: '.$field->name.' in table: '.$table->name.' doesn\'t exists!<br />';
            }
        }

        // Define key checkmark (foreign) to be dropped form checkmark_submissions!
        $key = new xmldb_key('assignment', XMLDB_KEY_FOREIGN, array('assignment'), 'assignment',
                             array('id'));

        // Launch drop key checkmark_id!
        $dbman->drop_key($table, $key);

        // Define key checkmark_id (foreign) to be added to checkmark_submissions!
        $key = new xmldb_key('checkmark_id', XMLDB_KEY_FOREIGN, array('checkmark_id'), 'checkmark',
                             array('id'));

        // Launch add key checkmark_id!
        $dbman->add_key($table, $key);

        // Define index user_id (not unique) to be dropped form checkmark_submissions!
        $index = new xmldb_index('userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));

        // Conditionally launch drop index user_id!
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index user_id (not unique) to be added to checkmark_submissions!
        $index = new xmldb_index('user_id', XMLDB_INDEX_NOTUNIQUE, array('user_id'));

        // Conditionally launch add index user_id!
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        upgrade_mod_savepoint(true, 2011122100, 'checkmark');
    }

    if ($oldversion < 2011122104) {
        $newname = 'course';
        $table = new xmldb_table('checkmark');
        $field = new xmldb_field('course_id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
                                 XMLDB_NOTNULL, null, '0', 'id');

        if ($dbman->field_exists($table, $field)) {
            echo 'rename field: '.$field->name.' in table: '.$table->name;
            $dbman->rename_field($table, $field, $newname);
            echo ' to '.$newname.'...OK<br />';
        } else {
            echo 'field: '.$field->name.' in table: '.$table->name.' doesn\'t exists!<br />';
        }

        // Define index course_id (not unique) to be dropped form checkmark!
        $table = new xmldb_table('checkmark');
        $index = new xmldb_index('course_id', XMLDB_INDEX_NOTUNIQUE, array('course_id'));

        // Conditionally launch drop index course!
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index course (not unique) to be added to checkmark!
        $table = new xmldb_table('checkmark');
        $index = new xmldb_index('course', XMLDB_INDEX_NOTUNIQUE, array('course'));

        // Conditionally launch add index course!
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        upgrade_mod_savepoint(true, 2011122104, 'checkmark');
    }

    // Moodle v2.1.0 release upgrade line.
    // Put any upgrade step following this!

    if ($oldversion < 2012022300) {
        $table = new xmldb_table('checkmark_submissions');
        $field = new xmldb_field('checkedexamples', XMLDB_TYPE_TEXT, 'small', null, null, null,
                                 null, 'timemodified');
        $newname = 'checked';
        if ($dbman->field_exists($table, $field)) {  // Inconsistent upgrades! @todo clean before publication!
            $dbman->rename_field($table, $field, $newname);
        }
        upgrade_mod_savepoint(true, 2012022300, 'checkmark');
    }

    // Moodle v2.3 release upgrade line.

    // Moodle v2.4 release upgrade line.
    if ($oldversion < 2013012800) {

        // Changing precision of field grade on table checkmark_submissions to (10)!
        $table = new xmldb_table('checkmark_submissions');
        $field = new xmldb_field('grade', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'checked');

        // Launch change of precision for field grade!
        $dbman->change_field_precision($table, $field);

        // Checkmark savepoint reached!
        upgrade_mod_savepoint(true, 2013012800, 'checkmark');
    }

    if ($oldversion < 2013061000) {
        // Adding 2 new tables to store examples and (un)checked-examples!
        // Define table checkmark_examples to be created!
        $table = new xmldb_table('checkmark_examples');

        // Adding fields to table checkmark_examples!
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('checkmarkid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('grade', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '10');

        // Adding keys to table checkmark_examples!
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('checkmarkid', XMLDB_KEY_FOREIGN, array('checkmarkid'), 'checkmark', array('id'));

        // Conditionally launch create table for checkmark_examples!
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table checkmark_checks to be created!
        $table = new xmldb_table('checkmark_checks');

        // Adding fields to table checkmark_checks!
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('exampleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('submissionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('state', XMLDB_TYPE_INTEGER, '4', null, null, null, null);

        // Adding keys to table checkmark_checks!
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('exampleid', XMLDB_KEY_FOREIGN, array('exampleid'), 'checkmark_examples', array('id'));
        $table->add_key('submissionid', XMLDB_KEY_FOREIGN, array('submissionid'), 'checkmark_submissions', array('id'));

        // Conditionally launch create table for checkmark_checks!
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Rename field checkmarkid on table checkmark_submissions to checkmarkid!
        $table = new xmldb_table('checkmark_submissions');
        $field = new xmldb_field('checkmark_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'id');
        // Define key checkmarkid (foreign) to be dropped form checkmark_submissions!
        $key = new xmldb_key('checkmark_id', XMLDB_KEY_FOREIGN, array('checkmark_id'), 'checkmark', array('id'));
        // Launch drop key checkmarkid!
        $dbman->drop_key($table, $key);
        // Launch rename field checkmarkid!
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'checkmarkid');
        }
        $key = new xmldb_key('checkmarkid', XMLDB_KEY_FOREIGN, array('checkmarkid'), 'checkmark', array('id'));
        // Launch add key checkmarkid!
        $dbman->add_key($table, $key);

        // Rename field userid on table checkmark_submissions to userid!
        $field = new xmldb_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'checkmarkid');
        // Define index user_id (not unique) to be dropped form checkmark_submissions!
        $index = new xmldb_index('user_id', XMLDB_INDEX_NOTUNIQUE, array('user_id'));
        // Conditionally launch drop index mailed!
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }
        // Launch rename field userid!
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'userid');
        }
        $key = new xmldb_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        // Launch add key userid!
        $dbman->add_key($table, $key);

        // Rename field teacherid on table checkmark_submissions to teacherid!
        $field = new xmldb_field('teacher_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'format');
        // Launch rename field teacherid!
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'teacherid');
        }
        // Define key teacherid (foreign) to be added to checkmark_submissions!
        $key = new xmldb_key('teacherid', XMLDB_KEY_FOREIGN, array('teacherid'), 'user', array('id'));
        // Launch add key teacherid!
        $dbman->add_key($table, $key);

        // Copy all old settings to the new tables!
        // After the development of the new DB-functions we can delete the old fields!
        // Get all checkmarks and copy on a per instance basics!
        $checkmarks = $DB->get_records('checkmark');

        $pbar = new progress_bar('checkmarkupgradeInstances', 500, true);
        $pbar2 = new progress_bar('checkmarkupgradeSubmissions', 500, true);
        $instancecount = 0;
        $countinstances = count($checkmarks);
        $ids = array();
        $params = array();
        unset($sql);
        foreach ($checkmarks as $checkmarkid => $checkmark) {
            // Create entries for checkmark examples!
            $ids[$checkmarkid] = array();
            @$pbar->update($instancecount, $countinstances, 'migrate instance '.$checkmark->name);
            $present = $DB->get_fieldset_select('checkmark_examples', 'name',
                                                'checkmarkid = :checkmarkid',
                                                array('checkmarkid' => $checkmark->id));
            if ($checkmark->flexiblenaming) {
                // Flexible naming?
                $names = explode(',', $checkmark->examplenames);
                $grades = explode(',', $checkmark->examplegrades);
                foreach ($names as $key => $name) {
                    if (in_array($name, $present)) {
                        // Skip some examples if they have allready been inserted!
                        continue;
                    }
                    $params['id'.$checkmarkid.'_'.$key] = $checkmark->id;
                    $params['name'.$checkmarkid.'_'.$key] = $names[$key];
                    $params['grade'.$checkmarkid.'_'.$key] = empty($grades[$key]) ? 0 : $grades[$key];
                    if (!isset($sql)) {
                        $sql = 'INSERT INTO {checkmark_examples} (checkmarkid, name, grade)
                                     VALUES (:id'.$checkmarkid.'_'.$key.',
                                             :name'.$checkmarkid.'_'.$key.',
                                             :grade'.$checkmarkid.'_'.$key.')';
                    } else {
                        $sql .= ', (:id'.$checkmarkid.'_'.$key.', :name'.$checkmarkid.'_'.$key.', :grade'.$checkmarkid.'_'.$key.')';
                    }
                }
            } else {
                // Standard naming?
                $examplecount = $checkmark->examplecount;
                if ($checkmark->grade <= 100 && $checkmark->grade >= 0) {
                    $grade = $checkmark->grade / $checkmark->examplecount;
                } else {
                    $grade = 0;
                }
                for ($i = $checkmark->examplestart; $i < $checkmark->examplestart + $checkmark->examplecount; $i++) {
                    if (in_array($i, $present)) {
                        // Skip some examples if they have allready been inserted!
                        continue;
                    }
                    $params['id'.$checkmarkid.'_'.$i] = $checkmark->id;
                    $params['name'.$checkmarkid.'_'.$i] = $i;
                    $params['grade'.$checkmarkid.'_'.$i] = $grade;
                    if (!isset($sql)) {
                        $sql = 'INSERT INTO {checkmark_examples} (checkmarkid, name, grade)
                                     VALUES (:id'.$checkmarkid.'_'.$i.',
                                             :name'.$checkmarkid.'_'.$i.',
                                             :grade'.$checkmarkid.'_'.$i.')';
                    } else {
                        $sql .= ', (:id'.$checkmarkid.'_'.$i.', :name'.$checkmarkid.'_'.$i.', :grade'.$checkmarkid.'_'.$i.')';
                    }
                }
            }
            if (isset($sql)) {
                $DB->execute($sql, $params);
                unset($sql);
                $params = array();
            }

            $instancecount++;
            $ids = $DB->get_fieldset_sql('SELECT id
                                            FROM {checkmark_examples}
                                           WHERE checkmarkid = :checkmarkid',
                                         array('checkmarkid' => $checkmark->id));
            $examplecount = count($ids);
            // Get all submissions for this instance!
            $submissions = $DB->get_records('checkmark_submissions', array('checkmarkid' => $checkmark->id));
            $subcounter = 1;
            $submissionscount = count($submissions);
            @$pbar2->update(0, $submissionscount, "migrate submissions for instance ".$checkmark->name);
            foreach ($submissions as $key => $submission) {
                @$pbar2->update($subcounter, $submissionscount,
                                "migrate submission ".$subcounter." for instance ".$checkmark->name);
                $subcounter++;
                $checkedexamples = explode(',', $submission->checked);
                $recordspresent = $DB->count_records('checkmark_checks', array('submissionid' => $submission->id));
                $present = $DB->get_fieldset_select('checkmark_checks', 'exampleid', 'submissionid = ?', array($submission->id));
                for ($k = 1; $k <= $examplecount; $k++) {
                    if (in_array($ids[$k - 1], $present)) {
                        continue;   // Skip allready migrated!
                    }
                    if (empty($sql)) {
                        $sql = 'INSERT INTO {checkmark_checks} (exampleid, submissionid, state)
                                     VALUES (:ex'.$ids[$k - 1].'_'.$submission->id.',
                                             :sub'.$ids[$k - 1].'_'.$submission->id.',
                                             :state'.$ids[$k - 1].'_'.$submission->id.')';
                    } else {
                        $sql .= ', (:ex'.$ids[$k - 1].'_'.$submission->id.',
                                    :sub'.$ids[$k - 1].'_'.$submission->id.',
                                    :state'.$ids[$k - 1].'_'.$submission->id.')';
                    }
                    $params['ex'.$ids[$k - 1].'_'.$submission->id] = $ids[$k - 1];
                    $params['sub'.$ids[$k - 1].'_'.$submission->id] = $submission->id;
                    if (in_array($k, $checkedexamples)) {
                        $params['state'.$ids[$k - 1].'_'.$submission->id] = 1;
                    } else {
                        $params['state'.$ids[$k - 1].'_'.$submission->id] = 0;
                    }
                }
                if (!empty($sql)) {
                    $DB->execute($sql, $params);
                    unset($sql);
                    $params = array();
                }
            }
        }

        $pbar->update($intancecount, $instancecount, "migration complete!");
        $pbar2->update($intancecount, $instancecount, "migration complete!");

        // Checkmark savepoint reached!
        upgrade_mod_savepoint(true, 2013061000, 'checkmark');
    }

    if ($oldversion < 2013062000) {

        // Define field checked to be dropped from checkmark_submissions!
        $table = new xmldb_table('checkmark_submissions');
        $field = new xmldb_field('checked');
        // Conditionally launch drop field checked!
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field examplecount to be dropped from checkmark!
        $table = new xmldb_table('checkmark');
        $field = new xmldb_field('examplecount');
        // Conditionally launch drop field examplecount!
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $field = new xmldb_field('examplestart');
        // Conditionally launch drop field examplestart!
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $field = new xmldb_field('examplenames');
        // Conditionally launch drop field examplenames!
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $field = new xmldb_field('examplegrades');
        // Conditionally launch drop field examplegrades!
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $field = new xmldb_field('flexiblenaming');
        // Conditionally launch drop field flexiblenaming!
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2013062000, 'checkmark');
    }

    if ($oldversion < 2013112500) {
        $table = new xmldb_table('checkmark');

        // Define field alwaysshowdescription to be added to checkmark.
        $field = new xmldb_field('alwaysshowdescription', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'introformat');
        // Conditionally launch add field alwaysshowdescription.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Get a list with future cut-off-dates of positive preventlate-stati of all checkmarks!
        $cutoffs = $DB->get_records_menu('checkmark', array('preventlate' => 1),
                                         'id ASC', 'id, timedue');

        // Rename field preventlate on table checkmark to cutoffdate.
        $field = new xmldb_field('preventlate', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'resubmit');
        // Launch rename field cutoffdate.
        $dbman->rename_field($table, $field, 'cutoffdate');

        // Changing precision of field cutoffdate on table checkmark to (10).
        $field = new xmldb_field('cutoffdate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'resubmit');
        // Launch change of precision for field cutoffdate.
        $dbman->change_field_precision($table, $field);

        // Set Cut-Off-Date for all Instances right (those with no cut-off-date are already allright).
        foreach ($cutoffs as $id => $cutoff) {
            $DB->set_field('checkmark', 'cutoffdate', $cutoff, array('id' => $id));
        }
        // Checkmark savepoint reached.
        upgrade_mod_savepoint(true, 2013112500, 'checkmark');
    }

    if ($oldversion < 2014052104) {
        // Upgrade old events!
        $events = $DB->get_records('event', array('eventtype'  => 'course',
                                                  'modulename' => 'checkmark'));
        $eventcount = count($events);
        $i = 0;
        $pbar = new progress_bar('CheckmarkFixEvents', 500, true);
        foreach ($events as $id => $event) {
            // Find related instance via courseid and duedate!
            $cond = array('course'  => $event->courseid,
                          'timedue' => $event->timestart);
            $matches = $DB->count_records('checkmark', $cond);
            if ($matches == 1) {
                // Only 1 record found, we can fix it!
                $event->instance = $DB->get_field('checkmark', 'id', $cond);
                $event->eventtype = 'due';
                $calendarevent = calendar_event::load($event->id);
                $calendarevent->update($event);
                $i++;
                echo $OUTPUT->notification(get_string('couldfixevent', 'checkmark', $event),
                                           'notifysuccess');
                $pbar->update($i, $eventcount, get_string('couldfixevent', 'checkmark', $event));
            } else {
                $cond2 = array('course'  => $event->courseid,
                               'timedue' => $event->timestart,
                               'name'    => $event->name);
                $matches2 = $DB->count_records('checkmark', $cond2);
                if ($matches2 == 1) {
                    // Only 1 record found, we can fix it!
                    $event->instance = $DB->get_field('checkmark', 'id', $cond2);
                    $event->eventtype = 'due';
                    $calendarevent = calendar_event::load($event->id);
                    $calendarevent->update($event);
                    $i++;
                    echo $OUTPUT->notification(get_string('couldfixevent', 'checkmark', $event),
                                               'notifysuccess');
                    $pbar->update($i, $eventcount, get_string('couldfixevent', 'checkmark', $event));
                } else {
                    $event->matches = min($matches, $matches2);
                    echo $OUTPUT->notification(get_string('cantfixevent', 'checkmark', $event),
                                               'notifyproblem');
                    $i++;
                    $pbar->update($i, $eventcount, get_string('cantfixevent', 'checkmark', $event));
                }
            }
        }
        $pbar->update($eventcount, $eventcount, "finished first phase");

        upgrade_mod_savepoint(true, 2014052104, 'checkmark');
    }

    if ($oldversion < 2014052105) {
        if (!isset($pbar)) {
            $pbar = new progress_bar('CheckmarkFixEvents', 500, true);
        }
        // Get all checkmarks which have no corresponding events but a due date!
        $checkmarks = $DB->get_records_sql("SELECT checkmark.id, checkmark.name, checkmark.intro, checkmark.timedue,
                                                   checkmark.course, COUNT( event.id ) AS present
                                              FROM {checkmark} checkmark
                                         LEFT JOIN {event} event ON event.instance = checkmark.id
                                                                 AND event.instance <> 0
                                                                 AND event.modulename LIKE 'checkmark'
                                          GROUP BY checkmark.id
                                            HAVING COUNT( event.id ) = 0 AND checkmark.timedue <> 0");
        $repairedids = array();
        $i = 0;
        $max = count($checkmarks);
        // Process each of these checkmarks alone!
        foreach ($checkmarks as $checkmark) {
            $params = array('name'       => '%'.$checkmark->name.'%',
                            'intro'      => '%'.$checkmark->intro.'%',
                            'courseid'   => $checkmark->course,
                            'timedue'    => $checkmark->timedue,
                            'modulename' => 'checkmark',
                            'eventtype'  => 'course');
            $events = $DB->get_records_sql("SELECT *
                                              FROM {event} event
                                             WHERE event.timestart = :timedue
                                                   AND event.courseid = :courseid
                                                   AND ".$DB->sql_like('event.name', ':name')."
                                                   AND ".$DB->sql_like('event.description', ':intro')."
                                                   AND ".$DB->sql_like('event.modulename', ':modulename')."
                                                   AND ".$DB->sql_like('event.eventtype', ':eventtype')."
                                                   AND event.instance = 0", $params);
            $matches = count($events);
            if ($matches > 0) {
                $event = current($events);
                while (($matches > 1) && in_array($event->id, $repairedids)) {
                    // Get next unrepaired matching event!
                    $matches--;
                    $event = next($events);
                }
                if (in_array($event->id, $repairedids)) { // We allready used this event, copy it for the other instance!
                    $newevent = clone $event;
                    unset($newevent->id);
                    $newevent->instance = $checkmark->id;
                    $newevent->eventtype = 'due';
                    if ($eventobj = calendar_event::create($newevent)) {
                        $repairedids[] = $eventobj->id;
                        echo $OUTPUT->notification(get_string('couldfixevent', 'checkmark', $event),
                                                   'notifysuccess');
                        $i++;
                        $pbar->update($i, $max, get_string('couldfixevent', 'checkmark', $event));
                    } else {
                        $event->id = -1;
                        echo $OUTPUT->notification(get_string('cantfixevent', 'checkmark', $event),
                                                   'notifyproblem');
                        $i++;
                        $pbar->update($i, $max, get_string('cantfixevent', 'checkmark', $event));
                    }
                } else {
                    $event->instance = $checkmark->id;
                    $event->eventtype = 'due';
                    $eventobj = calendar_event::load($event->id);
                    if ($eventobj->update($event)) {
                        $repairedids[] = $event->id;
                        echo $OUTPUT->notification(get_string('couldfixevent', 'checkmark', $event),
                                                   'notifysuccess');
                        $i++;
                        $pbar->update($i, $max, get_string('couldfixevent', 'checkmark', $event));
                    }
                }
            } else {
                // Create a New Event for this instance...

                $event = new stdClass();
                $event->name        = get_string('end_of_submission_for', 'checkmark', $checkmark->name);
                if (!empty($checkmark->intro)) {
                    $event->description = $checkmark->intro;
                }
                $event->courseid    = $checkmark->course;
                $event->groupid     = 0;
                $event->userid      = 0;
                $event->modulename  = 'checkmark';
                $event->instance    = $checkmark->id;
                $event->eventtype   = 'due';
                $event->timestart   = $checkmark->timedue;
                $event->timeduration = 0;

                if ($eventobj = calendar_event::create($event)) {
                    $event->id = $eventobj->id;
                    $repairedids[] = $event->id;
                    echo $OUTPUT->notification(get_string('couldfixevent', 'checkmark', $event),
                                               'notifysuccess');
                    $i++;
                    $pbar->update($i, $max, get_string('couldfixevent', 'checkmark', $event));
                } else {
                    $event->id = -1;
                    echo $OUTPUT->notification(get_string('cantfixevent', 'checkmark', $event),
                                               'notifyproblem');
                    $i++;
                    $pbar->update($i, $max, get_string('cantfixevent', 'checkmark', $event));
                }
            }
        }

        // Remove other event-stubs...
        $events = $DB->get_records('event', array('modulename' => 'checkmark',
                                                  'instance' => 0,
                                                  'eventtype' => 'course'));
        $i = 0;
        $max = count($events);
        if (!empty($max)) {
            foreach ($events as $event) {
                $event = calendar_event::load($event->id);
                $event->delete(true);
                $i++;
                $pbar->update($i, $max, get_string('delete').' '.$i.'/'.$max);
            }
        }

        $pbar->update(1, 1, '-');

        upgrade_mod_savepoint(true, 2014052105, 'checkmark');
    }

    if ($oldversion < 2015071501) {
        if (isset($CFG->checkmark_requiremodintro)) {
            set_config('requiremodintro', $CFG->checkmark_requiremodintro, 'checkmark');
            unset($CFG->checkmark_requiremodintro);
            $DB->delete_records('config', array('name' => 'checkmark_requiremodintro'));
        } else {
            set_config('requiremodintro', 1, 'mod_checkmark');
        }

        if (isset($CFG->checkmark_stdexamplecount)) {
            set_config('stdexamplecount', $CFG->checkmark_stdexamplecount, 'checkmark');
            $DB->delete_records('config', array('name' => 'checkmark_stdexamplecount'));
        } else {
            set_config('stdexamplecount', 10, 'mod_checkmark');
        }

        if (isset($CFG->checkmark_stdexamplestart)) {
            set_config('stdexamplestart', $CFG->checkmark_stdexamplestart, 'checkmark');
            $DB->delete_records('config', array('name' => 'checkmark_stdexamplestart'));
        } else {
            set_config('stdexamplestart', 1, 'checkmark');
        }

        if (isset($CFG->checkmark_stdnames)) {
            set_config('stdnames', $CFG->checkmark_stdnames, 'checkmark');
            $DB->delete_records('config', array('name' => 'checkmark_stdnames'));
        } else {
            set_config('stdnames', 'a,b,c,d,e,f', 'checkmark');
        }

        if (isset($CFG->checkmark_stdgrades)) {
            set_config('stdgrades', $CFG->checkmark_stdgrades, 'checkmark');
            $DB->delete_records('config', array('name' => 'checkmark_stdgrades'));
        } else {
            set_config('stdgrades', '10,10,20,20,20,20', 'checkmark');
        }
        if (isset($CFG->checkmark_validmsgtime)) {
            set_config('validmsgtime', $CFG->checkmark_validmsgtime, 'checkmark');
            $DB->delete_records('config', array('name' => 'checkmark_validmsgtime'));
        } else {
            set_config('validmsgtime', 2, 'checkmark');
        }

        upgrade_mod_savepoint(true, 2015071501, 'checkmark');
    }

    return true;
}