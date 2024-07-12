
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
    And I add a Checkmark activity to course "Course 1" section "1" and I fill the form with:
      | Checkmark name | checkmark |
      | Description    | check     |
      | ID number      | checkmark |
      | Additional files | lib/tests/fixtures/upload_users.csv |
    When I am on the "checkmark" Activity page logged in as teacher1
    Then "upload_users.csv" "link" should exist
    And following "upload_users.csv" should download between "150" and "300" bytes
    When I navigate to "Settings" in current page administration
    And I upload "lib/tests/fixtures/empty.txt" file to "Additional files" filemanager
    And I press "Save and return to course"
    When I am on the "checkmark" Activity page logged in as teacher1
    Then "empty.txt" "link" should exist
    And "upload_users.csv" "link" should exist
    And following "empty.txt" should download between "10" and "40" bytes
    When I navigate to "Settings" in current page administration
    And I delete "empty.txt" from "Additional files" filemanager
    And I delete "upload_users.csv" from "Additional files" filemanager
    And I press "Save and return to course"
    When I am on the "checkmark" Activity page logged in as teacher1
    Then "empty.txt" "link" should not exist
    And "upload_users.csv" "link" should not exist
