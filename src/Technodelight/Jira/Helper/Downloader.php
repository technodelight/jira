<?php

declare(strict_types=1);

namespace Technodelight\Jira\Helper;

use CurlHandle;
use Symfony\Component\Console\Output\OutputInterface;

class Downloader
{
    public function downloadWithCurl(OutputInterface $output, string $downloadUrl, string $targetFile): bool
    {
        $file = fopen($targetFile, 'w');
        $curl = $this->initCurl($downloadUrl, $file, $this->progressBar($output));

        $mask = umask(0);
        curl_exec($curl);
        $err = curl_errno($curl);
        curl_close($curl);
        fclose($file);
        umask($mask);

        $output->writeln('');

        return $err === 0;
    }

    public function progressBar(OutputInterface $output): callable {
        $startTime = microtime(true);
        return static function (...$args) use ($output, $startTime) {
            [, $downloadedBytes, $downloadTotal] = $args;
            if ($downloadTotal > 0) {
                $output->write(
                    sprintf(
                        "\033[1G\033[2K" . '<fg=green>[%s%%]</> downloaded: %.4fMiB / %.4fMiB in %.3fs',
                        str_pad(
                            (string)(($downloadedBytes / $downloadTotal) * 100),
                            3,
                            ' ',
                            STR_PAD_LEFT
                        ),
                        $downloadedBytes / 1024 / 1024,
                        $downloadTotal / 1024 / 1024,
                        microtime(true) - $startTime
                    )
                );
            }
        };
    }

    private function initCurl(string $downloadUrl, $file, callable $callback): CurlHandle
    {
        $curl = curl_init($downloadUrl);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_NOPROGRESS, false);
        curl_setopt($curl, CURLOPT_FILE, $file);
        curl_setopt($curl, CURLOPT_PROGRESSFUNCTION, $callback);

        return $curl;
    }
}
