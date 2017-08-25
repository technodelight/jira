<?php

namespace Technodelight\Jira\Api;

use Technodelight\Jira\Api\Worklog;
use Technodelight\Jira\Helper\DateHelper;

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
     * Subtasks
     *
     * @var Issue[]
     */
    private $subtasks;

    /**
     * Worklogs, if all fields are returned by API
     *
     * @var WorklogCollection
     */
    private $worklogs;

    /**
     * Comments, if any
     *
     * @var Comment[]
     */
    private $comments = [];

    /**
     * @var Attachment[]
     */
    private $attachments = [];

    public function id()
    {
        return $this->id;
    }

    public function key()
    {
        return $this->key;
    }

    public function ticketNumber()
    {
        return $this->key;
    }

    public function issueKey()
    {
        return $this->ticketNumber();
    }

    public function project()
    {
        return (object) ($this->findField('project') ?: []);
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

    public function statusCategory()
    {
        if ($field = $this->findField('status')) {
            return $field['statusCategory'];
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

    public function remainingEstimate()
    {
        if ($field = $this->findField('timetracking')) {
            return isset($field['remainingEstimateSeconds'])
                ? $field['remainingEstimateSeconds']
                : null;
        }
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

    /**
     * @return WorklogCollection
     */
    public function worklogs()
    {
        if ($this->worklogs) {
            return $this->worklogs;
        }

        if ($field = $this->findField('worklog')) {
            $this->worklogs = WorklogCollection::fromIssueArray($this, $field['worklogs']);
        }
        return $this->worklogs ?: WorklogCollection::createEmpty();
    }

    public function assignWorklogs(WorklogCollection $worklogs)
    {
        if (!$this->worklogs) {
            $this->worklogs = $worklogs;
        } else {
            throw new \RuntimeException('Issue contains worklogs already, re-assigning worklogs is forbidden');
        }
    }

    public function attachments()
    {
        if (empty($this->attachments) && $attachments = $this->findField('attachment')) {
            foreach ($attachments as $attachment) {
                $this->attachments[] = Attachment::fromArray($attachment, $this);
            }
        }
        return $this->attachments;
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

    public function subtasks()
    {
        if (($subtasks = $this->findField('subtasks')) && !isset($this->subtasks)) {
            $this->subtasks = [];
            foreach ($subtasks as $subtask) {
                $this->subtasks[] = Issue::fromArray($subtask);
            }
        }

        return $this->subtasks ?: [];
    }

    public static function fromArray($resultArray)
    {
        $issue = new self;
        $issue->id = $resultArray['id'];
        $issue->link = $resultArray['self'];
        $issue->key = $resultArray['key'];
        $issue->fields = isset($resultArray['fields']) ? $resultArray['fields'] : [];

        return $issue;
    }

    private function findField($name)
    {
        return isset($this->fields[$name]) ? $this->fields[$name] : false;
    }

    private function __construct()
    {
    }
}
