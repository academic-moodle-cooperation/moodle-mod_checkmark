@mod @mod_checkmark @amc
Feature: In a checkmark I want to grade ungraded submissions.
  In order to name/grade the examples individually
  As a teacher
  In need to be able to make the changes in the editing mode.

  @javascript
  Scenario: Grade ungraded
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    # We do not need to manually create the checkmark instance again,
    # this has been tested in checkmark_adding.feature, use generators!
    And the following "activities" exist:
      | activity  | course | idnumber | name        | intro         |
      | checkmark | C1     | CM1      | Checkmark 1 | Description 1 |
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Checkmark 1"
    And I set the following fields to these values:
      | Example 1 | 1 |
      | Example 2 | 1 |
      | Example 3 | 1 |
      | Example 4 | 1 |
      | Example 5 | 1 |
      | Example 6 | 1 |
    And I press "Save submission"
    And I log out
    When I am on the "CM1" Activity page logged in as teacher1
    And I navigate to "Submissions" in current page administration
    And I set the following fields to these values:
      | filter | 3 |
    And I click on "selected[]" "checkbox"
    And I set the following fields to these values:
      | bulkaction | grade |
    And I press "Start"
    Then I should see "Auto-grading successful!"
    And I follow "Export"
    And I should see "60 / 100"
