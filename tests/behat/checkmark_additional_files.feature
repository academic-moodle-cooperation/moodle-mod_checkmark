@mod @mod_checkmark @amc
Feature: In course, a teacher should be able to add files to a checkmark
  In order to add a new checkmark with additional files
  As a teacher
  I need to be able to add files to a new checkmark as well as to update and delete them

  @javascript @_file_upload
  Scenario: Add, update and delete additional files in a checkmark instance
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
      | Additional files | lib/tests/fixtures/upload_users.csv |
    And I click on "checkmark" "activity"
    Then "upload_users.csv" "link" should exist
    And following "upload_users.csv" should download between "150" and "300" bytes
    When I navigate to "Edit settings" in current page administration
    And I upload "lib/tests/fixtures/empty.txt" file to "Additional files" filemanager
    And I press "Save and return to course"
    And I click on "checkmark" "activity"
    Then "empty.txt" "link" should exist
    And "upload_users.csv" "link" should exist
    And following "empty.txt" should download between "10" and "40" bytes
    When I navigate to "Edit settings" in current page administration
    And I delete "empty.txt" from "Additional files" filemanager
    And I delete "upload_users.csv" from "Additional files" filemanager
    And I press "Save and return to course"
    And I click on "checkmark" "activity"
    Then "empty.txt" "link" should not exist
    And "upload_users.csv" "link" should not exist
