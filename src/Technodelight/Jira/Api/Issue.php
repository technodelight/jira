<?php

namespace Technodelight\Jira\Api;

use Technodelight\Jira\Api\Worklog;

class Issue
{
    private $id, $link, $key, $fields;

    /**
     * Parent issue, if any
     *
     * @var Issue|null
     */
    private $parent;

    /**
     * Worklogs, if all fields are returned by API
     *
     * @var array
     */
    private $worklogs = [];

    /**
     * Comments, if any
     *
     * @var array
     */
    private $comments = [];

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

    public function issueKey()
    {
        return $this->ticketNumber();
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

    public function status()
    {
        if ($field = $this->findField('status')) {
            return $field['name'];
        }
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
        return '';
    }

    public function creator()
    {
        $field = $this->findField('creator');
        if ($field) {
            return $field['displayName'] ?: '<unknown>';
        }
        return '';
    }

    public function assignee()
    {
        $field = $this->findField('assignee');
        if ($field) {
            return $field['displayName'] ?: '?';
        }
        return 'Unassigned';
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
        if ($comps = $this->findField('components')) {
            $names = [];
            foreach ($comps as $field) {
                $names[] = $field['name'];
            }

            return $names;
        }
    }

    public function worklogs()
    {
        if ($field = $this->findField('worklog') && empty($this->worklogs)) {
            $logs = $field['worklogs'];
            foreach ($logs as $logArray) {
                $this->worklogs[] = Worklog::fromArray($logArray);
            }
        }

        return $this->worklogs;
    }

    public function comments()
    {
        if (isset($this->fields['comment']) && is_array($this->fields['comment']) && empty($this->comments)) {
            $comments = $this->fields['comment']['comments'];
            foreach ($comments as $commentArray) {
                $this->comments[] = Comment::fromArray($commentArray);
            }
        }

        return $this->comments;
    }

    /**
     * @return Issue|null
     */
    public function parent()
    {
        if ($parent = $this->findField('parent')) {
            $this->parent = Issue::fromArray($parent);
        }

        return $this->parent;
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
