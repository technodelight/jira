<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration\Service;

interface RegistrableConfiguration
{
    /**
     * @return string
     */
    public function servicePrefix();

    /**
     * @return array
     */
    public function configAsArray();
}
