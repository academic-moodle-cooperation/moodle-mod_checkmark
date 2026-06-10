@mod @mod_checkmark @amc @with_checkmarkaddon_simple
Feature: Manage Checkmark add-on subplugins
  In order to confirm that Checkmark supports add-on subplugins
  As an administrator
  I need to see installed Checkmark add-ons in site administration

  Scenario: Admin can manage a Checkmark add-on
    Given I log in as "admin"
    When I navigate to "Plugins > Activity modules > Checkmark > Checkmark add-ons > Manage Checkmark add-ons" in site administration
    Then I should see "Simple Checkmark add-on"
    And I should see "Settings" in the "Simple Checkmark add-on" "table_row"
    And "//tr[contains(., 'Simple Checkmark add-on')]//a[contains(@href, 'action=hide')]" "xpath_element" should exist
    When I click on "//tr[contains(., 'Simple Checkmark add-on')]//a[contains(@href, 'action=hide')]" "xpath_element"
    Then "//tr[contains(., 'Simple Checkmark add-on')]//a[contains(@href, 'action=show')]" "xpath_element" should exist
