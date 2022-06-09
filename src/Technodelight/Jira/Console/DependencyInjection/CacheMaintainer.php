<?php

namespace Technodelight\Jira\Console\DependencyInjection;

use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Technodelight\Jira\Console\Configuration\DirectoryProvider;

class CacheMaintainer
{
    private const CACHE_PATH = '.jira/container/cache.php';
    private const HASH_PATH = '.jira/container/cache.md5';

    private DirectoryProvider $directoryProvider;
    /**
     * @var string[]
     */
    private static array $directoriesCache = [];

    public function __construct(DirectoryProvider $directoryProvider)
    {
        $this->directoryProvider = $directoryProvider;
    }

    public static function containerCachePath(): string
    {
        $file = getenv('HOME') . DIRECTORY_SEPARATOR . self::CACHE_PATH;
        self::ensureDirectoryForFile($file);

        return $file;
    }

    public function checkAndInvalidate(): bool
    {
        $containerHash = $this->containerHash();
        $configHash = $this->configHash();

        if (is_null($containerHash)) {
            return false;
        }

        if ($containerHash !== $configHash) {
            $this->clear();
            return true;
        }

        return false;
    }

    public function dump(ContainerBuilder $builder): void
    {
        $dumper = new PhpDumper($builder);

        file_put_contents(self::containerCachePath(), $dumper->dump());
        file_put_contents($this->hashFilePath(), $this->configHash());
    }

    public function clear(): void
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

    private function hashFilePath(): string
    {
        $file = getenv('HOME') . DIRECTORY_SEPARATOR . self::HASH_PATH;
        self::ensureDirectoryForFile($file);

        return $file;
    }

    private function containerHash(): ?string
    {
        $hash = $this->hashFilePath();
        if (is_file($hash)) {
            return file_get_contents($hash);
        }

        return null;
    }

    private function configHash(): ?string
    {
        $files = [
            $this->directoryProvider->user() . DIRECTORY_SEPARATOR . '.jira.yml',
            $this->directoryProvider->project() . DIRECTORY_SEPARATOR . '.jira.yml',
        ];
        $mds = [];
        foreach ($files as $file) {
            if (is_readable($file)) {
                $mds[] = md5_file($file);
            }
        }

        if (count($mds) > 0) {
            return md5(implode('', $mds));
        }

        return null;
    }

    private static function ensureDirectoryForFile($file): void
    {
        $dir = dirname($file);
        if (!in_array($dir, self::$directoriesCache)) {
            if (!is_dir($dir) && !mkdir($dir, 0744, true) && !is_dir($dir)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
            }
            self::$directoriesCache[] = $dir;
        }
    }
}
