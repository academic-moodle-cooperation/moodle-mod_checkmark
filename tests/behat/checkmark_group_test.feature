@mod @mod_checkmark @amc
Feature: In courses, a teacher should be able to add overrides to general dates for a certain user
  In order to change the dates for a single user or multiple users
  As a teacher
  I need to be able to create a user override and save it. Also I need to be capable of editing, duplicating and deleting the override

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
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
    And the following "activities" exist:
      | activity  | course | idnumber | name        | intro         | timeavailable | timedue |
      | checkmark | C1     | CM1      | Checkmark 1 | Description 1 | 0             | 0       |


  @javascript
  Scenario: A teacher without accessallgroups permission should only be able to see the user override for users that he/she shares groups with,
  when the activity's group mode is "separate groups"
    Given the following "permission overrides" exist:
      | capability                  | permission | role           | contextlevel | reference |
      | moodle/site:accessallgroups | Prevent    | editingteacher | Course       | C1        |
    And the following "activities" exist:
      | activity    | name        | intro                   | course | idnumber    | groupmode |
      | checkmark   | Checkmark 2 | Checkmark 2 description | C1     | checkmark2  | 1         |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | C1     | G1       |
      | Group 2 | C1     | G2       |
    And the following "group members" exist:
      | user     | group |
      | teacher1 | G1    |
      | student1 | G1    |
      | student2 | G2    |
    And I am on the "checkmark2" Activity page logged in as admin
    And I navigate to "Overrides" in current page administration
    And I select "User overrides" from the "jump" singleselect
    And I press "Add user override"
    And I set the following fields to these values:
      | Allow submissions from | ##1 January 2015 08:00## |
      | Due date               | disabled                 |
      | Cut-off date           | disabled                 |
      | Users                  | Student 1                |
    And I press "Override and create a new override"
    And I set the following fields to these values:
      | Allow submissions from | ##1 January 2015 08:00## |
      | Users                  | Student 2                |
    And I press "id_override"
    And I log out
    When I am on the "checkmark2" Activity page logged in as teacher1
    And I navigate to "Overrides" in current page administration
    And I select "User overrides" from the "jump" singleselect
    Then I should see "Student 1"
    And I should not see "Student 2"