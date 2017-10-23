<?php

namespace spec\Technodelight\Jira\Domain\Project;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class VersionSpec extends ObjectBehavior
{
    private $version = [
        'projectId' => 12345,
        'released' => true,
        'userReleaseDate' => '30/Jul/15',
        'name' => '0.9.2',
        'self' => 'https://fixture.jira.phar/rest/api/2/version/12441',
        'description' => 'Maintenance release',
        'releaseDate' => '2015-07-30',
        'id' => '12441',
        'archived' => false,
    ];

    function it_is_initializable()
    {
        $this->beConstructedFromArray($this->version);
        $this->name()->shouldReturn($this->version['name']);
        $this->isReleased()->shouldReturn($this->version['released']);
        $this->releaseDate()->shouldBeLike(new \DateTime('2015-07-30'));
        $this->description()->shouldReturn($this->version['description']);
        $this->id()->shouldReturn($this->version['id']);
        $this->isArchived()->shouldReturn($this->version['archived']);
    }
}
