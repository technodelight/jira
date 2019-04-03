<?php

namespace Technodelight\Jira\Renderer\Action\Issue\Link;

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
    /**
     * @var Api
     */
    private $api;
    /**
     * @var Header
     */
    private $headerRenderer;
    /**
     * @var IssueRelations
     */
    private $issueRelationsRenderer;
    /**
     * @var FormatterHelper
     */
    private $formatterHelper;
    /**
     * @var StyleGuide
     */
    private $styleGuide;

    public function __construct(
        Api $api,
        Header $headerRenderer,
        IssueRelations $issueRelationsRenderer,
        FormatterHelper $formatterHelper,
        StyleGuide $styleGuide
    )
    {
        $this->api = $api;
        $this->headerRenderer = $headerRenderer;
        $this->issueRelationsRenderer = $issueRelationsRenderer;
        $this->formatterHelper = $formatterHelper;
        $this->styleGuide = $styleGuide;
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

        return 0;
    }

    /**
     * @param OutputInterface $output
     * @param Result $result
     * @return int
     */
    protected function renderSuccess(OutputInterface $output, Success $result): int
    {
        $issue = $this->api->retrieveIssue($result->issueKey());
        $this->headerRenderer->render($output, $issue);
        $this->issueRelationsRenderer->render($output, $issue);

        $output->writeln([
            '',
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
        ]);

        return 0;
    }

    private function renderError(OutputInterface $output, Error $error): int
    {
        if ($output->getVerbosity() == OutputInterface::VERBOSITY_QUIET) {
            return $error->exception()->getCode() ?: 1;
        }

        $output->writeln(
            $this->formatterHelper->formatBlock(
                vsprintf($error->phrase(), array_filter(array_merge([$error->issueKey()], $error->data()))),
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
