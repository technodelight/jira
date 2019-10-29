<?php

namespace Technodelight\Jira\Extension;

use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;

class Locator
{
    /**
     * @param array $config unprocessed configurations?
     * @return ExtensionInterface[]
     */
    public function locate(array $config): array
    {
        $config = array_merge(['class' => [], 'paths' => []], $config);

        $extensionClasses = $config['class'];
        $extensionPaths = array_merge($config['paths'], [APPLICATION_ROOT_DIR]);

        $classMap = [];
        foreach ($extensionClasses as $extensionClass) {
            $parts = explode('\\', $extensionClass);
            $nameSpace = $parts;
            $className = array_pop($nameSpace) . '.php';
            $nameSpacePath = join('/', $nameSpace);

            foreach ($extensionPaths as $extensionPath) {
                $locator = new FileLocator([
                    "$extensionPath/",
                    "$extensionPath/src",
                    "$extensionPath/lib",
                    "$extensionPath/$nameSpacePath",
                    "$extensionPath/src/$nameSpacePath",
                    "$extensionPath/lib/$nameSpacePath",
                    "~/.composer/vendor/*/*/src",
                    "~/.composer/vendor/*/*/lib",
                    "~/.composer/vendor/*/*/$nameSpacePath",
                    "~/.composer/vendor/*/*/src/$nameSpacePath",
                    "~/.composer/vendor/*/*/lib/$nameSpacePath",
                ]);

                try {
                    $paths = (array) $locator->locate($className);
                    $classMap[$extensionClass] = array_shift($paths);
                } catch (FileLocatorFileNotFoundException $e) {
                    // nothing to do here?
                }
            }
        }

        return $classMap;
    }
}
