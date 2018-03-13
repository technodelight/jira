<?php

namespace Technodelight\Jira\Domain\IssueLink;

class Type
{
    private $id;
    private $name;
    private $inward;
    private $outward;
    public static function fromArray(array $array)
    {
        $type = new Type;
        $type->id = $array['id'];
        $type->name = $array['name'];
        $type->inward = $array['inward'];
        $type->outward = $array['outward'];
        return $type;
    }

    public function id()
    {
        return (int) $this->id;
    }

    public function name()
    {
        return (string) $this->name;
    }

    public function inward()
    {
        return (string) $this->inward;
    }

    public function outward()
    {
        return (string) $this->outward;
    }

    private function __construct()
    {
    }
}
