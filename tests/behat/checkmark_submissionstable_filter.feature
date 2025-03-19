@mod @mod_checkmark @amc
Feature: In a course, a teacher should be able to filter users in the "Submissions" table with the optional setting "Show".

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 0         |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
      | student3 | Student   | 3        | student3@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
    And the following "activities" exist:
      | activity  | course | idnumber | name        | intro         | timeavailable | timedue |
      | checkmark | C1     | CM1      | Checkmark 1 | Description 1 | 0             | 0       |

  @javascript
  Scenario: Filter users in the "Submissions" table with the optional setting "Show" set to "Graded".
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Checkmark 1"
    And I set the following fields to these values:
      | Example 1 | 1 |
      | Example 2 | 1 |
      | Example 3 | 1 |
      | Example 4 | 1 |
      | Example 5 | 1 |
      | Example 6 | 1 |
    And I press "Save submission"
    And I log out
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Checkmark 1"
    And I navigate to "Submissions" in current page administration
    And I set the following fields to these values:
      | chmrk_selectallcb | 1     |
      | bulkaction        | grade |
    And I press "Start"
    Then I should see "Auto-grading successful! 3 submissions updated."
    And I set the following fields to these values:
      | filter | 12 |
    And I press "Save preferences"
    Then I should see "student1"

  @javascript
  Scenario: Override student due date and filter users in the "Submissions" table with the
            optional setting "Show" set to "Granted extension".
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Checkmark 1"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | id_timedue_enabled | 1 |
      | timedue[day]       | 1 |
      | timedue[month]     | January |
      | timedue[year]      | 2023 |
      | timedue[hour]      | 08 |
      | timedue[minute]    | 00 |
    And I press "Save and display"
    When I navigate to "Overrides" in current page administration
    And I select "User overrides" from the "jump" singleselect
    And I press "Add user override"
    And I set the following fields to these values:
      | id_timedue_enabled | 1 |
      | timedue[day]       | 1 |
      | timedue[month]     | February |
      | timedue[year]      | 2023 |
      | timedue[hour]      | 08 |
      | timedue[minute]    | 00 |
    And I open the autocomplete suggestions list
    And I click on "Student 1" item in the autocomplete list
    And I press the escape key
    And I press "id_override"
    Then I should see "Wednesday, 1 February 2023, 8:00"
    And I log out
    When I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Checkmark 1"
    Then I should see "Sunday, 1 January 2023, 8:00"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Checkmark 1"
    Then I should see "Wednesday, 1 February 2023, 8:00"
    And I log out
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Checkmark 1"
    And I navigate to "Submissions" in current page administration
    And I set the following fields to these values:
      | filter | 8 |
    And I press "Save preferences"
    Then I should see "student1"

  @javascript
  Scenario: Filter users in the "Submissions" table with the optional setting "Show" set to "Not submitted".
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Checkmark 1"
    And I navigate to "Submissions" in current page administration
    And I set the following fields to these values:
      | filter | 9 |
    And I press "Save preferences"
    Then I should see "student1"
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Checkmark 1"
    And I set the following fields to these values:
      | Example 1 | 1 |
      | Example 2 | 1 |
      | Example 3 | 1 |
      | Example 4 | 1 |
      | Example 5 | 1 |
      | Example 6 | 1 |
    And I press "Save submission"
    And I log out
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Checkmark 1"
    And I navigate to "Submissions" in current page administration
    And I set the following fields to these values:
      | filter | 9 |
    And I press "Save preferences"
    Then I should not see "student1"

  @javascript
  Scenario: Filter users in the "Submissions" table with the optional setting "Show" set to "Requires grading".
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Checkmark 1"
    And I set the following fields to these values:
      | Example 1 | 1 |
      | Example 2 | 1 |
      | Example 3 | 1 |
      | Example 4 | 1 |
      | Example 5 | 1 |
      | Example 6 | 1 |
    And I press "Save submission"
    And I log out
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Checkmark 1"
    And I navigate to "Submissions" in current page administration
    And I set the following fields to these values:
      | filter | 3 |
    And I press "Save preferences"
    Then I should see "student1"
    And I set the following fields to these values:
      | chmrk_selectallcb | 1 |
      | bulkaction | grade |
    And I press "Start"
    Then I should see "Auto-grading successful! 1 submission updated."
    And I set the following fields to these values:
      | filter | 3 |
    And I press "Save preferences"
    Then I should not see "student1"

  @javascript
  Scenario: Filter users in the "Submissions" table with the optional setting "Show" set to "Submitted".
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Checkmark 1"
    And I set the following fields to these values:
      | Example 1 | 1 |
      | Example 2 | 1 |
      | Example 3 | 1 |
      | Example 4 | 1 |
      | Example 5 | 1 |
      | Example 6 | 1 |
    And I press "Save submission"
    And I log out
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Checkmark 1"
    And I navigate to "Submissions" in current page administration
    And I set the following fields to these values:
      | filter | 2 |
    And I press "Save preferences"
    Then I should see "student1"
    And I set the following fields to these values:
      | chmrk_selectallcb | 1 |
      | bulkaction | grade |
    And I press "Start"
    Then I should see "Auto-grading successful! 1 submission updated."
    And I set the following fields to these values:
      | filter | 2 |
    And I press "Save preferences"
    Then I should see "student1"
