<?php

namespace Technodelight\Jira\Renderer;

use Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\RendererConfiguration\FieldConfiguration;
use Technodelight\Jira\Renderer\Issue\CustomField\Factory;
use Technodelight\Jira\Renderer\Issue\Renderer;

class IssueRendererFactory
{
    /**
     * @var \Technodelight\Jira\Configuration\ApplicationConfiguration
     */
    private $config;
    /**
     * @var \Technodelight\Jira\Renderer\Issue\CustomField\Factory
     */
    private $factory;

    public function __construct(ApplicationConfiguration $config, Factory $factory)
    {
        $this->config = $config;
        $this->factory = $factory;
    }

    /**
     * @param string $mode
     * @param \Technodelight\Jira\Renderer\IssueRenderer[] $renderers
     * @return Renderer
     */
    public function build($mode, $renderers = [])
    {
        $rendererConfig = $this->config($mode);

        if (!$rendererConfig->inherit()) {
            $renderers = ['header' => array_shift($renderers)];
        }

        foreach ($rendererConfig->fields() as $fieldConfiguration) {
            $this->processConfiguration($fieldConfiguration, $renderers);
        }

        return new Renderer($renderers);
    }

    /**
     * @param string $mode
     * @return \Technodelight\Jira\Configuration\ApplicationConfiguration\RendererConfiguration
     */
    private function config($mode)
    {
        if ($mode == 'short') {
            return $this->config->renderers()->short();
        }
        return $this->config->renderers()->full();
    }

    /**
     * @param FieldConfiguration $fieldConfiguration
     * @param \Technodelight\Jira\Renderer\IssueRenderer[] $renderers
     */
    private function processConfiguration(FieldConfiguration $fieldConfiguration, array &$renderers)
    {
        if (isset($renderers[$fieldConfiguration->name()])) {
            $this->changeField($fieldConfiguration, $renderers);
        } else {
            $this->createField($fieldConfiguration, $renderers);
        }
    }

    /**
     * @param FieldConfiguration $fieldConfiguration
     * @param \Technodelight\Jira\Renderer\IssueRenderer[] $renderers
     */
    private function changeField(FieldConfiguration $fieldConfiguration, array &$renderers)
    {
        if ($fieldConfiguration->remove()) {
            unset($renderers[$fieldConfiguration->name()]);
        } elseif ($fieldConfiguration->shouldBeMoved()) {
            $this->moveField($fieldConfiguration, $renderers);
        }
    }

    /**
     * @param FieldConfiguration $fieldConfiguration
     * @param \Technodelight\Jira\Renderer\IssueRenderer[] $renderers
     */
    private function createField(FieldConfiguration $fieldConfiguration, array &$renderers)
    {
        $renderers[$fieldConfiguration->name()] = $this->createCustomFieldRenderer($fieldConfiguration);
        if ($fieldConfiguration->shouldBeMoved()) {
            $this->moveField($fieldConfiguration, $renderers);
        }
    }

    /**
     * @param FieldConfiguration $fieldConfiguration
     * @param \Technodelight\Jira\Renderer\IssueRenderer[] $renderers
     */
    private function moveField(FieldConfiguration $fieldConfiguration, array &$renderers)
    {
        $moveName = $fieldConfiguration->name();
        $reorderedRenderers = [];
        if ($fieldConfiguration->after() == '-') {
            $reorderedRenderers = $renderers;
            $renderer = $renderers[$moveName];
            unset($reorderedRenderers[$moveName]);
            $reorderedRenderers[$moveName] = $renderer;
        } else {
            foreach ($renderers as $rendererName => $renderer) {
                if ($rendererName == $moveName) {
                    continue;
                }
                if ($fieldConfiguration->before() == '-' && $rendererName == 'header') {
                    $reorderedRenderers[$rendererName] = $renderer;
                    $reorderedRenderers[$moveName] = $renderers[$moveName];
                    break;
                } elseif ($fieldConfiguration->before() == $rendererName) {
                    $reorderedRenderers[$moveName] = $renderers[$moveName];
                    $reorderedRenderers[$rendererName] = $renderer;
                } elseif ($fieldConfiguration->after() == $rendererName) {
                    $reorderedRenderers[$rendererName] = $renderer;
                    $reorderedRenderers[$moveName] = $renderers[$moveName];
                } else {
                    $reorderedRenderers[$rendererName] = $renderer;
                }
            }
        }
        $renderers = $reorderedRenderers;
    }

    /**
     * @param \Technodelight\Jira\Configuration\ApplicationConfiguration\RendererConfiguration\FieldConfiguration $fieldConfiguration
     * @return \Technodelight\Jira\Renderer\Issue\CustomField
     */
    private function createCustomFieldRenderer(FieldConfiguration $fieldConfiguration)
    {
        $formatters = $this->config->renderers()->formatters();
        $formatter = isset($formatters[$fieldConfiguration->formatter()]) ? $formatters[$fieldConfiguration->formatter()] : null;
        return $this->factory->fromFieldName(
            $fieldConfiguration->name(),
            $fieldConfiguration->inline(),
            $formatter ? $formatter->createInstance() : null
        );
    }
}
