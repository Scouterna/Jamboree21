@chrome @en.m.wikipedia.beta.wmflabs.org @firefox @test2.m.wikipedia.org @vagrant @integration @extension-cite
Feature: Reference popup drawer

  Background:
    Given I am using the mobile site

  Scenario: Opening and closing the reference drawer
    Given I go to a page that has references
    When I click on a reference
      And I click on the page
    Then I should not see the reference drawer

  Scenario: Opening a nested reference
    Given I go to a page that has references
    When I click on a reference
      And I click on a nested reference
    Then I should see a drawer with message "This is a nested ref."
