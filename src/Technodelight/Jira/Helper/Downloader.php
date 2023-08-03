<?php

declare(strict_types=1);

namespace Technodelight\Jira\Helper;

use CurlHandle;
use Symfony\Component\Console\Output\OutputInterface;

class Downloader
{
    public function downloadWithCurl(OutputInterface $output, $downloadUrl, $targetFile): bool
    {
        $callback = static function ($resource, $downloadTotal, $downloadedBytes) use ($output) {
            $output->write(
                sprintf(
                    "\033[1G\033[2K" . 'downloaded: %.4fMiB / %.4fMiB' . "\033[1A",
                    $downloadedBytes / 1024 / 1024,
                    $downloadTotal / 1024 / 1024
                )
            );
        };

        $f = fopen($targetFile, 'w');
        $ch = $this->initCurl($downloadUrl, $f, $callback);

        $old = umask(0);
        curl_exec($ch);
        $err = curl_errno($ch);
        curl_close($ch);
        fclose($f);
        umask($old);

        $output->writeln('');

        return $err === 0;
    }

    /**
     * @param string $downloadUrl
     * @param resource $f
     * @param callable $callback
     * @return CurlHandle
     */
    private function initCurl(string $downloadUrl, $f, callable $callback): CurlHandle
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
