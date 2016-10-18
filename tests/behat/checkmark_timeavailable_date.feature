@mod @mod_checkmark @amc
Feature: Change Allow submission from date
    In order to change to start date of a checkmar
    As a teacher
    I need to be able change to Allow submission from date
@javascript
  Scenario: Change timeavailable
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
      | Description | Description1 |
        | timeavailable[day] | 9 |
        | timeavailable[month] | April |
        | timeavailable[year] | 2016 |
    And I follow "Checkmark1"
    Then I should see "Allow submissions from"
    And I should see "Saturday, 9 April 2016"