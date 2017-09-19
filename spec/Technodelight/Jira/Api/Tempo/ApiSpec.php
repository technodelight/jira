<?php

namespace spec\Technodelight\Jira\Api\Tempo;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Technodelight\Jira\Api\Tempo\Client;
use Technodelight\Jira\Domain\WorklogCollection;

class ApiSpec extends ObjectBehavior
{
    const DATE_FROM = '2017-08-01';
    const DATE_TO = '2018-08-25';

    private $record = [
        'timeSpentSeconds' => 27000,
        'billedSeconds' => 27000,
        'dateStarted' => '2017-08-01T13:02:00.000',
        'dateCreated' => null,
        'dateUpdated' => null,
        'comment' => 'work',
        'origin' => null,
        'meta' => null,
        'self' => 'https://fixtures.jira.phar/tempo-timesheets/3/worklogs/463134',
        'id' => 463134,
        'author' => [
            'self' => 'https://fixtures.jira.phar/rest/api/2/user?username=zgal',
            'name' => 'zgal',
            'key' => 'zgal',
            'displayName' => 'Zsolt Gal',
            'avatar' => 'https://avatar-cdn.atlassian.com/163e59164af29a67e5a19a68ca17ced4?s=24&d=https%3A%2F%2Ffixtures.jira.phar%2Fsecure%2Fuseravatar%3Fsize%3Dsmall%26ownerId%3Dzgal%26avatarId%3D30101%26noRedirect%3Dtrue',
        ],
        'issue' => [
            'self' => 'https://fixtures.jira.phar/rest/api/2/issue/141588',
            'id' => 141588,
            'projectId' => 26400,
            'key' => 'PROJ-1',
            'remainingEstimateSeconds' => 0,
            'issueType' => [
                'name' => 'Task',
                'iconUrl' => 'https://fixtures.jira.phar/secure/viewavatar?size=xsmall&avatarId=15318&avatarType=issuetype',
            ],
            'summary' => 'Chargeable work',
            'internalIssue' => false,
        ],
        'worklogAttributes' => [],
        'workAttributeValues' => [],
    ];

    function let(Client $client)
    {
        $this->beConstructedWith($client);
    }

    function it_returns_worklogs(Client $client)
    {
        $client->get(Argument::type('string'), Argument::type('array'))->shouldBeCalled()->willReturn([$this->record]);
        $this->find(self::DATE_FROM, self::DATE_TO)
             ->shouldBeArray();
        $this->find(self::DATE_FROM, self::DATE_TO)
             ->shouldHaveCount(1);
    }
}
