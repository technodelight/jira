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
use Symfony\Component\Config\Definition\ConfigurationInterface as Configuration;
use Technodelight\Jira\Console\Command\IssueRendererAware;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Template\IssueRenderer;

class Search extends Command implements IssueRendererAware
{
    public function __construct(
        private readonly Api $api,
        private readonly IssueRenderer $renderer,
        private readonly TemplateHelper $templateHelper,
        private readonly OpenApp $openApp,
        private readonly CurrentInstanceProvider $instanceProvider,
        private readonly Configuration $configuration
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('search')
            ->setDescription('Search in Jira using JQL')
            ->setHelp('Search using JQL.' . PHP_EOL
                . 'See advanced search help at '
                . 'https://confluence.atlassian.com/jiracorecloud/advanced-searching-765593707.html'
            )
            ->addArgument(
                'jql',
                InputArgument::REQUIRED,
                'The JQL query'
            )
            ->addOption(
                'dump-config',
                'c',
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // open in browser instead
        if ($input->getOption('open')) {
            $this->openApp->open(
                sprintf(
                    'https://%s/issues/?jql=%s',
                    $this->instanceProvider->currentInstance()->domain(),
                    urlencode($input->getArgument('jql'))
                )
            );
            return self::SUCCESS;
        }

        // render query results in console
        $command = new IssueFilter('run_' . md5(microtime(true)), $input->getArgument('jql') ?: null);
        $command->setIssueRenderer($this->renderer);
        $command->setJiraApi($this->api);
        $command->execute($input, $output);

        if ($input->getOption('dump-config')) {
            $this->dumpFilterConfiguration(
                $output,
                '<filter command name>',
                $input->getArgument('jql')
            );
        }

        return self::SUCCESS;
    }

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    protected function dumpFilterConfiguration(OutputInterface $output, string $filterName, string $jql): void
    {
        // perform saving of that filter!
        $output->writeln('You can add the following filter to your configuration yaml file:');
        $output->writeln('');

        /** @var ArrayNode $config */
        $config = $this->configuration->getConfigTreeBuilder()->buildTree();

        foreach ($config->getChildren() as $child) {
            /** @var $child NodeInterface */
            if ($child->getName() === 'filters') {
                $value = $child->normalize([['command' => $filterName, 'jql' => $jql]]);
                $output->writeln([
                    'filters:',
                    $this->templateHelper->tabulate(Yaml::dump($child->finalize($value)))
                ]);
            }
        }
    }
}
