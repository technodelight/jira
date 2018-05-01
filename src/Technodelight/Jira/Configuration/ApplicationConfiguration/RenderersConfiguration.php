<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;
use Technodelight\Jira\Renderer\Issue\CustomField\DefaultFormatter;

class RenderersConfiguration implements RegistrableConfiguration
{
    /**
     * @var RendererConfiguration
     */
    private $short;
    /**
     * @var RendererConfiguration
     */
    private $full;
    /**
     * @var \Technodelight\Jira\Configuration\ApplicationConfiguration\FormatterConfiguration[]
     */
    private $formatters;

    /**
     * @var RendererConfiguration[]
     */
    private $modes = [];

    private $defaultFormatters = [
        ['name' => 'default', 'class' => DefaultFormatter::class],
    ];

    private $defaults = [
        'short' => [ //@FIXME: remove in 0.9.10
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
        'full' => [ //@FIXME: remove in 0.9.10
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
        'modes' => [
            'minimal' => [
                'name' => 'minimal',
                'inherit' => true,
                'fields' => [
                    ['name' => 'minimal_header']
                ]
            ],
            'short' => [
                'name' => 'short',
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
                'name' => 'full',
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
        ]
    ];

    public static function fromArray(array $config)
    {
        $instance = new self;
        /** @var RendererConfiguration[] $modes */
        $modesConfigMerged = $instance->configMerged($config, 'modes');
        foreach ($modesConfigMerged as $name => $config) {
            if (is_array($config['name'])) {
                $modesConfigMerged[$name]['name'] = $config['name'][1];
            }
            if (is_array($config['inherit'])) {
                $modesConfigMerged[$name]['inherit'] = $config['inherit'][1];
            }
        }
        $modes = array_map(
            function (array $mode) {
                return RendererConfiguration::fromArray($mode);
            },
            $modesConfigMerged
        );
        foreach ($modes as $mode) {
            $instance->modes[$mode->name()] = $mode;
        }
        self::addModesByDeprecatedNodes($config, 'short', $instance);
        self::addModesByDeprecatedNodes($config, 'full', $instance);
        $instance->formatters = array_map(
            function (array $formatter)  {
                return FormatterConfiguration::fromArray($formatter);
            },
            array_merge($instance->defaultFormatters, isset($config['formatters']) ? $config['formatters'] : [])
        );

        return $instance;
    }

    /**
     * @param array $config
     * @param self $instance
     * @return void
     */
    private static function addModesByDeprecatedNodes(array $config, $mode, self $instance)
    {
        if (isset($config[$mode])) {
            $convertedConfig = $instance->configMerged($config, $mode);
            $convertedConfig['name'] = $mode;
            $instance->modes[$mode] = RendererConfiguration::fromArray($convertedConfig);
        }
    }

    /**
     * Key is the renderer name
     *
     * @return RendererConfiguration[]
     */
    public function modes()
    {
        return $this->modes;
    }

    /**
     * @param string $mode
     * @return RendererConfiguration
     */
    public function mode($mode)
    {
        if (isset($this->modes[$mode])) {
            return $this->modes[$mode];
        }

        throw new \InvalidArgumentException('No such mode: ' . $mode);
    }

    /**
     * @param string $mode
     * @return bool
     */
    public function hasMode($mode)
    {
        try {
            $this->mode($mode);
            return true;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * @FIXME: remove in 0.9.10
     * @deprecated
     * @return RendererConfiguration
     */
    public function short()
    {
        return $this->mode('short');
    }

    /**
     * @FIXME: remove in 0.9.10
     * @deprecated
     * @return RendererConfiguration
     */
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
