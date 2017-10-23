<?php

namespace spec\Technodelight\Jira\Domain;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class StatusSpec extends ObjectBehavior
{
    private $status = [
        'description' => '',
        'statusCategory' => [
            'name' => 'In Progress',
            'colorName' => 'yellow',
            'key' => 'indeterminate',
            'self' => 'https://fixture.jira.phar/rest/api/2/statuscategory/4',
            'id' => 4,
        ],
        'id' => '10100',
        'name' => 'Shortcut (open/close)',
        'iconUrl' => 'https://fixture.jira.phar/images/icons/statuses/generic.png',
        'self' => 'https://fixture.jira.phar/rest/api/2/status/10100',
    ];

    function it_is_initializable()
    {
        $this->beConstructedFromArray($this->status);
        $this->description()->shouldReturn($this->status['description']);
        $this->name()->shouldReturn($this->status['name']);
        $this->id()->shouldReturn($this->status['id']);
        $this->statusCategory()->shouldReturn($this->status['statusCategory']['name']);
        $this->statusCategoryColor()->shouldReturn($this->status['statusCategory']['colorName']);
    }
}
