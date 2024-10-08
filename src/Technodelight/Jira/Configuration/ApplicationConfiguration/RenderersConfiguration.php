<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration;
use InvalidArgumentException;
use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;
use Technodelight\Jira\Renderer\Issue\CustomField\DefaultFormatter;

/** @SuppressWarnings(PHPMD) */
class RenderersConfiguration implements RegistrableConfiguration
{
    /**
     * @var \Technodelight\Jira\Configuration\ApplicationConfiguration\FormatterConfiguration[]
     */
    private array $formatters;

    /**
     * @var RendererConfiguration[]
     */
    private array $modes = [];

    /**
     * @var array
     */
    private array $preference = [];
    /**
     * @var array
     */
    private array $config;

    private array $defaultFormatters = [
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
            'relations' => [
                'name' => 'relations',
                'inherit' => false,
                'fields' => [
                    ['name' => 'minimal_header'],
                    ['name' => 'minimal_issue_relations'],
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
                    ['name' => 'transitions.short'],
                    ['name' => 'short_description'],
                    ['name' => 'attachments.short'],
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
                    ['name' => 'transitions'],
                    ['name' => 'full_description'],
                    ['name' => 'issue_relations'],
                    ['name' => 'versions'],
                    ['name' => 'attachments'],
                    ['name' => 'branches'],
                    ['name' => 'worklogs'],
                    ['name' => 'comments'],
                ],
            ],
        ]
    ];

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    public static function fromArray(array $config): RenderersConfiguration
    {
        $instance = new self;
        $instance->config = $config;
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
            array_merge($instance->defaultFormatters, $config['formatters'] ?? [])
        );

        $instance->preference = $config['preference'] ?? ['list' => 'short', 'view' => 'full'];
        foreach ($instance->preference as $type => $renderer) {
            if (!isset($instance->modes[$renderer])) {
                throw new InvalidArgumentException(
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
    public function modes(): array
    {
        return $this->modes;
    }

    /**
     * @param string $mode
     * @return RendererConfiguration
     */
    public function mode($mode): RendererConfiguration
    {
        if (isset($this->modes[$mode])) {
            return $this->modes[$mode];
        }

        throw new InvalidArgumentException('No such mode: ' . $mode);
    }

    /**
     * @param string $mode
     * @return bool
     */
    public function hasMode(string $mode): bool
    {
        try {
            $this->mode($mode);
            return true;
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * @return FormatterConfiguration[]
     */
    public function formatters(): array
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

    public function servicePrefix(): string
    {
        return 'renderers';
    }

    /**
     * @return array
     */
    public function configAsArray(): array
    {
        return $this->config;
    }

    private function __construct()
    {
    }

    /** @SuppressWarnings(PHPMD.UnusedPrivateMethod) */
    private function configMerged(array $config, string $key): array
    {
        return array_merge_recursive($this->defaults[$key], $config[$key] ?? []);
    }
}
