@mod @mod_checkmark @amc
Feature: In a course, a teacher should be able to add overrides to general dates for a certain user
  In order to change the dates for a single user or multiple users
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
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    # We do not need to manually create the checkmark instance again,
    # this has been testet in checkmark_adding.feature, use generators!
    And the following "activities" exist:
      | activity  | course | idnumber | name        | intro         | timeavailable | timedue |
      | checkmark | C1     | CM1      | Checkmark 1 | Description 1 | 0             | 0       |









