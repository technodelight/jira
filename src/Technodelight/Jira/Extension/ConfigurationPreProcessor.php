<?php

namespace Technodelight\Jira\Extension;

class ConfigurationPreProcessor
{
    public function preProcess(array $configurations)
    {
        // each configuration is a complete subtree we have to merge
        $config = array_shift($configurations);
        foreach ($configurations as $configuration) {
            $config = array_merge_recursive($config, $configuration);
        }

        return $config;
    }
}
