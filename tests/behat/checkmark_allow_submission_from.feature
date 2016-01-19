@mod @mod_checkmark @amc
Feature: Available From Date
    In order to allow students to use the checkmark course after a certain date
    As a teacher
    I need to be able to set the beginning date for the module to the December 11.

  @javascript
  Scenario: Add a checkmark which starts in the future
    Given the following "courses" exist:
        | fullname | shortname | category | group mode |
        | Checkmark 2.9 | CM 2.9 | 0 | 0 |
    And the following "users" exist:
        | username | firstname | lastname | email |
        | teacher1 | Teacher | 1 | teacher1@example.com |
        | student1 | Student | 1 | student1@example.com |
    And the following "course enrolments" exist:
        | user | course | role |
        | teacher1 | CM 2.9 | editingteacher |
        | student1 | CM 2.9 | student |
    And I log in as "teacher1"
    And I follow "Checkmark 2.9"
    And I turn editing mode on
    And I add a "Checkmark" to section "2" and I fill the form with:
        | Checkmark name | Checkmark1 |
        | Description | standard |
        | timeavailable[day] | 11 |
        | timeavailable[month] | December |
        | timeavailable[year] | 2015 |
    And I log out
    When I log in as "student1"
    And I follow "Checkmark 2.9"
    And I follow "Checkmark1"
    Then I should not see "Add submission"