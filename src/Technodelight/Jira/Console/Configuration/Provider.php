<?php

namespace Technodelight\Jira\Console\Configuration;

readonly class Provider
{
    public function __construct(
        private DirectoryProvider $directoryProvider,
        private Loader $loader
    ) {}

    public function get(): array
    {
        return $this->loader->load([
            $this->directoryProvider->project(),
            $this->directoryProvider->dotConfig(),
            $this->directoryProvider->user(),
        ]);
    }
}
