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
    private $schema = [];
    /**
     * @var bool
     */
    private $required;
    /**
     * @var bool
     */
    private $custom;
    /**
     * @var array
     */
    private $allowedValues = [];
    /**
     * @var string
     */
    private $autocompleteUrl;

    private function __construct()
    {
    }

    public static function fromArray(array $meta)
    {
        $field = new self;

        $field->key = $meta['key'];
        $field->name = $meta['name'];
        $field->operations = $meta['operations'];
        $field->schema = isset($meta['schema']) ? $meta['schema'] : [];
        $field->required = isset($meta['required']) ? (bool) $meta['required'] : false;
        $field->custom = isset($meta['schema']['custom']);
        $field->allowedValues = isset($meta['allowedValues']) ? $meta['allowedValues'] : [];
        $field->autocompleteUrl = isset($meta['autoCompleteUrl']) ? $meta['autoCompleteUrl'] : '';

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

    /**
     * @return array
     */
    public function schema()
    {
        return $this->schema;
    }

    /**
     * @return string
     */
    public function schemaType()
    {
        if (isset($this->schema['type'])) {
            return $this->schema['type'];
        }
        return '';
    }

    /**
     * @return string
     */
    public function schemaItemType()
    {
        if (isset($this->schema['items'])) {
            return $this->schema['items'];
        }
        return '';
    }

    /**
     * @return bool
     */
    public function isRequired()
    {
        return $this->required == true;
    }

    /**
     * @return bool
     */
    public function isCustom()
    {
        return $this->custom == true;
    }

    public function allowedValues()
    {
        return array_map(
            function ($valueArray) {
                if (is_array($valueArray)) {
                    if (isset($valueArray['name'])) {
                        return $valueArray['name'];
                    } else if (isset($valueArray['label'])) {
                        return $valueArray['label'];
                    }
                }
                return $valueArray;
            },
            $this->allowedValues
        );
    }

    public function autocompleteUrl()
    {
        return $this->autocompleteUrl;
    }

    public function __toString()
    {
        return (string) $this->key();
    }
}
