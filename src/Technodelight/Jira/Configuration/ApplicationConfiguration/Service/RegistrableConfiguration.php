<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration\Service;

interface RegistrableConfiguration
{
    public function servicePrefix(): string;

    public function configAsArray(): array;
}
