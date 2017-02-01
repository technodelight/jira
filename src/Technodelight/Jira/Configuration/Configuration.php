<?php

namespace Technodelight\Jira\Configuration;

use \UnexpectedValueException;

class Configuration
{
    const CONFIG_FILENAME = 'jira.ini';

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $githubToken;

    /**
     * @var string
     */
    private $domain;

    /**
     * @var string
     */
    private $project;

    /**
     * @var array
     */
    private $aliases;

    /**
     * @var mixed
     */
    private $yesterdayAsFriday;

    /**
     * @var array
     */
    private $transitions = [
        'pick' => 'Picked up by dev',
    ];

    /**
     * @var array
     */
    private $filters = [
        'todo' => 'project = {{ project }} and status = Open',
    ];

    protected function __construct(array $ini = [])
    {
        $this->username = $this->parseIniField($ini, 'username');
        $this->password = $this->parseIniField($ini, 'password');
        $this->githubToken = $this->parseIniField($ini, 'github-token');
        $this->domain = $this->parseIniField($ini, 'domain');
        $this->project = $this->parseIniField($ini, 'project');
        $this->transitions = $this->parseIniField($ini, 'transitions');
        $this->aliases = $this->parseIniField($ini, 'aliases');
        $this->yesterdayAsFriday = $this->parseIniField($ini, 'yesterday-as-friday');

        if ($transitions = $this->parseIniField($ini, 'transitions')) {
            $this->transitions = $transitions + $this->transitions;
        }
        if ($filters = $this->parseIniField($ini, 'filters')) {
            $this->filters = $filters + $this->filters;
        }
    }

    public static function initFromDirectory($iniFilePath)
    {
        return new self(self::parseIniFile($iniFilePath));
    }

    public function username()
    {
        return $this->username;
    }

    public function password()
    {
        return $this->password;
    }

    public function githubToken()
    {
        return $this->githubToken;
    }

    public function domain()
    {
        return $this->domain;
    }

    public function project()
    {
        return $this->project;
    }

    public function transitions()
    {
        return $this->transitions;
    }

    public function filters()
    {
        return $this->filters;
    }

    public function aliases()
    {
        if (!is_array($this->aliases)) {
            $this->aliases = [];
        }

        return $this->aliases;
    }

    public function yesterdayAsFriday()
    {
        return in_array(strtolower($this->yesterdayAsFriday), ['on', 'true', '1']);
    }

    public function merge(Configuration $configuration)
    {
        $fields = array_keys(get_object_vars($this));
        foreach ($fields as $field) {
            if ($value = $configuration->$field()) {
                $this->$field = $value;
            }
        }
    }

    protected function parseIniField(array $ini, $field)
    {
        if (!empty($ini[$field])) {
            if (is_array($ini[$field])) {
                foreach ($ini[$field] as $subField => $value) {
                    $ini[$field][$subField] = $this->parseIniField($ini[$field], $subField);
                }
                return $ini[$field];
            }

            return json_decode($ini[$field], true) ?: $ini[$field];
        }
    }

    protected static function parseIniFile($iniFilePath)
    {
        $iniFile = $iniFilePath . DIRECTORY_SEPARATOR . self::CONFIG_FILENAME;
        if (!is_file($iniFile)) {
            throw new UnexpectedValueException('No configuration found!');
        }

        if (!is_readable($iniFile)) {
            throw new UnexpectedValueException('Cannot read configuration!');
        }

        $perms = substr(sprintf('%o', fileperms($iniFile)), -4);
        if ($perms !== '0600') {
            throw new UnexpectedValueException(
                sprintf('Configuration cannot be readable by others! %s should be 0600)', $perms)
            );
        }

        return parse_ini_file($iniFile, true);
    }
}
