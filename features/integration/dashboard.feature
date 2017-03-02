Feature: The dashboard could be rendered

Scenario: I have worked today
  When I run the application with the following input:
  | command | dashboard  |
  | date    | 2017-02-23 |
  Then the exit code should be "0"
