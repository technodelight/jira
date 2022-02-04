<?php

namespace Technodelight\Jira\Console\Command\App;

use Github\Client;
use Phar;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\DependencyInjection\CacheMaintainer;
use Technodelight\Jira\Helper\Downloader;

class SelfUpdate extends Command
{
    const DEFAULT_LOCAL_BIN_JIRA = '/usr/local/bin/jira';
    /**
     * @var Client
     */
    private $github;
    /**
     * @var CacheMaintainer
     */
    private $cacheMaintainer;
    /**
     * @var DialogHelper
     */
    private $dialogHelper;

    public function __construct(Client $github, CacheMaintainer $cacheMaintainer, DialogHelper $dialogHelper)
    {
        $this->github = $github;
        $this->cacheMaintainer = $cacheMaintainer;
        $this->dialogHelper = $dialogHelper;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:selfupdate')
            ->setDescription('Check latest releases and update')
            ->setAliases(['selfupdate', 'self-update'])
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     * @throws \ErrorException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
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
            $consent = $this->dialogHelper->askConfirmation(
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

    /**
     * @param OutputInterface $output
     * @param string $runningFile
     * @param string $newReleaseUrl
     * @throws \ErrorException
     */
    private function update(OutputInterface $output, $runningFile, $newReleaseUrl): bool
    {
        $downloader = new Downloader;
        if ($downloader->downloadWithCurl($output, $newReleaseUrl, $runningFile)) {
            chmod($runningFile, 0755);
            $this->cacheMaintainer->clear();

            return true;
        } else {
            throw new \ErrorException(
                'Cannot update to the latest release :('
            );
        }
    }

    private function getReleaseNotesSince($currentVersion, $newVersion)
    {
        $releasesSince = array_filter(
            array_reverse($this->github->repo()->releases()->all('technodelight', 'jira')),
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
}
