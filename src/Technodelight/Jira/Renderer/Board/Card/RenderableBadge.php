<?php

namespace Technodelight\Jira\Renderer\Board\Card;

class RenderableBadge
{
    const VERSION = 1;
    const LABEL = 2;
    /**
     * @var string
     */
    private $badge;
    /**
     * @var string
     */
    private $type;

    public static function fromStringAndType($badge, $type)
    {
        $instance = new self;
        $instance->badge = $badge;
        $instance->type = $type;

        return $instance;
    }

    public function __toString()
    {
        return ' ' . $this->badge . ' ';
    }

    public function style()
    {
        if ($this->type == self::VERSION) {
            return 'bg=red;fg=white';
        }

        return 'bg=yellow;fg=white';
    }
}
