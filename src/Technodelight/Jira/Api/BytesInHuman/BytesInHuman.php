<?php

namespace Technodelight\Jira\Api\BytesInHuman;

class BytesInHuman
{
    private $bytes = 0;

    public static function fromBytes($bytes)
    {
        $instance = new self;
        $instance->bytes = (int) $bytes;
        return $instance;
    }

    public function asString()
    {
        switch (true) {
            case $this->bytes >= 1000 && $this->bytes < 1000000:
                return sprintf('%.2fK', $this->bytes / 1000);
            case $this->bytes >= 1000000 && $this->bytes < 1000000000:
                return sprintf('%.2fM', $this->bytes / 1000000);
            case $this->bytes > 1000000000:
                return sprintf('%.2fG', $this->bytes / 1000000000);
            default:
                return sprintf('%dB', $this->bytes);
        }
    }

    public function __toString()
    {
        return $this->asString();
    }

    protected function __construct() {}
}
