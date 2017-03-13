Feature: I can search using JQL

  Scenario: I can do a standard search using the search command
    Given Git command "remote -v" returns:
  """
    origin  git@github.com:technodelight/jira.git (fetch)
    origin  git@github.com:technodelight/jira.git (push)
  """
    When I run the application with the following input:
      | command | search                                                                                 |
      | jql     | worklogDate >= "2017-02-23" AND worklogDate <= "2017-02-23" AND worklogAuthor = "zgal" |
    Then the exit code should be "0"

  Scenario: I can save a search
    Given Git command "remote -v" returns:
  """
    origin  git@github.com:technodelight/jira.git (fetch)
    origin  git@github.com:technodelight/jira.git (push)
  """
    When I run the application with the following input:
      | command | search                                                                                 |
      | jql     | worklogDate >= "2017-02-23" AND worklogDate <= "2017-02-23" AND worklogAuthor = "zgal" |
      | --save  | test-filter AFdsaDsdfsd                                                                |
    Then the exit code should be "0"
