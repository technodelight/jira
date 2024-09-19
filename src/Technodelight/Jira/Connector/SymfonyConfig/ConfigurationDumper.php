<?php

namespace Technodelight\Jira\Connector\SymfonyConfig;

use ErrorException;
use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;
use Symfony\Component\Config\Definition\NodeInterface;
use Technodelight\Jira\Configuration\Configuration;
use Technodelight\Jira\Configuration\Configuration\TreeBuilderFactory;

class ConfigurationDumper
{
    private const GLOBAL_PROPS = ['integrations', 'instances'];

    public function __construct(private readonly TreeBuilderFactory $treeBuilderFactory)
    {}

    /**
     * @param string $path
     * @param bool $isGlobal
     * @return string[]
     * @throws ErrorException
     */
    public function dump(string $path, bool $isGlobal): array
    {
        if (is_file($path)) {
            throw new ErrorException('File already exists: ' . $path);
        }
        [$writtenFiles, $writtenData] = $this->putContents($path, $isGlobal);
        if (!$writtenData) {
            throw new ErrorException('Cannot write file ' . $path);
        }

        return $writtenFiles;
    }

    private function putContents(string $path, bool $isGlobal): array
    {
        $configuration = new Configuration($this->treeBuilderFactory);
        /** @var ArrayNode $config */
        $config = $configuration->getConfigTreeBuilder()->buildTree();
        $writtenFiles = [$path];
        $written = 0;
        $referenceDumper = new YamlReferenceDumper;
        file_put_contents($path, '');
        // write imports for global props
        if ($isGlobal) {
            file_put_contents($path, 'imports:' . PHP_EOL, FILE_APPEND);
            foreach ($config->getChildren() as $child) {
                if (in_array($child->getName(), self::GLOBAL_PROPS, true)) {
                    file_put_contents(
                        $path,
                        sprintf(
                            '    - { resource: %s }' . PHP_EOL,
                            $this->childConfigFilename($child)
                        ),
                        FILE_APPEND
                    );
                }
            }
        }
        foreach ($config->getChildren() as $child) {
            // skip config nodes otherwise included in the global config file
            if (!$isGlobal && in_array($child->getName(), self::GLOBAL_PROPS, true)) {
                continue;
            }
            // write global configs into their respectable config files
            if ($isGlobal && in_array($child->getName(), self::GLOBAL_PROPS, true)) {
                $childConfigFile = $this->fullPathForChildConfigFile($path, $child);
                $written+= file_put_contents(
                    $childConfigFile,
                    $referenceDumper->dumpNode($child),
                    FILE_APPEND
                );
                $writtenFiles[] = $childConfigFile;
            }
            $written+= (int) file_put_contents($path, $referenceDumper->dumpNode($child), FILE_APPEND);
        }
        return [$writtenFiles, $written];
    }

    private function fullPathForChildConfigFile(string $path, NodeInterface $child): string
    {
        return dirname($path) . DIRECTORY_SEPARATOR . $this->childConfigFilename($child);
    }

    private function childConfigFilename(NodeInterface $child): string
    {
        return '.jira.' . $child->getName() . '.yml';
    }
}
