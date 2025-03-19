@mod @mod_checkmark @amc
Feature: Deactivate "Allow submissions from" setting
    In order to have a checkmark where submission start is not set
    As a teacher
    I need to able to deactivate the "Allow submissions from" date

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
      | activity  | course | idnumber | name        |
      | checkmark | C1     | CM1      | Checkmark 1 |

  @javascript
  Scenario: Deactivate "Allow submissions from" setting
    Given I am on the "CM1" Activity page logged in as "teacher1"
    Then I should see "Opened:"
    And I follow "Settings"
    And I set the following fields to these values:
      | id_timeavailable_enabled | 0 |
    And I press "Save and return to course"
    And I follow "Checkmark"
    Then I should not see "Opened:"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Checkmark 1"
    Then I should not see "Opened:"
