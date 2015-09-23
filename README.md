# TODO:

+ extract templating to technodelight/simplate w/ javascript, add `depends`
+ move jira client into separate class, inject it into the wrapper which will have the specific methods
+ hide branch names from list views, show branch names incl. generated when verbosity enabled
+ add verbosity handling where it makes sense (ie. show assignee if `-v`, show assignee and description if `-vv` etc)
+ add `--all` option for `in-progress` command to show what others are doing
- log time command (https://docs.atlassian.com/jira/REST/latest/#d2e2855)
- your daily worklog (worklogDate >= startOfDay() AND worklogAuthor = currentUser())
- use service container
- create a jql query builder for assembling various queries?
- add a command which reads meeting ticket IDs from config, adds separate commands for each (ie. `standup=PROJ-321`, then `jira standup 15m`)
- add a command which reads transitions from config, adds separate commands for each (ie. `pick="Picked up by dev", oops="Oops", then `jira oops PROJ-123` will do the trick)
- handle multiple projects at once

    [projects]
    project=PROJ1
    project=PROJ2


- add cli autocomplete
- create `hub` tool helper class?
- pull-request command using hub, with interactive work log input (offer something based on the update date when dev picked up the task?)
x preconfigure PR message for `hub` with issue id + summary + commit messages differing from develop (`git log develop..head --format=%s --no-merges`)

# Resources
- https://confluence.atlassian.com/jiracloud/advanced-searching-735937166.html
- https://docs.atlassian.com/jira/REST/latest/
- https://hub.github.com/
