<?php

namespace Technodelight\Jira\Domain;

class Field
{
    private $id;
    private $key;
    private $name;
    private $custom;
    private $clauseNames;
    private $schema;

    public static function fromArray(array $field)
    {
        $instance = new self;

        $instance->id = $field['id'];
        $instance->key = $field['key'];
        $instance->name = $field['name'];
        $instance->custom = $field['custom'];
        $instance->clauseNames = $field['clauseNames'];
        $instance->schema = isset($field['schema']) ? $field['schema'] : [];

        return $instance;
    }

    /**
     * @return string
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isCustom()
    {
        return $this->custom == true;
    }

    /**
     * @return array
     */
    public function clauseNames()
    {
        return $this->clauseNames;
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

    public function __toString()
    {
        return (string) $this->key();
    }

    private function __construct()
    {
    }
}
