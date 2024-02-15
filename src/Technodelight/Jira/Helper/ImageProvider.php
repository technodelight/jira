<?php

namespace Technodelight\Jira\Helper;

use GlobIterator;
use SplFileInfo;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\ITermConfiguration;

class ImageProvider
{
    private const PREFIX = '_img_';

    private string $cacheDir;
    private int $ttl;

    public function __construct(
        private readonly Api $api,
        ITermConfiguration $config
    ) {
        $this->ttl = (int)$config->imageCacheTtl();
        $this->cacheDir = getenv('HOME') . DIRECTORY_SEPARATOR . '.jira/img_cache';
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0744);
        }
        $this->cleanup();
    }

    public function contents(string $url): string
    {
        $path = $this->cacheDir . DIRECTORY_SEPARATOR . $this->filename($url);
        if (is_file($path)) {
            return file_get_contents($path);
        }

        $this->download($url, $path);
        return file_get_contents($path);
    }

    private function filename($url): string
    {
        return self::PREFIX . md5($url);
    }

    private function download($url, $filename)
    {
        $this->api->download($url, $filename);
    }

    private function cleanup()
    {
        $delta = strtotime('-' . $this->ttl . ' day');
        $iterator = new GlobIterator($this->cacheDir . DIRECTORY_SEPARATOR . self::PREFIX . '*');
        foreach ($iterator as $splFileInfo) {
            /** @var $splFileInfo SplFileInfo  */
            if ($splFileInfo->getMTime() < $delta) {
                unlink($splFileInfo->getRealPath());
            }
        }
    }
}
