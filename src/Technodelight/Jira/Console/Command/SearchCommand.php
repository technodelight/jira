<?php

namespace Technodelight\Jira\Console\Command;

use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Technodelight\Jira\Api\Api;
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
                'save',
                's',
                InputOption::VALUE_OPTIONAL,
                'Save the query so it could be recalled as a command'
            )
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $filterName = $input->getOption('save') ?: false;

        if (false !== $filterName && is_null($filterName)) {
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $question = new Question('<info>Please specify a command name for this search</info> ');
            $filterName = $helper->ask($input, $output, $question);
            if (!$filterName) {
                throw new \InvalidArgumentException("Filter name must not be empty!");
            }
            $input->setOption('save', (new NameNormalizer($filterName))->normalize());
        }
        if (!is_null($filterName)) {
            $input->setOption('save', (new NameNormalizer($filterName))->normalize());
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $command = new IssueFilterCommand($this->container, 'run', $input->getArgument('jql') ?: null);
        $command->execute($input, $output);
        if ($filterName = $input->getOption('save')) {
            // perform saving of that filter!
            $output->writeln('here comes the --save ' . $filterName);
        }
    }
}
