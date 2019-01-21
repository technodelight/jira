<?php

namespace Technodelight\Jira\Console\Command\Action\Issue;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Technodelight\GitShell\Api;
use Technodelight\Jira\Api\JiraRestApi\Api as Jira;
use Technodelight\Jira\Console\Input\PullRequest\EditorInput;
use Technodelight\Jira\Helper\HubHelper;
use Technodelight\Jira\Helper\TemplateHelper;

class PullRequest extends Command
{
    /**
     * @var HubHelper
     */
    private $hub;
    /**
     * @var Api
     */
    private $git;
    /**
     * @var Jira
     */
    private $jira;
    /**
     * @var EditorInput
     */
    private $prInput;
    /**
     * @var TemplateHelper
     */
    private $templateHelper;

    public function __construct(HubHelper $hub, Api $git, Jira $jira, EditorInput $prInput, TemplateHelper $templateHelper)
    {
        parent::__construct();

        $this->hub = $hub;
        $this->git = $git;
        $this->jira = $jira;
        $this->prInput = $prInput;
        $this->templateHelper = $templateHelper;
    }

    protected function configure()
    {
        $this
            ->setName('issue:pr')
            ->setAliases(['pr'])
            ->setDescription('Create pull request from current branch')
            ->addOption(
                'base',
                'b',
                InputOption::VALUE_OPTIONAL,
                'Specify base branch to create the PR against',
                'develop'
            )
            ->addOption(
                'head',
                'h',
                InputOption::VALUE_OPTIONAL,
                'Specify head branch, defaults to current branch',
                false
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     * @throws \Github\Exception\MissingArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $base = $input->getOption('base');
        $head = $input->getOption('head') === false ? $this->git->currentBranch()->name() : $input->getOption('head');
        $this->checkIfBranchIsBehind($input, $output, $head);
        if (false === $this->checkIfHasPr($input, $output, $head)) {
            $output->writeln(sprintf('<error>You already have an open PR for %s</error>', $head));
            return 1;
        }

        $pr = $this->prInput->gatherDataForPr($base, $head);

        $createdPr = $this->hub->createPr($pr->title(), $pr->body(), $base, $head);
        $prNumber = $createdPr['number'];
        $output->writeln(sprintf('<info>You have successfully created PR #%s</info> <fg=black>(%s)</>', $prNumber, $createdPr['url']));
        if (!empty($pr->labels())) {
            $this->hub->addLabels($prNumber, $pr->labels());
            $output->writeln(sprintf('Labels added: <comment>%s</>', join(', ', $pr->labels())));
        }
    }

    private function checkIfBranchIsBehind(InputInterface $input, OutputInterface $output, $head)
    {
        //@TODO: check also when the head is not the current branch!
        //@TODO: check for uncommited files too!
        if ($head === $this->git->currentBranch()) {
            $logs = $this->git->log('origin/' . $head, $head);
            if (!empty($logs) || $diff = $this->git->diff()) {
                $helper = $this->questionHelper();

                $output->writeln('It seems you have the following uncommited changes on your current branch:');
                foreach ($diff as $entry) {
                    $output->writeln(
                        $this->tab(
                            sprintf('<comment>%s</comment> %s', $entry->state(), $entry->file())
                        )
                    );
                }
                $question = new ConfirmationQuestion(
                    'Are you sure you want to create a PR? [Y/n] ',
                true
                );

                if (!$helper->ask($input, $output, $question)) {
                    throw new \RuntimeException('OK. See you later ;)');
                }
            }
        }
    }

    private function checkIfHasPr(InputInterface $input, OutputInterface $output, $head)
    {
        $prs = $this->hub->prForHead($head, 'open');
        if (!empty($prs)) {
            $output->writeln('You already have an open PR for this branch!');
            return false;
        }

        return true;
    }

    /**
     * @return \Symfony\Component\Console\Helper\QuestionHelper
     */
    private function questionHelper()
    {
        return $this->getHelper('question');
    }

    private function tab($string)
    {
        return $this->templateHelper->tabulate($string);
    }
}
