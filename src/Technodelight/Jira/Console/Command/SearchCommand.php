<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\Dumper\YamlDumper;
use Symfony\Component\Yaml\Yaml;
use Technodelight\Jira\Configuration\Symfony\Configuration;
use Technodelight\Jira\Console\Argument\NameNormalizer;

class SearchCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('search')
            ->setDescription('Search in Jira using JQL')
            ->addArgument(
                'jql',
                InputArgument::REQUIRED,
                'The JQL query'
            )
            ->addOption(
                'dump-config',
                'd',
                InputOption::VALUE_NONE,
                'Dump the query as yaml configuration for quicker config updates'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $command = new IssueFilterCommand($this->container, 'run_' . md5(microtime(true)), $input->getArgument('jql') ?: null);
        $command->execute($input, $output);
        if ($input->getOption('dump-config')) {
            $this->dumpFilterConfiguration($output, '<insert your preferred filter command here>', $input->getArgument('jql'));
        }
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param $filterName
     */
    protected function dumpFilterConfiguration(OutputInterface $output, $filterName, $jql)
    {
        // perform saving of that filter!
        $output->writeln('You can add the following filter to your configuration yaml file:');
        $output->writeln('');

        $configuration = new Configuration;
        /** @var \Symfony\Component\Config\Definition\ArrayNode $config */
        $config = $configuration->getConfigTreeBuilder()->buildTree();
        $referenceDumper = new YamlReferenceDumper;

        foreach ($config->getChildren() as $child) {
            /** @var $child \Symfony\Component\Config\Definition\NodeInterface */
            if ($child->getName() == 'filters') {
                $value = $child->normalize([['command' => $filterName, 'jql' => $jql]]);
                $output->writeln([
                    'filters:',
                    $this->templateHelper()->tabulate(Yaml::dump($child->finalize($value)))
                ]);
            }
        }
    }

    /**
     * @return \Technodelight\Jira\Helper\TemplateHelper
     */
    private function templateHelper()
    {
        return $this->getService('technodelight.jira.template_helper');
    }
}
