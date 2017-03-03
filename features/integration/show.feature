Feature: An issue could be rendered

Scenario: I want to see the details of an issue
  Given GitHub returns "issues" fixture for "get" path "repos/technodelight/jira/issues"
  And Git command "remote -v" returns:
  """
    origin  git@github.com:technodelight/jira.git (fetch)
    origin  git@github.com:technodelight/jira.git (push)
  """
  When I run the application with the following input:
  | command     | show       |
  | issueKey    | GEN-359    |
  Then the exit code should be "0"
