@mod @mod_checkmark @amc
Feature: Use attendance tracking and auto-grading linked to attendance
    In order to track the attendance of students
    As a teacher
    I need to be able change change the settings and the attendance

  @javascript
  Scenario: Track attendance and check if auto-grading works with and without linking to attendance
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
      | activity  | course | idnumber | name        | intro                                               | timeavailable | timedue |
      | checkmark | C1     | CM1      | Checkmark 1 | This checkmark is always available since yesterday! | ##yesterday## | ##tomorrow## |
    When I am on the "CM1" Activity page logged in as teacher1
    And I follow "Settings"
    And I press "Expand all"
    And I set the following fields to these values: 
      | id_trackattendance     | 0 | 
      | id_attendancegradelink | 0 |
      | id_attendancegradebook | 0 |
    And I press "Save and display"
    And I follow "Submissions"
    And I should not see "Attendance"
    And I follow "Settings"
    And I set the following fields to these values: 
      | id_trackattendance     | 1 | 
      | id_attendancegradelink | 0 |
      | id_attendancegradebook | 0 |
    And I press "Save and display"
    And I follow "Submissions"
    And I should see "Attendance"
    And I log out
    And I am on the "CM1" Activity page logged in as student1
    And I set the following fields to these values:
      | Example 1 | 1 |
      | Example 2 | 1 |
      | Example 3 | 1 |
      | Example 4 | 1 |
      | Example 5 | 1 |
      | Example 6 | 1 |
    And I press "Save submission"
    And I log out
    And I am on the "CM1" Activity page logged in as teacher1
    And I follow "Submission"
    And I set the following fields to these values:
      | chmrk_selectallcb | 1     |
      | bulkaction        | grade |
    And I press "Start"
    Then I should see "Auto-grading successful! 2 submissions updated."
    And I follow "Settings"
    And I set the following fields to these values:  
      | id_attendancegradelink | 1 |
    And I press "Save and display"
    And I follow "Submission"
    And I set the following fields to these values:
      | chmrk_selectallcb | 1     |
      | bulkaction        | grade |
    And I press "Start"
    Then I should see "Auto-grading successful! 0 submissions updated."
    And I follow "Update"
    And I set the following fields to these values:
      | id_attendance | 1 |
    And I press "Save changes"
    And I set the following fields to these values:
      | chmrk_selectallcb | 1     |
      | bulkaction        | grade |
    And I press "Start"
    And I press "Continue"
    Then I should see "Auto-grading successful! 1 submission updated."
