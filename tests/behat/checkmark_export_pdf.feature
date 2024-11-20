@mod @mod_checkmark
Feature: Test the export to PDF functionality

  Background:
    Given the following "courses" exist:
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
  Scenario: Export all data to PDF with sequential numbering of rows.
    When I am on the "CM1" Activity page logged in as teacher1
    And I navigate to "Submissions" in current page administration
    And I follow "Export"
    And I set the following fields to these values:
      | Filter | All |
      | Sequential numbering of rows | enabled |
    # Use the id_export button to trigger the export submit button instead of the "Export settings" collapsible element.
    And I press "id_export"
