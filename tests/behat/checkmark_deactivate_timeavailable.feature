@mod @mod_checkmark @amc
Feature: Deactivate Allow submissions from
    In order to have a checkmark available all the time
    As a teacher
    I need to able to deactivate the Allow-submission-from-date
@javascript
  Scenario: Deactivate timeavailable
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
    When I add a "Checkmark" to section "2" and I fill the form with:
      | Checkmark name | Checkmark1  |
      | timeavailable[enabled] | 0 |
    And I follow "Checkmark1"
    Then I should not see "Allow submissions from"
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    And I follow "Checkmark1"
    Then I should not see "Allow submission from"
    And  I press "Add submission"