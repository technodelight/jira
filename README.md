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
+ create `hub` tool helper class, which could return open PRs associated with an issue
+ worklog command should be interactive by default, remove `-i` option
+ default to parsed issueKey from git branch for every command where an `issueKey` is required
+ fix todo list to be non-verbose (somehow it went verbose and shows more than 2 lines of `description`)
+ when transitioning, show generated branch name for starting on a task quicker
+ refactor to use service container (http://symfony.com/doc/current/components/dependency_injection/introduction.html)
+ ability to add worklog to given day
- fix query builder condition, to pass array and generate value with `join('","')` instead of doing this in the builder model itself
- improve branch name generator to shorten feature branch name
- change `todo` command to `list-issues` command, should be configurable like the transitions (`todo=Open`, `toqa="Ready to QA"`)
  It would be better to have a query associated to a task, `todo="sprint in openSprints()..."` https://github.com/sirprize/queried to assemble where parts
- add `show` command to render a given issue, regardless of it's state
- add `work` command which shows `yesterday`s work logs (issue: at time: - message) grouped by worklog authors, OR introduce `groupby` first
- refactor time spent summary collector logic to it's own class
- render worklog link after added to the issue, probably list every link per worklog in the worklog history renderer
- reduce build size and time: rework build process to exclude non-php/tests files from vendor https://github.com/secondtruth/php-phar-compiler
  collect paths from packages under `autoload->exclude_*`, use `composer install --no-dev` -------> `.box.json` ?
- add `--groupby=<field>` for issue lists
- `git log --format="<hash><![CDATA[%H]]></hash><message><![CDATA[%B]]></message>" develop..head` should show your commits differing from develop,
  would be helpful for the worklog message -> *will working only if you're on the correct feature branch*
  But, may be better to retrieve branch name using the git helper instead of using `head`
  TODO: find Parent branch, find feature branch and use ie. `log <...> develop..feature/PROJ-321-something`
- parse remote branches for tasks, modify branchname generator to base on remotes first
- make the default worklog message to the "main" message section parsed from commit messages and render them in the WL comment as unordered list (`- parsed commit message`)
- add proper error handling if no configuration found, trigger `init` command
- add `init` command, which guides the user throughout the initial/per project setup
- aliasable tickets configuration (`[issue-aliases]` config section, accepts alias=issueKey configs like 'standup=PROJ-123')
- render/handle colors from jira description/comments `{color:red}something{/color}`
- add cli autocomplete to commands ie. `jira pick PROJ-<tab` (check if `/transitions` returns the initial state of an issue (ie. `Open`) and filter issues based on this initial state)
- refactor commands to extract business logic into separate action classes
- refactor to use `symfony/config` to load configuration files as allows more flexibility
- add progress bar to in progress issues (original estimate vs time spent)
- show last update time per issue using `php-time-ago`
- handle multiple projects at once, change `project` arguments to receive multiple projects separated by comma

```
    [projects]
    project=PROJ1
    project=PROJ2
```
# Ideas:

- idea: edit worklog details (`jira log PROJ-321 --edit` which should be interactive)
- idea: add a walker-like implementation for iterating through search results (https://github.com/chobie/jira-api-restclient/blob/master/src/Jira/Issues/Walker.php)
- idea: worklog issue autocomplete based on this weeks time summary details, desc ordered by missing time
+ idea: create a jql query builder for assembling various queries?
- idea: possibly create a command, to generate branch name with autocomplete based on split words from issue summary, like `jira branch PROJ-321 add<tab> -> jira branch PROJ-321 additional-`
- idea: contractor helper command to help generating invoices at the end of month, like total workdays, official holidays, personal holidays, unpaid time?

# Resources
- https://confluence.atlassian.com/jiracloud/advanced-searching-735937166.html
- https://docs.atlassian.com/jira/REST/latest/
- https://hub.github.com/
- https://github.com/chobie/jira-api-restclient/blob/master/src/Jira/Issues/Walker.php
- https://github.com/sirprize/queried
- https://github.com/secondtruth/php-phar-compiler
- http://symfony.com/doc/current/components/dependency_injection/introduction.html
