@mod @mod_checkmark @amc
Feature: In a course, a student should be able to add a submission
    In order to add a submission
    As a student
    I need to be able to change the submission and save it.

  @javascript
  Scenario: Add a submission
    Given the following "courses" exist:
        | fullname | shortname | category | groupmode |
        | Checkmark 2.9 | CM 2.9 | 0 | 0 |
    And the following "users" exist:
        | username | firstname | lastname | email |
        | teacher1 | Teacher | 1 | teacher1@example.com |
        | student1 | Student | 1 | student1@example.com |
    And the following "course enrolments" exist:
        | user | course | role |
        | teacher1 | CM 2.9 | editingteacher |
        | student1 | CM 2.9 | student |
    And I log in as "teacher1"
    And I follow "Checkmark 2.9"
    And I turn editing mode on
    And I add a "Checkmark" to section "2" and I fill the form with:
        | Checkmark name | Kreuzerlübung |
        | Description | Standard-Einstellungen |
    And I log out
    When I log in as "student1"
    And I follow "Checkmark 2.9"
    And I follow "Kreuzerlübung"
    And I press "Add submission"
    And I set the following fields to these values:
        | Example 1 | 1 |
        | Example 2 | 1 |
        | Example 3 | 1 |
        | Example 4 | 1 |
        | Example 5 | 1 |
        | Example 6 | 1 |
    And I press "Save changes"
    Then I should see "Your changes have been saved"