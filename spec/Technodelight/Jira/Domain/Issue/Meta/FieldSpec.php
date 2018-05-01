<?php

namespace spec\Technodelight\Jira\Domain\Issue\Meta;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class FieldSpec extends ObjectBehavior
{
    private $issueMetaField = [
        'name' => 'Priority',
        'allowedValues' => [
            [
                'id' => '1',
                'self' => 'https://inviqa-de.atlassian.net/rest/api/2/priority/1',
                'name' => 'Blocker (Prio A)',
                'iconUrl' => 'https://inviqa-de.atlassian.net/images/icons/priorities/blocker.svg',
            ],
            [
                'iconUrl' => 'https://inviqa-de.atlassian.net/images/icons/priorities/critical.svg',
                'name' => 'Critical',
                'self' => 'https://inviqa-de.atlassian.net/rest/api/2/priority/2',
                'id' => '2',
            ],
            [
                'self' => 'https://inviqa-de.atlassian.net/rest/api/2/priority/3',
                'name' => 'Major (Prio B)',
                'id' => '3',
                'iconUrl' => 'https://inviqa-de.atlassian.net/images/icons/priorities/major.svg',
            ],
            [
                'iconUrl' => 'https://inviqa-de.atlassian.net/images/icons/priorities/minor.svg',
                'id' => '4',
                'name' => 'Minor',
                'self' => 'https://inviqa-de.atlassian.net/rest/api/2/priority/4',
            ],
            [
                'iconUrl' => 'https://inviqa-de.atlassian.net/images/icons/priorities/trivial.svg',
                'id' => '5',
                'self' => 'https://inviqa-de.atlassian.net/rest/api/2/priority/5',
                'name' => 'Trivial (Prio C)',
            ],
        ],
        'operations' => ['set'],
        'key' => 'priority',
        'required' => false,
        'schema' => [
            'system' => 'priority',
            'type' => 'priority',
        ],
    ];

    function it_is_initializable()
    {
        $this->beConstructedThroughFromArray($this->issueMetaField);
        $this->name()->shouldReturn($this->issueMetaField['name']);
        $this->allowedValues()->shouldReturn(['Blocker (Prio A)', 'Critical', 'Major (Prio B)', 'Minor', 'Trivial (Prio C)']);
        $this->key()->shouldReturn($this->issueMetaField['key']);
        $this->operations()->shouldReturn(['set']);
    }
}
