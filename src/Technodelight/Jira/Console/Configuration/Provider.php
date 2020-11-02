<?php

namespace Technodelight\Jira\Console\Configuration;

class Provider
{
    private $directoryProvider;
    private $loader;

    public function __construct(DirectoryProvider $directoryProvider, Loader $loader)
    {
        $this->directoryProvider = $directoryProvider;
        $this->loader = $loader;
    }

    public function get(): array
    {
        return $this->loader->load([
            $this->directoryProvider->project(),
            $this->directoryProvider->user(),
        ]);
    }
}
