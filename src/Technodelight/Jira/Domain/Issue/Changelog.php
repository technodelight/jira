<?php

namespace Technodelight\Jira\Domain\Issue;

use DateTime;
use Technodelight\Jira\Domain\Issue\Changelog\Item;
use Technodelight\Jira\Domain\User;
use Technodelight\Jira\Helper\DateHelper;

class Changelog
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $issueKey;
    /**
     * @var string
     */
    private $created;
    /**
     * @var array
     */
    private $author;
    /**
     * @var array
     */
    private $items = [];

    public static function fromArray(array $changeLog, $issueKey)
    {
        $instance = new self;
        $instance->id = $changeLog['id'];
        $instance->issueKey = $issueKey;
        $instance->created = $changeLog['created'];
        $instance->author = $changeLog['author'];
        $instance->items = isset($changeLog['items']) ? $changeLog['items'] : [];

        return $instance;
    }

    /**
     * @return int
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function issueKey()
    {
        return $this->issueKey;
    }

    /**
     * @return DateTime
     */
    public function created()
    {
        return DateTime::createFromFormat(DateHelper::FORMAT_FROM_JIRA, $this->created);
    }

    /**
     * @return User
     */
    public function author()
    {
        return User::fromArray($this->author);
    }

    /**
     * @return Item[]
     */
    public function items()
    {
        return array_map(function ($item) {
            return Item::fromArray($item);
        }, $this->items);
    }
}
