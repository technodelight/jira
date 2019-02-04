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
     * @param string|null $milestone
     * @param array|null $labels
     * @throws \Github\Exception\MissingArgumentException
     */
    public function createPr($title, $body, $base, $branch, $milestone = null, array $labels = null)
    {
        return $this->hub->pullRequest()->create(
            $this->owner,
            $this->repo,
            array_filter([
                'title' => $title,
                'body' => $body,
                'base' => $base,
                'head' => $branch,
                'milestone' => $milestone,
                'labels' => $labels
            ])
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
            $this->owner,
            $this->repo,
            [
                'state' => $state,
                'head' => $head
            ]
        );
    }

    /**
     * @return array
     */
    public function labels()
    {
        return $this->hub->repo()->labels()->all($this->owner, $this->repo);
    }

    public function milestones()
    {
        return $this->hub->repo()->milestones($this->owner, $this->repo);
    }

    public function assignees()
    {
        return $this->hub->issue()->assignees()->listAvailable($this->owner, $this->repo, ['type' => 'Contributor']);
    }

    public function collaborators()
    {
        return $this->hub->repo()->collaborators()->all($this->owner, $this->repo);
    }

    public function addLabels($prNumber, array $labels)
    {
        $this->hub->issue()->update(
            $this->owner,
            $this->repo,
            $prNumber,
            [
                'labels' => $labels,
            ]
        );
    }

    public function addMilestone($prNumber, $milestoneTitle)
    {
        $milestones = $this->milestones();
        $milestoneNumber = null;
        foreach ($milestones as $milestone) {
            if ($milestone['title'] == $milestoneTitle) {
                $milestoneNumber = $milestone['number'];
            }
        }

        if (!empty($milestoneNumber)) {
            $this->hub->issue()->update(
                $this->owner,
                $this->repo,
                $prNumber,
                [
                    'milestone' => $milestoneNumber,
                ]
            );
        }
    }

    public function addAssignees($prNumber, array $assignees)
    {
        $this->hub->issue()->update(
            $this->owner,
            $this->repo,
            $prNumber,
            [
                'assignees' => $assignees,
            ]
        );
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
