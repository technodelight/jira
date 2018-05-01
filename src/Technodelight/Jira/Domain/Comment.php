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

    public function __construct($id, $author, $body, $created, $updated)
    {
        $this->id = $id;
        $this->author = $author;
        $this->body = $body;
        $this->created = $created;
        $this->updated = $updated;
    }

    public static function fromArray(array $jiraRecord)
    {
        return new self(
            $jiraRecord['id'],
            $jiraRecord['author'],
            $jiraRecord['body'],
            $jiraRecord['created'],
            $jiraRecord['updated']
        );
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

    public function created()
    {
        return \DateTime::createFromFormat(DateHelper::FORMAT_FROM_JIRA, $this->created);
    }

    public function updated()
    {
        return \DateTime::createFromFormat(DateHelper::FORMAT_FROM_JIRA, $this->updated);
    }
}
