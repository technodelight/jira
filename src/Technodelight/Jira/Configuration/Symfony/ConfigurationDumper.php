<?php

namespace Technodelight\Jira\Configuration\Symfony;

use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;
use Technodelight\Jira\Configuration\Symfony\Configuration;

class ConfigurationDumper
{
    private $filenameProvider;

    public function __construct($filenameProvider)
    {
        $this->filenameProvider = $filenameProvider;
    }

    public function dumpLocal()
    {
        return $this->dump($this->filenameProvider->localFile());
    }

    public function dumpGlobal()
    {
        return $this->dump($this->filenameProvider->globalFile());
    }

    private function dump($path)
    {
        if (is_file($path)) {
            throw new \ErrorException('File already exists: ' . $path);
        }
        if (!$this->putContents($path)) {
            throw new \ErrorException('Cannot write file ' . $path);
        }
        if (!chmod($path, 0600)) {
            throw new \ErrorException('Cannot change file perms on ' . $path);
        }

        return $path;
    }

    private function putContents($path)
    {
        $configuration = new Configuration;
        $config = $configuration->getConfigTreeBuilder()->buildTree();
        $written = 0;
        foreach ($config->getChildren() as $child) {
            $written+= (int) file_put_contents($path, (new YamlReferenceDumper)->dumpAtPath($configuration, $child->getName()), FILE_APPEND);
        }
        return $written;
    }
}
