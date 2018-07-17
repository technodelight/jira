<?php

namespace Technodelight\Jira\Renderer;

use Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\RendererConfiguration\FieldConfiguration;
use Technodelight\Jira\Renderer\Issue\CustomField\Factory;
use Technodelight\Jira\Renderer\Issue\Renderer;
use Technodelight\Jira\Renderer\Issue\RendererProvider;

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
    /**
     * @var \Technodelight\Jira\Renderer\Issue\RendererProvider
     */
    private $rendererProvider;

    public function __construct(ApplicationConfiguration $config, Factory $factory, RendererProvider $rendererProvider)
    {
        $this->config = $config;
        $this->factory = $factory;
        $this->rendererProvider = $rendererProvider;
    }

    /**
     * @param string $mode
     * @return Renderer
     * @throws RendererConfigurationError
     */
    public function build($mode)
    {
        $rendererConfig = $this->config($mode);

        $renderers = [];

        foreach ($rendererConfig->fields() as $fieldConfiguration) {
            $this->createField($fieldConfiguration, $renderers);
        }
        foreach ($rendererConfig->fields() as $fieldConfiguration) {
            $this->changeField($fieldConfiguration, $renderers);
        }

        return new Renderer($renderers);
    }

    /**
     * @param string $mode
     * @return \Technodelight\Jira\Configuration\ApplicationConfiguration\RendererConfiguration
     */
    private function config($mode)
    {
        return $this->config->renderers()->mode($mode);
    }

    /**
     * @param FieldConfiguration $fieldConfiguration
     * @param \Technodelight\Jira\Renderer\IssueRenderer[] $renderers
     * @throws RendererConfigurationError
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
        if (!$fieldConfiguration->remove()) {
            $renderers[$fieldConfiguration->name()] = $this->createFieldRenderer($fieldConfiguration);
        }
    }

    /**
     * @param FieldConfiguration $fieldConfiguration
     * @param \Technodelight\Jira\Renderer\IssueRenderer[] $renderers
     * @throws RendererConfigurationError
     */
    private function moveField(FieldConfiguration $fieldConfiguration, array &$renderers)
    {
        $this->validateMove($fieldConfiguration, $renderers);

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
     * @return \Technodelight\Jira\Renderer\IssueRenderer
     */
    private function createFieldRenderer(FieldConfiguration $fieldConfiguration)
    {
        if ($this->isCustomField($fieldConfiguration)) {
            return $this->createCustomFieldRenderer($fieldConfiguration);
        }

        return $this->createBuiltInFieldRenderer($fieldConfiguration);
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

    private function isCustomField(FieldConfiguration $fieldConfiguration)
    {
        return !$this->rendererProvider->has($fieldConfiguration->name());
    }

    private function createBuiltInFieldRenderer(FieldConfiguration $fieldConfiguration)
    {
        return $this->rendererProvider->get($fieldConfiguration->name());
    }

    /**
     * @param FieldConfiguration $fieldConfiguration
     * @param \Technodelight\Jira\Renderer\IssueRenderer[] $renderers
     * @throws RendererConfigurationError
     */
    private function validateMove(FieldConfiguration $fieldConfiguration, array &$renderers)
    {
        if (!empty($fieldConfiguration->before())
            && !isset($renderers[$fieldConfiguration->before()])
            && $fieldConfiguration->before() !== '-') {
            throw RendererConfigurationError::fromFieldConfigurationWithBefore($fieldConfiguration, $renderers);
        }
        if (!empty($fieldConfiguration->after())
            && !isset($renderers[$fieldConfiguration->after()])
            && $fieldConfiguration->after() !== '-') {
            throw RendererConfigurationError::fromFieldConfigurationWithAfter($fieldConfiguration, $renderers);
        }
    }
}
