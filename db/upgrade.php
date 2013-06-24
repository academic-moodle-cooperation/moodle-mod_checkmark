<?php
// This file is made for Moodle - http://moodle.org/
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
defined('MOODLE_INTERNAL') || die;

/**
 * @package       mod_checkmark
 * @author        Philipp Hager (e0803285@gmail.com)
 * @copyright     2011 TSC TU Vienna
 * @since         Moodle 2.1
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
        $table = new xmldb_table('checkmark_submissions');
        $key = new xmldb_key('assignment', XMLDB_KEY_FOREIGN, array('assignment'), 'assignment',
                             array('id'));

        // Launch drop key checkmark_id!
        $dbman->drop_key($table, $key);

        // Define key checkmark_id (foreign) to be added to checkmark_submissions!
        $table = new xmldb_table('checkmark_submissions');
        $key = new xmldb_key('checkmark_id', XMLDB_KEY_FOREIGN, array('checkmark_id'), 'checkmark',
                             array('id'));

        // Launch add key checkmark_id!
        $dbman->add_key($table, $key);

        // Define index user_id (not unique) to be dropped form checkmark_submissions!
        $table = new xmldb_table('checkmark_submissions');
        $index = new xmldb_index('userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));

        // Conditionally launch drop index user_id!
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }
        
        
        //tsprc:
            //is there a special reason to redefine $table everytime?
        
        // Define index user_id (not unique) to be added to checkmark_submissions!
        $table = new xmldb_table('checkmark_submissions');
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
        //adding 2 new tables to store examples and (un)checked-examples
        // Define table checkmark_examples to be created
        $table = new xmldb_table('checkmark_examples');

        // Adding fields to table checkmark_examples
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('checkmarkid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('grade', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '10');

        // Adding keys to table checkmark_examples
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('checkmarkid', XMLDB_KEY_FOREIGN, array('checkmarkid'), 'checkmark', array('id'));

        // Conditionally launch create table for checkmark_examples
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
                // Define table checkmark_checks to be created
        $table = new xmldb_table('checkmark_checks');

        // Adding fields to table checkmark_checks
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('exampleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('submissionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('state', XMLDB_TYPE_INTEGER, '4', null, null, null, null);

        // Adding keys to table checkmark_checks
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('exampleid', XMLDB_KEY_FOREIGN, array('exampleid'), 'checkmark_examples', array('id'));
        $table->add_key('submissionid', XMLDB_KEY_FOREIGN, array('submissionid'), 'checkmark_submissions', array('id'));

        // Conditionally launch create table for checkmark_checks
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        // Rename field checkmarkid on table checkmark_submissions to NEWNAMEGOESHERE
        $table = new xmldb_table('checkmark_submissions');
        $field = new xmldb_field('checkmark_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'id');
        // Define key checkmarkid (foreign) to be dropped form checkmark_submissions
        $key = new xmldb_key('checkmark_id', XMLDB_KEY_FOREIGN, array('checkmark_id'), 'checkmark', array('id'));
        // Launch drop key checkmarkid
        $dbman->drop_key($table, $key);
        // Launch rename field checkmarkid
        if($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'checkmarkid');
        }
        $key = new xmldb_key('checkmarkid', XMLDB_KEY_FOREIGN, array('checkmarkid'), 'checkmark', array('id'));
        // Launch add key checkmarkid
        $dbman->add_key($table, $key);
        
        // Rename field userid on table checkmark_submissions to NEWNAMEGOESHERE
        $field = new xmldb_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'checkmarkid');
        // Define index user_id (not unique) to be dropped form checkmark_submissions
        $index = new xmldb_index('user_id', XMLDB_INDEX_NOTUNIQUE, array('user_id'));
        // Conditionally launch drop index mailed
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }
        // Launch rename field userid
        if($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'userid');
        }
        $key = new xmldb_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        // Launch add key userid
        $dbman->add_key($table, $key);

        // Rename field teacherid on table checkmark_submissions to NEWNAMEGOESHERE
        $field = new xmldb_field('teacher_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'format');
        // Launch rename field teacherid
        if($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'teacherid');
        }
        // Define key teacherid (foreign) to be added to checkmark_submissions
        $key = new xmldb_key('teacherid', XMLDB_KEY_FOREIGN, array('teacherid'), 'user', array('id'));
        // Launch add key teacherid
        $dbman->add_key($table, $key);
        
        //copy all old settings to the new tables
        //after the development of the new DB-functions we can delete the old fields
        
        //get all checkmarks and copy on a per instance basics
        $checkmarks = $DB->get_records('checkmark');

        $pbar = new progress_bar('checkmarkupgradeDB', 500, true);
        $i=0;
        $count = $DB->count_records_sql('SELECT count(\'x\') FROM {checkmark_submissions}');
        $count += count($checkmarks);
        foreach($checkmarks as $checkmarkid => $checkmark) {
            //create entries for checkmark examples
            $ids = array();
            $pbar->update($i, $count, "migrate instance ".$checkmark->name);
            if($checkmark->flexiblenaming) {
                //flexible naming
                $names = explode(",", $checkmark->examplenames);
                $grades = explode(",", $checkmark->examplegrades);
                $examplecount = count($names);
                foreach($names as $key => $name) {
                    $data = new stdClass();
                    $data->checkmarkid = $checkmark->id;
                    $data->name = $names[$key];
                    $data->grade = $grades[$key];
                    if(!$ids[$key+1] = $DB->insert_record('checkmark_examples', $data)) {
                        echo $OUTPUT->notification('Error migrating instance '.$checkmark->id.' > Example '.$name.' ('.$grades[$key].')', 'notifyproblem');
                    } else {
                        echo $OUTPUT->notification('Success migrating instance '.$checkmark->id.' > Example '.$name.' ('.$grades[$key].')', 'notifysuccess');
                    }
                }
            } else {
                //standard naming
                $examplecount = $checkmark->examplecount;
                $data = new stdClass();
                $data->grade = $checkmark->grade/$checkmark->examplecount;
                $key = 1;
                for($i=$checkmark->examplestart; $i<$checkmark->examplestart+$checkmark->examplecount; $i++) {
                    $data->checkmarkid = $checkmark->id;
                    $data->name = $i;
                    if(!$ids[$key] = $DB->insert_record('checkmark_examples', $data)) {
                        echo $OUTPUT->notification('Error migrating instance '.$checkmark->id.' > Example '.$data->name.' ('.$data->grade.')', 'notifyproblem');
                    } else {
                        echo $OUTPUT->notification('Success migrating instance '.$checkmark->id.' > Example '.$data->name.' ('.$data->grade.')', 'notifysuccess');
                    }
                    $key++;
                }
            }
            $i++;
            $pbar->update($i, $count, "migrate submissions for instance ".$checkmark->name);
            
            //get all submissions for this instance
            $submissions = $DB->get_records('checkmark_submissions', array('checkmarkid'=>$checkmark->id));
            $j = 1;
            foreach($submissions as $key => $submission) {
                $pbar->update($i, $count, "migrate submission ".$j." for instance ".$checkmark->name);
                $j++;
                $checked_examples = explode(',', $submission->checked);
                for($k=1;$k<=$examplecount;$k++) {
                    $data = new stdClass();
                    $data->exampleid = $ids[$k];
                    $data->submissionid = $submission->id;
                    if(in_array($k, $checked_examples)) {
                        $data->state = 1;
                    } else {
                        $data->state = 0;
                    }
                    $DB->insert_record('checkmark_checks', $data);
                }
                $i++;
            }

            unset($ids);
        }
        
        $pbar->update($count, $count, "migration complete!");

        // checkmark savepoint reached
        upgrade_mod_savepoint(true, 2013061000, 'checkmark');
    }
    
    if($oldversion < 2013062000) {

        // Define field checked to be dropped from checkmark_submissions
        $table = new xmldb_table('checkmark_submissions');
        $field = new xmldb_field('checked');
        // Conditionally launch drop field checked
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field examplecount to be dropped from checkmark
        $table = new xmldb_table('checkmark');
        $field = new xmldb_field('examplecount');
        // Conditionally launch drop field examplecount
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $field = new xmldb_field('examplestart');
        // Conditionally launch drop field examplestart
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $field = new xmldb_field('examplenames');
        // Conditionally launch drop field examplenames
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $field = new xmldb_field('examplegrades');
        // Conditionally launch drop field examplegrades
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $field = new xmldb_field('flexiblenaming');
        // Conditionally launch drop field flexiblenaming
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        
        upgrade_mod_savepoint(true, 2013062000, 'checkmark');
    }

    return true;
}

