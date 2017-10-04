Feature: I can search using JQL

  Background:
    Given GitHub returns "issues" fixture for "get" path "repos/technodelight/jira/issues"
    Given Git command "remote -v" returns:
    """
      origin  git@github.com:technodelight/jira.git (fetch)
      origin  git@github.com:technodelight/jira.git (push)
    """

  Scenario: I can do a standard search using the search command
    When I run the application with the following input:
      | command | search                                                                                 |
      | jql     | worklogDate >= "2017-02-23" AND worklogDate <= "2017-02-23" AND worklogAuthor = "zgal" |
    Then the exit code should be "0"

  Scenario: I can save a search
    When I run the application with the following input:
      | command       | search                                                                                 |
      | jql           | worklogDate >= "2017-02-23" AND worklogDate <= "2017-02-23" AND worklogAuthor = "zgal" |
      | --dump-config | test-filter AFdsaDsdfsd                                                                |
    Then the exit code should be "0"
