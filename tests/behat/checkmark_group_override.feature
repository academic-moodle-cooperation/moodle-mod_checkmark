@mod @mod_checkmark @amc
Feature: In a course, a teacher should be able to add overrides to general dates for a certain group
  In order to change the dates for a single group or multiple groups
  As a teacher
  I need to be able to create a group override and save it. Also I need to be capable of editing, duplicating and deleting the override

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 0         |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
      | student3 | Student   | 3        | student3@example.com |
      | student4 | Student   | 4        | student4@example.com |
      | student5 | Student   | 5        | student5@example.com |
      | student6 | Student   | 6        | student6@example.com |
      | student7 | Student   | 7        | student7@example.com |
      | student8 | Student   | 8        | student8@example.com |
    And the following "groups" exist:
      | name | course | idnumber |
      | Group 1 | C1 | G1 |
      | Group 2 | C1 | G2 |
      | Group 3 | C1 | G3 |
      | Group 4 | C1 | G4 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | student4 | C1     | student        |
      | student5 | C1     | student        |
      | student6 | C1     | student        |
      | student7 | C1     | student        |
      | student8 | C1     | student        |
    And the following "group members" exist:
      | user | group |
      | student1 | G1 |
      | student2 | G2 |
      | student3 | G3 |
      | student4 | G4 |
      | student5 | G1 |
      | student5 | G2 |
      | student6 | G3 |
      | student6 | G4 |
      | student7 | G1 |
      | student7 | G2 |
      | student7 | G3 |
      | student7 | G4 |
    # We do not need to manually create the checkmark instance again,
    # this has been testet in checkmark_adding.feature, use generators!
    And the following "activities" exist:
      | activity  | course | idnumber | name        | intro         | timeavailable | timedue |
      | checkmark | C1     | CM1      | Checkmark 1 | Description 1 | 0             | 0       |









