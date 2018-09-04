<?php

namespace Technodelight\Jira\Api\JiraTagConverter\Components;

class Chunk
{
    /**
     * @var string
     */
    private $rawContent;
    /**
     * @var string
     */
    private $content;

    public function __construct($rawContent, $start = null, $length = null)
    {
        $this->rawContent = $rawContent;
        $this->content = $this->parse($rawContent);
    }

    public function isParseable()
    {
        return true;
    }

    public function content()
    {
        return $this->content;
    }

    public function rawContent()
    {
        return $this->rawContent;
    }

    /**
     * @param string $rawContent
     * @return string
     */
    private function parse($rawContent)
    {
        return $rawContent;
    }
}
