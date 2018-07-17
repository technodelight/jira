<?php

namespace spec\Technodelight\Jira\Domain;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Technodelight\Jira\Domain\User;

class FilterSpec extends ObjectBehavior
{
    private $filter = [
        'viewUrl' => 'https://fixture.jira.phar/issues/?filter=12345/issues/?filter=12345',
        'favouritedCount' => 1,
        'subscriptions' => [
            'max-results' => 1000,
            'start-index' => 0,
            'items' => [],
            'size' => 0,
            'end-index' => 0,
        ],
        'sharedUsers' => [
            'end-index' => 0,
            'size' => 0,
            'start-index' => 0,
            'max-results' => 1000,
            'items' => [],
        ],
        'jql' => 'project = "PROJ Spec Project Application Maintenance" AND (labels not in (no_board) OR labels is EMPTY) ORDER BY Rank ASC',
        'favourite' => false,
        'self' => 'https://fixture.jira.phar/rest/api/2/filter/12345',
        'id' => '12345',
        'description' => '',
        'owner' => [
            'self' => 'https://fixture.jira.phar/rest/api/2/user?username=user.name',
            'accountId' => '12345:uuid',
            'avatarUrls' => [
                '48x48' => 'https://avatar-cdn.atlassian.com/md5-here?s=48&d=https%3A%2F%2Ffixture.jira.phar%2Fsecure%2Fuseravatar%3FownerId%3Duser.name%26avatarId%3D12345%26noRedirect%3Dtrue',
                '32x32' => 'https://avatar-cdn.atlassian.com/md5-here?s=32&d=https%3A%2F%2Ffixture.jira.phar%2Fsecure%2Fuseravatar%3Fsize%3Dmedium%26ownerId%3Duser.name%26avatarId%3D12345%26noRedirect%3Dtrue',
                '16x16' => 'https://avatar-cdn.atlassian.com/md5-here?s=16&d=https%3A%2F%2Ffixture.jira.phar%2Fsecure%2Fuseravatar%3Fsize%3Dxsmall%26ownerId%3Duser.name%26avatarId%3D12345%26noRedirect%3Dtrue',
                '24x24' => 'https://avatar-cdn.atlassian.com/md5-here?s=24&d=https%3A%2F%2Ffixture.jira.phar%2Fsecure%2Fuseravatar%3Fsize%3Dsmall%26ownerId%3Duser.name%26avatarId%3D12345%26noRedirect%3Dtrue',
            ],
            'active' => true,
            'name' => 'user.name',
            'key' => 'user.name',
            'displayName' => 'User Name',
        ],
        'searchUrl' => 'https://fixture.jira.phar/rest/api/2/search?jql=project+%3D+%22PROJ+Spec+Project+Application+Maintenance%22+AND+(labels+not+in+(no_board)+OR+labels+is+EMPTY)+ORDER+BY+Rank+ASC',
        'name' => 'Spec Project Kanban RF',
        'sharePermissions' => [
            [
                'group' =>
                    [
                        'self' => 'https://fixture.jira.phar/rest/api/2/group?groupname=Developers+Spec+Project',
                        'name' => 'Developers Spec Project',
                    ],
                'type' => 'group',
                'id' => 16414,
            ],
            [
                'type' => 'project',
                'id' => 16415,
                'project' => [
                    'key' => 'PROJ',
                    'avatarUrls' => [
                        '24x24' => 'https://fixture.jira.phar/secure/projectavatar?size=small&pid=54321&avatarId=12345',
                        '16x16' => 'https://fixture.jira.phar/secure/projectavatar?size=xsmall&pid=54321&avatarId=12345',
                        '48x48' => 'https://fixture.jira.phar/secure/projectavatar?pid=54321&avatarId=12345',
                        '32x32' => 'https://fixture.jira.phar/secure/projectavatar?size=medium&pid=54321&avatarId=12345',
                    ],
                    'assigneeType' => 'PROJECT_LEAD',
                    'self' => 'https://fixture.jira.phar/rest/api/2/project/54321',
                    'projectTypeKey' => 'service_desk',
                    'id' => '54321',
                    'name' => 'PROJ Spec Project Application Maintenance',
                    'roles' => [],
                    'simplified' => false,
                    'projectCategory' => [
                        'self' => 'https://fixture.jira.phar/rest/api/2/projectCategory/10010',
                        'id' => '10010',
                        'name' => 'Customer',
                        'description' => '',
                    ],
                ],
            ],
        ],
    ];

    function it_is_initializable()
    {
        $this->beConstructedThrough('fromArray', [$this->filter]);
        $this->shouldHaveType('Technodelight\Jira\Domain\Filter');
        $this->id()->shouldReturn($this->filter['id']);
        $this->isFavourite()->shouldReturn(false);
        $this->jql()->shouldReturn($this->filter['jql']);
        $this->name()->shouldReturn($this->filter['name']);
        $this->description()->shouldReturn($this->filter['description']);
        $this->owner()->shouldBeLike(User::fromArray($this->filter['owner']));
        $this->favouritedCount()->shouldReturn($this->filter['favouritedCount']);
    }
}
