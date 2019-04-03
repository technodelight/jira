<?php

namespace Technodelight\Jira\Console\Command\Action\Issue\Attachment;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Console\Input\Issue\Attachment\DownloadableAttachment;
use Technodelight\Jira\Console\Input\Issue\Attachment\TargetPath;
use Technodelight\Jira\Helper\Downloader;

class Download extends Command
{
    const SUCCESS_MESSAGE = 'File "%s" has been successfully downloaded to "%s" .';
    const CANCEL_MESSAGE = 'Skipped downloading file "%s"';
    const ERROR_MESSAGE = 'Something went wrong while downloading "%s."';

    /**
     * @var Api
     */
    private $api;
    /**
     * @var IssueKeyResolver
     */
    private $issueKeyResolver;
    /**
     * @var DownloadableAttachment
     */
    private $downloadableAttachmentInput;
    /**
     * @var TargetPath
     */
    private $targetPathInput;

    public function __construct(Api $api, IssueKeyResolver $issueKeyResolver, DownloadableAttachment $downloadableAttachmentInput, TargetPath $targetPathInput)
    {
        $this->api = $api;
        $this->issueKeyResolver = $issueKeyResolver;
        $this->downloadableAttachmentInput = $downloadableAttachmentInput;
        $this->targetPathInput = $targetPathInput;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('issue:attachment:download')
            ->setDescription('Download attachment from a ticket')
            ->setAliases(['download'])
            ->addArgument(
                'issueKey',
                InputArgument::OPTIONAL,
                'Issue key (ie. PROJ-123), defaults to current issue, taken from branch name'
            )
            ->addArgument(
                'filename',
                InputArgument::OPTIONAL,
                'Filename to download'
            )
            ->addArgument(
                'targetPath',
                InputArgument::OPTIONAL,
                'Path to download the file to (defaults to current working directory)'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \ErrorException
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $issueKey = $this->issueKeyResolver->argument($input, $output);

        $input->setArgument('filename', $this->downloadableAttachmentInput->resolve($input, $output, $issueKey));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issueKey = $this->issueKeyResolver->argument($input, $output);
        $filename = $input->getArgument('filename');
        $targetPath = $this->targetPathInput->resolve($input);

        /** @var \Technodelight\Jira\Domain\Issue $issue */
        $issue = $this->api->retrieveIssue($issueKey);
        /** @var \Symfony\Component\Console\Helper\FormatterHelper $formatter */
        $formatter = $this->getHelper('formatter');

        try {
            if (!count($issue->attachments())) {
                throw new \ErrorException(
                    sprintf('No attachments for %s', $issue->issueKey())
                );
            }

            // download file
            $attachment = $this->downloadableAttachmentInput->findAttachmentByFilename($issue, $filename);
            $targetFilePath = $targetPath . DIRECTORY_SEPARATOR . $filename;
            if ($this->confirmDownload($input, $output, $targetFilePath)) {

                $downloader = new Downloader;
                $f = fopen($targetFilePath, 'w');
                /** @var ProgressBar $progress */
                list($progress, $callback) = $downloader->progressBarWithProgressFunction($output);
                $progress->start($attachment->size());
                $this->api->download($attachment->url(), $f, $callback);
                $progress->finish();

                $output->writeln('');

                $output->writeln(
                    $formatter->formatBlock(
                        sprintf(self::SUCCESS_MESSAGE, $filename, $targetFilePath),
                        'info'
                    )
                );
            } else {
                $output->writeln(
                    $formatter->formatBlock(sprintf(self::CANCEL_MESSAGE, $filename), 'comment')
                );
            }
        } catch (\Exception $e) {
            $errors = [
                sprintf(self::ERROR_MESSAGE, $filename),
                $e->getMessage(),
            ];
            $output->writeln(
                $formatter->formatBlock($errors, 'error', true)
            );
        }
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string $targetFilePath
     * @return bool
     */
    protected function confirmDownload(InputInterface $input, OutputInterface $output, $targetFilePath)
    {
        if (is_file($targetFilePath)) {
            $question = new ConfirmationQuestion(
                sprintf('<comment>File "%s" already exists, do you want to overwrite it?</comment> [y/N] ', $targetFilePath),
                false
            );
            $helper = new QuestionHelper;
            return $helper->ask($input, $output, $question);
        }

        return true;
    }
}
