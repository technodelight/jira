#!/bin/sh

phar-composer build && mv jira.phar /usr/local/bin/jira && chmod u+x /usr/local/bin/jira
