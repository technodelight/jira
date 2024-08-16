<?php

namespace Technodelight\Jira\Renderer;

use Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\RendererConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\RendererConfiguration\FieldConfiguration;
use Technodelight\Jira\Renderer\Issue\CustomField\Factory;
use Technodelight\Jira\Renderer\Issue\Renderer;
use Technodelight\Jira\Renderer\Issue\RendererContainer;

class IssueRendererFactory
{
    public function __construct(
        private readonly ApplicationConfiguration $config,
        private readonly Factory $factory,
        private readonly RendererContainer $rendererProvider
    ) {
    }

    /** @throws RendererConfigurationError */
    public function build(string $mode): Renderer
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

    private function config(string $mode): RendererConfiguration
    {
        return $this->config->renderers()->mode($mode);
    }

    /**
     * @param FieldConfiguration $fieldConfiguration
     * @param IssueRenderer[] $renderers
     * @throws RendererConfigurationError
     */
    private function changeField(FieldConfiguration $fieldConfiguration, array &$renderers): void
    {
        if ($fieldConfiguration->remove()) {
            unset($renderers[$fieldConfiguration->name()]);
        } elseif ($fieldConfiguration->shouldBeMoved()) {
            $this->moveField($fieldConfiguration, $renderers);
        }
    }

    /**
     * @param FieldConfiguration $fieldConfiguration
     * @param IssueRenderer[] $renderers
     */
    private function createField(FieldConfiguration $fieldConfiguration, array &$renderers): void
    {
        if (!$fieldConfiguration->remove()) {
            $renderers[$fieldConfiguration->name()] = $this->createFieldRenderer($fieldConfiguration);
        }
    }

    /**
     * @param FieldConfiguration $fieldConfiguration
     * @param IssueRenderer[] $renderers
     * @throws RendererConfigurationError
     */
    private function moveField(FieldConfiguration $fieldConfiguration, array &$renderers): void
    {
        $this->validateMove($fieldConfiguration, $renderers);

        $moveName = $fieldConfiguration->name();
        $reorderedRenderers = [];
        if ($fieldConfiguration->after() == '-') {
            $reorderedRenderers = $renderers;
            $renderer = $renderers[$moveName];
            unset($reorderedRenderers[$moveName]);
            $reorderedRenderers[$moveName] = $renderer;
            $renderers = $reorderedRenderers;

            return;
        }

        //@TODO: refactor me
        foreach ($renderers as $rendererName => $renderer) {
            if ($rendererName === $moveName) {
                continue;
            }
            $reorderedRenderers[$rendererName] = $renderer;
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
            }
        }

        $renderers = $reorderedRenderers;
    }

    private function createFieldRenderer(FieldConfiguration $fieldConfiguration): IssueRenderer
    {
        if ($this->isCustomField($fieldConfiguration)) {
            return $this->createCustomFieldRenderer($fieldConfiguration);
        }

        return $this->createBuiltInFieldRenderer($fieldConfiguration);
    }

    private function createCustomFieldRenderer(FieldConfiguration $fieldConfiguration): IssueRenderer
    {
        $formatters = $this->config->renderers()->formatters();
        $formatter = $formatters[$fieldConfiguration->formatter()] ?? null;
        return $this->factory->fromFieldName(
            $fieldConfiguration->name(),
            $fieldConfiguration->inline(),
            $formatter?->createInstance()
        );
    }

    private function isCustomField(FieldConfiguration $fieldConfiguration): bool
    {
        return !$this->rendererProvider->has($fieldConfiguration->name());
    }

    private function createBuiltInFieldRenderer(FieldConfiguration $fieldConfiguration): IssueRenderer
    {
        return $this->rendererProvider->get($fieldConfiguration->name());
    }

    /**
     * @param FieldConfiguration $fieldConfiguration
     * @param IssueRenderer[] $renderers
     * @throws RendererConfigurationError
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function validateMove(FieldConfiguration $fieldConfiguration, array &$renderers): void
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
