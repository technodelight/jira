<?php

namespace Technodelight\Jira\Domain;

use Technodelight\Jira\Domain\Project\ProjectId;
use Technodelight\Jira\Domain\Project\Version;

class Project
{
    private $id;
    private $key;
    private $name;
    private $projectTypeKey;
    private $versions;
    private $lead;
    private $components;
    private $issueTypes;
    private $description;

    private function __construct()
    {
    }

    public static function fromArray(array $project)
    {
        $instance = new self;
        $instance->id = ProjectId::fromString($project['id']);
        $instance->key = $project['key'];
        $instance->name = $project['name'];
        $instance->projectTypeKey = isset($project['projectTypeKey']) ? $project['projectTypeKey'] : null;
        $instance->versions = isset($project['versions']) ? $project['versions'] : [];
        $instance->lead = isset($project['lead']) ? User::fromArray($project['lead']) : null;
        $instance->components = isset($project['components']) ? $project['components'] : [];
        $instance->issueTypes = isset($project['issueTypes']) ? $project['issueTypes'] : [];
        $instance->description = isset($project['description']) ? $project['description'] : '';

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
    public function key()
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function projectTypeKey()
    {
        return $this->projectTypeKey;
    }

    /**
     * @return Version[]
     */
    public function versions()
    {
        return array_map(
            function (array $version) {
                return Version::fromArray($version);
            },
            $this->versions
        );
    }

    /**
     * @return User
     */
    public function lead()
    {
        return $this->lead;
    }

    /**
     * @return array
     */
    public function components()
    {
        return $this->components;
    }

    /**
     * @return array
     */
    public function issueTypes()
    {
        return $this->issueTypes;
    }

    /**
     * @return string
     */
    public function description()
    {
        return $this->description;
    }
}
