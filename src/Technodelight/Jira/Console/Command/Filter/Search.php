<?php

namespace Technodelight\Jira\Console\Command\Filter;

use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\NodeInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\CliOpen\CliOpen as OpenApp;
use Technodelight\Jira\Configuration\ApplicationConfiguration\CurrentInstanceProvider;
use Technodelight\Jira\Configuration\Configuration;
use Technodelight\Jira\Console\Command\IssueRendererAware;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Template\IssueRenderer;

class Search extends Command implements IssueRendererAware
{
    /**
     * @var Api
     */
    private $api;
    /**
     * @var IssueRenderer
     */
    private $renderer;
    /**
     * @var TemplateHelper
     */
    private $templateHelper;
    /**
     * @var OpenApp
     */
    private $openApp;
    /**
     * @var Configuration
     */
    private $configuration;
    /**
     * @var CurrentInstanceProvider
     */
    private $currentInstanceProvider;

    public function __construct(
        Api $api,
        IssueRenderer $renderer,
        TemplateHelper $templateHelper,
        OpenApp $openApp,
        CurrentInstanceProvider $currentInstanceProvider,
        Configuration $configuration
    )
    {
        $this->api = $api;
        $this->renderer = $renderer;
        $this->templateHelper = $templateHelper;
        $this->openApp = $openApp;
        $this->currentInstanceProvider = $currentInstanceProvider;
        $this->configuration = $configuration;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('search')
            ->setDescription('Search in Jira using JQL')
            ->setHelp('Search using JQL.' . PHP_EOL . 'See advanced search help at https://confluence.atlassian.com/jiracorecloud/advanced-searching-765593707.html')
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
            ->addOption(
                'open',
                'o',
                InputOption::VALUE_NONE,
                'Open search in browser instead'
            )
            ->addOption(
                'page',
                'p',
                InputOption::VALUE_REQUIRED,
                'Page',
                null
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // open in browser instead
        if ($input->getOption('open')) {
            $this->openApp->open(
                sprintf(
                    'https://%s/issues/?jql=%s',
                    $this->currentInstanceProvider->currentInstance()->domain(),
                    urlencode($input->getArgument('jql'))
                )
            );
            return;
        }

        // render query results in console
        $command = new IssueFilter('run_' . md5(microtime(true)), $input->getArgument('jql') ?: null);
        $command->setIssueRenderer($this->renderer);
        $command->setJiraApi($this->api);
        $command->execute($input, $output);

        if ($input->getOption('dump-config')) {
            $this->dumpFilterConfiguration($output, '<insert your preferred filter command here>', $input->getArgument('jql'));
        }
    }

    /**
     * @param OutputInterface $output
     * @param string $filterName
     * @param string $jql
     */
    protected function dumpFilterConfiguration(OutputInterface $output, $filterName, $jql)
    {
        // perform saving of that filter!
        $output->writeln('You can add the following filter to your configuration yaml file:');
        $output->writeln('');

        /** @var ArrayNode $config */
        $config = $this->configuration->getConfigTreeBuilder()->buildTree();

        foreach ($config->getChildren() as $child) {
            /** @var $child NodeInterface */
            if ($child->getName() == 'filters') {
                $value = $child->normalize([['command' => $filterName, 'jql' => $jql]]);
                $output->writeln([
                    'filters:',
                    $this->templateHelper->tabulate(Yaml::dump($child->finalize($value)))
                ]);
            }
        }
    }
}
