<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;
use Technodelight\Jira\Renderer\Issue\CustomField\DefaultFormatter;

class RenderersConfiguration implements RegistrableConfiguration
{
    /**
     * @var \Technodelight\Jira\Configuration\ApplicationConfiguration\FormatterConfiguration[]
     */
    private $formatters;

    /**
     * @var RendererConfiguration[]
     */
    private $modes = [];

    /**
     * @var array
     */
    private $preference = [];

    private $defaultFormatters = [
        ['name' => 'default', 'class' => DefaultFormatter::class],
    ];

    private $defaults = [
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
                    ['name' => 'changelogs'],
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
        $instance->formatters = array_map(
            function (array $formatter)  {
                return FormatterConfiguration::fromArray($formatter);
            },
            array_merge($instance->defaultFormatters, isset($config['formatters']) ? $config['formatters'] : [])
        );

        $instance->preference = isset($config['preference']) ? $config['preference'] : ['list' => 'short', 'view' => 'full'];
        foreach ($instance->preference as $type => $renderer) {
            if (!isset($instance->modes[$renderer])) {
                throw new \InvalidArgumentException(
                    sprintf('Preferred renderer "%s" for "%s" does not exists!', $renderer, $type)
                );
            }
        }

        return $instance;
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
     * @return FormatterConfiguration[]
     */
    public function formatters()
    {
        return $this->formatters;
    }

    public function preferredListRenderer()
    {
        return $this->preference['list'];
    }

    public function preferredViewRenderer()
    {
        return $this->preference['view'];
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
