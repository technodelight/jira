Feature: Time worked on could be managed

  Scenario: log new time to a ticket
    Given jira responds to "post" url "issue/GEN-359/worklog?adjustEstimate=auto" with:
    """
    {"id":123456, "comment": "comment", "timeSpent": "1d", "timeSpentSeconds": "27000", "started": "2017-03-06T20:30:40.000+0000", "author":{"key": "zgal", "name": "zgal", "displayName": "Zsolt Gal", "emailAddress":"zenc@fixture.jira.phar", "avatarUrls": [], "active": true, "timeZone": ""}}
    """
    When I run the application with the following input:
      | command             | log       |
      | issueKeyOrWorklogId | GEN-359   |
      | time                | 1d        |
      | comment             | worked on |
      | date                | yesterday |
    Then the exit code should be "0"

  Scenario: update existing worklog
    Given jira responds to "post" url "worklog/list?expand=properties" with:
    """
    [{"self":"https://fixture.jira.phar/rest/api/2/issue/140265/worklog/427026","author":{"self":"https://fixture.jira.phar/rest/api/2/user?username=zgal","name":"zgal","key":"zgal","emailAddress":"zgal@fixture.jira.phar","avatarUrls":[],"displayName":"Zsolt Gal","active":true,"timeZone":"Europe/Budapest"},"updateAuthor":{"self":"https://jira.fixture.phar/rest/api/2/user?username=zgal","name":"zgal","key":"zgal","emailAddress":"zgal@inviqa.com","avatarUrls":{"48x48":"https://jira.fixture.phar/secure/useravatar?ownerId=zgal&avatarId=21500","24x24":"https://jira.fixture.phar/secure/useravatar?size=small&ownerId=zgal&avatarId=21500","16x16":"https://jira.fixture.phar/secure/useravatar?size=xsmall&ownerId=zgal&avatarId=21500","32x32":"https://jira.fixture.phar/secure/useravatar?size=medium&ownerId=zgal&avatarId=21500"},"displayName":"Zsolt Gal","active":true,"timeZone":"Europe/Budapest"},"comment":"Worked on issue PROJ-253","created":"2017-03-06T15:15:56.288+0000","updated":"2017-03-06T23:15:36.059+0000","started":"2017-03-06T15:15:55.000+0000","timeSpent":"1d","timeSpentSeconds":27000,"id":"427026","issueId":"140265","properties":[]}]
    """
    And jira responds to "put" url "issue/140265/worklog/427026?adjustEstimate=auto" with:
    """
    {"id":123456, "issueId": "140265", "comment": "comment", "timeSpent": "1d", "timeSpentSeconds": "27000", "started": "2017-03-06T20:30:40.000+0000", "author":{"key": "zgal", "name": "zgal", "displayName": "Zsolt Gal", "emailAddress":"zenc@fixture.jira.phar", "avatarUrls": [], "active": true, "timeZone": ""}}
    """
    When I run the application with the following input:
      | command             | log       |
      | issueKeyOrWorklogId | 427026    |
      | time                | 1d        |
      | comment             | worked on |
      | date                | yesterday |
    Then the exit code should be "0"
