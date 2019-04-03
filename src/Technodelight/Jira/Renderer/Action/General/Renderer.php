<?php

namespace Technodelight\Jira\Renderer\Action\General;

use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Renderer\Action\Error;
use Technodelight\Jira\Renderer\Action\Renderer as ActionRenderer;
use Technodelight\Jira\Renderer\Action\Result;
use Technodelight\Jira\Renderer\Action\Success;

class Renderer implements ActionRenderer
{
    /**
     * @var FormatterHelper
     */
    private $formatterHelper;

    public function __construct(FormatterHelper $formatterHelper)
    {
        $this->formatterHelper = $formatterHelper;
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
            sprintf('<fg=green>%s</>', vsprintf($success->phrase(), $success->data()))
        );

        return 0;
    }

    private function renderError(OutputInterface $output, Error $error): int
    {
        if ($output->getVerbosity() == OutputInterface::VERBOSITY_QUIET) {
            return $error->exception()->getCode() ?: 1;
        }

        $output->writeln(
            $this->formatterHelper->formatBlock(
                vsprintf($error->phrase(), array_filter($error->data())),
                'error',
                true
            )
        );
        $output->writeln('');

        if ($output->getVerbosity() == OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln($error->exception()->getTraceAsString()); //@TODO: a nice formatting would be good here
        }

        return $error->exception()->getCode() ?: 1;
    }
}
