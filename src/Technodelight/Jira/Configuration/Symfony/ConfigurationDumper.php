<?php

namespace Technodelight\Jira\Configuration\Symfony;

use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;

class ConfigurationDumper
{
    private $filenameProvider;
    private $globalProps = ['credentials', 'integrations', 'project'];

    public function __construct(FilenameProvider $filenameProvider)
    {
        $this->filenameProvider = $filenameProvider;
    }

    public function dumpLocal()
    {
        return $this->dump($this->filenameProvider->localFile(), false);
    }

    public function dumpGlobal()
    {
        return $this->dump($this->filenameProvider->globalFile(), true);
    }

    private function dump($path, $isGlobal)
    {
        if (is_file($path)) {
            throw new \ErrorException('File already exists: ' . $path);
        }
        if (!$this->putContents($path, $isGlobal)) {
            throw new \ErrorException('Cannot write file ' . $path);
        }
        if (!chmod($path, 0600)) {
            throw new \ErrorException('Cannot change file perms on ' . $path);
        }

        return $path;
    }

    private function putContents($path, $isGlobal)
    {
        $configuration = new Configuration;
        $config = $configuration->getConfigTreeBuilder()->buildTree();
        $written = 0;
        foreach ($config->getChildren() as $child) {
            if ($isGlobal && !in_array($child->getName(), $this->globalProps)) {
                continue;
            }
            $written+= (int) file_put_contents($path, (new YamlReferenceDumper)->dumpAtPath($configuration, $child->getName()), FILE_APPEND);
        }
        return $written;
    }
}
