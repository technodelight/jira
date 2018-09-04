Feature: An issue could be rendered

Scenario: I want to see the details of an issue
  Given GitHub returns "issues" fixture for "get" path "/repos/technodelight/jira/issues"
  And Git command "remote -v 2> /dev/null" returns:
  """
    origin  git@github.com:technodelight/jira.git (fetch)
    origin  git@github.com:technodelight/jira.git (push)
  """
  And Git command "branch -a | grep 'GEN-359'" returns:
  """
    feature/GEN-359-something
  """
  And Git command "branch -a | grep 'feature/GEN-359-training-self-study'" returns:
  """
  """
  When I run the application with the following input:
  | command     | show       |
  | issueKey    | GEN-359    |
  Then the exit code should be "0"
  And the output should contain "parent"
  And the output should contain "branches"
  And the output should contain "worklogs"
