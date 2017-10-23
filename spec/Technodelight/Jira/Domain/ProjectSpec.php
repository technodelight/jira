<?php

namespace spec\Technodelight\Jira\Domain;

use PhpSpec\ObjectBehavior;
use Technodelight\Jira\Domain\Project\Version;
use Technodelight\Jira\Domain\User;

class ProjectSpec extends ObjectBehavior
{
    private $project = [
        'self' => 'https://fixture.jira.phar/rest/api/2/project/11522',
        'projectTypeKey' => 'software',
        'expand' => 'description,lead,issueTypes,url,projectKeys',
        'id' => '12345',
        'name' => 'SPEC Project',
        'avatarUrls' =>
            [
                '32x32' => 'https://fixture.jira.phar/secure/projectavatar?size=medium&pid=12345&avatarId=12750',
                '48x48' => 'https://fixture.jira.phar/secure/projectavatar?pid=12345&avatarId=12750',
                '24x24' => 'https://fixture.jira.phar/secure/projectavatar?size=small&pid=12345&avatarId=12750',
                '16x16' => 'https://fixture.jira.phar/secure/projectavatar?size=xsmall&pid=12345&avatarId=12750',
            ],
        'key' => 'SPEC',
        'projectCategory' =>
            [
                'name' => 'Internal',
                'id' => '10020',
                'self' => 'https://fixture.jira.phar/rest/api/2/projectCategory/54321',
                'description' => 'Projects for internal usage only',
            ],
    ];

