<?php

namespace Technodelight\Jira\Renderer\Action;

class Error implements Result
{
    /**
     * @var \Exception
     */
    protected $exception;
    /**
     * @var string
     */
    protected $phrase;
    /**
     * @var array
     */
    protected $data = [];

    public static function fromExceptionAndPhrase(\Exception $exception, $phrase, ...$data)
    {
        $instance = new self;
        $instance->exception = $exception;
        $instance->phrase = $phrase;
        $instance->data = $data;

        return $instance;
    }

    /**
     * @return \Exception
     */
    public function exception(): \Exception
    {
        return $this->exception;
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
