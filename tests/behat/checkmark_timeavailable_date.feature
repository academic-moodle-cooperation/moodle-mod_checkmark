@mod @mod_checkmark @amc
Feature: Change "Allow submissions from" date
  In order to change the start date of a checkmark
  As a teacher
  I need to be able change the "Allow submission from" date

  @javascript
  Scenario: Change "Allow submissions from" data and check if it is displayed correctly
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
      | activity  | course | idnumber | name        | intro                                               | timeavailable |
      | checkmark | C1     | CM1      | Checkmark 1 | This checkmark is always available since yesterday! | ##yesterday## |
    When I am on the "CM1" Activity page logged in as teacher1
    Then I should see "Opened"
    And I should see "##yesterday##%A, %d %B %Y##"
    And I follow "Settings"
    And I set the following fields to these values:
      | Allow submissions from | ##today## |
    And I press "Save and display"
    Then I should see "##today##%A, %d %B %Y##"
