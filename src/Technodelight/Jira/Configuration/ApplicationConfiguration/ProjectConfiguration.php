<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration;

use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;

class ProjectConfiguration implements RegistrableConfiguration
{
    private $yesterdayAsWeekday;
    private $defaultWorklogTimestamp;
    private $oneDay;
    private $cacheTtl;
    /**
     * @var array
     */
    private $config;

    public static function fromArray(array $config)
    {
        $instance = new self;
        $instance->config = $config;
        $instance->yesterdayAsWeekday = $config['yesterdayAsWeekday'];
        $instance->defaultWorklogTimestamp = $config['defaultWorklogTimestamp'];
        $instance->oneDay = $config['oneDay'];
        $instance->cacheTtl = $config['cacheTtl'];

        return $instance;
    }

    /**
     * @return bool
     */
    public function yesterdayAsWeekday()
    {
        return $this->yesterdayAsWeekday;
    }

    /**
     * @return string
     */
    public function defaultWorklogTimestamp()
    {
        return $this->defaultWorklogTimestamp;
    }

    /**
     * @return string|int
     */
    public function oneDayAmount()
    {
        return $this->oneDay;
    }

    /**
     * @return int
     */
    public function cacheTtl()
    {
        return $this->cacheTtl;
    }

    public function servicePrefix(): string
    {
        return 'project';
    }

    /**
     * @return array
     */
    public function configAsArray(): array
    {
        return $this->config;
    }

    private function __construct()
    {
    }
}
