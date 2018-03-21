<?php

namespace Technodelight\Jira\Console\Command\Action\Issue;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\Argument\IssueKey;
use Technodelight\Jira\Console\Argument\IssueLinkArgument;
use Technodelight\Jira\Console\Command\AbstractCommand;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\IssueLink;
use Technodelight\Jira\Domain\IssueLink\Type;

class Link extends AbstractCommand
{
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
        $issueKey = $this->issueKeyArgument($input, $output);
        $linkTypes = $this->jiraApi()->linkTypes();

        $links = IssueLinkArgument::fromOptions($input->getOptions());

        foreach ($links as $link) {
            $issueLink = $this->link($issueKey, $link, $linkTypes);
            $this->showLinkInfo($output, $issueLink, $issueKey);
        }
    }

    private function showLinkInfo(OutputInterface $output, IssueLink $link, IssueKey $issueKey)
    {
        if ($link->isInward()) {
            $output->writeln(
                sprintf('<info>%s</info> %s %s', $link->inwardIssue()->issueKey(), $link->type()->inward(), $issueKey)
            );
        } else {
            $output->writeln(
                sprintf('<info>%s</info> %s %s', $issueKey, $link->type()->outward(), $link->outwardIssue()->issueKey())
            );
        }
    }

    /**
     * @param \Technodelight\Jira\Console\Argument\IssueKey $issueKey
     * @param \Technodelight\Jira\Console\Argument\IssueLinkArgument $link
     * @param \Technodelight\Jira\Domain\IssueLink\Type[] $linkTypes
     * @return \Technodelight\Jira\Domain\IssueLink
     * @throws \InvalidArgumentException
     */
    private function link(IssueKey $issueKey, IssueLinkArgument $link, array $linkTypes)
    {
        $existingLinks = $this->existingLinks($issueKey);
        $linkedLinks = $this->existingLinks($link->issueKey());

        foreach ($linkTypes as $linkType) {
            if ($this->canInwardLink($link, $linkType, $existingLinks)) {
                return $this->jiraApi()->linkIssue(
                    $link->issueKey(),
                    $issueKey,
                    $linkType->name()
                );
            }
            if ($this->canOutwardLink($link, $issueKey, $linkType, $linkedLinks)) {
                return $this->jiraApi()->linkIssue(
                    $issueKey,
                    $link->issueKey(),
                    $linkType->name()
                );
            }
        }

        throw new \InvalidArgumentException(
            sprintf('Cannot link %s with "%s" to %s', $issueKey, $link->relation(), $link->issueKey())
        );
    }

    private function existingLinks(IssueKey $issueKey)
    {
        $links = [];
        $issue = $this->jiraApi()->retrieveIssue($issueKey);
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
     * @return \Technodelight\Jira\Api\JiraRestApi\Api
     */
    private function jiraApi()
    {
        return $this->getService('technodelight.jira.api');
    }

    /**
     * @param \Technodelight\Jira\Console\Argument\IssueLinkArgument $link
     * @param \Technodelight\Jira\Domain\IssueLink\Type $linkType
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
     * @param \Technodelight\Jira\Console\Argument\IssueLinkArgument $link
     * @param \Technodelight\Jira\Console\Argument\IssueKey $issueKey
     * @param \Technodelight\Jira\Domain\IssueLink\Type $linkType
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
