@chrome @en.m.wikipedia.beta.wmflabs.org @extension-echo @firefox @test2.m.wikipedia.org @vagrant @login
Feature: Notification

  Background:
    Given I am logged into the mobile website
      And I have no notifications
    When I click on the notification icon
    Then I should see the notifications overlay

  Scenario: Closing notifications (overlay button)
    When I click the notifications overlay close button
    Then after 1 seconds I should not see the notifications overlay

  Scenario: Closing notifications (browser button)
    When I click the browser back button
    Then after 1 seconds I should not see the notifications overlay
