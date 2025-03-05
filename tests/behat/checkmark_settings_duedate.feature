@mod @mod_checkmark @amc
Feature: Testing due date settings
  In order to have a due date for a checkmark
  As a teacher
  I need to be able to change the due date and its corresponding settings

  Background:
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
    And the following "activities" exist:
      | activity  | course | idnumber | name        | intro                                               | timeavailable | timedue      |
      | checkmark | C1     | CM1      | Checkmark 1 | This checkmark is always available since yesterday! | ##yesterday## | ##tomorrow## |

  @javascript
  Scenario: Check if due date is displayed correctly as teacher, also in the dashboard
    When I am on the "CM1" Activity page logged in as teacher1
    Then I should see "Opened"
    Then I should see "Due"
    And I should see "##yesterday##%A, %d %B %Y##"
    And I should see "##tomorrow##%A, %d %B %Y##"
    And I follow "Settings"
    And I set the following fields to these values:
      | calendarteachers | 1 |
    And I press "Save and display"
    And I follow "Dashboard"
    And I should see "Checkmark 1 is due"
    And I am on the "CM1" Activity page
    And I follow "Settings"
    And I set the following fields to these values:
      | calendarteachers | 0 |
    And I press "Save and display"
    And I follow "Dashboard"
    And I should not see "Checkmark 1 is due"

  @javascript
  Scenario: Check if due date can be changed and is displayed correctly
    When I am on the "CM1" Activity page logged in as teacher1
    Then I should see "##tomorrow##%A, %d %B %Y##"
    And I follow "Settings"
    And I set the following fields to these values:
      | Due date | ##today## |
    And I press "Save and display"
    Then I should see "##today##%A, %d %B %Y##"
