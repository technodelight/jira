<?php

namespace Technodelight\Jira\Configuration\Symfony;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

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
                array_merge(
                    [
                        $this->filenameProvider->projectFile(),
                        $this->filenameProvider->userFile(),
                    ],
                    $this->filenameProvider->moduleFiles()
                )
            )
        );
    }

    /**
     * @param string[] $filePaths
     * @return string[]
     */
    private function loadValidConfigurationYamls(array $filePaths)
    {
        $yamls = [];
        foreach ($filePaths as $path) {
            $yamls[]= $this->loadConfigurationYaml($path);
        }

        return array_filter($yamls);
    }

    /**
     * @param string $path
     * @param bool $isRequired
     * @return mixed|null
     */
    private function loadConfigurationYaml($path)
    {
        if (!is_readable($path)) {
            throw FilePriviledgeErrorException::fromUnreadablePath($path);
        }

        $perms = substr(sprintf('%o', fileperms($path)), -4);
        if ($perms !== '0600') {
            throw FilePriviledgeErrorException::fromInvalidPermAndPath($perms, $path);
        }

        return Yaml::parse(file_get_contents($path));
    }
}
