<?php

namespace Technodelight\Jira\Connector\SymfonyConfig;

use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;
use Symfony\Component\Config\Definition\NodeInterface;

class ConfigurationDumper
{
    private $globalProps = ['credentials', 'integrations'];

    /**
     * @param string $path
     * @param bool $isGlobal
     * @return string
     * @throws \ErrorException
     */
    public function dump($path, $isGlobal)
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
        /** @var ArrayNode $config */
        $config = $configuration->getConfigTreeBuilder()->buildTree();
        $written = 0;
        $referenceDumper = new YamlReferenceDumper;
        file_put_contents($path, '');
        foreach ($config->getChildren() as $child) {
            /** @var $child NodeInterface */
            if ($isGlobal && !in_array($child->getName(), $this->globalProps)) {
                continue;
            }
            $written+= (int) file_put_contents($path, $referenceDumper->dumpNode($child), FILE_APPEND);
        }
        return $written;
    }
}
