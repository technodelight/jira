# JIRA in command line

This is a proof-of-concept state command line application which allows you to do JIRA actions and helps your daily workflow.
In short, what it does:
- do any kind of transitions for an issue, defined by your custom aliases (`pick="Picked up by Dev"`)
- create static search queries and recall them by using your custom alias (`to-qa='project = PROJ and status = "QA Approved/Pending Deploy to UAT"'`)
- search issues on-demand by using `jira search`
- log work against an issue (`jira log PROJ-123 1h "worklog comment" yesterday`)
- list your daily/weekly worklog in a nice manner (`jira dashboard` and `jira dashboard -w`)
- list your / your team's work in progress issues (`jira in-progress` with `-a` to show your team's progress)
- render issue details in terminal (`jira show` with optional issue ID)
- open your issue by issue ID in your default browser (`jira browse`)

And other powerful features such as:
- guess issue ID from your current GIT branch (works with all the commands where an issue key is required!)
- list commit messages as hint when you log your time interactively (by typing `jira log` on issue branch)
- auto-generate branch name from issue summary, auto-checkout to branch (by specifying `-b` option, ie. `jira pick PROJ-123 -b`)
- change assignee on transition (`-a` to assign it to you, `-u` to unassign)
- show branches and open pull requests for issue (you need github's `hub` tool available for PR list)
- refer to issues by aliases (`meeting=GEN-123` where GEN-123 is a general ticket you use for meetings)

Please bear in mind *this app is still in development phase* and it may contain bugs, although it's pretty much stable most of the time.

# Installation
1. By downloading latest release
Check out the releases tab and download the latest `jira.phar`.
Or, build your own using `phar-composer`:
```
phar-composer.phar build technodelight/jira
```
Then place it to somewhere, make it executable:
```
chmod +x /path/to/downloads/jira.phar
mv /path/to/downloads/jira.phar /path/to/somewhere/jira
```
You just need to add the above path to your bash profile (or simply move it to somewhere which is already in `$PATH`:
```
PATH=~/path/to/somewhere:$PATH
```
(This step varies across different shells, please refer to the respective manuals)


# Resources
- https://confluence.atlassian.com/jiracloud/advanced-searching-735937166.html
- https://docs.atlassian.com/jira/REST/latest/
- https://hub.github.com/
- https://github.com/secondtruth/php-phar-compiler
- https://github.com/box-project/box2
- https://github.com/box-project/amend
- http://symfony.com/doc/current/components/dependency_injection/introduction.html
