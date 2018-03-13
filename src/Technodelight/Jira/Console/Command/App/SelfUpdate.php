<?php

namespace Technodelight\Jira\Console\Command\App;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\Command\AbstractCommand;

class SelfUpdate extends AbstractCommand
{
    const DEFAULT_LOCAL_BIN_JIRA = '/usr/local/bin/jira';

    protected function configure()
    {
        $this
            ->setName('app:selfupdate')
            ->setDescription('Check latest releases and update')
            ->setAliases(['selfupdate', 'self-update'])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getService('console.dialog_helper');
        $runningFile = \Phar::running(false) ?: self::DEFAULT_LOCAL_BIN_JIRA;
        $release = $this->gitHubApi()->api('repo')->releases()->all('technodelight', 'jira')[0];
        $currentVersion = trim($this->getApplication()->getVersion());

        if (version_compare($currentVersion, $release['tag_name'], '<') && isset($release['assets'][0])) {
            $output->writeln('✨ <comment>Yay, let\'s do an update!</comment>✨');
            $output->writeln('');
            $output->writeln($this->getReleaseNotesSince($currentVersion, $release['tag_name']));
            $output->writeln('');
            $output->writeln("Release date is {$release['published_at']}");
            $consent = $dialog->askConfirmation(
                $output,
                PHP_EOL . "<comment>Do you want to perform an update now?</comment> [y/N]",
                false
            );
            if (!is_writable($runningFile)) {
                $output->writeln("<error>Can't write file {$runningFile}.</error>");
                return 1;
            }
            if ($consent) {
                if ($this->update($output, $runningFile, $release['assets'][0]['browser_download_url'])) {
                    $output->writeln("<info>Successfully updated to {$release['tag_name']}</info> 🤘");
                } else {
                    $output->writeln("<error>Something unexpected happened during update.</error>");
                    return 1;
                }
            }
        } else {
            $output->writeln('👍 You are using the latest <info>' . $currentVersion . '</info> version');
        }
        return 0;
    }

    private function update(OutputInterface $output, $runningFile, $newReleaseUrl)
    {
        $progress = $this->createProgressBar($output);

        $ch = curl_init($newReleaseUrl);
        $f = fopen($runningFile, 'w');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOPROGRESS, false);
        curl_setopt($ch, CURLOPT_FILE, $f);
        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function($ch, $downloadTotal, $downloadedBytes) use ($progress) {
            if ($progress->getMaxSteps() == 0) {
                $progress->start($downloadTotal);
                $progress->setProgress($downloadedBytes);
            } else {
                $progress->setFormat('%bar% %percent%% %remaining%');
                $progress->setProgress($downloadedBytes);
            }
        });
        curl_exec($ch);
        $err = curl_errno($ch);
        curl_close($ch);
        fclose($f);
        $progress->finish();
        $output->writeln('');
        chmod($runningFile, 0755);
        return $err == 0;
    }

    private function createProgressBar(OutputInterface $output)
    {
        $progress = new ProgressBar($output);
        $progress->setFormat('%bar% %percent%%');
        $progress->setBarCharacter('<bg=green> </>');
        $progress->setEmptyBarCharacter('<bg=white> </>');
        $progress->setProgressCharacter('<bg=green> </>');
        $progress->setBarWidth(50);
        $progress->setRedrawFrequency(500000);
        return $progress;
    }

    private function getReleaseNotesSince($currentVersion, $newVersion)
    {
        $releasesSince = array_filter(
            array_reverse($this->gitHubApi()->api('repo')->releases()->all('technodelight', 'jira')),
            function (array $release) use ($currentVersion, $newVersion) {
                return version_compare($currentVersion, $release['tag_name'], '<') && isset($release['assets'][0])
                    && version_compare($newVersion, $release['tag_name'], '>=');
            }
        );
        $releaseNotes = [];
        foreach ($releasesSince as $release) {
            $releaseNotes[] = "<info>{$release['tag_name']}</info> {$release['name']} " . ($newVersion == $release['tag_name'] ? '<info>(latest)</>' : '');
            $releaseNotes[] = '';
            if (!empty(trim($release['body']))) {
                $releaseNotes[] = trim($release['body']);
                $releaseNotes[] = '';
            }
        }
        return $releaseNotes;
    }

    /**
     * @return object
     */
    protected function gitHubApi()
    {
        return $this->getService('technodelight.github.api');
    }
}
