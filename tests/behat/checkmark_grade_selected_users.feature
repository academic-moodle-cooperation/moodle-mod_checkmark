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
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Checkmark 1"
    And I set the following fields to these values:
      | Example 1 | 1 |
      | Example 2 | 1 |
      | Example 3 | 1 |
    And I press "Save changes"
    And I log out
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
    Then "Student 1" row "Grade" column of "generaltable" table should contain "30 / 100"
