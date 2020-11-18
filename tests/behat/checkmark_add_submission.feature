@mod @mod_checkmark @amc
Feature: In a course, a student should be able to add a submission
    In order to add a submission
    As a student
    I need to be able to change the submission and save it.

  @javascript
  Scenario: Add a submission
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 0         |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    # We do not need to manually create the checkmark instance again,
    # this has been testet in checkmark_adding.feature, use generators!
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
    And I press "Save changes"
    Then I should see "Your changes have been saved"
    And I should see "You've checked 6 out of 10 examples."
    And I should see "(60 out of a maximum of 100 points)"
