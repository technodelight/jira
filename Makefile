#
# A simple makefile for compiling three java classes
#

# define a makefile variable for the java compiler
#
BUILDER = ./build
VERSION = $(shell git describe --abbrev=0 --tags)
PHAR_FILE = jira.phar
INSTALL_DEST = /usr/local/bin/jira

# typing 'make' will invoke the first target entry in the makefile
# (the default one in this case)
#
default: jira.phar

# this target entry builds the Average class
#
jira.phar:
	@echo Building $(VERSION)
	$(BUILDER) $(VERSION)

install: jira.phar
	@cp $(PHAR_FILE) $(INSTALL_DEST)

release:
	git tag $(git tag --list | sort -k1V | tail -1 | awk -F. -v OFS=. 'NF==1{print ++$$NF}; NF>1{if(length($$NF+1)>length($$NF))$$(NF-1)++; $$NF=sprintf("%0*d", length($$NF), ($$NF+1)%(10^length($$NF))); print}')
push:
	git push -u && git push --tags --no-verify
# To start over from scratch, type 'make clean'.
# Removes all .phar files, so that the next make rebuilds them
#
clean:
	$(RM) $(PHAR_FILE)

