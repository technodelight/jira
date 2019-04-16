<?php

namespace Technodelight\Jira\Renderer\Action;

class Success implements Result
{
    /**
     * @var string
     */
    protected $phrase;
    /**
     * @var array
     */
    protected $data;

    public static function fromPhrase($phrase, ...$data)
    {
        $instance = new self;
        $instance->phrase = $phrase;
        $instance->data = $data;

        return $instance;
    }

    /**
     * @return string
     */
    public function phrase(): string
    {
        return $this->phrase;
    }

    /**
     * @return array
     */
    public function data(): array
    {
        return $this->data;
    }
}
