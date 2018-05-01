<?php

namespace spec\Technodelight\Jira\Console\Dashboard;

use PhpSpec\ObjectBehavior;
use Technodelight\Jira\Console\Dashboard\Dashboard;
use Technodelight\Jira\Api\JiraRestApi\Api as Jira;
use Technodelight\Jira\Connector\WorklogHandler;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\IssueCollection;
use Technodelight\Jira\Domain\Worklog;
use Technodelight\Jira\Domain\WorklogCollection;
use Technodelight\Jira\Console\Dashboard\Collection as Collection;

class DashboardSpec extends ObjectBehavior
{
    const DATE = '2018-01-14 22:05:23';
    const ISSUEKEY = 'TEST-001';

    private $logRecord = [
        'id' => 123321,
        'author' => [
            'id' => 321321,
            'key' => 'zenc',
            'name' => 'zenc',
            'displayName' => 'Zenc'
        ],
        'comment' => 'Test',
        'started' => self::DATE,
        'timeSpentSeconds' => 3600
    ];

    function let(Jira $jira, WorklogHandler $worklogHandler)
    {
        $this->beConstructedWith($jira, $worklogHandler);
    }

    function it_loads_and_groups_worklogs_per_day(Jira $jira, WorklogHandler $worklogHandler, IssueCollection $issueCollection, Issue $issue)
    {
        $log = Worklog::fromArray($this->logRecord, self::ISSUEKEY);
        $worklogCollection = WorklogCollection::createEmpty();
        $worklogCollection->push($log);
        $ref = new \DateTime(self::DATE);

        $worklogHandler->find($ref, $ref)->willReturn($worklogCollection);
        $issue->issueKey()->willReturn(self::ISSUEKEY);
        $issue->worklogs()->willReturn($worklogCollection);

        $jira->retrieveIssues([self::ISSUEKEY])->shouldBeCalled()->willReturn($issueCollection);
        $issueCollection->current()->willReturn($issue);
        $issueCollection->find(self::ISSUEKEY)->willReturn($issue);

        $dashboardCollection = Collection::fromWorklogCollection($worklogCollection, $ref, $ref);

        $this->fetch(self::DATE, 'zenc', Dashboard::MODE_DAILY)->shouldBeLike($dashboardCollection);
    }

    function it_loads_and_groups_worklogs_per_week(Jira $jira, WorklogHandler $worklogHandler, IssueCollection $issueCollection, Issue $issue)
    {
        $log = Worklog::fromArray($this->logRecord, self::ISSUEKEY);
        $worklogCollection = WorklogCollection::createEmpty();
        $worklogCollection->push($log);
        $start = new \DateTime('2018-01-08');
        $end = new \DateTime('2018-01-12');
        $worklogHandler->find($start, $end)->willReturn($worklogCollection);

        $jira->retrieveIssues([self::ISSUEKEY])->shouldBeCalled()->willReturn($issueCollection);
        $issueCollection->find(self::ISSUEKEY)->willReturn($issue);
        $issue->issueKey()->willReturn(self::ISSUEKEY);
        $issue->worklogs()->willReturn($worklogCollection);

        $dashboardCollection = Collection::fromWorklogCollection($worklogCollection, $start, $end);

        $this->fetch(self::DATE, 'zenc', Dashboard::MODE_WEEKLY)->shouldBeLike($dashboardCollection);
    }

    function it_loads_and_groups_worklogs_per_month(Jira $jira, WorklogHandler $worklogHandler, IssueCollection $issueCollection, Issue $issue)
    {
        $log = Worklog::fromArray($this->logRecord, self::ISSUEKEY);
        $worklogCollection = WorklogCollection::createEmpty();
        $worklogCollection->push($log);
        $start = new \DateTime('2018-01-01');
        $end = new \DateTime('2018-01-31');
        $worklogHandler->find($start, $end)->willReturn($worklogCollection);

        $jira->retrieveIssues([self::ISSUEKEY])->shouldBeCalled()->willReturn($issueCollection);
        $issueCollection->find(self::ISSUEKEY)->willReturn($issue);
        $issue->issueKey()->willReturn(self::ISSUEKEY);
        $issue->worklogs()->willReturn($worklogCollection);

        $dashboardCollection = Collection::fromWorklogCollection($worklogCollection, $start, $end);

        $this->fetch(self::DATE, 'zenc', Dashboard::MODE_MONTHLY)->shouldBeLike($dashboardCollection);
    }
}
