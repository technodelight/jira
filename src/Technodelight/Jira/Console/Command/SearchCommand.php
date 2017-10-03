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
                InputOption::VALUE_OPTIONAL,
                'Save the query to the project jira.yml file so it could be used as a command'
            )
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $filterName = $input->getOption('dump-config') ?: false;

        if (!$filterName) {
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $question = new Question('<info>Please specify a command name for this search</info> ');
            $filterName = $helper->ask($input, $output, $question);
            if (!$filterName) {
                throw new \InvalidArgumentException("Filter name must not be empty!");
            }
            $input->setOption('dump-config', (new NameNormalizer($filterName))->normalize());
        }
        if (!is_null($filterName)) {
            $input->setOption('dump-config', (new NameNormalizer($filterName))->normalize());
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $command = new IssueFilterCommand($this->container, 'run', $input->getArgument('jql') ?: null);
        $command->execute($input, $output);
        if ($filterName = $input->getOption('dump-config')) {
            $this->dumpFilterConfiguration($output, $filterName, $input->getArgument('jql'));
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
