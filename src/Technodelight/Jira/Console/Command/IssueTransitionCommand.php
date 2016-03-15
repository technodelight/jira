<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\Issue;
use Technodelight\Jira\Configuration\Configuration;
use Technodelight\Jira\Console\Command\AbstractCommand;
use Technodelight\Jira\Helper\GitBranchnameGenerator;
use Technodelight\Jira\Helper\GitHelper;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Template\Template;
use Technodelight\Simplate;
use \UnexpectedValueException;

class IssueTransitionCommand extends AbstractCommand
{
    protected function configure()
    {
        $transitions = $this->getService('technodelight.jira.config')->transitions();
        if (!isset($transitions[$this->getName()])) {
            throw new UnexpectedValueException(
                sprintf('Undefined transition: "%s"', $this->getName())
            );
        }
        $this->transitionName = $transitions[$this->getName()];

        $this
            ->setDescription($this->transitionName)
            ->addArgument(
                'issueKey',
                InputArgument::REQUIRED,
                'Issue key (ie. PROJ-123)'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issueKey = $this->issueKeyArgument($input);
        $jira = $this->getService('technodelight.jira.api');
        $issue = $jira->retrieveIssue($issueKey);
        $transitions = $jira->retrievePossibleTransitionsForIssue($issueKey);

        try {
            $transition = $this->filterTransitionByName($transitions, $this->transitionName);
            $jira->performIssueTransition($issueKey, $transition['id']);

            $output->writeln(
                sprintf(
                    'Task <info>%s</info> has been successfully moved to <comment>%s</comment>',
                    $issueKey,
                    $this->transitionName
                )
            );
            $success = true;
        } catch (UnexpectedValueException $exception) {
            $output->writeln(
                sprintf('<error>%s</error>' . PHP_EOL, $exception->getMessage())
            );
            $success = false;
        }

        $output->writeln(
            Simplate::fromFile($this->getApplication()->directory('views') . '/Commands/transition.template')->render(
                [
                    'success' => $success,
                    'issueKey' => $issue->ticketNumber(),
                    'transitionName' => $this->transitionName,
                    'transitionsNames' => implode(', ', $this->transitionsNames($transitions)),
                    'status' => $issue->status(),
                    'asignee' => $issue->assignee(),
                    'url' => $issue->url(),
                    'branches' => $this->getService('technodelight.jira.template_helper')->tabulate(
                        $this->retrieveGitBranches($issue)
                    ),
                ]
            )
        );
    }

    private function transitionsNames(array $transitions)
    {
        $names = [];
        foreach ($transitions as $transition) {
            $names[] = $transition['name'];
        }

        return $names;
    }

    private function filterTransitionByName($transitions, $name)
    {
        foreach ($transitions as $transition) {
            if ($transition['name'] == $name) {
                return $transition;
            }
        }

        throw new UnexpectedValueException(
            sprintf('No "%s" transition available for this issue', $name)
        );
    }

    private function retrieveGitBranches(Issue $issue)
    {
        $branches = $this->getService('technodelight.jira.git_helper')->branches($issue->ticketNumber());
        if (empty($branches)) {
            $branches = [
                $this->getService('technodelight.jira.git_branchname_generator')->fromIssue($issue) . ' (generated)'
            ];
        }

        return implode(PHP_EOL, $branches);
    }
}
