@mod @mod_checkmark @amc
Feature: In a checkmark, a teacher wants to grade a student.
    In order to grade a stundent manually
    As a teacher
    I need to be able to acces the personal grading page of a student and change his grade

  @javascript
  Scenario: Display a grade
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 0         |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    # We do not need to manually create the checkmark instance again,
    # this has been testet in checkmark_adding.feature, use generators!
    And the following "activities" exist:
      | activity  | course | idnumber | name        | intro         |
      | checkmark | C1     | CM1      | Checkmark 1 | Description 1 |
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Checkmark 1"
    And I click on "submissions" "link"
    And I click on "Grade" "link" in the "student1" "table_row"
    And I set the field "xgrade" to "50"
    And I press "Save changes"
    Then "Student 1" row "Grade" column of "generaltable" table should contain "50 / 100"
