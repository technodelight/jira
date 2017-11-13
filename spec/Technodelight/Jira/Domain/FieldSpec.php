<?php

namespace spec\Technodelight\Jira\Domain;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class FieldSpec extends ObjectBehavior
{
    private $field = [
        'id' => 'customfield_10652',
        'key' => 'customfield_10652',
        'name' => 'Acceptance Criteria',
        'custom' => true,
        'orderable' => true,
        'navigable' => true,
        'searchable' => true,
        'clauseNames' => [
            'Acceptance Criteria',
            'cf[10652]',
        ],
        'schema' => [
            'type' => 'string',
            'custom' => 'com.atlassian.jira.plugin.system.customfieldtypes:textarea',
            'customId' => 10652,
        ],
    ];

    function it_is_initializable()
    {
        $this->beConstructedFromArray($this->field);
        $this->shouldHaveType('Technodelight\Jira\Domain\Field');

        $this->id()->shouldReturn($this->field['id']);
        $this->key()->shouldReturn($this->field['key']);
        $this->isCustom()->shouldReturn($this->field['custom']);
        $this->schemaType()->shouldReturn($this->field['schema']['type']);
    }

}
