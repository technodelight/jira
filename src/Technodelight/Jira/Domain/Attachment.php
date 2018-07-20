<?php

namespace Technodelight\Jira\Domain;

use Technodelight\Jira\Helper\DateHelper;

class Attachment
{
    private $attachment = [];

    /**
     * @var Issue
     */
    private $issue;

    private function __construct()
    {
    }

    public static function fromArray(array $attachment, Issue $issue)
    {
        $instance = new Attachment();
        $instance->attachment = $attachment;
        $instance->issue = $issue;

        return $instance;
    }

    /**
     * @return string
     */
    public function id()
    {
        return $this->attachment['id'];
    }

    /**
     * @return string
     */
    public function author()
    {
        if (isset($this->attachment['author']['displayName'])) {
            return $this->attachment['author']['displayName'];
        }
        return '';
    }

    /**
     * @return \DateTime
     */
    public function created()
    {
        return \DateTime::createFromFormat(DateHelper::FORMAT_FROM_JIRA, $this->attachment['created']);
    }

    /**
     * @return int
     */
    public function size()
    {
        return $this->attachment['size'];
    }

    /**
     * @return string
     */
    public function filename()
    {
        return $this->attachment['filename'];
    }

    /**
     * @return string
     */
    public function url()
    {
        return $this->attachment['content'];
    }

    /**
     * @return Issue
     */
    public function issue()
    {
        return $this->issue;
    }
}
