Feature: Tempo can be used to retrieve worklogs

  Scenario: Tempo gets worklogs for a range
    Given Tempo is enabled
    And Git command "remote -v" returns:
    """
      origin  git@github.com:technodelight/jira.git (fetch)
      origin  git@github.com:technodelight/jira.git (push)
    """
    And Tempo responds to "GET" "/worklogs?dateFrom=2017-08-23&dateTo=2017-08-23" with "worklogs"
    When I run the application with the following input:
      | command | show:today |
      | date    | 2017-08-23 |
    Then the exit code should be "0"
    And Tempo should have been called with "GET" "/worklogs?dateFrom=2017-08-23&dateTo=2017-08-23"
