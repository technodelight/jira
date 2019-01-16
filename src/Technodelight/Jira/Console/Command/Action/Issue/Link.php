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

        $existingLinks = $this->jiraApi()->retrieveIssue((string) $issueKey)->links();
        $links = IssueLinkArgument::fromOptions($this->optionsFromInput($input));

        foreach ($links as $link) {
            $this->link($issueKey, $link, $linkTypes);
        }
        $links = $this->jiraApi()->retrieveIssue((string) $issueKey)->links();
        $this->showLinkInfo($output, $existingLinks, $links, $issueKey);
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param IssueLink[] $existingLinks
     * @param IssueLink[] $newLinks
     * @param \Technodelight\Jira\Console\Argument\IssueKey $issueKey
     */
    private function showLinkInfo(OutputInterface $output, array $existingLinks, array $newLinks, IssueKey $issueKey)
    {
        $output->writeln(['<info>Done!</info>', '']);
        $addedLinks = array_diff($newLinks, $existingLinks);
        $removedLinks = array_diff($existingLinks, $newLinks);
        $unchangedLinks = array_intersect($existingLinks, $newLinks);

        if (!empty($addedLinks)) {
            $output->writeln('<comment>Added:</comment>');
            $this->writelnLinksArray($output, $issueKey, $addedLinks);
        }
        if (!empty($removedLinks)) {
            $output->writeln('<comment>Removed:</comment>');
            $this->writelnLinksArray($output, $issueKey, $removedLinks);
        }
        if (!empty($unchangedLinks)) {
            $output->writeln('<comment>Unchanged:</comment>');
            $this->writelnLinksArray($output, $issueKey, $unchangedLinks);
        }
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param \Technodelight\Jira\Console\Argument\IssueKey $issueKey
     * @param IssueLink[] $links
     */
    private function writelnLinksArray(OutputInterface $output, IssueKey $issueKey, array $links)
    {
        foreach ($links as $link) {
            $output->writeln($this->renderLink($link, $issueKey));
        }
    }

    private function renderLink(IssueLink $link, IssueKey $issueKey)
    {
        if ($link->isInward()) {
            return sprintf('<info>%s</info> %s <comment>%s</comment>', $issueKey, $link->type()->outward(), $link->inwardIssue()->issueKey());
        }

        return sprintf('<info>%s</info> %s <comment>%s</comment>', $link->outwardIssue()->issueKey(), $link->type()->inward(), $issueKey);
    }

    /**
     * @param \Technodelight\Jira\Console\Argument\IssueKey $issueKey
     * @param \Technodelight\Jira\Console\Argument\IssueLinkArgument $link
     * @param \Technodelight\Jira\Domain\IssueLink\Type[] $linkTypes
     * @return void
     * @throws \InvalidArgumentException
     */
    private function link(IssueKey $issueKey, IssueLinkArgument $link, array $linkTypes)
    {
        $existingLinks = $this->existingLinks($issueKey);
        $linkedLinks = $this->existingLinks($link->issueKey());

        foreach ($linkTypes as $linkType) {
            if ($this->canInwardLink($link, $linkType, $existingLinks)) {
                $this->jiraApi()->linkIssue(
                    (string) $link->issueKey(),
                    (string) $issueKey,
                    $linkType->name()
                );
                return;
            }
            if ($this->canOutwardLink($link, $issueKey, $linkType, $linkedLinks)) {
                $this->jiraApi()->linkIssue(
                    (string) $issueKey,
                    (string) $link->issueKey(),
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
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @return array
     */
    protected function optionsFromInput(InputInterface $input)
    {
        $aliasesConfiguration = $this->aliasesConfig();
        $opts = [];
        foreach ($input->getOptions() as $relation => $issueKey) {
            $opts[$relation] = $aliasesConfiguration->aliasToIssueKey($issueKey);
        }
        return $opts;
    }

    /**
     * @return \Technodelight\Jira\Configuration\ApplicationConfiguration\AliasesConfiguration
     */
    private function aliasesConfig()
    {
        return $this->getService('technodelight.jira.config.aliases');
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
