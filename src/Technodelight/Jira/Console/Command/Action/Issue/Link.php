<?php

namespace Technodelight\Jira\Console\Command\Action\Issue;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Configuration\ApplicationConfiguration\AliasesConfiguration;
use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Console\Argument\IssueLinkArgument;
use Technodelight\Jira\Domain\IssueLink;
use Technodelight\Jira\Domain\IssueLink\Type;
use Technodelight\Jira\Renderer\Action\Issue\Link\Error;
use Technodelight\Jira\Renderer\Action\Issue\Link\Renderer;
use Technodelight\Jira\Renderer\Action\Issue\Link\Success;

class Link extends Command
{
    /**
     * @var Api
     */
    private $api;
    /**
     * @var IssueKeyResolver
     */
    private $issueKeyResolver;
    /**
     * @var AliasesConfiguration
     */
    private $aliasesConfiguration;
    /**
     * @var Renderer
     */
    private $renderer;
    /**
     * @var string[]
     */
    private $relations = [
        'relates to',
        'blocks',
        'is blocked by',
        'depends on',
        'is depended by',
        'duplicates',
        'is duplicated by',
        'causes',
        'is caused by',
        'replaces',
        'is replaced by',
    ];

    public function __construct(
        Api $api,
        IssueKeyResolver $issueKeyResolver,
        AliasesConfiguration $aliasesConfiguration,
        Renderer $renderer
    )
    {
        $this->api = $api;
        $this->issueKeyResolver = $issueKeyResolver;
        $this->aliasesConfiguration = $aliasesConfiguration;

        parent::__construct();
        $this->renderer = $renderer;
    }


    protected function configure()
    {
        $this
            ->setName('issue:link')
            ->setAliases(['link'])
            ->setDescription('Create issue links')
            ->addArgument(
                'issueKey',
                InputArgument::OPTIONAL,
                'IssueKey (eg. PROJ-1)'
            );
        foreach ($this->relations as $relation) {
            $this->addOption(
                strtr($relation, [' ' => '-']),
                null,
                InputOption::VALUE_REQUIRED,
                sprintf('Set up "%s" link', $relation)
            );
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $issueKey = $this->issueKeyResolver->argument($input, $output);
            $linkTypes = $this->api->linkTypes();
            $links = IssueLinkArgument::fromOptions($this->optionsFromInput($input));
            foreach ($links as $link) {
                $this->link($issueKey, $link, $linkTypes);
            }
            $this->renderer->render($output, Success::fromIssueKeys($issueKey, $links));
        } catch (\Exception $e) {
            $this->renderer->render($output, Error::fromExceptionAndIssueKey($e, $issueKey));
        }
    }

    /**
     * @param IssueKey $issueKey
     * @param IssueLinkArgument $link
     * @param Type[] $linkTypes
     * @return void
     * @throws \InvalidArgumentException
     */
    private function link(IssueKey $issueKey, IssueLinkArgument $link, array $linkTypes)
    {
        $existingLinks = $this->existingLinks($issueKey);
        $linkedLinks = $this->existingLinks($link->issueKey());

        foreach ($linkTypes as $linkType) {
            if ($this->canInwardLink($link, $linkType, $existingLinks)) {
                $this->api->linkIssue(
                    $link->issueKey(),
                    $issueKey,
                    $linkType->name()
                );
                return;
            }
            if ($this->canOutwardLink($link, $issueKey, $linkType, $linkedLinks)) {
                $this->api->linkIssue(
                    $issueKey,
                    $link->issueKey(),
                    $linkType->name()
                );
                return;
            }
        }

        throw new \InvalidArgumentException(
            sprintf('Cannot link %s with "%s" to %s', $issueKey, $link->relation(), $link->issueKey())
        );
    }

    private function existingLinks(IssueKey $issueKey)
    {
        $links = [];
        $issue = $this->api->retrieveIssue($issueKey);
        foreach ($issue->links() as $link) {
            $links[] = sprintf(
                '%s %s',
                $link->isInward() ? $link->type()->inward() : $link->type()->outward(),
                $link->isInward() ? $link->inwardIssue()->key() : $link->outwardIssue()->key()
            );
        }
        return $links;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @return array
     */
    protected function optionsFromInput(InputInterface $input)
    {
        $opts = [];
        foreach ($input->getOptions() as $relation => $issueKey) {
            $opts[$relation] = $this->aliasesConfiguration->aliasToIssueKey($issueKey);
        }
        return $opts;
    }

    /**
     * @param IssueLinkArgument $link
     * @param Type $linkType
     * @param IssueLink[] $existing
     * @return bool
     */
    private function canInwardLink(IssueLinkArgument $link, Type $linkType, array $existing = [])
    {
        if ($linkType->inward() == $link->relation()) {
            $key = $linkType->inward() . ' ' . $link->issueKey();
            return !in_array($key, $existing);
        }

        return false;
    }

    /**
     * @param IssueLinkArgument $link
     * @param IssueKey $issueKey
     * @param Type $linkType
     * @param IssueLink[] $existing
     * @return bool
     */
    private function canOutwardLink(IssueLinkArgument $link, IssueKey $issueKey, Type $linkType, array $existing = [])
    {
        if ($linkType->outward() == $link->relation()) {
            $key = $linkType->outward() . ' ' . $issueKey;
            return !in_array($key, $existing);
        }

        return false;
    }
}
