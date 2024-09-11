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

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    private function loadConfigurationYaml(SplFileInfo $splFileInfo): array
    {
        // there can be multiple config files and we must track it by absolute path
        static $noticeTriggered = [];

        $absFilePath = $splFileInfo->getPathname() . DIRECTORY_SEPARATOR . $splFileInfo->getFilename();
        if ($splFileInfo->isReadable() === false && $splFileInfo->getRealPath() !== false) {
            throw FilePrivilegeErrorException::fromUnreadablePath(
                $absFilePath
            );
        }

        return $this->handleImports(Yaml::parse(file_get_contents($splFileInfo->getRealPath())), $splFileInfo->getRealPath());
    }

    private function handleImports(array $rawConfig, string $parentPath): array
    {
        if (!empty($rawConfig['imports'])) {
            $imports = $rawConfig['imports'];
            foreach ($imports as $importDef) {
                $iterator = new GlobIterator(dirname($parentPath) . DIRECTORY_SEPARATOR . $importDef['resource'], FilesystemIterator::CURRENT_AS_FILEINFO);
                foreach ($iterator as $fileInfo) {
                    $rawConfig = array_merge_recursive($rawConfig, $this->loadConfigurationYaml($fileInfo));
                }
            }
            unset($rawConfig['imports']);
        }

        return $rawConfig;
    }
}
