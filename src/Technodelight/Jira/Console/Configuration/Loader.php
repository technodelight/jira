<?php

namespace Technodelight\Jira\Console\Configuration;

use CallbackFilterIterator;
use FilesystemIterator;
use GlobIterator;
use SplFileInfo;
use Symfony\Component\Yaml\Yaml;

class Loader
{
    public function load(array $directories): array
    {
        $configs = [];
        foreach ($directories as $directory) {
            $iterator = new CallbackFilterIterator(
                new GlobIterator("$directory/.*", FilesystemIterator::CURRENT_AS_FILEINFO),
                function (SplFileInfo $fileInfo) {
                    return $fileInfo->getFilename() === '.jira.yml' && $fileInfo->isFile();
                }
            );

            $config = array_map(
                [$this, 'loadConfigurationYaml'],
                iterator_to_array($iterator)
            );
            foreach ($config as $conf) {
                $configs[] = $conf;
            }
        }

        return $configs;
    }

    private function loadConfigurationYaml(SplFileInfo $splFileInfo): array
    {
        if ($splFileInfo->isReadable() === false && $splFileInfo->getRealPath() !== false) {
            throw FilePrivilegeErrorException::fromUnreadablePath($splFileInfo->getPathname());
        }

        $perms = substr(sprintf('%o', $splFileInfo->getPerms()), -4);
        if ($perms !== '0600') {
            throw FilePrivilegeErrorException::fromInvalidPermAndPath(
                $splFileInfo->getPerms(), $splFileInfo->getPathname()
            );
        }

        return Yaml::parse(file_get_contents($splFileInfo->getRealPath()));
    }
}
