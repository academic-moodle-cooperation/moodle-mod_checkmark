@mod @mod_checkmark @amc
Feature: Change Allow submission from date
    In order to change to start date of a checkmar
    As a teacher
    I need to be able change to Allow submission from date

  @javascript
  Scenario: Change timeavailable
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
    # We do not need to manually create the checkmark instance again,
    # this has been testet in checkmark_adding.feature, use generators!
    And the following "activities" exist:
      | activity  | course | idnumber | name        | intro                                               | timeavailable |
      | checkmark | C1     | CM1      | Checkmark 1 | This checkmark is always available since yesterday! | ##yesterday## |
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "Checkmark 1" "activity"
    Then I should see "Allow submissions from"
    And I should see "##yesterday##%A, %d %B %Y##"
