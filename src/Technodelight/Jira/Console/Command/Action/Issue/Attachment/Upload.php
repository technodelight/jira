<?php

declare(strict_types=1);

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
    public function __construct(
        private readonly Api $api,
        private readonly IssueKeyResolver $issueKeyResolver,
        private readonly UploadableAttachment $uploadInput
    ) {
         parent::__construct();
    }

    protected function configure(): void
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $issueKey = $this->issueKeyResolver->argument($input, $output);
        $filename = $this->uploadInput->resolve($input, $output);

        $this->api->addAttachment($issueKey, $filename);

        return self::SUCCESS;
    }
}
