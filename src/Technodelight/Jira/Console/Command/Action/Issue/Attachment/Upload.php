<?php

namespace Technodelight\Jira\Console\Command\Action\Issue\Attachment;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Console\Input\Issue\Attachment\UploadableAttachment;

class Upload extends Command
{
    /**
     * @var Api
     */
    private $api;
    /**
     * @var UploadableAttachment
     */
    private $uploadableAttachmentInput;
    /**
     * @var IssueKeyResolver
     */
    private $issueKeyResolver;

    public function __construct(Api $api, IssueKeyResolver $issueKeyResolver, UploadableAttachment $uploadableAttachmentInput)
    {
        $this->api = $api;
        $this->issueKeyResolver = $issueKeyResolver;
        $this->uploadableAttachmentInput = $uploadableAttachmentInput;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('issue:attachment:upload')
            ->setAliases(['upload'])
            ->addArgument(
                'issueKey',
                InputArgument::OPTIONAL,
                'Issue key (ie. PROJ-123), defaults to current issue, taken from branch name'
            )
            ->addArgument(
                'filename',
                InputArgument::OPTIONAL,
                'Filename to upload'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \ErrorException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issueKey = $this->issueKeyResolver->argument($input, $output);
        $filename = $this->uploadableAttachmentInput->resolve($input, $output);

        $this->api->addAttachment($issueKey, $filename);
    }
}
