<?php

namespace Technodelight\Jira\Console\Configuration;

class Provider
{
    public function __construct(
        private readonly DirectoryProvider $directoryProvider,
        private readonly Loader $loader
    ) {}

    public function get(): array
    {
        return $this->loader->load([
            $this->directoryProvider->project(),
            $this->directoryProvider->user(),
        ]);
    }
}
