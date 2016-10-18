@mod @mod_checkmark @amc
Feature: In a checkmark, a teacher wants to grade a student.
    In order to grade a stundent manually
    As a teacher
    I need to be able to acces the personal grading page of a student and change his grade
@javascript
  Scenario: Add a submission
    Given the following "courses" exist:
        | fullname | shortname | category | groupmode |
        | Course 1 | C1 | 0 | 0 |
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
    And I add a "Checkmark" to section "2" and I fill the form with:
        | Checkmark name | Checkmark1|
        | Description | Description1 |
    When I follow "Checkmark1"
    Then I follow "No attempts have been made on this checkmark"
    And I click on "Grade" "link" in the "student1" "table_row"
    And I set the field "xgrade" to "50"
    And I press "Save changes"
    And "Student 1" row "Grade" column of "generaltable" table should contain "50 / 100"