<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Renderer\Issue\CustomField\DefaultFormatter;

class RenderersConfiguration
{
    /**
     * @var \Technodelight\Jira\Configuration\ApplicationConfiguration\RendererConfiguration
     */
    private $short;
    /**
     * @var \Technodelight\Jira\Configuration\ApplicationConfiguration\RendererConfiguration
     */
    private $full;
    /**
     * @var \Technodelight\Jira\Configuration\ApplicationConfiguration\FormatterConfiguration[]
     */
    private $formatters;

    private $defaultFormatters = [
        ['name' => 'default', 'class' => DefaultFormatter::class],
    ];

    public static function fromArray(array $config)
    {
        $instance = new self;
        $instance->short = RendererConfiguration::fromArray(isset($config['short']) ? $config['short'] : []);
        $instance->full = RendererConfiguration::fromArray(isset($config['full']) ? $config['full'] : []);
        $instance->formatters = array_map(
            function (array $formatter)  {
                return FormatterConfiguration::fromArray($formatter);
            },
            array_merge($instance->defaultFormatters, isset($config['formatters']) ? $config['formatters'] : [])
        );

        return $instance;
    }

    public function short()
    {
        return $this->short;
    }

    public function full()
    {
        return $this->full;
    }

    public function formatters()
    {
        return $this->formatters;
    }

    private function __construct()
    {
    }
}
