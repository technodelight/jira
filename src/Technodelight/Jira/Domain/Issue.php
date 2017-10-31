<?php

namespace Technodelight\Jira\Domain;

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
        return \DateTime::createFromFormat('Y-m-d H:i:s', $this->findField('created'));
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

    public function creatorUser()
    {
        $field = $this->findField('creator');
        if (is_array($field)) {
            return User::fromArray($field);
        }
    }

    public function assignee()
    {
        $field = $this->findField('assignee');
        if ($field) {
            return $field['displayName'] ?: '?';
        }
        return 'Unassigned';
    }

    public function assigneeUser()
    {
        $field = $this->findField('assignee');
        if (is_array($field)) {
            return User::fromArray($field);
        }
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
        return null;
    }

    public function issueType()
    {
        if ($field = $this->findField('issuetype')) {
            return $field['name'];
        }
        return '';
    }

    public function priority()
    {
        if ($field = $this->findField('priority')) {
            return $field['name'];
        }
        return '';
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
        $this->worklogs = $worklogs;
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
        if (!empty($this->fields['comment']['comments']) && empty($this->comments)) {
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

    /**
     * Find a custom issue field by it's name
     *
     * @param string $fieldName
     * @return array|false
     */
    public function findField($fieldName)
    {
        return isset($this->fields[$fieldName]) ? $this->fields[$fieldName] : false;
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

    private function __construct()
    {
    }
}
