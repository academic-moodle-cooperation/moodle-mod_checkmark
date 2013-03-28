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

// This file keeps track of upgrades to
// the checkmark module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installation to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the methods of database_manager class
//
// Please do not forget to use upgrade_set_timeout()
// before any action that may take longer time to finish.

function xmldb_checkmark_upgrade($oldversion) {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager();

    if ($oldversion < 2011102002) {
        //convert old data to new one
        upgrade_mod_savepoint(true, 2011102002, 'checkmark');
    }
    if ($oldversion < 2011111500) {
        //table rename in accordance to the moodle db guidlines
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
        echo "<hr />dropping fields in table: {$table->name}<br />";
        foreach ($fieldstodrop as $fieldname) {
            $field = new xmldb_field($fieldname);
            if ($dbman->field_exists($table, $field)) {
                echo "drop field: {$field->name} in table: {$table->name}";
                $dbman->drop_field($table, $field);
                echo "...OK<br />";
                //todo index and key alteration
            } else {
                echo "field: {$field->name} in table: {$table->name} doesn't exists!<br />";
            }
        }
        $fieldstorename = array(
            'course' => 'course_id',
            'var1' => 'examplecount',
            'var2' => 'examplestart');
        echo "<hr />renaming fields in table: {$table->name}<br />";
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
                echo "rename field: {$field->name} in table: {$table->name}";
                $dbman->rename_field($table, $field, $newname);
                echo " to {$newname}...OK<br />";
                //todo index and key alteration
            } else {
                echo "field: {$field->name} in table: {$table->name} doesn\'t exists!<br />";
            }
        }

        // Define index course_id (not unique) to be dropped form checkmark
        $table = new xmldb_table('checkmark');
        $index = new xmldb_index('course', XMLDB_INDEX_NOTUNIQUE, array('course'));

        // Conditionally launch drop index course
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index course_id (not unique) to be added to checkmark
        $table = new xmldb_table('checkmark');
        $index = new xmldb_index('course_id', XMLDB_INDEX_NOTUNIQUE, array('course_id'));

        // Conditionally launch add index course_id
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        //checkmark_submissions
        $table = new xmldb_table('checkmark_submissions');
        $fieldstodrop = array('numfiles', 'data2');
        echo "<hr />dropping fields in table: $table->name<br />";
        foreach ($fieldstodrop as $fieldname) {
            $field = new xmldb_field($fieldname);
            if ($dbman->field_exists($table, $field)) {
                echo "drop field: $field->name in table: $table->name";
                $dbman->drop_field($table, $field);
                echo "...OK<br />";
                //todo index and key alteration
            } else {
                echo "field: $field->name in table: $table->name doesn\'t exists!<br />";
            }
        }
        $fieldstorename = array(
            'assignment' => 'checkmark_id',
            'userid' => 'user_id',
            'data1' => 'checked',
            'teacher' => 'teacher_id');
        echo "<hr />renaming fields in table: {$table->name}<br />";
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
                echo "rename field: {$field->name} in table: {$table->name}";
                $dbman->rename_field($table, $field, $newname);
                echo " to {$newname}...OK<br />";
                //todo index and key alteration
            } else {
                echo "field: {$field->name} in table: {$table->name} doesn't exists!<br />";
            }
        }

        // Define key checkmark (foreign) to be dropped form checkmark_submissions
        $table = new xmldb_table('checkmark_submissions');
        $key = new xmldb_key('assignment', XMLDB_KEY_FOREIGN, array('assignment'), 'assignment',
                             array('id'));

        // Launch drop key checkmark_id
        $dbman->drop_key($table, $key);

        // Define key checkmark_id (foreign) to be added to checkmark_submissions
        $table = new xmldb_table('checkmark_submissions');
        $key = new xmldb_key('checkmark_id', XMLDB_KEY_FOREIGN, array('checkmark_id'), 'checkmark',
                             array('id'));

        // Launch add key checkmark_id
        $dbman->add_key($table, $key);

        // Define index user_id (not unique) to be dropped form checkmark_submissions
        $table = new xmldb_table('checkmark_submissions');
        $index = new xmldb_index('userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));

        // Conditionally launch drop index user_id
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index user_id (not unique) to be added to checkmark_submissions
        $table = new xmldb_table('checkmark_submissions');
        $index = new xmldb_index('user_id', XMLDB_INDEX_NOTUNIQUE, array('user_id'));

        // Conditionally launch add index user_id
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
            echo "rename field: {$field->name} in table: {$table->name}";
            $dbman->rename_field($table, $field, $newname);
            echo " to {$newname}...OK<br />";
            //todo index and key alteration
        } else {
            echo "field: {$field->name} in table: {$table->name} doesn\'t exists!<br />";
        }

        // Define index course_id (not unique) to be dropped form checkmark
        $table = new xmldb_table('checkmark');
        $index = new xmldb_index('course_id', XMLDB_INDEX_NOTUNIQUE, array('course_id'));

        // Conditionally launch drop index course
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index course (not unique) to be added to checkmark
        $table = new xmldb_table('checkmark');
        $index = new xmldb_index('course', XMLDB_INDEX_NOTUNIQUE, array('course'));

        // Conditionally launch add index course
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        upgrade_mod_savepoint(true, 2011122104, 'checkmark');
    }

    // Moodle v2.1.0 release upgrade line
    // Put any upgrade step following this

    if ($oldversion < 2012022300) {
        $table = new xmldb_table('checkmark_submissions');
        $field = new xmldb_field('checkedexamples', XMLDB_TYPE_TEXT, 'small', null, null, null,
                                 null, 'timemodified');
        $newname = 'checked';
        if ($dbman->field_exists($table, $field)) {  //inconsistent upgrades :(
            $dbman->rename_field($table, $field, $newname);
        }
        upgrade_mod_savepoint(true, 2012022300, 'checkmark');
    }
    
    // Moodle v2.3 release upgrade line
    
    // Moodle v2.4 release upgrade line
    if ($oldversion < 2013012800) {

        // Changing precision of field grade on table checkmark_submissions to (10)
        $table = new xmldb_table('checkmark_submissions');
        $field = new xmldb_field('grade', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'checked');

        // Launch change of precision for field grade
        $dbman->change_field_precision($table, $field);

        // checkmark savepoint reached
        upgrade_mod_savepoint(true, 2013012800, 'checkmark');
    }

    return true;
}


