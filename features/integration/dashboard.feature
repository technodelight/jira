Feature: The dashboard could be rendered

  Scenario: I have worked today
    Given Git command "remote -v" returns:
    """
      origin  git@github.com:technodelight/jira.git (fetch)
      origin  git@github.com:technodelight/jira.git (push)
    """
    When I run the application with the following input:
      | command | dashboard  |
      | date    | 2017-02-23 |
    Then the exit code should be "0"
    And the output should contain "You have been working on 1 issue on 2017-02-23"
    And the output should contain "Total time logged: 1d of 1d"
