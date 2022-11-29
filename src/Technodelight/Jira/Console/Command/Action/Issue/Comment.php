<?php

namespace Technodelight\Jira\Console\Command\Action\Issue;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Domain\Comment\CommentId;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Console\Input\Issue\Comment\Comment as CommentInput;
use Technodelight\Jira\Renderer\Issue\Comment as CommentRenderer;
use Technodelight\Jira\Template\IssueRenderer;

class Comment extends Command
{
    public function __construct(
        private readonly Api $jira,
        private readonly CommentInput $commentInput,
        private readonly IssueKeyResolver $issueKeyResolver,
        private readonly IssueRenderer $issueRenderer,
        private readonly CommentRenderer $commentRenderer
    ) {
         parent::__construct();
    }

    protected function configure(): void
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
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $issueKey = $this->issueKeyResolver->argument($input, $output);
        if ($input->getOption('delete')) {
            return;
        }

        if (!$input->getArgument('comment')) {
            $issue = $this->jira->retrieveIssue($issueKey);

            $this->issueRenderer->render($output, $issue);

            try {
                $commentId = CommentId::fromString($input->getOption('update'));
                $input->setArgument('comment', $this->commentInput->updateComment($issueKey, $commentId, $output));
            } catch (\InvalidArgumentException $e) {
                $input->setArgument('comment', $this->commentInput->createComment($issue, $input, $output));
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $issueKey = $this->issueKeyResolver->argument($input, $output);
        $comment = $input->getArgument('comment');
        $deleteCommentId = $input->getOption('delete');
        $updateCommentId = $input->getOption('update');

        if ($updateCommentId) {
            $comment = $this->jira->updateComment($issueKey, CommentId::fromString($updateCommentId), $comment);
            $output->writeln(sprintf('Comment <info>%s</> was updated successfully', $comment->id()));
            $this->commentRenderer->renderComment($output, $comment);
        } elseif ($deleteCommentId) {
            $this->jira->deleteComment($issueKey, CommentId::fromString($deleteCommentId));
            $output->writeln('<info>Comment has been deleted</>');
        } else {
            $comment = $this->jira->addComment($issueKey, $comment);
            $output->writeln(sprintf('Comment <info>%s</> was created successfully', $comment->id()));
            $this->commentRenderer->renderComment($output, $comment);
        }

        return self::SUCCESS;
    }
}
