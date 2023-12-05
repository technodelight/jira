<?php

namespace Technodelight\Jira\Console\Command\App;

use ErrorException;
use Github\Client;
use Phar;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Technodelight\Jira\Console\DependencyInjection\CacheMaintainer;
use Technodelight\Jira\Helper\Downloader;

class SelfUpdate extends Command
{
    private const DEFAULT_LOCAL_BIN_JIRA = '/usr/local/bin/jira';

    public function __construct(
        private readonly Client $github,
        private readonly CacheMaintainer $cacheMaintainer
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:self-update')
            ->setDescription('Check latest releases and update')
            ->setAliases(['selfupdate', 'self-update'])
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Accept everything')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $runningFile = Phar::running(false) ?: self::DEFAULT_LOCAL_BIN_JIRA;
        $release = $this->github->repo()->releases()->all('technodelight', 'jira')[0];
        $currentVersion = trim($this->getApplication()->getVersion());

        if (version_compare($currentVersion, $release['tag_name'], '<') && isset($release['assets'][0])) {
            $output->writeln('✨ <comment>Yay, let\'s do an update!</comment>✨');
            $output->writeln('');
            $output->writeln($this->getReleaseNotesSince($currentVersion, $release['tag_name']));
            $output->writeln('');
            $output->writeln("Release date is {$release['published_at']}");
            $q = new QuestionHelper();
            if ($input->getOption('yes')) {
                $consent = true;
            } else {
                $consent = $q->ask(
                    $input,
                    $output,
                    new Question(PHP_EOL . "<comment>Do you want to perform an update now?</comment> [Y/n]", true)
                );
            }
            if ($consent) {
                if (!is_writable($runningFile)) {
                    $output->writeln("<error>Can't write file {$runningFile}.</error>");
                    return self::FAILURE;
                }
                if ($this->update($output, $runningFile, $release['assets'][0]['browser_download_url'])) {
                    $output->writeln("<info>Successfully updated to {$release['tag_name']}</info> 🤘");
                } else {
                    $output->writeln("<error>Something unexpected happened during update.</error>");
                    return self::FAILURE;
                }
            }
        } else {
            $output->writeln('👍 You are using the latest <info>' . $currentVersion . '</info> version');
        }
        return self::SUCCESS;
    }

    private function update(OutputInterface $output, $runningFile, $newReleaseUrl): bool
    {
        $downloader = new Downloader;
        if ($downloader->downloadWithCurl($output, $newReleaseUrl, $runningFile)) {
            if(!is_executable($runningFile) && !@chmod($runningFile, 0755)) {
                $output->writeln(sprintf('chmod failed and file %1$s is not executable,'
                    . ' please check the output of \'chmod 0755 %1$s\'', $runningFile));
            }
            $this->cacheMaintainer->clear();

            return true;
        }

        return false;
    }

    private function getReleaseNotesSince($currentVersion, $newVersion): array
    {
        $releasesSince = array_filter(
            array_reverse($this->github->repo()->releases()->all('technodelight', 'jira')),
            static function (array $release) use ($currentVersion, $newVersion) {
                return version_compare($currentVersion, $release['tag_name'], '<') && isset($release['assets'][0])
                    && version_compare($newVersion, $release['tag_name'], '>=');
            }
        );
        $releaseNotes = [];
        foreach ($releasesSince as $release) {
            $releaseNotes[] = "<info>{$release['tag_name']}</info> {$release['name']} "
                . ($newVersion === $release['tag_name'] ? '<info>(latest)</>' : '');
            $releaseNotes[] = '';
            if (!empty(trim($release['body']))) {
                $releaseNotes[] = trim($release['body']);
                $releaseNotes[] = '';
            }
        }
        return $releaseNotes;
    }
}
