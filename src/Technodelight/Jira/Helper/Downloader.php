<?php

namespace Technodelight\Jira\Helper;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class Downloader
{
    /**
     * @param OutputInterface $output
     * @return array [ProgressBar, Closure]
     */
    public function progressBarWithProgressFunction(OutputInterface $output)
    {
        /** @var ProgressBar $progress */
        $progress = $this->createProgressBar($output);
        $progressFunction = function($resource, $downloadTotal, $downloadedBytes, $upload_size, $uploaded) use ($progress) {
            static $total = null;

            try {
                if ($total !== $downloadTotal) {
                    $progress->clear();
                    $progress->start($downloadTotal);
                    $progress->setFormat('%bar% %percent%% %remaining%');
                    $progress->setProgress($downloadedBytes);
                    $total = $downloadTotal;
                } else if ($progress->getStartTime() && $downloadedBytes > 0) {
                    $progress->setProgress($downloadedBytes);
                }
            } catch (\Exception $e) {
                
            }
        };

        return [$progress, $progressFunction];
    }

    public function downloadWithCurl(OutputInterface $output, $downloadUrl, $targetFile)
    {
        list($progress, $callback) = $this->progressBarWithProgressFunction($output);
        /** @var ProgressBar $progress */
        /** @var \Closure $callback */

        $f = fopen($targetFile, 'w');
        $ch = $this->initCurl($downloadUrl, $f, $callback);

        curl_exec($ch);
        $err = curl_errno($ch);
        curl_close($ch);
        fclose($f);

        $progress->finish();
        $output->writeln('');

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

    /**
     * @param string $downloadUrl
     * @param resource $f
     * @param callable $callback
     * @return resource
     */
    private function initCurl($downloadUrl, $f, callable $callback)
    {
        $ch = curl_init($downloadUrl);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOPROGRESS, false);
        curl_setopt($ch, CURLOPT_FILE, $f);
        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, $callback);

        return $ch;
    }
}
