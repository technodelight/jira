#!/bin/sh
echo "Build composer.phar, make it executable and move it to /usr/local/bin/jira, copy builded phar to dropbox"
phar-composer build && mv jira.phar /usr/local/bin/jira && chmod +x /usr/local/bin/jira && cp /usr/local/bin/jira ~/Dropbox/jira.phar
