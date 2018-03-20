<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;
use Technodelight\Jira\Renderer\Issue\CustomField\DefaultFormatter;

class RenderersConfiguration implements RegistrableConfiguration
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

    private $defaults = [
        'short' => [
            'inherit' => true,
            'fields' => [
                ['name' => 'header'],
                ['name' => 'user_details'],
                ['name' => 'progress'],
                ['name' => 'priority'],
                ['name' => 'short_description'],
                ['name' => 'versions'],
            ],
        ],
        'full' => [
            'inherit' => true,
            'fields' => [
                ['name' => 'header'],
                ['name' => 'user_details'],
                ['name' => 'progress'],
                ['name' => 'priority'],
                ['name' => 'full_description'],
                ['name' => 'issue_relations'],
                ['name' => 'versions'],
                ['name' => 'attachments'],
                ['name' => 'branches'],
                ['name' => 'github'],
                ['name' => 'worklogs'],
                ['name' => 'comments'],
            ],
        ],
    ];

    public static function fromArray(array $config)
    {
        $instance = new self;
        $instance->short = RendererConfiguration::fromArray($instance->configMerged($config, 'short'));
        $instance->full = RendererConfiguration::fromArray($instance->configMerged($config, 'full'));
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

    public function servicePrefix()
    {
        return 'renderers';
    }

    private function __construct()
    {
    }

    private function configMerged(array $config, $key)
    {
        return array_merge_recursive($this->defaults[$key], isset($config[$key]) ? $config[$key] : []);
    }
}
