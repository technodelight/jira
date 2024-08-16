<?php

namespace Technodelight\Jira\Console\Command\Action\Issue;

use Exception;
use InvalidArgumentException;
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
use Technodelight\Jira\Domain\IssueLink\Type;
use Technodelight\Jira\Renderer\Action\Issue\Link\Error;
use Technodelight\Jira\Renderer\Action\Issue\Link\Renderer;
use Technodelight\Jira\Renderer\Action\Issue\Link\Success;

class Link extends Command
{
    /** @var string[] */
    private array $relations = [
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
        private readonly Api $api,
        private readonly IssueKeyResolver $issueKeyResolver,
        private readonly AliasesConfiguration $aliasesConfiguration,
        private readonly Renderer $renderer
    ) {
        parent::__construct();
    }


    protected function configure(): void
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

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $issueKey = $this->issueKeyResolver->argument($input, $output);
            $linkTypes = $this->api->linkTypes();
            $links = IssueLinkArgument::fromOptions($this->optionsFromInput($input));
            foreach ($links as $link) {
                $this->link($issueKey, $link, $linkTypes);
            }
            $this->renderer->render($output, Success::fromIssueKeys($issueKey, $links));

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->renderer->render($output, Error::fromExceptionAndIssueKey($e, $issueKey));

            return self::FAILURE;
        }
    }

    private function link(IssueKey $issueKey, IssueLinkArgument $link, array $linkTypes): void
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

        throw new InvalidArgumentException(
            sprintf('Cannot link %s with "%s" to %s', $issueKey, $link->relation(), $link->issueKey())
        );
    }

    private function existingLinks(IssueKey $issueKey): array
    {
        return array_map(static fn($link) => sprintf(
            '%s %s',
            $link->isInward() ? $link->type()->inward() : $link->type()->outward(),
            $link->isInward() ? $link->inwardIssue()->key() : $link->outwardIssue()->key()
        ), $this->api->retrieveIssue($issueKey)->links());
    }

    private function optionsFromInput(InputInterface $input): array
    {
        $opts = [];
        foreach ($input->getOptions() as $relation => $issueKey) {
            $opts[$relation] = $this->aliasesConfiguration->aliasToIssueKey($issueKey);
        }
        return $opts;
    }

    private function canInwardLink(IssueLinkArgument $link, Type $linkType, array $existing = []): bool
    {
        if ($linkType->inward() === $link->relation()) {
            $key = $linkType->inward() . ' ' . $link->issueKey();
            return !in_array($key, $existing, true);
        }

        return false;
    }

    private function canOutwardLink(IssueLinkArgument $link, IssueKey $issueKey, Type $linkType, array $existing = []): bool
    {
        if ($linkType->outward() === $link->relation()) {
            $key = $linkType->outward() . ' ' . $issueKey;
            return !in_array($key, $existing, true);
        }

        return false;
    }
}
