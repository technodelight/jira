Feature: Time worked on could be managed

  Scenario: log new time to a ticket
    Given jira responds to "post" url "issue/GEN-359/worklog?adjustEstimate=auto" with:
    """
    {"id":123456, "comment": "comment", "timeSpent": "1d", "timeSpentSeconds": "27000", "started": "2017-03-06T20:30:40.000+0000", "author":{"key": "zgal", "name": "zgal", "displayName": "Zsolt Gal", "emailAddress":"zenc@fixture.jira.phar", "avatarUrls": [], "active": true, "timeZone": ""}}
    """
    When I run the application with the following input:
      | command     | log        |
      | issueKey    | GEN-359    |
      | time        | 1d         |
      | comment     | worked on  |
      | date        | yesterday  |
    Then the exit code should be "0"