<?php

namespace Technodelight\Jira\Console\Application\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Technodelight\Jira\Configuration\Symfony\FilenameProvider;

class CacheMaintainer
{
    const CACHE_PATH = '.jira.container_cache/container.php';
    const HASH_PATH = '.jira.container_cache/container.md5';

    /**
     * @var FilenameProvider
     */
    private $filenameProvider;

    /**
     * @var string[]
     */
    private static $directoriesCache = [];

    public function __construct(FilenameProvider $filenameProvider)
    {
        $this->filenameProvider = $filenameProvider;
    }

    public static function containerCachePath()
    {
        $file = getenv('HOME') . DIRECTORY_SEPARATOR . self::CACHE_PATH;
        self::ensureDirectoryForFile($file);

        return $file;
    }

    public function checkAndInvalidate()
    {
        $containerHash = $this->containerHash();
        $configHash = $this->configHash();

        if (is_null($containerHash)) {
            return false;
        }

        if ($containerHash != $configHash) {
            $this->clear();
            return true;
        }

        return false;
    }

    public function dump(ContainerBuilder $builder)
    {
        $dumper = new PhpDumper($builder);

        file_put_contents(self::containerCachePath(), $dumper->dump());
        file_put_contents($this->hashFilePath(), $this->configHash());
    }

    public function clear()
    {
        $cache = self::containerCachePath();

        if (is_writable($cache)) {
            unlink($cache);
        }

        $hash = $this->hashFilePath();

        if (is_writable($hash)) {
            unlink($hash);
        }
    }

    private function hashFilePath()
    {
        $file = getenv('HOME') . DIRECTORY_SEPARATOR . self::HASH_PATH;
        self::ensureDirectoryForFile($file);

        return $file;
    }

    private function containerHash()
    {
        $hash = $this->hashFilePath();
        if (is_file($hash)) {
            return file_get_contents($hash);
        }

        return null;
    }

    private function configHash()
    {
        $files = [$this->filenameProvider->globalFile(), $this->filenameProvider->localFile()];
        $mds = [];
        foreach ($files as $file) {
            if (is_readable($file)) {
                $mds[] = md5_file($file);
            }
        }

        if (count($mds) > 0) {
            return md5(join('', $mds));
        }

        return null;
    }

    private static function ensureDirectoryForFile($file)
    {
        $dir = dirname($file);
        if (!in_array($dir, self::$directoriesCache)) {
            if (!is_dir($dir)) {
                mkdir($dir, 0744, true);
            }
            self::$directoriesCache[] = $dir;
        }
    }
}
