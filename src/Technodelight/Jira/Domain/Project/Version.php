<?php

namespace Technodelight\Jira\Domain\Project;

class Version
{
    private $id;
    private $name;
    private $isReleased;
    private $releaseDate;
    private $description;
    private $isArchived;

    private function __construct()
    {
    }

    public static function fromArray(array $version)
    {
        $instance = new self;
        $instance->name = $version['name'];
        $instance->isReleased = $version['released'];
        $instance->releaseDate = isset($version['releaseDate']) ? $version['releaseDate'] : null;
        $instance->description = isset($version['description']) ? $version['description'] : '';
        $instance->id = $version['id'];
        $instance->isArchived = $version['archived'];
        return $instance;
    }

    public function id()
    {
        return $this->id;
    }
    public function name()
    {
        return $this->name;
    }
    public function isReleased()
    {
        return $this->isReleased;
    }
    public function releaseDate()
    {
        return new \DateTime($this->releaseDate);
    }
    public function description()
    {
        return $this->description;
    }
    public function isArchived()
    {
        return $this->isArchived;
    }
}
