<?php

namespace Technodelight\Jira\Console\Command\Action\Issue;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Technodelight\Jira\Api\GitShell\Branch;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Console\Command\AbstractCommand;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Api\GitShell\Api as GitShell;
use Technodelight\Jira\Domain\Transition as IssueTransition;
use Technodelight\Jira\Helper\TemplateHelper;
use \UnexpectedValueException;

class Transition extends AbstractCommand
{
    const TRANSITION_DESCRIPTION_SINGLE = 'Moves issue to %s';
    const TRANSITION_DESCRIPTION_MULTIPLE = 'Moves issue to one of: %s (whichever applies first)';

    private $name;

    private $transitions;

    /**
     * Constructor.
     *
     * @throws \UnexpectedValueException When the command name is empty
     */
    public function __construct(ContainerBuilder $container, $name, $transitions)
    {
        if (empty($transitions)) {
            throw new UnexpectedValueException(
                sprintf('No transitions were defined for command: "%s"', $name)
            );
        }
        $this->name = $name;
        $this->transitions = $transitions;
        parent::__construct($container, $this->prepareIssueTransitionCommandName($name));
    }

    private function prepareIssueTransitionCommandName($name)
    {
        return sprintf('workflow:%s', $name);
    }

    protected function configure()
    {
        $this
            ->setDescription($this->getCommandDescription())
            ->setAliases([$this->name])
            ->addArgument(
                IssueKeyResolver::ARGUMENT,
                InputArgument::OPTIONAL,
                'Issue key (ie. PROJ-123)'
            )
            ->addOption(
                'assign',
                'a',
                InputOption::VALUE_NONE,
                'change assignee to you'
            )
            ->addOption(
                'unassign',
                'u',
                InputOption::VALUE_NONE,
                'unassign issue'
            )
            ->addOption(
                'branch',
                'b',
                InputOption::VALUE_NONE,
                'generate/choose branch'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issueKey = $this->issueKeyArgument($input, $output);
        $issue = $this->jiraApi()->retrieveIssue($issueKey);
        $transitions = $this->jiraApi()->retrievePossibleTransitionsForIssue($issueKey);

        try {
            $transition = $this->findTransitionByName($transitions, $this->transitions);
            $this->checkGitChanges($input, $output, $transition);
            $this->jiraApi()->performIssueTransition($issueKey, $transition->id());
            $actionString = '';
            if ($input->getOption('assign')) {
                $this->jiraApi()->updateIssue($issueKey, ['fields' => ['assignee' => ['name' => $this->jiraApi()->user()->name()]]]);
                $issue = $this->jiraApi()->retrieveIssue($issueKey);
                $actionString = ' and has been assigned to you';
            } else
            if ($input->getOption('unassign')) {
                $this->jiraApi()->updateIssue($issueKey, ['fields' => ['assignee' => ['name' => '']]]);
                $issue = $this->jiraApi()->retrieveIssue($issueKey);
                $actionString = ' and has been unassigned';
            }

            $output->writeln(
                sprintf(
                    'Task <info>%s</info> has been successfully moved to <comment>%s</comment>%s',
                    $issueKey,
                    $transition->name(),
                    $actionString
                )
            );
            $output->writeln($this->renderSuccessMessage($issue));
            $this->checkoutToBranch($input, $output, $issue);
        } catch (UnexpectedValueException $exception) {
            $output->writeln(
                sprintf('<error>%s</error>' . PHP_EOL, $exception->getMessage())
            );
            $output->writeln($this->renderUnsuccesfulMessage($issue, $transitions));
            $this->checkoutToBranch($input, $output, $issue);
            return 1;
        }
    }

    private function renderSuccessMessage(Issue $issue)
    {
        return [
            "<comment>link:</comment> {$issue->url()}",
            '<comment>branches:</comment>',
            $this->tab($this->retrieveGitBranches($issue))
        ];
    }

    /**
     * @param Issue $issue
     * @param IssueTransition[] $transitions
     * @return array
     */
    private function renderUnsuccesfulMessage(Issue $issue, array $transitions)
    {
        return [
            "It seems the issue <info>{$issue->key()}</info> is already <info>{$issue->status()}</info>, and currently assigned to <info>{$issue->assignee()}</info>",
            '',
            'Possible transitions:',
            $this->tab($this->listTransitions($issue, $transitions)),
            "<comment>link:</comment> {$issue->url()}"
        ];
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param Issue $issue
     */
    private function checkoutToBranch(InputInterface $input, OutputInterface $output, Issue $issue)
    {
        if ($input->getOption('branch')) {
            $this->checkoutBranch()->checkoutToBranch($input, $output, $issue);
        }
    }

    /**
     * @param IssueTransition[] $transitions
     * @param array $transitionsToSearchFor
     * @return IssueTransition
     * @throws \UnexpectedValueException
     */
    private function findTransitionByName(array $transitions, $transitionsToSearchFor)
    {
        foreach ($transitionsToSearchFor as $name) {
            foreach ($transitions as $transition) {
                if ($transition->name() == $name) {
                    return $transition;
                }
            }
        }

        throw new UnexpectedValueException(
            sprintf('Cannot apply any transition from %s for this issue', join(', ', $transitionsToSearchFor))
        );
    }

    private function gitBranchesForIssue(Issue $issue)
    {
        // @TODO: refactor this. The very same branch retrieval logic is present in Branch issue renderer
        $generatedName = $this->generateBranchName($issue);
        return array_map(
            function(Branch $branch) {
                return sprintf('%s (%s)', $branch->name(), $branch->isRemote() ? 'remote' : 'local');
            },
            array_merge(
                $this->gitShell()->branches($issue->ticketNumber()),
                $this->gitShell()->branches($generatedName)
            )
        );
    }

    private function generateBranchName(Issue $issue)
    {
        return $this->branchnameGenerator()->fromIssue($issue);
    }

    private function retrieveGitBranches(Issue $issue)
    {
        $branches = $this->gitBranchesForIssue($issue);
        if (empty($branches)) {
            $branches = [
                $this->generateBranchName($issue) . ' (generated)',
            ];
        }

        return implode(PHP_EOL, $branches);
    }

    /**
     * @param Issue $issue
     * @param IssueTransition[] $transitions
     * @return array
     */
    private function listTransitions(Issue $issue, array $transitions)
    {
        $list = [];
        foreach ($transitions as $transition) {
            $commandString = '';
            if ($command = $this->config()->transitions()->commandForTransition($transition->name())) {
                $commandString = "(jira $command {$issue->key()})";
            }
            $list[] = sprintf(
                '- <info>%s</info> %s' . PHP_EOL . '%s',
                $transition->name(),
                $commandString,
                $this->tab($this->tab(
                    wordwrap("Moves issue to <comment>{$transition->resolvesToName()}</comment>. {$transition->resolvesToDescription()}")
                ))
            );
        }

        return $list;
    }

    private function checkGitChanges(InputInterface $input, OutputInterface $output, IssueTransition $transition)
    {
        $git = $this->gitShell();
        $helper = $this->questionHelper();
        $templateHelper = $this->templateHelper();

        if ($diff = $git->diff()) {
            $output->writeln('It seems you have the following uncommited changes on your current branch:');
            foreach ($diff as $entry) {
                $output->writeln(
                    $templateHelper->tabulate(
                        sprintf('<comment>%s</comment> %s', $entry->state(), $entry->file())
                    )
                );
            }
            $question = new ConfirmationQuestion(
                sprintf(
                    'Are you sure you want to perform the <comment>%s</comment> transition?  [Y/n] ',
                    $transition->name()
                ),
                true
            );

            if (!$helper->ask($input, $output, $question)) {
                throw new \RuntimeException('Please commit your changes first.');
            }
        }
    }

    private function getCommandDescription()
    {
        if (count($this->transitions) == 1) {
            return sprintf(self::TRANSITION_DESCRIPTION_SINGLE, current($this->transitions));
        }
        return sprintf(self::TRANSITION_DESCRIPTION_MULTIPLE, join(', ', $this->transitions));
    }

    private function tab($string)
    {
        return $this->templateHelper()->tabulate($string);
    }

    /**
     * @return \Technodelight\Jira\Api\JiraRestApi\Api
     */
    private function jiraApi()
    {
        return $this->getService('technodelight.jira.api');
    }

    /**
     * @return \Technodelight\Jira\Helper\CheckoutBranch
     */
    private function checkoutBranch()
    {
        return $this->getService('technodelight.jira.checkout_branch');
    }

    /**
     * @return \Technodelight\Jira\Configuration\ApplicationConfiguration
     */
    private function config()
    {
        return $this->getService('technodelight.jira.config');
    }

    /**
     * @return \Technodelight\Jira\Helper\GitBranchnameGenerator
     */
    private function branchnameGenerator()
    {
        return $this->getService('technodelight.jira.git_branchname_generator');
    }

    /**
     * @return \Technodelight\Jira\Api\GitShell\Api
     */
    private function gitShell()
    {
        /** @var GitShell $git */
        return $this->getService('technodelight.gitshell.api');
    }

    /**
     * @return TemplateHelper
     */
    private function templateHelper()
    {
        return $this->getService('technodelight.jira.template_helper');
    }

    /**
     * @return \Symfony\Component\Console\Helper\QuestionHelper
     */
    private function questionHelper()
    {
        return $this->getHelper('question');
    }
}
