<?php

namespace spec\Technodelight\Jira\Domain;

use PhpSpec\ObjectBehavior;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\IssueLink\Type;

class IssueLinkSpec extends ObjectBehavior
{
    private $inwardIssue = [
        'id' => '38493',
        'self' => 'https://fixture.jira.phar/rest/api/2/issueLink/38493',
        'type' => [
            'id' => '10320',
            'name' => 'Problem/Incident',
            'inward' => 'is caused by',
            'outward' => 'causes',
            'self' => 'https://fixture.jira.phar/rest/api/2/issueLinkType/10320',
        ],
        'inwardIssue' => [
            'id' => '48602',
            'key' => 'PROJ-512',
            'self' => 'https://fixture.jira.phar/rest/api/2/issue/48602',
            'fields' => [
                'summary' => 'Links on CMS banners',
                'status' => [
                    'self' => 'https://fixture.jira.phar/rest/api/2/status/6',
                    'description' => 'An improvement or enhancement to an existing feature or task.',
                    'iconUrl' => 'https://fixture.jira.phar/images/icons/statuses/closed.png',
                    'name' => 'Closed',
                    'id' => '6',
                    'statusCategory' => [
                        'self' => 'https://fixture.jira.phar/rest/api/2/statuscategory/3',
                        'id' => 3,
                        'key' => 'done',
                        'colorName' => 'green',
                        'name' => 'Done',
                    ],
                ],
                'priority' => [
                    'self' => 'https://fixture.jira.phar/rest/api/2/priority/1',
                    'iconUrl' => 'https://fixture.jira.phar/images/icons/priorities/blocker.svg',
                    'name' => 'Blocker (Prio A)',
                    'id' => '1',
                ],
                'issuetype' => [
                    'self' => 'https://fixture.jira.phar/rest/api/2/issuetype/4',
                    'id' => '4',
                    'description' => 'An improvement or enhancement to an existing feature or task.',
                    'iconUrl' => 'https://fixture.jira.phar/secure/viewavatar?size=xsmall&avatarId=12345&avatarType=issuetype',
                    'name' => 'Improvement',
                    'subtask' => false,
                    'avatarId' => 13050,
                ],
            ],
        ],
    ];

    private $outwardIssue = [
        'id' => '38494',
        'self' => 'https://fixture.jira.phar/rest/api/2/issueLink/38494',
        'type' => [
            'id' => '10120',
            'name' => 'Relates to',
            'inward' => 'relates to',
            'outward' => 'relates to',
            'self' => 'https://fixture.jira.phar/rest/api/2/issueLinkType/10120',
        ],
        'outwardIssue' => [
            'id' => '39185',
            'key' => 'PROJ-120',
            'self' => 'https://fixture.jira.phar/rest/api/2/issue/39185',
            'fields' => [
                'summary' => 'Banner Management: Banner replacement should be easier',
                'status' => [
                    'self' => 'https://fixture.jira.phar/rest/api/2/status/6',
                    'description' => 'An improvement or enhancement to an existing feature or task.',
                    'iconUrl' => 'https://fixture.jira.phar/images/icons/statuses/closed.png',
                    'name' => 'Closed',
                    'id' => '6',
                    'statusCategory' => [
                        'self' => 'https://fixture.jira.phar/rest/api/2/statuscategory/3',
                        'id' => 3,
                        'key' => 'done',
                        'colorName' => 'green',
                        'name' => 'Done',
                    ],
                ],
                'priority' => [
                    'self' => 'https://fixture.jira.phar/rest/api/2/priority/5',
                    'iconUrl' => 'https://fixture.jira.phar/images/icons/priorities/trivial.svg',
                    'name' => 'Trivial (Prio C)',
                    'id' => '5',
                ],
                'issuetype' => [
                    'self' => 'https://fixture.jira.phar/rest/api/2/issuetype/4',
                    'id' => '4',
                    'description' => 'An improvement or enhancement to an existing feature or task.',
                    'iconUrl' => 'https://fixture.jira.phar/secure/viewavatar?size=xsmall&avatarId=12345&avatarType=issuetype',
                    'name' => 'Improvement',
                    'subtask' => false,
                    'avatarId' => 13050,
                ],
            ],
        ],
    ];

    function it_has_props()
    {
        $this->beConstructedFromArray($this->inwardIssue);
        $this->id()->shouldReturn(38493);
        $this->type()->shouldBeAnInstanceOf(Type::class);
        $this->inwardIssue()->shouldBeAnInstanceOf(Issue::class);
    }
    function it_can_be_outward_props()
    {
        $this->beConstructedFromArray($this->outwardIssue);
        $this->id()->shouldReturn(38494);
        $this->type()->shouldBeAnInstanceOf(Type::class);
        $this->outwardIssue()->shouldBeAnInstanceOf(Issue::class);
    }
}
