<?php

namespace Technodelight\Jira\Console\Command\Action;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\Application;

class Batch extends Command
{
    /**
     * @var Application
     */
    private $app;

    protected function configure()
    {
        $this
            ->setName('batch')
            ->setDescription('Batch any command and execute in one go')
            ->ignoreValidationErrors()
        ;
    }


    public function setApp(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issueKeys = file('php://stdin', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $args = $_SERVER['argv'];
        $this->app->setAutoExit(false);
        foreach ($issueKeys as $issueKey) {
            $this->app->run($this->prepareArgs($issueKey, $args), $output);
        }
        $this->app->setAutoExit(true);
    }

    /**
     * @param array $args
     * @return ArgvInput
     */
    protected function prepareArgs($issueKey, array $args)
    {
        if ($args[0] == $_SERVER['PHP_SELF']) {
            array_shift($args);
        }
        if ($args[0] == 'batch') {
            array_shift($args);
        }

        if (in_array('+', $args)) {
            while(in_array('+', $args) === true) {
                $args[array_search('+', $args)] = $issueKey;
            }
        } else {
            array_push($args, $issueKey);
        }

        return new StringInput(join(' ', $args));
    }
}
