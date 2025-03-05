@mod @mod_checkmark @amc
Feature: Test individual naming settings for checkmarks
    In order to have custom checkmarks
    As a teacher
    I need to be able to change the individual naming settings for checkmarks

  Background:
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

  @javascript
  Scenario: Add custom checkmarks and check if they work correctly
    When I am on the "CM1" Activity page logged in as teacher1
    And I follow "Settings"
    And I press "Show more"
    And I set the following fields to these values: 
      | exampleprefix     | CustomX                            | 
      | id_flexiblenaming | 1                                  | 
      | id_examplenames   | 0,1,2,3,4,5,6,7,8,9,10,11,12,13,14 | 
      | id_examplegrades  | 10,5,10,10,10,5,5,5,5,5,10,5,5,5,5 | 
    And I press "Save and display"
    And I follow "View preview"
    Then I should see "CustomX0 (10 Points)"
    Then I should see "CustomX1 (5 Points)"
    Then I should see "CustomX4 (10 Points)"
    Then I should see "CustomX6 (5 Points)"
    Then I should see "CustomX8 (5 Points)"
    Then I should see "CustomX10 (10 Points)"
    Then I should see "CustomX12 (5 Points)"
    Then I should see "CustomX14 (5 Points)"
    Then I should not see "CustomX 15 (10 Points)"
    Then I should not see "Example 14 (10 Points)"
    And I log out 
    And I am on the "CM1" Activity page logged in as student1
    And I set the following fields to these values:
      | CustomX0  | 1 |
      | CustomX1  | 0 |
      | CustomX2  | 1 |
      | CustomX3  | 1 |
      | CustomX4  | 1 |
      | CustomX5  | 1 |
      | CustomX6  | 1 |
      | CustomX7  | 1 |
      | CustomX8  | 1 |
      | CustomX9  | 1 |
      | CustomX10 | 1 |
      | CustomX11 | 1 |
      | CustomX12 | 0 |
      | CustomX13 | 0 |
      | CustomX14 | 0 |
    And I press "Save submission"
    And I log out 
    And I am on the "CM1" Activity page logged in as teacher1
    And I follow "Submissions" 
    And I set the following fields to these values:
      | filter | 3 |
    And I click on "selected[]" "checkbox"
    And I set the following fields to these values:
      | bulkaction | grade |
    And I press "Start"
    Then I should see "Auto-grading successful!"
    And I follow "Export"
    Then I should see "80 / 100"
