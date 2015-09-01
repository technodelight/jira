<?php

namespace Technodelight\Jira\Api;

class Issue
{
    private $id, $link, $key, $fields;

    public function __construct($id, $link, $key, $fields)
    {
        $this->id = $id;
        $this->link = $link;
        $this->key = $key;
        $this->fields = $fields;
    }

    public function ticketNumber()
    {
        return $this->key;
    }

    public function summary()
    {
        return $this->findField('summary');
    }

    public function description()
    {
        return $this->findField('description');
    }

    public function created()
    {
        $field = $this->findField('created');
        if ($field) {
            $field = DateHelper::dateTimeFromJira($field);
        }

        return $field;
    }

    public function environment()
    {
        return $this->findField('environment');
    }

    public function reporter()
    {
        $field = $this->findField('reporter');
        if ($field) {
            return $field['displayName'] ?: '<unknown>';
        }
        return '<unknown>';
    }

    public function creator()
    {
        $field = $this->findField('creator');
        if ($field) {
            return $field['displayName'] ?: '<unknown>';
        }
        return '<unknown>';
    }

    public function progress()
    {
        return $this->findField('progress');
    }

    public function estimate()
    {
        return (int) $this->findField('timeestimate');
    }

    public function timeSpent()
    {
        return (int) $this->findField('timespent');
    }

    public function issueType()
    {
        if ($field = $this->findField('issuetype')) {
            return $field['name'];
        }
    }

    public function url()
    {
        $uriParts = parse_url($this->link);
        return sprintf(
            '%s://%s/browse/%s',
            $uriParts['scheme'],
            $uriParts['host'],
            $this->key
        );
    }

    public function components()
    {
        if ($comps = $this->findField('compnents')) {
            $names = [];
            foreach ($comps as $field) {
                $names[] = $field['name'];
            }

            return $names;
        }
    }

    public static function fromArray($resultArray)
    {
        return new self($resultArray['id'], $resultArray['self'], $resultArray['key'], $resultArray['fields']);
    }

    private function findField($name)
    {
        return isset($this->fields[$name]) ? $this->fields[$name] : false;
    }
}
