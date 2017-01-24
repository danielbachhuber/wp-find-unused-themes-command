Feature: Inspect WordPress multisite for unused themes

  Background:
    Given a WP multisite install
    And I run `wp site create --slug=foo`
    And I run `wp site create --slug=bar`

  Scenario: Find unused themes on the network
    When I run `wp theme install p2`
    Then STDOUT should not be empty

    When I run `wp find-unused-themes`
    Then STDOUT should contain:
      """
      p2
      """
    And STDOUT should contain:
      """
      twentyfifteen
      """

    When I run `wp --url=example.com/foo theme enable p2`
    Then STDOUT should not be empty

    When I run `wp find-unused-themes`
    Then STDOUT should not contain:
      """
      p2
      """
    And STDOUT should contain:
      """
      twentyfifteen
      """


