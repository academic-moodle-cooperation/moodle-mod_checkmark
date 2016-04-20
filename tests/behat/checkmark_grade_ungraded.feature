@mod @mod_checkmark @amc
Feature: In a checkmark I want to name and grade the examples individually.
    In order to name/grade the examples individually
    As a teacher
    In need to be able to make the changes in the editing mode.
  @javascript
  Scenario: Grade ungraded
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Checkmark" to section "2" and I fill the form with:
      | Checkmark name | Checkmark1  |
      | Description | Description1 |
And I log out
And I log in as "student1"
And I follow "Course 1"
And I follow "Checkmark1"
And I press "Add submission"
And I set the following fields to these values:
      | Example 1 | 1 |
      | Example 2 | 1 |
      | Example 3 | 1 |
      | Example 4 | 1 |
      | Example 5 | 1 |
      | Example 6 | 1 |
And I press "Save changes"
And I log out
And I log in as "teacher1"
And I follow "Course 1"
And I follow "Checkmark1"
And I follow "View 1 submitted checkmarks"
When I press "Grade ungraded"
Then I press "Continue"
Then I should see "Auto-grading successful! 1 submissions updated."
And I follow "Export"
And I should see "60 / 100"