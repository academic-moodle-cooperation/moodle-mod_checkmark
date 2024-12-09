@mod @mod_checkmark
Feature: In the submissions and export table, there should not be the option to hide the column "select".

  Background:
    Given the following "courses" exist:
      # No group mode needed because overrides always work if groups are present
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "activities" exist:
      | activity  | course | idnumber | name        | intro         | timeavailable | timedue |
      | checkmark | C1     | CM1      | Checkmark 1 | Description 1 | 0             | 0       |

  @javascript
  Scenario: In the submissions and export table, there should not be the option to hide the column "select".
  Other columns should be able to be hidden (only a few checked).
    When I am on the "CM1" Activity page logged in as teacher1
    And I navigate to "Submissions" in current page administration
    And I follow "Export"
    Then "Hide Select" "button" should not be visible
    And "Hide Name" "button" should be visible
    And "Hide Email address" "button" should be visible
