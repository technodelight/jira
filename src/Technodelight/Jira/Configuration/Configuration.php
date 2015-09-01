<?php

namespace Technodelight\Jira\Configuration;

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
    private $domain;

    /**
     * @var string
     */
    private $project;

    protected function __construct(array $ini = [])
    {
        $this->username = $this->setIniField($ini, 'username');
        $this->password = $this->setIniField($ini, 'password');
        $this->domain = $this->setIniField($ini, 'domain');
        $this->project = $this->setIniField($ini, 'project');
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

    public function domain()
    {
        return $this->domain;
    }

    public function project()
    {
        return $this->project;
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

    protected function setIniField(array $ini, $field)
    {
        if (!empty($ini[$field])) {
            return $ini[$field];
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

        return parse_ini_file($iniFile);
    }
}
