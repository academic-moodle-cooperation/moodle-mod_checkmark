@mod @mod_checkmark @amc
Feature: In course, a teacher should be able to add a new checkmark
    In order to add a new checkmark
    As a teacher
    I need to be able to add a new checkmark and save it correctly.

  @javascript
  Scenario: Add a checkmark instance
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 0         |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@teacher.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    When I add a "Checkmark" to section "2" and I fill the form with:
      | Checkmark name | checkmark |
      | Description    | check     |
    And I follow "checkmark"
    Then I should see "check"