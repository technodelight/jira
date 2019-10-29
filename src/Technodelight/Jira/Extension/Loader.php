<?php

namespace Technodelight\Jira\Extension;

class Loader
{
    public function load($classMap)
    {
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
