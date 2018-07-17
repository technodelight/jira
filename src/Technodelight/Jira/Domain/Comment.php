<?php

namespace Technodelight\Jira\Domain;

use Technodelight\Jira\Helper\DateHelper;

class Comment
{
    private $id;
    private $author;
    private $body;
    private $created;
    private $updated;
    private $visibility;

    public static function fromArray(array $jiraRecord)
    {
        $instance = new self;
        $instance->id = $jiraRecord['id'];
        $instance->author = $jiraRecord['author'];
        $instance->body = $jiraRecord['body'];
        $instance->created = $jiraRecord['created'];
        $instance->updated = $jiraRecord['updated'];
        $instance->visibility = isset($jiraRecord['visibility']) ? $jiraRecord['visibility'] : [];

        return $instance;
    }

    public function id()
    {
        return $this->id;
    }

    public function body()
    {
        return $this->body;
    }

    public function author()
    {
        return User::fromArray($this->author);
    }

    public function visibility()
    {
        if (!empty($this->visibility)) {
            return $this->visibility['value'];
        }
        return '';
    }

    public function created()
    {
        return \DateTime::createFromFormat(DateHelper::FORMAT_FROM_JIRA, $this->created);
    }

    public function updated()
    {
        return \DateTime::createFromFormat(DateHelper::FORMAT_FROM_JIRA, $this->updated);
    }
}