    private $fullProject = [
        'id' => '12345',
        'projectTypeKey' => 'service_desk',
        'versions' => [
            [
                'projectId' => 12345,
                'released' => true,
                'userReleaseDate' => '30/Jul/15',
                'name' => '0.9.2',
                'self' => 'https://fixture.jira.phar/rest/api/2/version/12441',
                'description' => 'Maintenance release',
                'releaseDate' => '2015-07-30',
                'id' => '12441',
                'archived' => false,
            ],
        ],
        'lead' => [
            'accountId' => '557058:51f883d6-6891-48b0-8e73-94e0de060b82',
            'self' => 'https://fixture.jira.phar/rest/api/2/user?username=zsolt.gal',
            'name' => 'zsolt.gal',
            'key' => 'zsolt.gal',
            'active' => true,
            'avatarUrls' =>
                [
                    '24x24' => 'https://avatar-cdn.atlassian.com/782cc8c2a7ed54495d2f5be2aac26b29?s=24&d=https%3A%2F%2Ffixture.jira.phar%2Fsecure%2Fuseravatar%3Fsize%3Dsmall%26ownerId%3Dzsolt.gal%26avatarId%3D14900%26noRedirect%3Dtrue',
                    '32x32' => 'https://avatar-cdn.atlassian.com/782cc8c2a7ed54495d2f5be2aac26b29?s=32&d=https%3A%2F%2Ffixture.jira.phar%2Fsecure%2Fuseravatar%3Fsize%3Dmedium%26ownerId%3Dzsolt.gal%26avatarId%3D14900%26noRedirect%3Dtrue',
                    '48x48' => 'https://avatar-cdn.atlassian.com/782cc8c2a7ed54495d2f5be2aac26b29?s=48&d=https%3A%2F%2Ffixture.jira.phar%2Fsecure%2Fuseravatar%3FownerId%3Dzsolt.gal%26avatarId%3D14900%26noRedirect%3Dtrue',
                    '16x16' => 'https://avatar-cdn.atlassian.com/782cc8c2a7ed54495d2f5be2aac26b29?s=16&d=https%3A%2F%2Ffixture.jira.phar%2Fsecure%2Fuseravatar%3Fsize%3Dxsmall%26ownerId%3Dzsolt.gal%26avatarId%3D14900%26noRedirect%3Dtrue',
                ],
            'displayName' => 'Zsolt Gal',
        ],
        'key' => 'SPEC',
        'assigneeType' => 'PROJECT_LEAD',
        'roles' => [
            'Service Desk Team' => 'https://fixture.jira.phar/rest/api/2/project/12345/role/10641',
            'Tempo Project Managers' => 'https://fixture.jira.phar/rest/api/2/project/12345/role/10642',
            'Service Desk Customers' => 'https://fixture.jira.phar/rest/api/2/project/12345/role/10640',
            'Administrators' => 'https://fixture.jira.phar/rest/api/2/project/12345/role/10740',
            'Developers' => 'https://fixture.jira.phar/rest/api/2/project/12345/role/10840',
            'atlassian-addons-project-access' => 'https://fixture.jira.phar/rest/api/2/project/12345/role/10900',
        ],
        'projectCategory' => [
            'id' => '123123',
            'description' => '',
            'name' => 'Internal',
            'self' => 'https://fixture.jira.phar/rest/api/2/projectCategory/123123',
        ],
        'avatarUrls' => [
            '32x32' => 'https://fixture.jira.phar/secure/projectavatar?size=medium&pid=12345&avatarId=54321',
            '24x24' => 'https://fixture.jira.phar/secure/projectavatar?size=small&pid=12345&avatarId=54321',
            '16x16' => 'https://fixture.jira.phar/secure/projectavatar?size=xsmall&pid=12345&avatarId=54321',
            '48x48' => 'https://fixture.jira.phar/secure/projectavatar?pid=12345&avatarId=54321',
        ],
        'description' => '<p>Anything HTML</p>',
        'name' => 'SPEC Project',
        'self' => 'https://fixture.jira.phar/rest/api/2/project/12345',
        'components' => [
            [
                'isAssigneeTypeValid' => false,
                'name' => 'Backend',
                'self' => 'https://fixture.jira.phar/rest/api/2/component/11664',
                'id' => '11664',
            ],
            [
                'isAssigneeTypeValid' => false,
                'self' => 'https://fixture.jira.phar/rest/api/2/component/11665',
                'name' => 'Frontend',
                'id' => '11665',
            ],
            [
                'name' => 'PM',
                'self' => 'https://fixture.jira.phar/rest/api/2/component/11666',
                'id' => '11666',
                'isAssigneeTypeValid' => false,
            ],
            [
                'isAssigneeTypeValid' => false,
                'name' => 'QA',
                'self' => 'https://fixture.jira.phar/rest/api/2/component/11667',
                'id' => '11667',
            ],
        ],
        'issueTypes' => [
            [
                'iconUrl' => 'https://fixture.jira.phar/images/icons/issuetypes/epic.png',
                'id' => '16',
                'subtask' => false,
                'self' => 'https://fixture.jira.phar/rest/api/2/issuetype/16',
                'name' => 'Epic',
                'description' => 'A big user story that needs to be broken down. Created by JIRA Software - do not edit or delete.',
            ],
            [
                'iconUrl' => 'https://fixture.jira.phar/secure/viewavatar?size=xsmall&avatarId=13055&avatarType=issuetype',
                'id' => '17',
                'subtask' => false,
                'avatarId' => 13055,
                'name' => 'Story',
                'description' => 'A user story. Created by JIRA Software - do not edit or delete.',
                'self' => 'https://fixture.jira.phar/rest/api/2/issuetype/17',
            ],
            [
                'name' => 'Spike',
                'self' => 'https://fixture.jira.phar/rest/api/2/issuetype/20',
                'description' => 'Tickets used for research and analysis',
                'subtask' => false,
                'id' => '20',
                'iconUrl' => 'https://fixture.jira.phar/images/icons/issuetypes/task_agile.png',
            ],
            [
                'iconUrl' => 'https://fixture.jira.phar/images/icons/issuetypes/undefined.png',
                'id' => '6',
                'subtask' => false,
                'name' => 'Support',
                'self' => 'https://fixture.jira.phar/rest/api/2/issuetype/6',
                'description' => 'A request that needs to be answered',
            ],
            [
                'id' => '4',
                'iconUrl' => 'https://fixture.jira.phar/secure/viewavatar?size=xsmall&avatarId=321321&avatarType=issuetype',
                'self' => 'https://fixture.jira.phar/rest/api/2/issuetype/4',
                'name' => 'Improvement',
                'description' => 'An improvement or enhancement to an existing feature or task.',
                'avatarId' => 321321,
                'subtask' => false,
            ],
            [
                'id' => '13',
                'iconUrl' => 'https://fixture.jira.phar/secure/viewavatar?size=xsmall&avatarId=321321&avatarType=issuetype',
                'self' => 'https://fixture.jira.phar/rest/api/2/issuetype/13',
                'name' => 'Sub-task (Improvement)',
                'description' => 'A sub-task of an issue (as improvement)',
                'subtask' => true,
                'avatarId' => 321321,
            ],
            [
                'subtask' => false,
                'avatarId' => 321322,
                'description' => 'A new feature of the product, which has yet to be developed.',
                'name' => 'New Feature',
                'self' => 'https://fixture.jira.phar/rest/api/2/issuetype/2',
                'iconUrl' => 'https://fixture.jira.phar/secure/viewavatar?size=xsmall&avatarId=321322&avatarType=issuetype',
                'id' => '2',
            ],
            [
                'avatarId' => 321322,
                'subtask' => true,
                'name' => 'Sub-task (New feature)',
                'self' => 'https://fixture.jira.phar/rest/api/2/issuetype/12',
                'description' => 'A sub-task of an issue (as new feature)',
                'iconUrl' => 'https://fixture.jira.phar/secure/viewavatar?size=xsmall&avatarId=321322&avatarType=issuetype',
                'id' => '12',
            ],
            [
                'iconUrl' => 'https://fixture.jira.phar/secure/viewavatar?size=xsmall&avatarId=321323&avatarType=issuetype',
                'id' => '3',
                'avatarId' => 321323,
                'subtask' => false,
                'name' => 'Task',
                'description' => 'A task that needs to be done.',
                'self' => 'https://fixture.jira.phar/rest/api/2/issuetype/3',
            ],
            [
                'id' => '7',
                'iconUrl' => 'https://fixture.jira.phar/secure/viewavatar?size=xsmall&avatarId=321324&avatarType=issuetype',
                'name' => 'Sub-task',
                'description' => 'The sub-task of the issue',
                'self' => 'https://fixture.jira.phar/rest/api/2/issuetype/7',
                'avatarId' => 321324,
                'subtask' => true,
            ],
            [
                'subtask' => false,
                'avatarId' => 321325,
                'description' => 'A problem which impairs or prevents the functions of the product.',
                'name' => 'Bug',
                'self' => 'https://fixture.jira.phar/rest/api/2/issuetype/1',
                'iconUrl' => 'https://fixture.jira.phar/secure/viewavatar?size=xsmall&avatarId=321325&avatarType=issuetype',
                'id' => '1',
            ],
            [
                'description' => 'A sub-task of an issue (as bug)',
                'name' => 'Sub-task (Bug)',
                'self' => 'https://fixture.jira.phar/rest/api/2/issuetype/11',
                'avatarId' => 321325,
                'subtask' => true,
                'id' => '11',
                'iconUrl' => 'https://fixture.jira.phar/secure/viewavatar?size=xsmall&avatarId=321325&avatarType=issuetype',
            ],
        ],
    ];

    function it_can_be_created_from_an_array()
    {
        $this->beConstructedFromArray($this->project);
        $this->id()->shouldReturn($this->project['id']);
        $this->key()->shouldReturn($this->project['key']);
        $this->name()->shouldReturn($this->project['name']);
        $this->projectTypeKey()->shouldReturn($this->project['projectTypeKey']);
    }

    function it_can_be_created_from_full_array()
    {
        $this->beConstructedFromArray($this->fullProject);
        $this->versions()->shouldBeLike([Version::fromArray($this->fullProject['versions'][0])]);
        $this->lead()->shouldBeLike(User::fromArray($this->fullProject['lead']));
        $this->components()->shouldReturn($this->fullProject['components']);
        $this->issueTypes()->shouldReturn($this->fullProject['issueTypes']);
        $this->description()->shouldReturn($this->fullProject['description']);
    }
}
