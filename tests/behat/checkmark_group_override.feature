@mod @mod_checkmark @amc
Feature: In a course, a teacher should be able to add overrides to general dates for a certain group
  In order to change the dates for a single group or multiple groups
  As a teacher
  I need to be able to create a group override and save it. Also I need to be capable of editing, duplicating and deleting the override

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
      | student4 | Student   | 4        | student4@example.com |
      | student5 | Student   | 5        | student5@example.com |
      | student6 | Student   | 6        | student6@example.com |
      | student7 | Student   | 7        | student7@example.com |
      | student8 | Student   | 8        | student8@example.com |
    And the following "groups" exist:
      | name | course | idnumber |
      | Group 1 | C1 | G1 |
      | Group 2 | C1 | G2 |
      | Group 3 | C1 | G3 |
      | Group 4 | C1 | G4 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | student4 | C1     | student        |
      | student5 | C1     | student        |
      | student6 | C1     | student        |
      | student7 | C1     | student        |
      | student8 | C1     | student        |
    And the following "group members" exist:
      | user | group |
      | student1 | G1 |
      | student2 | G2 |
      | student3 | G3 |
      | student4 | G4 |
      | student5 | G1 |
      | student5 | G2 |
      | student6 | G3 |
      | student6 | G4 |
      | student7 | G1 |
      | student7 | G2 |
      | student7 | G3 |
      | student7 | G4 |
    And the following "activities" exist:
      | activity  | course | idnumber | name        | intro         | timeavailable | timedue |
      | checkmark | C1     | CM1      | Checkmark 1 | Description 1 | 0             | 0       |

  @javascript
  Scenario: Add, edit and delete a group override
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Checkmark 1"
    And I navigate to "Group overrides" in current page administration
    And I press "Add group override"
    And I open the autocomplete suggestions list
    And I click on "Group 1" item in the autocomplete list
    And I set the following fields to these values:
      | id_timedue_enabled | 1 |
      | timedue[day]       | 1 |
      | timedue[month]     | February |
      | timedue[year]      | 2020 |
      | timedue[hour]      | 08 |
      | timedue[minute]    | 00 |
    And I press "id_override"
    Then I should see "Group 1"
    And I should see "Due date"
    And I should see "Saturday, 1 February 2020, 8:00"
    When I follow "Edit"
    And I set the following fields to these values:
      | id_timedue_enabled | 1 |
      | timedue[day]       | 15 |
      | timedue[month]     | March |
      | timedue[year]      | 2020 |
      | timedue[hour]      | 08 |
      | timedue[minute]    | 00 |
      | id_timeavailable_enabled | 1 |
      | timeavailable[day]       | 1 |
      | timeavailable[month]     | March |
      | timeavailable[year]      | 2020 |
      | timeavailable[hour]      | 08 |
      | timeavailable[minute]    | 00 |
    And I press "id_override"
    Then I should see "Group 1"
    And I should see "Due date"
    And I should see "Sunday, 15 March 2020, 8:00"
    And I should see "Open"
    And I should see "Sunday, 1 March 2020, 8:00"
    When I follow "Delete"
    And I press "Continue"
    Then I should not see "Group 1"
    And I should not see "Due date"
    And I should not see "Sunday, 15 March 2020, 8:00"
    And I should not see "Open"
    And I should not see "Sunday, 1 March 2020, 8:00"
    And I should see "Add group override"

    @javascript
    Scenario: Add and duplicate a group override
      When I log in as "teacher1"
      And I am on "Course 1" course homepage
      And I follow "Checkmark 1"
      And I navigate to "Group overrides" in current page administration
      And I press "Add group override"
      And I open the autocomplete suggestions list
      And I click on "Group 1" item in the autocomplete list
      And I set the following fields to these values:
        | id_timedue_enabled | 1 |
        | timedue[day]       | 1 |
        | timedue[month]     | February |
        | timedue[year]      | 2020 |
        | timedue[hour]      | 08 |
        | timedue[minute]    | 00 |
      And I press "id_override"
      Then I should see "Group 1"
      And I should see "Due date"
      And I should see "Saturday, 1 February 2020, 8:00"
      When I follow "copy"
      And I open the autocomplete suggestions list
      And I click on "Group 2" item in the autocomplete list
      And I click on "Group 3" item in the autocomplete list
      And I press "id_override"
      Then I should see "Group 2"
      And I should see "Group 3"

  @javascript
  Scenario: Allow users in a given group to have a different due date
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    When I follow "Checkmark 1"
    And I navigate to "Edit settings" in current page administration
    And I set the following fields to these values:
      | id_timedue_enabled | 1 |
      | timedue[day]       | 1 |
      | timedue[month]     | January |
      | timedue[year]      | 2020 |
      | timedue[hour]      | 08 |
      | timedue[minute]    | 00 |
    And I press "Save and display"
    And I navigate to "Group overrides" in current page administration
    And I press "Add group override"
    And I open the autocomplete suggestions list
    And I click on "Group 1" item in the autocomplete list
    And I set the following fields to these values:
      | id_timedue_enabled | 1 |
      | timedue[day]       | 1 |
      | timedue[month]     | February |
      | timedue[year]      | 2020 |
      | timedue[hour]      | 08 |
      | timedue[minute]    | 00 |
    And I press "id_override"
    Then I should see "Saturday, 1 February 2020, 8:00"
    And I log out
    When I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Checkmark 1"
    Then I should see "Wednesday, 1 January 2020, 8:00"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Checkmark 1"
    Then I should see "Saturday, 1 February 2020, 8:00"

  @javascript
  Scenario: Allow a user to have a different allow submissions from date
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    When I follow "Checkmark 1"
    And I navigate to "Edit settings" in current page administration
    And I set the following fields to these values:
      | id_timeavailable_enabled | 1 |
      | timeavailable[day]       | 1 |
      | timeavailable[month]     | January |
      | timeavailable[year]      | 2020 |
      | timeavailable[hour]      | 08 |
      | timeavailable[minute]    | 00 |
    And I press "Save and display"
    And I navigate to "Group overrides" in current page administration
    And I press "Add group override"
    And I open the autocomplete suggestions list
    And I click on "Group 1" item in the autocomplete list
    And I set the following fields to these values:
      | id_timeavailable_enabled | 1 |
      | timeavailable[day]       | ## tomorrow ## j ## |
      | timeavailable[month]     | ## tomorrow ## n ## |
      | timeavailable[year]      | ## tomorrow ## Y ## |
      | timeavailable[hour]      | 08 |
      | timeavailable[minute]    | 00 |
    And I press "id_override"
    Then I should see "## tomorrow ##l, j F Y, 8:00##"
    And I log out
    When I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Checkmark 1"
    Then I should see "Wednesday, 1 January 2020, 8:00"
    And "Save changes" "button" should be visible
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Checkmark 1"
    Then I should see "## tomorrow ##l, j F Y, 8:00##"
    And "Save changes" "button" should not be visible

  @javascript
  Scenario: Allow a user to have a different cut-off date
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    When I follow "Checkmark 1"
    And I navigate to "Edit settings" in current page administration
    And I set the following fields to these values:
      | id_timedue_enabled | 1 |
      | timedue[day]       | 1 |
      | timedue[month]     | February |
      | timedue[year]      | 2020 |
      | timedue[hour]      | 08 |
      | timedue[minute]    | 00 |
      | id_cutoffdate_enabled | 1 |
      | cutoffdate[day]       | ## tomorrow ## j ## |
      | cutoffdate[month]     | ## tomorrow ## n ## |
      | cutoffdate[year]      | ## tomorrow ## Y ## |
      | cutoffdate[hour]      | 08 |
      | cutoffdate[minute]    | 00 |
    And I press "Save and display"
    And I navigate to "Group overrides" in current page administration
    And I press "Add group override"
    And I open the autocomplete suggestions list
    And I click on "Group 1" item in the autocomplete list
    And I set the following fields to these values:
      | id_cutoffdate_enabled | 1 |
      | cutoffdate[day]       | 2 |
      | cutoffdate[month]     | February |
      | cutoffdate[year]      | 2020 |
      | cutoffdate[hour]      | 08 |
      | cutoffdate[minute]    | 00 |
    And I press "id_override"
    Then I should see "Sunday, 2 February 2020, 8:00"
    And I log out
    When I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Checkmark 1"
    Then I should see "Saturday, 1 February 2020, 8:00"
    And "Save changes" "button" should be visible
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Checkmark 1"
    Then I should see "Saturday, 1 February 2020, 8:00"
    And "Save changes" "button" should not be visible

    @javascript @currentdev
    Scenario: Reorder group overrides and use the one with the highest priority for a given user
      When I log in as "teacher1"
      And I am on "Course 1" course homepage
      And I follow "Checkmark 1"
      And I navigate to "Group overrides" in current page administration
      And I press "Add group override"
      And I open the autocomplete suggestions list
      And I click on "Group 1" item in the autocomplete list
      And I set the following fields to these values:
        | id_timedue_enabled | 1 |
        | timedue[day]       | 1 |
        | timedue[month]     | February |
        | timedue[year]      | 2020 |
        | timedue[hour]      | 08 |
        | timedue[minute]    | 00 |
      And I press "id_override"
      Then I should see "Group 1"
      And I should see "Due date"
      And I should see "Saturday, 1 February 2020, 8:00"
      When I press "Add group override"
      And I open the autocomplete suggestions list
      And I click on "Group 2" item in the autocomplete list
      And I set the following fields to these values:
        | id_timedue_enabled | 1 |
        | timedue[day]       | 1 |
        | timedue[month]     | March |
        | timedue[year]      | 2020 |
        | timedue[hour]      | 08 |
        | timedue[minute]    | 00 |
      And I press "id_override"
      Then I should see "Group 2"
      And I log out
      When I log in as "student1"
      And I am on "Course 1" course homepage
      And I follow "Checkmark 1"
      Then I should see "Saturday, 1 February 2020, 8:00"
      And I log out
      When I log in as "student2"
      And I am on "Course 1" course homepage
      And I follow "Checkmark 1"
      Then I should see "Sunday, 1 March 2020, 8:00"
      And I log out
      When I log in as "student5"
      And I am on "Course 1" course homepage
      And I follow "Checkmark 1"
      Then I should see "Sunday, 1 March 2020, 8:00"
      And I log out
      When I log in as "teacher1"
      And I am on "Course 1" course homepage
      And I follow "Checkmark 1"
      And I navigate to "Group overrides" in current page administration
      And I follow "Move down"
      And I log out
      When I log in as "student5"
      And I am on "Course 1" course homepage
      And I follow "Checkmark 1"
      Then I should see "Saturday, 1 February 2020, 8:00"
      And I log out
      When I log in as "teacher1"
      And I am on "Course 1" course homepage
      And I follow "Checkmark 1"
      And I navigate to "Group overrides" in current page administration
      And I follow "Move up"
      And I log out
      When I log in as "student5"
      And I am on "Course 1" course homepage
      And I follow "Checkmark 1"
      Then I should see "Sunday, 1 March 2020, 8:00"
      And I log out










