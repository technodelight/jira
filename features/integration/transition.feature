Feature: Perform issue transitions

  Background:
    Given Git command "branch -a | grep '* '" returns:
    """
    """
    And Git command "remote -v 2> /dev/null" returns:
    """
      origin  git@github.com:technodelight/jira.git (fetch)
      origin  git@github.com:technodelight/jira.git (push)
    """
    And Git command "diff --name-status" returns:
    """
    """
    And GitHub returns "issues" fixture for "get" path "/repos/technodelight/jira/issues"

  Scenario: An issue transition could not be performed
    Given the application configuration "transitions" is configured with:
    """
    [{"command": "pick", "transition":["Picked up by Dev"]}]
    """
    When I run the application with the following input:
      | command  | pick    |
      | issueKey | GEN-359 |
    Then the exit code should be "1"

  Scenario: An issue transition could be performed
    Given the application configuration "transitions" is configured with:
    """
    [{"command": "resolve", "transition":["Resolve Issue"]}]
    """
    When I run the application with the following input:
      | command  | workflow:resolve |
      | issueKey | GEN-359          |
    Then the output should contain "moved to"
    Then the exit code should be "0"
