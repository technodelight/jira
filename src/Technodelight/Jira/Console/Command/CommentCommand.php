<?php

namespace Technodelight\Jira\Console\Command;

use Hoa\Console\Readline\Autocompleter\Word;
use Hoa\Console\Readline\Readline;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\AutocompleteHelper;

class CommentCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('comment')
            ->setDescription('Add/remove/update/delete comments on issues')
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
                InputOption::VALUE_OPTIONAL,
                'Delete given comment by ID',
                false
            )
            ->addOption(
                'update',
                'u',
                InputOption::VALUE_OPTIONAL,
                'Update comment by ID',
                false
            )
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $issueKey = $this->issueKeyArgument($input);
        if ($input->getOption('delete')) {
            return;
        }

        if (!$input->getArgument('comment')) {
            $issue = $this->jiraApi()->retrieveIssue($issueKey);
            $renderer = $this->issueRenderer();
            $renderer->setOutput($output);
            $renderer->render($issue);
            $output->write('<info>Comment: </>');

            $readline = new Readline;
            $readline->setAutocompleter(new Word($this->getAutocompleteWords($issue)));
            $comment = $readline->readLine();
            $output->write('</>');
            $input->setArgument('comment', $comment);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issueKey = $this->issueKeyArgument($input);
        $comment = $input->getArgument('comment');
        $delete = $input->getOption('delete');
        $update = $input->getOption('update');
        $render = $this->commentRenderer();
        $render->setOutput($output);

        if ($update) {
            $comment = $this->jiraApi()->updateComment($issueKey, $update, $comment);
            $output->writeln(sprintf('Comment added successfully with ID <info>%s</>', $comment->id()));
            $render->renderComments([$comment]);
        } elseif ($delete) {
            $this->jiraApi()->deleteComment($issueKey, $delete);
            $output->writeln('<info>Comment has been deleted</>');
        } else {
            $comment = $this->jiraApi()->addComment($issueKey, $comment);
            $output->writeln(sprintf('Comment updated successfully with ID <info>%s</>', $comment->id()));
            $render->renderComments([$comment]);
        }
    }

    /**
     * @param \Technodelight\Jira\Domain\Issue $issue
     * @return array
     */
    private function getAutocompleteWords(Issue $issue)
    {
        $helper = new AutocompleteHelper;
        return $helper->getWords([$issue->description(), $issue->summary()]);
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
     * @return \Technodelight\Jira\Template\CommentRenderer
     */
    private function commentRenderer()
    {
        return $this->getService('technodelight.jira.comment_renderer');
    }
}
