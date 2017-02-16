<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\Command\AbstractCommand;

class SelfUpdateCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('selfupdate')
            ->setDescription('Check latest releases and update')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $git = $this->getService('technodelight.github.api');
        $dialog = $this->getService('console.dialog_helper');
        $runningFile = \Phar::running();
        $release = $git->api('repo')->releases()->all('technodelight', 'jira')[0];
        $currentVersion = trim($this->getApplication()->getVersion());
        if (version_compare($currentVersion, $release['tag_name'], '>=')) {
            $output->writeln('✨ <comment>Yay, let\'s do an update!</comment>✨');
            $output->writeln('');
            $output->writeln("<info>{$release['tag_name']}</info> {$release['name']}");
            $output->writeln($release['body']);
            $output->writeln('');
            $output->writeln("Release date is {$release['published_at']}");
            $consent = $dialog->askConfirmation(
                $output,
                PHP_EOL . "<question>Do you want to perform an update now?</question>",
                false
            );
            if ($consent && $runningFile) {
                file_put_contents($runningFile, file_get_contents($release['assets'][0]['browser_download_url']));
                chmod($runningFile, 755);
            }
        } else {
            $output->writeln('You are using the latest <info>' . $currentVersion . '</info> version');
        }
    }
}
