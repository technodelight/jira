<?php

namespace Technodelight\Jira\Console\Configuration;

class Provider
{
    /**
     * @var DirectoryProvider
     */
    private $directoryProvider;
    /**
     * @var Loader
     */
    private $loader;
    /**
     * @var array|null
     */
    private $configs;

    public function __construct(DirectoryProvider $directoryProvider, Loader $loader)
    {
        $this->loader = $loader;
        $this->directoryProvider = $directoryProvider;
    }

    public function get(): array
    {
        if (!$this->configs) {
            $this->configs = $this->loader->load([
                $this->directoryProvider->project(),
                $this->directoryProvider->user(),
            ]);
        }

        return $this->configs;
    }
}
