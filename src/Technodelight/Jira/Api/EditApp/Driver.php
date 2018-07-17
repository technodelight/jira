<?php

namespace Technodelight\Jira\Api\EditApp;

interface Driver
{
    /**
     * @param string $title
     * @param string $content
     * @return string
     */
    public function edit($title, $content);
}
