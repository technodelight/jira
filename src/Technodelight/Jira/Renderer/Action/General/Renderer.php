<?php

declare(strict_types=1);

namespace Technodelight\Jira\Renderer\Action\General;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Renderer\Action\Error;
use Technodelight\Jira\Renderer\Action\Renderer as ActionRenderer;
use Technodelight\Jira\Renderer\Action\Result;
use Technodelight\Jira\Renderer\Action\StyleGuide;
use Technodelight\Jira\Renderer\Action\Success;

class Renderer implements ActionRenderer
{
    public function __construct(private readonly StyleGuide $styleGuide)
    {
    }

    public function canProcess(Result $result): bool
    {
        return $result instanceof Success || $result instanceof Error;
    }


    public function render(OutputInterface $output, Result $result): int
    {
        if ($result instanceof Success) {
            return $this->renderSuccess($output, $result);
        }
        if ($result instanceof Error) {
            return $this->renderError($output, $result);
        }
    }

    private function renderSuccess(OutputInterface $output, Success $success): int
    {
        if ($output->getVerbosity() == OutputInterface::VERBOSITY_QUIET) {
            return 0;
        }

        $output->writeln(
            $this->styleGuide->success(vsprintf($success->phrase(), $success->data()))
        );

        return 0;
    }

    private function renderError(OutputInterface $output, Error $error): int
    {
        if ($output->getVerbosity() == OutputInterface::VERBOSITY_QUIET) {
            return $error->exception()->getCode() ?: 1;
        }

        $output->writeln(
            $this->styleGuide->error(
                vsprintf($error->phrase(), array_filter($error->data()))
            )
        );

        if ($output->getVerbosity() == OutputInterface::VERBOSITY_VERBOSE) {
            //@TODO: a nice formatting would be good here
            $output->writeln($error->exception()->getTraceAsString());
        }

        return $error->exception()->getCode() ?: Command::FAILURE;
    }
}
