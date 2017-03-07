<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Fixture\JiraFixtureClient;

class JiraContext implements Context
{
    /**
     * @Given jira responds to :method url :url with:
     */
    public function jiraRespondsToUrlWith($method, $url, PyStringNode $string)
    {
        JiraFixtureClient::setup($method, $url, json_decode(trim($string->getRaw()), true));
    }

}