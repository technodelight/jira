<?php

namespace Technodelight\Jira\Domain;

use Technodelight\Jira\Domain\IssueLink\Type;

class IssueLink
{
    private $id;
    private $type;
    private $inwardIssue;
    private $outwardIssue;

    private function __construct()
    {
    }

    public static function fromArray(array $array)
    {
        $issueLink = new IssueLink();
        $issueLink->id = $array['id'];
        $issueLink->type = $array['type'];
        $issueLink->inwardIssue = isset($array['inwardIssue']) ? $array['inwardIssue'] : null;
        $issueLink->outwardIssue = isset($array['outwardIssue']) ? $array['outwardIssue'] : null;

        return $issueLink;
    }

    public function id()
    {
        return (int) $this->id;
    }

    public function type()
    {
        return Type::fromArray($this->type);
    }

    public function inwardIssue()
    {
        if (!$this->inwardIssue) {
            return null;
        }

        return Issue::fromArray($this->inwardIssue);
    }

    public function outwardIssue()
    {
        if (!$this->outwardIssue) {
            return null;
        }

        return Issue::fromArray($this->outwardIssue);
    }

    public function isInward()
    {
        return !is_null($this->inwardIssue);
    }

    public function isOutward()
    {
        return !is_null($this->outwardIssue);
    }
}
