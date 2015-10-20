# TODO:

+ extract templating to technodelight/simplate w/ javascript, add `depends`
+ move jira client into separate class, inject it into the wrapper which will have the specific methods
+ hide branch names from list views, show branch names incl. generated when verbosity enabled
+ add verbosity handling where it makes sense (ie. show assignee if `-v`, show assignee and description if `-vv` etc)
+ add `--all` option for `in-progress` command to show what others are doing
+ log time command (https://docs.atlassian.com/jira/REST/latest/#d2e2855)
+ interactive work log input (offer commit messages differing from develop (`git log develop..head --format=%s --no-merges`))
+ your daily/weekly worklog
+ add transitions configuration (`[transitions]` where `command="Transition"` like `pick="Picked up by dev"`)
+ refactor `PickupIssueCommand` to accept transition command and transition name from above config
+ add "story: PROJ-321 (https://sub.jira.domain/browse/PROJ-321)" info for every issues if available
+ search result renderer sort by stories
+ add default verbosity as 3 for in progress issues to show worklogs/comments
+ limit displaying previous worklogs for the recent 10
+ fix fatal error unexpected value exception not found
+ tabulate issue list better
+ display comments too when verbosity is very verbose
+ add filter options to todo (--stories --bugs --tasks --filter "search term")
+ fix weekly dashboard
+ render weekly dashboard similar as in Jira
+ fix dashboard to order columns by dates increment, add day name
+ shorten in-progress --all view
+ fix worklog command output to filter empty rows
- create `hub` tool helper class, which could return open PRs associated with an issue
- add `--groupby=<field>` for in progress issues (might be cool to have it for todo as well?)
- add `--status=<issueType>` for todo command (which defaults to `Open`). One can list `Dev to check` issues if wants to review code
- worklog command should be interactive by default, remove `-i` option
- ability to add worklog to given day
- default to parsed issueKey from git branch for every command where an `issueKey` is required
- add `show` command to render a given issue, regardless of it's state
? idea: worklog issue autocomplete based on this weeks time summary details, desc ordered by missing time
? idea: edit worklog details (`jira log PROJ-321 --edit` for interactive worklog edit)
- add a walker-like implementation for iterating through search results (https://github.com/chobie/jira-api-restclient/blob/master/src/Jira/Issues/Walker.php)
- add `init` command, which guides the user throughout the initial/per project setup
- refactor time spent summary collector logic to it's own class
- render colors from jira description/comments `{color:red}something{/color}`
- add proper error handling if no configuration found
- aliasable tickets configuration (`[issue-aliases]` config section, accepts alias=issueKey configs like 'standup=PROJ-123')
- add cli autocomplete to commands ie. `jira pick PROJ-<tab` (check if `/transitions` returns the initial state of an issue (ie. `Open`) and filter issues based on this initial state)
? idea: refactor helpers to benefit from symfony built-in helper solutions, therefore it will be available through `getHelper`
- refactor commands to extract business logic into separate action classes
- refactor to use service container
- reduce build size and time: rework build process to exclude non-php/tests files from vendor
- add progress bar to in progress issues (original estimate vs time spent)
- handle multiple projects at once, change `project` arguments to receive multiple projects separated by comma

```
    [projects]
    project=PROJ1
    project=PROJ2
```

? idea: create a jql query builder for assembling various queries?
x preconfigure PR message for `hub` with issue id + summary + commit messages differing from develop (`git log develop..head --format=%s --no-merges`)

# Resources
- https://confluence.atlassian.com/jiracloud/advanced-searching-735937166.html
- https://docs.atlassian.com/jira/REST/latest/
- https://hub.github.com/
- https://github.com/chobie/jira-api-restclient/blob/master/src/Jira/Issues/Walker.php
