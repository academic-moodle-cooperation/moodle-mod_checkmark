@mod @mod_checkmark @amc
Feature: Track presentations and grade them
    In order to track presentations from students for a checkmark
    As a teacher
    I need to be able change the settings and grade the presentations

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
      | activity  | course | idnumber | name        | intro | timeavailable | timedue      |
      | checkmark | C1     | CM1      | Checkmark 1 | Intro | ##yesterday## | ##tomorrow## |

  @javascript
  Scenario: Activate presentation tracking and see if it appears in the submissions tab
    When I am on the "CM1" Activity page logged in as teacher1
    And I follow "Settings"
    And I press "Expand all"
    And I set the following fields to these values:
      | id_presentationgrading | 0 |
    And I press "Save and display"
    And I follow "Submissions"
    Then I should not see "presentation"
    And I follow "Settings"
    And I set the following fields to these values:
      | id_presentationgrading             | 1    |
      | id_presentationgrade_modgrade_type | none |
    And I press "Save and display"
    And I follow "Submissions"
    Then I should see "Presentation"
    And I should see "Comment (Presentation)"
    And I should not see "Grade (Presentation)"
    And I follow "Settings"
    And I set the following fields to these values:
      | id_presentationgrading             | 1     |
      | id_presentationgrade_modgrade_type | point |
    And I press "Save and display"
    And I follow "Submissions"
    Then I should see "Comment (Presentation)"
    And I should see "Grade (Presentation)"
    And I click on "#hidepresentation" "css_element"
    And I wait until the page is ready
    Then I should see "Presentation"
    And I should not see "Comment (Presentation)"
    And I should not see "Grade (Presentation)"
    And I click on "#showpresentation" "css_element"
    And I wait until the page is ready
    Then I should see "Comment (Presentation)"
    And I should see "Grade (Presentation)"
    And I follow "Settings"
    And I set the following fields to these values:
      | id_presentationgrading             | 1     |
      | id_presentationgrade_modgrade_type | scale |
    And I press "Save and display"
    And I follow "Submissions"
    Then I should see "Comment (Presentation)"
    And I should see "Grade (Presentation)"

  @javascript
  Scenario: Grade presentation by using the scale type
    When I am on the "CM1" Activity page logged in as teacher1
    And I follow "Settings"
    And I press "Expand all"
    And I set the following fields to these values:
      | id_presentationgrading             | 1     |
      | id_presentationgrade_modgrade_type | scale |
    And I press "Save and display"
    And I follow "Submissions"
    And I set the following fields to these values:
      | chmrk_selectallcb | 1     |
      | bulkaction        | grade |
    And I press "Start"
    And I follow "Update"
    And I set the following fields to these values:
      | id_presentationgrade | 2 |
    And I press "Save changes"
    Then I should see "Competent"

  @javascript
  Scenario: Grade presentation by using the point type
    When I am on the "CM1" Activity page logged in as teacher1
    And I follow "Settings"
    And I press "Expand all"
    And I set the following fields to these values:
      | id_presentationgrading             | 1     |
      | id_presentationgrade_modgrade_type | point |
    And I press "Save and display"
    And I follow "Submissions"
    And I set the following fields to these values:
      | chmrk_selectallcb | 1     |
      | bulkaction        | grade |
    And I press "Start"
    And I follow "Update"
    Then I should see "Presentation"
    And I set the following fields to these values:
      | id_presentationstatus | 0  |
      | id_presentationgrade  | 50 |
    And I press "Save changes"
    Then I should see "50 / 100"
    And "Student 1" row "Presentation" column of "generaltable" table should contain "Yes"

  @javascript
  Scenario: Update only the presentation modification time when the presentation status changes
    When I am on the "CM1" Activity page logged in as teacher1
    And I follow "Settings"
    And I press "Expand all"
    And I set the following fields to these values:
      | id_presentationgrading             | 1    |
      | id_presentationgrade_modgrade_type | none |
    And I press "Save and display"
    And I follow "Submissions"
    And I click on "Quick grading" "checkbox"
    And I set the field with xpath "//tr[contains(normalize-space(.), 'Student 1')]//select[contains(@name, 'presentationstatus')]" to "Marked"
    And I press "Save all grading changes"
    Then "Student 1" row "Presentation" column of "generaltable" table should contain "Marked"
    And "Student 1" row "Last modified (Grade)" column of "generaltable" table should contain "-"
    And "Student 1" row "Last modified (Presentation)" column of "generaltable" table should not contain "-"
