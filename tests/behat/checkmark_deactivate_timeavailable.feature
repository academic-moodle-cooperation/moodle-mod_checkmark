@mod @mod_checkmark @amc
Feature: Deactivate Allow submissions from
    In order to have a checkmark available all the time
    As a teacher
    I need to able to deactivate the Allow-submission-from-date

  @javascript
  Scenario: Deactivate timeavailable
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
    # this has been testet in checkmark_adding.feature, use generators!
    And the following "activities" exist:
      | activity  | course | idnumber | name        | timeavailable |
      | checkmark | C1     | CM1      | Checkmark 1 | 0             |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Checkmark 1"
    Then I should not see "Allow submissions from"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Checkmark 1"
    Then I should not see "Allow submission from"
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
