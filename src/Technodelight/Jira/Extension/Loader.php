<?php

namespace Technodelight\Jira\Extension;

use FilesystemIterator;
use GlobIterator;
use Symfony\Component\Config\FileLocator;

class Loader
{
    /**
     * @param array $config unprocessed configurations?
     * @return ExtensionInterface[]
     */
    public function loadExtensions(array $config): array
    {
        $config = array_merge(['class' => [], 'paths' => []], $config);

        $extensionClasses = $config['class'];
        $extensionPaths = array_merge($config['paths'], [APPLICATION_ROOT_DIR . '/src/extensions']);

        $classMap = [];
        foreach ($extensionClasses as $extensionClass) {
            foreach ($extensionPaths as $path) {
                $iterator = new GlobIterator("$path/*/*", FilesystemIterator::CURRENT_AS_PATHNAME);
                foreach ($iterator as $extensionDir) {
                    $className = explode('\\', $extensionClass) . '.php';
                    $nameSpace = explode('\\', $extensionClass);
                    array_pop($nameSpace);
                    $nameSpacePath = join('/', $nameSpace);
                    $locator = new FileLocator([
                        "$extensionDir/",
                        "$extensionDir/src",
                        "$extensionDir/lib",
                        "$extensionDir/$nameSpacePath",
                        "$extensionDir/src/$nameSpacePath",
                        "$extensionDir/lib/$nameSpacePath",
                    ]);
                    $paths = $locator->locate($className);
                    $classMap[$extensionClass] = array_shift($paths);
                }
            }
        }

        $extensions = [];
        foreach ($classMap as $className => $path) {
            require_once $path;
            $extension = new $className;
            if ($extension instanceof ExtensionInterface) {
                $extensions[] = $extension;
            }
        }

        return $extensions;
    }
}
