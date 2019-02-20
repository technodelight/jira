<?php

namespace Technodelight\Jira\Console\Input\PullRequest;

class PullRequest
{
    /**
     * @var string
     */
    private $title;
    /**
     * @var string
     */
    private $body;
    /**
     * @var array
     */
    private $labels;
    /**
     * @var string|null
     */
    private $milestone;
    /**
     * @var array
     */
    private $assignees;

    /**
     * OutputParser constructor.
     *
     * @param string $output
     */
    public function __construct($title, $body, array $labels = [], $milestone = null, array $assignees = [])
    {
        $this->title = $title;
        $this->body = $body;
        $this->labels = $labels;
        $this->milestone = $milestone;
        $this->assignees = $assignees;
    }

    public function title()
    {
        return $this->title;
    }

    public function body()
    {
        return $this->body;
    }

    public function labels()
    {
        return $this->labels;
    }

    public function milestone()
    {
        return $this->milestone;
    }

    public function assignees()
    {
        return $this->assignees;
    }
}
