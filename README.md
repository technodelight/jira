![logo](./docs/logo.png)

# JIRA in command line

[![Build Status](https://travis-ci.org/technodelight/jira.svg?branch=master)](https://travis-ci.org/technodelight/jira)

Do you use JIRA in your daily work a lot? Are you a developer? Then this tool is for _you_!

This command line application helps you managing issue transitions, searches using JQL and assists time logging based on your recent git commit messages, with ease.
All these comes in a highly customisable manner and with many useful extras.

This tool was evolved into it's current state over the years I've spent with development on my largest side project. I wanted to create a tool I can quickly and effectively use, without having to grab my mouse. It spares a lot of time with all the micro management an issue needs. 

# Quickstart guide

### 1. Get the phar

  Check out the [github releases](https://github.com/technodelight/jira/releases) and download the latest `jira.phar`.

### 2. Make it executable and move to somewhere in your PATH:

  ```
  chmod +x /path/to/jira.phar
  #YMMV, please check your OS's guide for further reference
  mv /path/to/downloads/jira.phar /usr/local/bin/jira
  ```

### 3. CD into your project's git-managed directory and init the configuration:

  ```
  jira init
  ``` 
  Just follow the steps and you should be good to go.
  
### 4. Updating your tool

  Once you've installed `jira` you can perform updates by running `jira self-update`.
  
### Alternative installation mode, A.K.A. build-your-own:

  ```
  git clone https://github.com/technodelight/jira
  git checkout master
  make && make install
  ```
  Does the very same as above, tho it copies the built file into a fixed directory of `/usr/local/bin/jira`, but you can change this by adjusting the `Makefile`

# Features

- render issue details in terminal (`jira show`)
- perform any transition (for example, `jira workflow:start`, but you can configure commands for multiple transitions)
- list available transitions for an issue (`jira show:transitions`) or all possible issue status (`jira show:statuses`)
- create a branch for the issue you're going to work (`jira branch`)
- create pull request for your current branch, assign labels and milestones (`jira pr`) 
- assign issue to your team member or yourself (`jira assign`)
- log new work/edit existing records against an issue (`jira log PROJ-123 1h "worklog comment" yesterday`)
- add and edit comments quickly (`jira comment`)
- set up and remove links between issues (`jira link` / `jira unlink`, for example `jira link --relates-to PROJ-456`)
- edit issue properties from command line (`jira edit`)
- show editable fields for an issue (`jira show:fields`)
- download issue attachments (`jira attachment`)
- search issues on-demand by using Atlassian's JQL language (`jira search '<your JQL here>'`)
- create pre-stored search queries and run them quickly by assigning a command alias (`jira search:my-issues` for example)
- list your daily/weekly/monthly work logs in a nice manner (`jira day`, `jira week` and `jira month`)
- open your issue by issue key in your default browser (`jira browse`) which works with github integration too (open your PR in GitHub or CI tool's result page right from your terminal)
- perform any command on a list of issues using `jira batch` (nice use case: `git log asdf123...123asdf | egrep '[A-Z]+-[0-9]+' -o | sort | uniq | jira batch + deployed-uat`)
- filter any list of issues using `jira batch filter + 'issue.status() != "Closed"'`
- show details about projects (`jira show:project`)

And other powerful features such as:

- time logging works with JIRA built in mechanism and with [Tempo Timesheets](https://www.tempo.io/jira-project-management-tool) too
- the tool works in ANSI 256 colors mode
- supports almost every "jira markdown" syntax, displayed console-friendly
- render images from issue description, comments, etc. (requires iTerm2 on OSX!)
- can render code blocks using external tools (`highlight` and `bat` is currently supported)
- show available local/remote branches and pull requests for an issue (you will need a github API token for this), shows CI build statuses too
- guess issue key from your current GIT branch (works with all the commands where an issue key is required!)
- use issue key aliases or alias a full branch name for an issue (nice use case: `jira log standup 15m "standup"`)
- or be lazy and just paste the full URL (`jira show https://project.atlassian.net/browse/PROJ-123` for example)  
- assemble worklog messages from commit messages, when you log your time interactively (by typing `jira log` on the issue's branch)
- auto-generate branch name from issue summary, auto-checkout to branch (by specifying `-b` option, ie. `jira start PROJ-123 -b`)
- define your own branch name generation strategies by adding rules to your configuration file
- define your custom view modes for various use cases. There are 2 different rendering "modes" currently, one for rendering single issues and one for rendering lists. You can add/remove/reorder fields, add custom fields on demand. For all available renderers and fields `jira show:fields` and `jira show:renderers` can help.
- define how much work hours do you have per day, to easily track your overtime 

Please bear in mind *this app is still in development* and it may have bugs. If you find one or you have a feature request/suggestion, please open an issue [here](https://github.com/technodelight/jira/issues).


# Tips

- **Add .jira.yml to your global gitignore:** 

  `echo .jira.yml | tee -a ~/.gitignore_global`


# Configuration reference

You can have per-project (`/your/project/dir/.jira.yml`) and global configuration (`$HOME/.jira.yml`), which would be merged into one before being used by the application.
For example if you manage multiple projects on multiple jira instances, you could add your login details into the global configuration file, whilst issue aliases, transitions, searches in your per-project config file.
Interactively initialising the configuration is as easy as running the `init` command in your project directory.
You could always dump the configuration sample from below file by running `jira init --sample`

```yaml

# JIRA connection credentials
credentials:

    # JIRA's domain without protocol, like something.atlassian.net
    domain:               ~ # Required, Example: something.atlassian.net

    # Your JIRA username
    username:             ~ # Required

    # Your JIRA password
    password:             ~ # Required

# Different JIRA instances to use
instances:

    # Prototype
    name:

        # Unique internal ID to use in command line arguments as reference (ie. --instance secondary)
        name:                 default # Example: secondary

        # JIRA's domain without protocol, like something.atlassian.net
        domain:               something.atlassian.net # Required, Example: something.atlassian.net

        # Instance JIRA username
        username:             '<your jira username>' # Required

        # Instance JIRA password
        password:             supersecretpassword # Required

        # Is tempo enabled for this instance?
        tempo:                null

# Third party integration configs
integrations:

    # GitHub credentials - used to retrieve pull request data, including webhook statuses. Visit this page to generate a token: https://github.com/settings/tokens/new?scopes=repo&description=jira+cli+tool
    github:
        apiToken:             ~ # Required
        owner:                null
        repo:                 null

    # GIT related configurations
    git:

        # Maximum branch name length where the tool starts complaining during automatic branch name generation (-b option for issue transition type commands). Defaults to 30
        maxBranchNameLength:  30

        # Branch name generation settings. By default it conforms to https://nvie.com/posts/a-successful-git-branching-model/
        branchNameGenerator:
            patterns:

                # Prototype: Branch name generation patters, depending on if issue summary matches on regex
                expression:

                    # Expression in symfony expression language format
                    expression:           ~ # Required, Example: preg_match("~^Release ~", issue.summary())

                    # Pattern to use for generation, where {issueKey}, {summary} and any expression like {clean(issue.type()} can be used
                    pattern:              ~ # Required, Example: release/{clean(substr(issue.summary(), 8))}

            # Separator to use between words
            separator:            '-'

            # Keep this set of characters only when generating branch names
            whitelist:            A-Za-z0-9./-

            # Clean this set of phrases from generated names. Can be an array or a comma separated string. Defaults to "BE,FE"
            remove:

                # Defaults:
                - BE
                - FE

            # Always convert of these chars into separator char. Can be an array of values or a single string. Defaults to " :/,"
            replace:

                # Defaults:
                -  
                - :
                - /
                - ,

            # Include these words into autocompleter when shortening branch name due to generated name exceeding max length. Can be an array or a list of words separated by comma.
            autocompleteWords:

                # Defaults:
                - fix
                - add
                - change
                - remove
                - implement

    # Tempo timesheets (https://tempo.io/doc/timesheets/api/rest/latest)
    tempo:
        enabled:              false
        version:              null
        apiToken:             null
        instances:            null # Example: secondary

    # iTerm2 integration (OS X Only)
    iterm:
        renderImages:         true
        thumbnailWidth:       300
        imageCacheTtl:        5

    # Editor preferences
    editor:
        executable:           vim

    # Use application in server/client mode. Could speed things up a bit
    daemon:
        enabled:              false

        # IP to listen on. Defaults to 0.0.0.0
        address:              0.0.0.0

        # Port to listen on. Defaults to 50200.
        port:                 '50200'

# Project specific settings
project:

    # Using 'yesterday' means last workday on monday
    yesterdayAsWeekday:   true

    # Default worklog timestamp to use if date is omitted
    defaultWorklogTimestamp: now

    # Your work hours for a single day (valid values ie. "7 hours 30 minutes", 7.5 (treated as hours), 27000 (in seconds)
    oneDay:               !!float 27000

    # keep API data in caches
    cacheTtl:             900

# Issue transitions registered as commands
transitions:

    # Prototype
    -
        command:              ~ # Required
        transition:           ~

# Use named issues instead of numbers. Can be used anywhere where issueKey is a command's input
aliases:

    # Prototype
    -
        alias:                ~ # Required
        issueKey:             ~ # Required

# Custom quick filters registered as commands. See advanced search help at https://confluence.atlassian.com/jiracorecloud/advanced-searching-765593707.html
filters:

    # Prototype
    -
        command:              ~ # Required
        jql:                  ''
        filterId:             null
        instance:             null

# Rendering setup
renderers:
    preference:

        # Default view mode for lists
        list:                 short

        # Default view mode for a single issue
        view:                 full
    modes:

        # Prototype
        name:
            name:                 ~ # Required
            inherit:              true

            # see available fields in show:renderers command
            fields:

                # Prototype
                -
                    name:                 ~ # Required
                    formatter:            default
                    inline:               false
                    after:                null
                    before:               null
                    remove:               null

    # Custom formatters
    formatters:

        # Prototype
        -

            # Alias, as it will be used in renderer configs
            name:                 ~ # Required

            # Full class path with namespace
            class:                ~ # Required

```

# Useful links

- **Register GitHub api token:** https://github.com/settings/tokens/new?scopes=repo&description=jira+cli+tool
- **JQL:** https://confluence.atlassian.com/jiracloud/advanced-searching-735937166.html
- Tempo Timesheets: https://www.tempo.io/jira-project-management-tool
- Steps on how to gather an **API token for Tempo Timesheets** (section "Using the REST API as an individual user"): https://tempo-io.github.io/tempo-api-docs/

# License

GNU GPLv3

Copyright (c) 2015-2019 Zsolt Gál
See LICENSE.

