<?php

namespace Technodelight\Jira\Console\Command\Show;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Renderer\Action\Renderer;
use Technodelight\Jira\Renderer\Action\Show\User\Error;
use Technodelight\Jira\Renderer\Action\Show\User\Success;

/** @SuppressWarnings(PHPMD.StaticAccess) */
class User extends Command
{
    public function __construct(private readonly Api $api, private readonly Renderer $renderer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('show:user')
            ->addArgument('account', InputArgument::OPTIONAL, 'User account ID or name', null)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $accountId = $input->getArgument('accountId');
        try {
            $user = $this->api->user($accountId);
            $result = Success::fromUser($user);
        } catch (Exception $e) {
            $result = Error::fromExceptionAndAccountId($e, $accountId);
        } finally {
            $this->renderer->render($output, $result);

            return self::SUCCESS;
        }
    }
}
