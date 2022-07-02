@mod @mod_checkmark @amc
Feature: In checkmark, a teacher should be able to use any letters, numbers and special characters to name/describe a checkmark.
    In order to name/describe the checkmark however I want
    As a teacher
    In need to able to use letters, numbers and special characters .

  @javascript
  Scenario: Use special characters in name and description
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    # We do not need to manually create the checkmark instance again,
    # this has been testet in checkmark_adding.feature, use generators!
    And the following "activities" exist:
      | activity  | course | idnumber | name    | intro   |
      | checkmark | C1     | CM1      | Cm123!§ | Ds123!§ |
    When I am on the "CM1" Activity page logged in as teacher1
    Then I should see "Cm123!§"
    And I should see "Ds123!§"
