<?php

namespace Technodelight\Jira\Configuration;

class ApplicationConfiguration
{
    private $domain;
    private $username;
    private $password;
    private $instances;
    private $githubToken;
    private $maxBranchNameLength;
    private $aliases;
    private $transitions;
    private $filters;
    private $yesterdayAsWeekday;
    private $defaultWorklogTimestamp;
    private $cacheTtl;
    private $oneDay;
    private $tempo;

    public function domain()
    {
        try {
            return $this->instance('default')['domain'];
        } catch (\UnexpectedValueException $e) {
            return $this->domain;
        }
    }

    public function username()
    {
        try {
            return $this->instance('default')['username'];
        } catch (\UnexpectedValueException $e) {
            return $this->username;
        }
    }

    public function password()
    {
        try {
            return $this->instance('default')['password'];
        } catch (\UnexpectedValueException $e) {
            return $this->password;
        }
    }

    public function githubToken()
    {
        return $this->githubToken;
    }

    /**
     * @return int
     */
    public function maxBranchNameLength()
    {
        return $this->maxBranchNameLength;
    }

    /**
     * Tempo integration configs
     *
     * @return array
     */
    public function tempo()
    {
        return $this->tempo;
    }

    public function instances()
    {
        return $this->instances;
    }

    /**
     * @param string $instance
     * @return array
     * @throws \UnexpectedValueException
     */
    public function instance($instance)
    {
        if (!isset($this->instances[$instance])) {
            throw new \UnexpectedValueException(
                sprintf('No instance with name "%s" configured', $instance)
            );
        }

        return $this->instances[$instance];
    }

    public function yesterdayAsWeekday()
    {
        return $this->yesterdayAsWeekday;
    }

    public function defaultWorklogTimestamp()
    {
        return $this->defaultWorklogTimestamp;
    }

    public function oneDayAmount()
    {
        return $this->oneDay;
    }

    public function cacheTtl()
    {
        return $this->cacheTtl;
    }

    public function transitions()
    {
        return $this->transitions;
    }

    public function aliases()
    {
        return $this->aliases;
    }

    public function filters()
    {
        return $this->filters;
    }

    public static function fromSymfonyConfigArray(array $config)
    {
        $configuration = new self;
        $configuration->username = $config['credentials']['username'];
        $configuration->password = $config['credentials']['password'];
        $configuration->domain = $config['credentials']['domain'];
        $configuration->instances = self::useAttributeAsKey($config['instances'], 'name');
        $configuration->githubToken = $config['integrations']['github']['apiToken'];
        $configuration->maxBranchNameLength = $config['integrations']['git']['maxBranchNameLength'];
        $configuration->tempo = $config['integrations']['tempo'];
        $configuration->yesterdayAsWeekday = $config['project']['yesterdayAsWeekday'];
        $configuration->oneDay = $config['project']['oneDay'];
        $configuration->defaultWorklogTimestamp = $config['project']['defaultWorklogTimestamp'];
        $configuration->cacheTtl = $config['project']['cacheTtl'];
        $configuration->transitions = self::flattenArray($config['transitions'], 'command', 'transition');
        $configuration->aliases = self::flattenArray($config['aliases'], 'alias','issueKey');
        $configuration->filters = self::flattenArray($config['filters'], 'command', 'jql');

        return $configuration;
    }

    private static function flattenArray(array $array, $key, $valueKey)
    {
        $result = [];
        foreach ($array as $arr) {
            $result[$arr[$key]] = $arr[$valueKey];
        }
        return $result;
    }

    private static function useAttributeAsKey(array $array, $key)
    {
        $result = [];
        foreach ($array as $arr) {
            $result[$arr[$key]] = $arr;
        }
        return $result;
    }
}
