<?php

namespace Technodelight\Jira\Helper;

use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Configuration\ApplicationConfiguration;

class ImageProvider
{
    const PREFIX = '_img_';
    /**
     * @var \Technodelight\Jira\Api\JiraRestApi\Api
     */
    private $api;
    /**
     * @var string
     */
    private $cacheDir;
    /**
     * @var int
     */
    private $ttl;

    public function __construct(Api $api, ApplicationConfiguration $config)
    {
        $this->api = $api;
        $this->ttl = $config->iterm()['imageCacheTtl'];
        $this->cacheDir = getenv('HOME') . DIRECTORY_SEPARATOR . '.jira.api_cache';
        $this->cleanup();
    }

    public function contents($url)
    {
        $path = $this->cacheDir . DIRECTORY_SEPARATOR . $this->filename($url);
        if (is_file($path)) {
            return file_get_contents($path);
        }

        $this->download($url, $path);
        return file_get_contents($path);
    }

    private function filename($url)
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
        $iterator = new \GlobIterator($this->cacheDir . DIRECTORY_SEPARATOR . self::PREFIX . '*');
        foreach ($iterator as $splFileInfo) {
            /** @var $splFileInfo \SplFileInfo  */
            if ($splFileInfo->getMTime() < $delta) {
                unlink($splFileInfo->getRealPath());
            }
        }
    }
}
