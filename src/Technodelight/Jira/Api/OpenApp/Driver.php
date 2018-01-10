<?php

namespace Technodelight\Jira\Api\OpenApp;

interface Driver
{
    /**
     * @param string $uri
     * @throws Exception
     * @return void
     */
    public function open($uri);
}
