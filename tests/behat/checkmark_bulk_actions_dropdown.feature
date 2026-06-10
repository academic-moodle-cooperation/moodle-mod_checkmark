@mod @mod_checkmark @amc
Feature: Bulk actions are grouped in the submissions dropdown
  In order to find bulk actions quickly
  As a teacher
  I need the bulk actions dropdown to group related actions

  @javascript
  Scenario: Bulk actions are grouped and separators are removed
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
      | activity  | course | idnumber | name        | intro         | timeavailable | timedue | trackattendance | presentationgrading | presentationgrade |
      | checkmark | C1     | CM1      | Checkmark 1 | Description 1 | 0             | 0       | 1               | 1                   | 100               |
    When I am on the "CM1" Activity page logged in as teacher1
    And I navigate to "Submissions" in current page administration
    Then "//select[@name='bulkaction']/optgroup[1][@label='General']" "xpath_element" should exist
    And "//select[@name='bulkaction']/optgroup[2][@label='Attendance']" "xpath_element" should exist
    And "//select[@name='bulkaction']/optgroup[3][@label='Presentation']" "xpath_element" should exist
    And "//select[@name='bulkaction']/optgroup[4][@label='Grading']" "xpath_element" should exist
    And "select[name='bulkaction'] optgroup[label='General'] option[value='extend']" "css_element" should exist
    And "select[name='bulkaction'] optgroup[label='Attendance'] option[value='setattendant']" "css_element" should exist
    And "select[name='bulkaction'] optgroup[label='Attendance'] option[value='setabsent']" "css_element" should exist
    And "select[name='bulkaction'] optgroup[label='Attendance'] option[value='setattendantandgrade']" "css_element" should exist
    And "select[name='bulkaction'] optgroup[label='Attendance'] option[value='setabsentandgrade']" "css_element" should exist
    And "select[name='bulkaction'] optgroup[label='Presentation'] option[value='removepresentationgrade']" "css_element" should exist
    And "select[name='bulkaction'] optgroup[label='Grading'] option[value='grade'][selected]" "css_element" should exist
    And "select[name='bulkaction'] optgroup[label='Grading'] option[value='removegrade']" "css_element" should exist
    And "//div[contains(concat(' ', normalize-space(@class), ' '), ' fitem ') and contains(concat(' ', normalize-space(@class), ' '), ' form-select ') and .//select[@name='bulkaction']]" "xpath_element" should not exist
    And "//select[@name='bulkaction']//option[normalize-space(.)='---']" "xpath_element" should not exist
