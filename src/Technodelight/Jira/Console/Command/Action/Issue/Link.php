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
            )
        ;
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
        foreach ($linkTypes as $linkType) {
            if ($linkType->inward() == $link->relation()) {
                return $this->jiraApi()->linkIssue(
                    $link->issueKey(),
                    $issueKey,
                    $linkType->name()
                );
            }
            if ($linkType->outward() == $link->relation()) {
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

    /**
     * @return \Technodelight\Jira\Api\JiraRestApi\Api
     */
    private function jiraApi()
    {
        return $this->getService('technodelight.jira.api');
    }
}
