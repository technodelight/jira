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

    public function issues($state = 'all')
    {
        if (!$this->owner || !$this->repo) {
            return [];
        }
        if (!isset($this->issuesCache)) {
            $result = $this->hub->issue()->all($this->owner, $this->repo, array('state' => $state));
            $this->issuesCache = $result;
        }

        return $this->issuesCache;
    }

    public function prCommits($number)
    {
        if ($this->owner && $this->repo) {
            return $this->hub->api('pr')->commits($this->owner, $this->repo, $number);
        }
        return [];
    }

    public function statusCombined($ref)
    {
        return $this->hub->api('repo')->statuses()->combined($this->owner, $this->repo, $ref);
    }

    private function setupOwnerAndRepo()
    {
        try {
            foreach ($this->git->remotes(true) as $remote => $types) {
                if (isset($types['push'])) {
                    $this->owner = $types['push']['owner'];
                    $this->repo = $types['push']['repo'];
                    break;
                }
            }
        } catch (\Exception $e) {

        }
    }
}
