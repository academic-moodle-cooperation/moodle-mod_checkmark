@mod @mod_checkmark @amc
Feature: In checkmark, a teacher should be able to how many students have submitted their checkmark.
    In order to how many students have added a submission
    As a teacher
    In need to see "View_XX_submitted_checkmarks".

  @javascript
  Scenario: View 1 submitted
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
      | activity  | course | idnumber | name          | intro                  |
      | checkmark | C1     | CM1      | Kreuzerlübung | Standard-Einstellungen |
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Kreuzerlübung" "activity"
    And I set the following fields to these values:
      | Example 1 | 1 |
      | Example 2 | 1 |
      | Example 3 | 1 |
      | Example 4 | 1 |
      | Example 5 | 1 |
      | Example 6 | 1 |
    And I press "Save changes"
    And I log out
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "Kreuzerlübung" "activity"
    Then I should see "View all submissions"
    And the following should exist in the "generaltable" table:
      | Hidden from students  |
      | No                    |
    And the following should exist in the "generaltable" table:
      | Participants  |
      | 1             |
    And the following should exist in the "generaltable" table:
      | Submitted  |
      | 1          |
    And the following should exist in the "generaltable" table:
      | Needs grading  |
      | 1              |
