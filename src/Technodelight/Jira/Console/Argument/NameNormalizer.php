<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\Argument;

class NameNormalizer
{
    private const PATTERNS = [
        '~[^a-z0-9]+~i' => '-',
        '/(?<!^)[A-Z]/' => '-$0',
        '~[-]+~' => '-'
    ];
    private string $name;

    public function __construct(?string $name)
    {
        $this->name = $name ?? '';
    }

    public function normalize(): string
    {
        $str = $this->name;
        foreach (self::PATTERNS as $pattern => $replacement) {
            $str = preg_replace($pattern, $replacement, $str);
        }

        return strtolower(trim($this->name, '-'));
    }
}
