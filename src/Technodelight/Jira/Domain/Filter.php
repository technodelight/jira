<?php

namespace Technodelight\Jira\Domain;

use Technodelight\Jira\Domain\Filter\FilterId;

final class Filter
{
    /**
     * @var array
     */
    private $filter = [];

    public static function fromArray(array $filter)
    {
        $instance = new self;
        $instance->filter = $filter;
        return $instance;
    }

    private function __construct()
    {
    }

    /**
     * @return FilterId
     */
    public function id()
    {
        return FilterId::fromString($this->filter['id']);
    }

    /**
     * @return bool
     */
    public function isFavourite()
    {
        return !empty($this->filter['favourite']);
    }

    /**
     * @return string
     */
    public function jql()
    {
        return $this->filter['jql'];
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->filter['name'];
    }

    /**
     * @return string
     */
    public function description()
    {
        return !empty($this->filter['description']) ? $this->filter['description'] : '';
    }

    /**
     * @return User
     */
    public function owner()
    {
        return User::fromArray($this->filter['owner']);
    }

    /**
     * @return int
     */
    public function favouritedCount()
    {
        return !empty($this->filter['favouritedCount']) ? (int) $this->filter['favouritedCount'] : 0;
    }
}
