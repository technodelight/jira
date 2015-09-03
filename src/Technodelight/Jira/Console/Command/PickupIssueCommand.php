<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Technodelight\Jira\Template\Template;

use UnexpectedValueException;

class PickupIssueCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('pick')
            ->setDescription('Pick an open issue')
            ->addArgument(
                'issueKey',
                InputArgument::REQUIRED,
                'Issue key (ie. PROJ-123)'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issueKey = $input->getArgument('issueKey');
        $jira = $this->getApplication()->jira();
        $issue = $jira->retrieveIssue($issueKey);

        if ($issue->status() != 'Open') {
            $template = Template::fromFile('Technodelight/Jira/Resources/views/Commands/pickup.template');
            $output->writeln(
                $template->render(
                    [
                        'issueKey' => $issue->ticketNumber(),
                        'status' => $issue->status(),
                        'asignee' => $issue->asignee(),
                        'url' => $issue->url(),
                    ]
                )
            );
        } else {
            $transitions = $jira->retrievePossibleTransitionsForIssue($issueKey);
            $transition = $this->filterTransitionByName($transitions, 'Picked up by dev');
            $jira->performIssueTransition($issueKey, $transition['id']);

            $output->writeln(
                sprintf(
                    'Task <info>%s</info> has been successfully <comment>%s</comment>',
                    $issueKey,
                    $transition['name']
                )
            );
        }

    }

    protected function filterTransitionByName($transitions, $name)
    {
        foreach ($transitions as $transition) {
            if ($transition['name'] == $name) {
                return $transition;
            }
        }

        throw new UnexpectedValueException(sprintf('No such transition: %s', $name));
    }
}
