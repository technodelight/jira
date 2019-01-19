<?php

namespace Technodelight\Jira\Console\Input\PullRequest;

class PullRequest
{
    private $title;
    private $body;
    /**
     * @var array
     */
    private $labels;

    /**
     * OutputParser constructor.
     *
     * @param string $output
     */
    public function __construct($title, $body, array $labels = [])
    {
        $this->title = $title;
        $this->body = $body;
        $this->labels = $labels;
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
}
