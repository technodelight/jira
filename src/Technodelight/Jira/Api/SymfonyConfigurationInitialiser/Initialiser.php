<?php

namespace Technodelight\Jira\Api\SymfonyConfigurationInitialiser;

use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\BaseNode;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\PrototypedArrayNode;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class Initialiser
{
    /** @var ArrayNode */
    private $root;
    /** @var QuestionHelper */
    private $q;
    private $data = [];

    /**
     * @param \Symfony\Component\Config\Definition\ConfigurationInterface $configuration
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function init(ConfigurationInterface $configuration, InputInterface $input, OutputInterface $output)
    {
        $this->q = new QuestionHelper();
        /** @var ArrayNode $root */
        $root = $configuration->getConfigTreeBuilder()->buildTree();
        $this->root = $root;
        foreach ($root->getChildren() as $child) {
            $this->readValue($child, $root, $input, $output, 1);
        }

        $data = [];
        foreach ($this->data as $item) {
            foreach ($item as $path => $value) {
                $this->setNestedArrayValue($data, $path, $value, '.');
            }
        }
        unset($this->data);

        return $data;
    }

    private function readValue(BaseNode $node, BaseNode $parent, InputInterface $input, OutputInterface $output, $level)
    {
        $this->nodeInfo($node, $output, $level);
        if ($node->getAttribute('deprecated', false)) { // skip intialising deprecated values
            return;
        }

        if ($node instanceof PrototypedArrayNode) {
            $output->writeln('<info>' . $this->nodePath($node) . ' is prototyped configuration</info>');
            if ($this->confirm('Do you want to configure items now?', $input, $output)) {
                $proto = $node->getPrototype();
                $i = 0;
                do {
                    $proto->setName($i++);
                    $this->readValue($node->getPrototype(), $node, $input, $output, $level + 1);
                } while($this->confirm('Do you want to add another configuration?', $input, $output));
            }
            //read value until quit
        } else if ($node instanceof ArrayNode) {
            foreach ($node->getChildren() as $child) {
                $this->readValue($child, $parent, $input, $output, $level + 1);
            }
        } else {
            // must be the remaining stuff, read value
            $this->data[] = [$this->nodePath($node) => $this->readline($node, $input, $output)];
        }
    }

    /**
     * @param \Symfony\Component\Config\Definition\BaseNode $node
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    private function nodeInfo(BaseNode $node, OutputInterface $output, $level)
    {
        if ($level == 1) {
            // show bigger block
            $output->writeln('<bg=yellow;fg=black>' . str_repeat(' ', strlen($node->getName()) + 2) . '</>');
            $output->writeln('<bg=yellow;fg=black> ' . $node->getName() . ' </>');
            $output->writeln('<bg=yellow;fg=black>' . str_repeat(' ', strlen($node->getName()) + 2) . '</>');
            $output->writeln('');
            $output->writeln($node->getInfo());
        } else {
            $output->writeln(sprintf('<comment>%s</comment> %s', $this->nodePath($node), $node->getInfo()));
        }
        if ($node->getExample()) {
            $output->writeln('  Example: ' . $node->getExample());
        }
    }

    private function nodePath(BaseNode $node)
    {
        return substr($node->getPath(), strlen($this->root->getPath()) + 1);
    }

    /**
     * @param \Symfony\Component\Config\Definition\BaseNode $node
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return mixed
     */
    private function readline(BaseNode $node, InputInterface $input, OutputInterface $output)
    {
        if ($node->hasDefaultValue()) {
            $q = new Question('Please enter a value for ' . get_class($node) . ' (' . $node->getDefaultValue() . '): ', $node->getDefaultValue());
        } else {
            $q = new Question('Please enter a value for ' . get_class($node) . ': ');
        }
        if ($node->getName() == 'password') {
            $q->setHidden(true);
        }
        return $this->q->ask($input, $output, $q);
    }

    private function confirm($confirm, InputInterface $input, OutputInterface $output)
    {
        return $this->q->ask($input, $output, new ConfirmationQuestion(PHP_EOL . $confirm . ' [Yn]'));
    }

    private function setNestedArrayValue(&$array, $path, &$value, $delimiter = '/') {
        $pathParts = explode($delimiter, $path);

        $current = &$array;
        foreach($pathParts as $key) {
            $current = &$current[$key];
        }

        $backup = $current;
        $current = $value;

        return $backup;
    }
}
