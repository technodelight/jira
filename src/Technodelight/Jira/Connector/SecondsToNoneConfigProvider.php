<?php

namespace Technodelight\Jira\Connector;

use Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\SecondsToNone;
use Technodelight\SecondsToNone\Config;

class SecondsToNoneConfigProvider
{
    const PATTERN = '%d%s';

    private $oneDayAmount;

    private function __construct(ApplicationConfiguration $config)
    {
        $this->oneDayAmount = $config->oneDayAmount();
    }

    public static function build(ApplicationConfiguration $config)
    {
        $instance = new self($config);
        return $instance->buildConfig();
    }

    private function buildConfig()
    {
        return new Config($this->buildMapping(), self::PATTERN);
    }

    private function calculateOneDay()
    {
        if (is_numeric($this->oneDayAmount)) {
            $amount = (int) $this->oneDayAmount;
            if ($amount < 24) {
                return $amount * 3600;
            }
            return $amount;
        }
        return (new SecondsToNone())->humanToSeconds($this->oneDayAmount);
    }

    /**
     * @return array
     */
    private function buildMapping()
    {
        return [
            'd' => $this->calculateOneDay(),
            'h' => 3600,
            'm' => 60,
            's' => 1,
            'none' => 0,
        ];
    }
}
