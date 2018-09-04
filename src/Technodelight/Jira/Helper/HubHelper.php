<?php

namespace Technodelight\Jira\Helper;

use GitHub\Client as Hub;
use Technodelight\GitShell\Api as Git;
use Technodelight\GitShell\Remote;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\GitHubConfiguration;

class HubHelper
{
    /**
     * @var Git
     */
    private $git;
    /**
     * @var Hub
     */
    private $hub;
    /**
     * @var string
     */
    private $owner;
    /**
     * @var string
     */
    private $repo;
    /**
     * @var GitHubConfiguration
     */
    private $configuration;
    /**
     * @var array
     */
    private $issuesCache = [];

    public function __construct(Git $git, Hub $hub, GitHubConfiguration $configuration)
    {
        $this->git = $git;
        $this->hub = $hub;
        $this->configuration = $configuration;
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
        if (!isset($this->issuesCache[$state])) {
            $result = $this->hub->issue()->all($this->owner, $this->repo, array('state' => $state));
            $this->issuesCache[$state] = $result;
        }

        return $this->issuesCache[$state];
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

    /**
     * @param string $title
     * @param string $body
     * @param string $base
     * @param string $branch
     * @throws \Github\Exception\MissingArgumentException
     */
    public function createPr($title, $body, $base, $branch)
    {
        return $this->hub->pullRequest()->create(
            $this->hub->currentUser()->show()['login'],
            $this->owner . '/' . $this->repo,
            [
                'title' => $title,
                'body' => $body,
                'base' => $base,
                'head' => $branch,
            ]
        );
    }

    /**
     * @param string $head
     * @param string $state
     * @return array
     */
    public function prForHead($head, $state = 'all')
    {
        return $this->hub->pullRequests()->all(
            $this->hub->currentUser()->show()['login'],
            $this->owner . '/' . $this->repo,
            [
                'state' => $state,
                'head' => $head
            ]
        );
    }

    /**
     * @return \Github\Api\Issue\Labels
     */
    public function labels()
    {
        return $this->hub->issues()->labels();
    }
    
    private function setupOwnerAndRepo()
    {
        if (!empty($this->configuration->owner()) && !empty($this->configuration->repo())) {
            $this->owner = $this->configuration->owner();
            $this->repo = $this->configuration->repo();
            return;
        }

        try {
            foreach (array_reverse($this->git->remotes(true)) as $remote) {
                /** @var Remote $remote */
                if ($remote->type() == 'push') {
                    $this->owner = $remote->owner();
                    $this->repo = $remote->repo();
                    break;
                }
            }
        } catch (\Exception $e) {

        }
    }
}
