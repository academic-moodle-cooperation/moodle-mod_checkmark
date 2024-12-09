@mod @mod_checkmark @amc
Feature: In a course, a teacher should be able to add overrides to general dates for a certain group
  In order to change the dates for a single group or multiple groups
  As a teacher
  I need to be able to create a group override and save it. Also I need to be capable of editing, duplicating and deleting the override

  Background:
    Given the following "courses" exist:
      # No group mode needed because overrides always work if groups are present
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
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
      | name    | course | idnumber |
      | Group 1 | C1     | G1       |
      | Group 2 | C1     | G2       |
      | Group 3 | C1     | G3       |
      | Group 4 | C1     | G4       |
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
    When I am on the "CM1" Activity page logged in as teacher1
    And I navigate to "Overrides" in current page administration
    And I select "Group overrides" from the "jump" singleselect
    And I press "Add group override"
    And I set the following fields to these values:
      | Allow submissions from | disabled             |
      | Due date               | ##1 Feb 2020 08:00## |
      | Cut-off date           | disabled             |
      | Groups                 | Group 1              |
    And I press "id_override"
    Then I should see "Group 1"
    And I should see "Due date"
    And I should see "Saturday, 1 February 2020, 8:00"
    When I follow "Edit"
    And I set the following fields to these values:
      | Allow submissions from | ##1 March 2020 08:00## |
      | Due date               | ##15 March 2020 08:00## |
      | Cut-off date           | disabled             |
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
    When I am on the "CM1" Activity page logged in as teacher1
    And I navigate to "Overrides" in current page administration
    And I select "Group overrides" from the "jump" singleselect
    And I press "Add group override"
    And I set the following fields to these values:
      | Allow submissions from | disabled             |
      | Due date               | ##1 Feb 2020 08:00## |
      | Cut-off date           | disabled             |
      | Groups                 | Group 1              |
    And I press "id_override"
    Then I should see "Group 1"
    And I should see "Due date"
    And I should see "Saturday, 1 February 2020, 8:00"
    When I follow "copy"
    And I open the autocomplete suggestions list
    And I click on "Group 2" item in the autocomplete list
    And I click on "Group 3" item in the autocomplete list
    And I press the escape key
    And I press "id_override"
    Then I should see "Group 2"
    And I should see "Group 3"

  @javascript
  Scenario: Allow users in a given group to have a different due date
    Given I am on the "CM1" Activity page logged in as teacher1
    When I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Allow submissions from | disabled             |
      | Due date               | ##1 Jan 2020 08:00## |
      | Cut-off date           | disabled             |
    And I press "Save and display"
    And I navigate to "Overrides" in current page administration
    And I select "Group overrides" from the "jump" singleselect
    And I press "Add group override"
    And I set the following fields to these values:
      | Allow submissions from | disabled                  |
      | Due date               | ##1 February 2020 08:00## |
      | Cut-off date           | disabled                  |
      | Groups                 | Group 1                   |
    And I press "id_override"
    Then I should see "Saturday, 1 February 2020, 8:00"
    And I log out
    When I log in as "student2"
    And I am on "Course 1" course homepage
    And I click on "Checkmark 1" "link"
    Then I should see "Wednesday, 1 January 2020, 8:00"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Checkmark 1" "link"
    Then I should see "Saturday, 1 February 2020, 8:00"

  @javascript
  Scenario: Allow a user to have a different allow submissions from date
    When I am on the "CM1" Activity page logged in as teacher1
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Due date               | disabled                 |
      | Allow submissions from | ##1 January 2020 08:00## |
      | Cut-off date           | disabled                 |
    And I press "Save and display"
    And I navigate to "Overrides" in current page administration
    And I select "Group overrides" from the "jump" singleselect
    And I press "Add group override"
    And I set the following fields to these values:
      | Due date               | disabled                         |
      | Allow submissions from | ##tomorrow noon## |
      | Cut-off date           | disabled                         |
      | Groups                 | Group 1                          |
    And I press "id_override"
    Then I should see "##tomorrow##%A, %d %B %Y, 12:00##"
    And I log out
    When I am on the "CM1" Activity page logged in as student2
    Then I should see "Wednesday, 1 January 2020, 8:00"
    And "Save submission" "button" should be visible
    And I log out
    When I am on the "CM1" Activity page logged in as student1
    Then I should see "##tomorrow##%A, %d %B %Y, 12:00##"
    And "Save submission" "button" should not be visible

  @javascript
  Scenario: Allow a user to have a different cut-off date
    When I am on the "CM1" Activity page logged in as teacher1
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Allow submissions from | disabled                  |
      | Due date               | ##1 February 2020 08:00## |
      | Cut-off date           | ##tomorrow noon##         |
    And I press "Save and display"
    And I navigate to "Overrides" in current page administration
    And I select "Group overrides" from the "jump" singleselect
    And I press "Add group override"
    And I set the following fields to these values:
      | Allow submissions from | disabled                  |
      | Due date               | disabled                  |
      | Cut-off date           | ##2 February 2020 08:00## |
    And I open the autocomplete suggestions list
    And I click on "Group 1" item in the autocomplete list
    And I press the escape key
    And I press "id_override"
    Then I should see "Sunday, 2 February 2020, 8:00"
    And I log out
    When I am on the "CM1" Activity page logged in as student2
    Then I should see "Saturday, 1 February 2020, 8:00"
    And "Save submission" "button" should be visible
    And I log out
    When I am on the "CM1" Activity page logged in as student1
    Then I should see "Saturday, 1 February 2020, 8:00"
    And "Save submission" "button" should not be visible

  @javascript
  Scenario: Reorder group overrides and use the one with the highest priority for a given user
    When I am on the "CM1" Activity page logged in as teacher1
    And I navigate to "Overrides" in current page administration
    And I select "Group overrides" from the "jump" singleselect
    And I press "Add group override"
    And I set the following fields to these values:
      | Allow submissions from | disabled                  |
      | Due date               | ##1 February 2020 08:00## |
      | Cut-off date           | disabled                  |
      | Groups                 | Group 1                   |
    And I press "id_override"
    Then I should see "Group 1"
    And I should see "Due date"
    And I should see "Saturday, 1 February 2020, 8:00"
    When I press "Add group override"
    And I set the following fields to these values:
      | Allow submissions from | disabled               |
      | Due date               | ##1 March 2020 08:00## |
      | Cut-off date           | disabled               |
      | Groups                 | Group 2                |
    And I press "id_override"
    Then I should see "Group 2"
    And I log out
    When I am on the "CM1" Activity page logged in as student1
    Then I should see "Saturday, 1 February 2020, 8:00"
    And I log out
    When I am on the "CM1" Activity page logged in as student2
    Then I should see "Sunday, 1 March 2020, 8:00"
    And I log out
    When I am on the "CM1" Activity page logged in as student5
    Then I should see "Sunday, 1 March 2020, 8:00"
    And I log out
    When I am on the "CM1" Activity page logged in as teacher1
    And I navigate to "Overrides" in current page administration
    And I select "Group overrides" from the "jump" singleselect
    And I follow "Move down"
    And I log out
    When I am on the "CM1" Activity page logged in as student5
    Then I should see "Saturday, 1 February 2020, 8:00"
    And I log out
    When I am on the "CM1" Activity page logged in as teacher1
    And I navigate to "Overrides" in current page administration
    And I select "Group overrides" from the "jump" singleselect
    And I follow "Move up"
    And I log out
    When I am on the "CM1" Activity page logged in as student5
    Then I should see "Sunday, 1 March 2020, 8:00"
    And I log out

  @javascript
  Scenario: Create user and group overrides and check if user overrides are priorotized over group overrides
    When I am on the "CM1" Activity page logged in as teacher1
    And I navigate to "Overrides" in current page administration
    And I select "Group overrides" from the "jump" singleselect
    And I press "Add group override"
    And I set the following fields to these values:
      | Allow submissions from | disabled                  |
      | Due date               | ##1 February 2020 08:00## |
      | Cut-off date           | disabled                  |
      | Groups                 | Group 1                   |
    And I press "id_override"
    Then I should see "Group 1"
    And I should see "Due date"
    And I should see "Saturday, 1 February 2020, 8:00"
    And I log out
    When I am on the "CM1" Activity page logged in as student1
    Then I should see "Saturday, 1 February 2020, 8:00"
    And I log out
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "Checkmark 1" "link"
    And I navigate to "Overrides" in current page administration
    And I select "User overrides" from the "jump" singleselect
    And I press "Add user override"
    And I set the following fields to these values:
      | Allow submissions from | disabled               |
      | Due date               | ##1 March 2020 08:00## |
      | Cut-off date           | disabled               |
      | Users                  | Student 1              |
    And I press "id_override"
    Then I should see "Student 1"
    And I should see "Due date"
    And I should see "Sunday, 1 March 2020, 8:00"

  @javascript
  Scenario: A teacher without accessallgroups permission should only be able to see the group overrides for groups that he/she is in,
    when the activity's group mode is "separate groups"
    Given the following "permission overrides" exist:
      | capability                  | permission | role           | contextlevel | reference |
      | moodle/site:accessallgroups | Prevent    | editingteacher | Course       | C1        |
    And the following "activities" exist:
      | activity    | name        | intro                   | course | idnumber    | groupmode |
      | checkmark   | Checkmark 2 | Checkmark 2 description | C1     | checkmark2  | 1         |
    And the following "group members" exist:
      | user     | group |
      | teacher1 | G1    |
    When I am on the "checkmark2" Activity page logged in as admin
    And I navigate to "Overrides" in current page administration
    And I select "Group overrides" from the "jump" singleselect
    And I press "Add group override"
    And I set the following fields to these values:
      | Allow submissions from | ##1 January 2015 08:00## |
      | Due date               | disabled                 |
      | Cut-off date           | disabled                 |
      | Groups                 | Group 1                  |
    And I press "Override and create a new override"
    And I set the following fields to these values:
      | Allow submissions from | ##1 January 2015 08:00## |
      | Due date               | disabled                 |
      | Cut-off date           | disabled                 |
      | Groups                 | Group 2                  |
    And I press "id_override"
    And I log out
    When I am on the "checkmark2" Activity page logged in as teacher1
    And I navigate to "Overrides" in current page administration
    And I select "Group overrides" from the "jump" singleselect
    Then I should see "Group 1"
    And I should not see "Group 2"
