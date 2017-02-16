<?php

namespace Technodelight\Jira\Configuration\Symfony;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;
use Technodelight\Jira\Configuration\Symfony\Configuration;

class ConfigurationLoader
{
    private $filenameProvider;

    public function __construct(FilenameProvider $filenameProvider)
    {
        $this->filenameProvider = $filenameProvider;
    }

    public function load()
    {
        return (new Processor)->processConfiguration(
            new Configuration,
            $this->loadValidConfigurationYamls(
                [
                    $this->filenameProvider->localFile() => true,
                    $this->filenameProvider->globalFile() => false,
                ]
            )
        );
    }

    private function loadValidConfigurationYamls(array $filePaths)
    {
        $yamls = [];
        foreach ($filePaths as $path => $isRequired) {
            $yamls[]= $this->loadConfigurationYaml($path, $isRequired);
        }

        return array_filter($yamls);
    }

    private function loadConfigurationYaml($path, $isRequired = false)
    {
        if (!is_file($path)) {
            if ($isRequired) {
                throw MissingConfigurationException::fromPath($path);
            }
            return;
        }

        if (!is_readable($path)) {
            if ($isRequired) {
                throw FilePriviledgeErrorException::fromUnreadablePath($path);
            }
            return;
        }

        $perms = substr(sprintf('%o', fileperms($path)), -4);
        if ($perms !== '0600') {
            throw FilePriviledgeErrorException::fromInvalidPermAndPath($perms, $path);
        }

        return Yaml::parse(file_get_contents($path));
    }
}
