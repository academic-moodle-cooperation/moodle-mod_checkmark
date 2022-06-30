@mod @mod_checkmark @amc
Feature: Available From Date
    In order to allow students to use the checkmark course after a certain date
    As a teacher
    I need to be able to set the beginning date for the module to the December 11.

  @javascript
  Scenario: Add a checkmark starting in the future
    Given the following "courses" exist:
      | fullname | shortname | category | group mode |
      | Course 1 | C1        | 0        | 0          |
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
      | activity  | course | idnumber | name        | intro    | timeavailable |
      | checkmark | C1     | CM1      | Checkmark 1 | Standard | ##tomorrow##  |
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Checkmark 1" "activity"
    Then I should not see "Add submission"
