<?php

declare(strict_types=1);

namespace Technodelight\Jira\Renderer\Action\Issue\Edit;

use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Renderer\Action\Renderer as ActionRenderer;
use Technodelight\Jira\Renderer\Action\Result;
use Technodelight\Jira\Renderer\Action\StyleGuide;
use Technodelight\Jira\Renderer\Issue\Header;
use Technodelight\Jira\Renderer\Issue\IssueRelations;

class Renderer implements ActionRenderer
{
    public function __construct(
        private readonly Api $api,
        private readonly Header $headerRenderer,
        private readonly IssueRelations $relationsRenderer,
        private readonly FormatterHelper $formatterHelper,
        private readonly StyleGuide $styleGuide
    ) {}

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

        return 0;
    }

    /**
     * @param OutputInterface $output
     * @param Result $result
     * @return int
     */
    protected function renderSuccess(OutputInterface $output, Success $result): int
    {
        $output->writeln(
            $this->styleGuide->success(
                vsprintf(
                    $result->phrase(),
                    array_filter(
                        [
                            $this->styleGuide->formatIssueKey($result->issueKey()),
                            $this->styleGuide->formatTransition($result->data()[0]),
                            !empty($result->data()[1]) ? $this->styleGuide->formatUsername($result->data()[1]) : null,
                        ]
                    )
                )
            )
        );

        $issue = $this->api->retrieveIssue($result->issueKey());
        $this->headerRenderer->render($output, $issue);
        $this->relationsRenderer->render($output, $issue);

        return 0;
    }

    private function renderError(OutputInterface $output, Error $error): int
    {
        if ($output->getVerbosity() == OutputInterface::VERBOSITY_QUIET) {
            return $error->exception()->getCode() ?: 1;
        }

        $output->writeln(
            $this->styleGuide->error(
                vsprintf($error->phrase(), array_filter(array_merge([$error->issueKey()], $error->data())))
            )
        );

        $issue = $this->api->retrieveIssue($error->issueKey());
        $this->headerRenderer->render($output, $issue);
        $this->relationsRenderer->render($output, $issue);

        if ($output->getVerbosity() == OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln($error->exception()->getTraceAsString()); //@TODO: a nice formatting would be good here
        }

        return $error->exception()->getCode() ?: 1;
    }
}
