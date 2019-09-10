<?php

namespace Technodelight\Jira\Extension;

use Symfony\Component\Config\Definition\Processor;
use Technodelight\Jira\Configuration\Configuration\Extensions;

class ConfigProcessor
{
    public function process(array $configs): array
    {
        if (isset($configs['extensions'])) {
            return (new Processor)->process((new Extensions)->configurations()->getNode(), $configs['extensions']);
        }

        return [];
    }
}
