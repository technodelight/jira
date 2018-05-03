<?php

namespace Technodelight\Jira\Console\Command\Action\Issue;

use Hoa\Console\Readline\Readline;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Console\HoaConsole\IssueMetaAutocompleter;
use Technodelight\Jira\Domain\Issue;

class Version extends Command
{
    /**
     * @var Api
     */
    private $jira;
    /**
     * @var QuestionHelper
     */
    private $questionHelper;
    /**
     * @var IssueKeyResolver
     */
    private $issueKeyResolver;

    protected function configure()
    {
        $this
            ->setName('issue:version')
            ->setAliases(['version'])
            ->setDescription('Set version and fixVersion attributes for an issue')
            ->addArgument(
                IssueKeyResolver::ARGUMENT,
                InputArgument::OPTIONAL,
                'Issue key, like PROJ-123 OR a specific worklog\'s ID'
            )
            ->addOption(
                'fix-version',
                'f',
                InputOption::VALUE_REQUIRED,
                'Set fixVersion number'
            )
            ->addOption(
                'affected-version',
                'a',
                InputOption::VALUE_REQUIRED,
                'Set (affected)version number'
            )
            ->addOption(
                'remove',
                null,
                InputOption::VALUE_NONE,
                'Indicate if affectedVersion/fixVersion or both should be removed'
            )
            ->addOption(
                'interactive',
                'I',
                InputOption::VALUE_NONE,
                'Define versions interactively'
            )
        ;
    }

    public function setJiraApi(Api $jira)
    {
        $this->jira = $jira;
    }

    public function setQuestionHelper(QuestionHelper $questionHelper)
    {
        $this->questionHelper = $questionHelper;
    }

    public function setIssueKeyResolver(IssueKeyResolver $issueKeyResolver)
    {
        $this->issueKeyResolver = $issueKeyResolver;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if ($this->shouldBeInteractive($input)) {
            $isRemove = $input->getOption('remove');
            $issueKey = $this->issueKeyResolver->argument($input, $output);
            $q = new ConfirmationQuestion(
                sprintf('Do you want to %s fixVersion? [y/N] ', $isRemove ? 'remove' : 'set'), false
            );
            if ($this->questionHelper->ask($input, $output, $q)) {
                $input->setOption('fix-version', $this->autocompleteMeta($output, $issueKey, 'fixVersions'));
            }
            $q = new ConfirmationQuestion(
                sprintf('Do you want to %s affectedVersion? [y/N] ', $isRemove ? 'remove' : 'set'), false
            );
            if ($this->questionHelper->ask($input, $output, $q)) {
                $input->setOption('affected-version', $this->autocompleteMeta($output, $issueKey, 'versions'));
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issueKey = $this->issueKeyResolver->argument($input, $output);
        $beforeUpdate = $this->jira->retrieveIssue($issueKey);
        $this->jira->updateIssue($issueKey, ['update' => $this->prepareUpdateData($input)]);
        $afterUpdate = $this->jira->retrieveIssue($issueKey);
        $this->renderChanges($output, $beforeUpdate, $afterUpdate);
    }

    private function renderChanges(OutputInterface $output, Issue $beforeUpdate, Issue $afterUpdate)
    {
        $this->renderChange('fixVersions', $beforeUpdate, $afterUpdate, $output);
        $this->renderChange('versions', $beforeUpdate, $afterUpdate, $output);
    }

    private function renderChange($field, Issue $before, Issue $after, OutputInterface $output)
    {
        $beforeValues = $this->arrayOfNamesFromField($before->findField($field) ?: []);
        $afterValues = $this->arrayOfNamesFromField($after->findField($field) ?: []);
        $added = array_diff($afterValues, $beforeValues);
        $removed = array_diff($beforeValues, $afterValues);

        if (!empty($added)) {
            $output->writeln(
                $this->renderChangeset('Added', $field, $added)
            );
        } else if (!empty($removed)) {
            $output->writeln(
                $this->renderChangeset('Removed', $field, $removed)
            );
        } else {
            $output->writeln(sprintf('<comment>No changes on %s</comment>', $field));
        }
    }

    private function renderChangeset($text, $field, array $changes)
    {
        return sprintf(
            '<comment>%s %s for %s</comment>',
            $text,
            '<info>' . join(', ', $changes) . '</info>',
            $field
        );
    }

    private function prepareUpdateData(InputInterface $input)
    {
        $operation = $input->getOption('remove') ? 'remove' : 'add';
        $fixVersion = $input->getOption('fix-version');
        $affectedVersion = $input->getOption('affected-version');

        $changeSet = ['fixVersions' => $fixVersion, 'versions' => $affectedVersion];
        foreach ($changeSet as $key => $value) {
            if (!is_null($value)) {
                $changeSet[$key] = [[$operation => ['name' => $value]]];
            }
        }

        return array_filter($changeSet);
    }

    private function arrayOfNamesFromField(array $valueArray)
    {
        return array_map(
            function (array $value) {
                return $value['name'];
            },
            $valueArray
        );
    }

    /**
     * @param OutputInterface $output
     * @param string $issueKey
     * @param string $fieldName
     * @return string
     */
    private function autocompleteMeta(OutputInterface $output, $issueKey, $fieldName)
    {
        $readline = new Readline;
        $readline->setAutocompleter(
            new IssueMetaAutocompleter($this->jira, $issueKey, $fieldName)
        );
        $output->writeln(sprintf('<comment>Please select value for</comment> <info>%s:</info>', $fieldName));
        return $readline->readLine();
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @return bool
     */
    protected function shouldBeInteractive(InputInterface $input)
    {
        return $input->getOption('interactive') || (!$input->getOption('fix-version') && !$input->getOption('affected-version'));
    }
}
