<?php

namespace Technodelight\Jira\Console;

use Symfony\Component\Console\Application as BaseApp;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Technodelight\Jira\Console\Command\TodoCommand;
use Technodelight\Jira\Console\Command\ListWorkInProgressCommand;

use Technodelight\Jira\Configuration\Configuration;
use Technodelight\Jira\Configuration\GlobalConfiguration;

use Technodelight\Jira\Helper\GitHelper;

use Technodelight\Jira\Api\Client as JiraClient;

class Application extends BaseApp
{
    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @var GitHelper
     */
    protected $gitHelper;

    /**
     * @var JiraClient
     */
    protected $jira;

    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();
        $commands[] = new TodoCommand;
        $commands[] = new ListWorkInProgressCommand;
        return $commands;
    }

    /**
     * @return Configuration
     */
    public function config()
    {
        if (!isset($this->config)) {
            // init configuration
            $config = GlobalConfiguration::initFromDirectory(getenv('HOME'));
            $projectConfig = Configuration::initFromDirectory($this->git()->topLevelDirectory());
            $config->merge($projectConfig);
            $this->config = $config;
        }

        return $this->config;
    }

    /**
     * @return GitHelper
     */
    public function git()
    {
        if (!isset($this->gitHelper)) {
            $this->gitHelper = new GitHelper;
        }

        return $this->gitHelper;
    }

    /**
     * @return JiraClient
     */
    public function jira()
    {
        if (!isset($this->jira)) {
            $this->jira = new JiraClient($this->config());
        }

        return $this->jira;
    }

    // /**
    //  * Configures the input and output instances based on the user arguments and options.
    //  *
    //  * @param InputInterface  $input  An InputInterface instance
    //  * @param OutputInterface $output An OutputInterface instance
    //  */
    // protected function configureIO(InputInterface $input, OutputInterface $output)
    // {
    //     parent::configureIO($input, $output);
    //     $this->handleProjectArgument($input);
    // }

    // private function handleProjectArgument(InputInterface $input)
    // {
    //     if (!$input->hasArgument('project')) {
    //         $input->setArgument('project', $this->config()->project());
    //     }
    // }
}
