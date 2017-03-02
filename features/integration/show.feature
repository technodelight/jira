Feature: An issue could be rendered

Scenario: I want to see the details of an issue
  When I run the application with the following input:
  | command     | show       |
  | issueKey    | GEN-359    |
  Then the exit code should be "0"
