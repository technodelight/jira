<?php

namespace Technodelight\Jira\Console\Command\Action\Issue;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Api\JiraRestApi\SearchQuery\Builder;
use Technodelight\Jira\Console\Argument\AutocompletedInput;
use Technodelight\Jira\Console\Command\AbstractCommand;

class Comment extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('issue:comment')
            ->setDescription('Add/remove/update/delete comments on issues')
            ->setAliases(['comment'])
            ->addArgument(
                'issueKey',
                InputArgument::OPTIONAL,
                'Issue key (ie. PROJ-123)'
            )
            ->addArgument(
                'comment',
                InputArgument::OPTIONAL,
                'Comment text'
            )
            ->addOption(
                'delete',
                'd',
                InputOption::VALUE_REQUIRED,
                'Delete given comment by ID',
                false
            )
            ->addOption(
                'update',
                'u',
                InputOption::VALUE_REQUIRED,
                'Update comment by ID',
                false
            )
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $issueKey = $this->issueKeyArgument($input, $output);
        if ($input->getOption('delete')) {
            return;
        }

        if (!$input->getArgument('comment')) {
            $issue = $this->jiraApi()->retrieveIssue($issueKey);
            $renderer = $this->issueRenderer();
            $renderer->render($output, $issue, true);
            $output->write('<info>Comment: </>');

            $autocomplete = new AutocompletedInput($issue, $this->getPossibleIssues(), [$issue->summary(), $issue->description()]);
            $comment = $autocomplete->getValue();
            $output->write('</>');
            $input->setArgument('comment', $comment);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issueKey = $this->issueKeyArgument($input, $output);
        $comment = $input->getArgument('comment');
        $delete = $input->getOption('delete');
        $update = $input->getOption('update');
        $render = $this->commentRenderer();

        if ($update) {
            $comment = $this->jiraApi()->updateComment($issueKey, $update, $comment);
            $output->writeln(sprintf('Comment <info>%s</> was updated successfully', $comment->id()));
            $render->renderComment($output, $comment);
        } elseif ($delete) {
            $this->jiraApi()->deleteComment($issueKey, $delete);
            $output->writeln('<info>Comment has been deleted</>');
        } else {
            $comment = $this->jiraApi()->addComment($issueKey, $comment);
            $output->writeln(sprintf('Comment <info>%s</> was created successfully', $comment->id()));
            $render->renderComment($output, $comment);
        }
    }

    private function getPossibleIssues()
    {
        return $this->jiraApi()->search(
            Builder::factory()
                ->issueKeyInHistory()
                ->assemble()
        );
    }

    /**
     * @return Api
     */
    private function jiraApi()
    {
        return $this->getService('technodelight.jira.api');
    }

    /**
     * @return \Technodelight\Jira\Template\IssueRenderer
     */
    private function issueRenderer()
    {
        return $this->getService('technodelight.jira.issue_renderer');
    }

    /**
     * @return \Technodelight\Jira\Renderer\Issue\Comment
     */
    private function commentRenderer()
    {
        return $this->getService('technodelight.jira.renderer.issue.comment');
    }
}
