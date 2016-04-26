@mod @mod_checkmark @amc
Feature: In checkmark, a teacher should be able to use any letters, numbers and special characters to name/describe a checkmark. 
    In order to name/describe the checkmark however I want
    As a teacher
    In need to able to use letters, numbers and special characters .

  @javascript
  Scenario: Special Characters 
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course1	 | C1 | 0 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    And I follow "Course1"
    And I turn editing mode on
    When I add a "Checkmark" to section "2" and I fill the form with:
        | Checkmark name | Cm123!§ |
        | Description | Ds123!§ |
    And I follow "Cm123!§"
    Then I should see "Cm123!§" 
    And I should see "Ds123!§"