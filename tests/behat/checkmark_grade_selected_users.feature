@mod @mod_checkmark @amc
Feature: In a checkmark I want to grade selected submissions.
    In order to grad a specific user
    As a teacher
    I need to able to grade a selected user

  @javascript @currentdev
  Scenario: Grade selected users
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
      | student3 | Student   | 3        | student3@example.com |
      | student4 | Student   | 4        | student4@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | student4 | C1     | student        |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | C1     | G1       |
      | Group 2 | C1     | G2       |
    And the following "group members" exist:
      | user     | group |
      | student1 | G1    |
      | student2 | G1    |
      | student3 | G2    |
      | student4 | G2    |
    # We do not need to manually create the checkmark instance again,
    # this has been tested in checkmark_adding.feature, use generators!
    And the following "activities" exist:
      | activity  | course | idnumber | name        | intro         | groupmode |
      | checkmark | C1     | CM1      | Checkmark 1 | Description 1 | 1         |
    Given the following "mod_checkmark > submissions" exist:
      | checkmark   | user      | example1 | example2 | example3 | example4 | example5 | example6 | example7 | example8 | example9 | example10 |
      | Checkmark 1 | student1  | 1        | 1        | 1        | 1        | 1        | 1        | 0        | 0        | 0        | 0         |
      | Checkmark 1 | student2  | 1        | 1        | 1        | 0        | 0        | 0        | 0        | 0        | 0        | 0         |
    And the following "mod_checkmark > feedbacks" exist:
      | checkmark   | user      | feedback              | grade |
      | Checkmark 1 | student1  | Lel so bad            | 81    |
      | Checkmark 1 | student2  | Lel so bad            | 40    |
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Checkmark 1"
    And I navigate to "View all submissions" in current page administration
    And I click on "selected[]" "checkbox"
    And I set the following fields to these values:
      | bulkaction | grade |
    And I press "start"
    And I press "Continue"
    Then I should see "Auto-grading successful! 1 submission updated."
    Then "Student 1" row "Grade" column of "generaltable" table should contain "60 / 100"
    And "Student 2" row "Grade" column of "generaltable" table should contain "40 / 100"
