<?php

namespace Technodelight\Jira\Domain\Issue\Meta;

class Field
{
    /**
     * @var string
     */
    private $key;
    /**
     * @var string
     */
    private $name;
    /**
     * @var array
     */
    private $operations = [];
    /**
     * @var array
     */
    private $allowedValues = [];

    private function __construct()
    {
    }

    public static function fromArray(array $meta)
    {
        $field = new self;
        $field->key = $meta['key'];
        $field->name = $meta['name'];
        $field->operations = $meta['operations'];
        $field->allowedValues = isset($meta['allowedValues']) ? $meta['allowedValues'] : [];

        return $field;
    }

    public function key()
    {
        return $this->key;
    }

    public function name()
    {
        return $this->name;
    }

    public function operations()
    {
        return $this->operations;
    }

    public function allowedValues()
    {
        return array_map(
            function (array $valueArray) {
                return $valueArray['name'];
            },
            $this->allowedValues
        );
    }
}
