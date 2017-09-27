<?php

namespace Technodelight\Jira\Helper;

use GitHub\Client as Hub;
use Technodelight\Jira\Api\GitShell\Api as Git;

class HubHelper
{
    private $git;
    private $hub;
    private $owner;
    private $repo;

    public function __construct(Git $git, Hub $hub)
    {
        $this->git = $git;
        $this->hub = $hub;
        $this->setupOwnerAndRepo();
    }

    public function getName()
    {
        return 'hub';
    }

    public function issues()
    {
        if (!isset($this->issuesCache)) {
            $result = $this->hub->api('issue')->all($this->owner, $this->repo, array('state' => 'all'));
            $this->issuesCache = $result;
        }

        return $this->issuesCache;
    }

    public function prCommits($number)
    {
        return $this->hub->api('pr')->commits($this->owner, $this->repo, $number);
    }

    public function statusCombined($ref)
    {
        return $this->hub->api('repo')->statuses()->combined($this->owner, $this->repo, $ref);
    }

    private function setupOwnerAndRepo()
    {
        foreach ($this->git->remotes(true) as $remote => $types) {
            if (isset($types['push'])) {
                $this->owner = $types['push']['owner'];
                $this->repo = $types['push']['repo'];
                break;
            }
        }
    }
}
