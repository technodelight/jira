Feature: Perform issue transitions

  Scenario: An issue transition could not be performed
    Given the application configuration "transitions" is configured with:
    """
    {"pick":["Picked up by Dev"]}
    """
    When I run the application with the following input:
      | command  | pick    |
      | issueKey | GEN-359 |
    Then the exit code should be "1"

  Scenario: An issue transition could be performed
    Given the application configuration "transitions" is configured with:
    """
    {"resolve":["Resolve Issue"]}
    """
    And Git command "diff --name-status" returns:
    """
    """
    And Git command "remote -v" returns:
    """
      origin  git@github.com:technodelight/jira.git (fetch)
      origin  git@github.com:technodelight/jira.git (push)
    """
    And Git command "branch -a | grep 'GEN-359'" returns:
    """
      feature/GEN-359-something
    """
    When I run the application with the following input:
      | command  | resolve |
      | issueKey | GEN-359 |
    Then the exit code should be "0"
