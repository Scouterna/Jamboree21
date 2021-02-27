@chrome @firefox @vagrant
Feature: Switch between mobile and desktop views

  @integration
  Scenario: Switching from desktop view to mobile view
    Given I am on the "Main Page" page
    And I toggle the mobile view
    Then I should see the mobile view

  @integration
  Scenario: Switching from mobile view to desktop view
    Given I am using the mobile site
    And I am on the "Main Page" page
    And I toggle the desktop view
    Then I should see the desktop view

