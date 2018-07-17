<?php

namespace Technodelight\Jira\Api\JiraRestApi\HttpClient;

interface Config
{
    public function username();

    public function password();

    public function domain();
}
