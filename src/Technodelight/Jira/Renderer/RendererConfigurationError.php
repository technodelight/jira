<?php

namespace Technodelight\Jira\Renderer;

use Fuse\Fuse;
use Technodelight\Jira\Configuration\ApplicationConfiguration\RendererConfiguration\FieldConfiguration;

class RendererConfigurationError extends \Exception
{
    /**
     * @param FieldConfiguration $fieldConfiguration
     * @param \Technodelight\Jira\Renderer\IssueRenderer[] $renderers
     * @return RendererConfigurationError
     */
    public static function fromFieldConfigurationWithBefore(FieldConfiguration $fieldConfiguration, array $renderers)
    {
        return new self(
            sprintf(
                'Cannot display "%s" field before "%s" field as it does not exists'
                . self::guessText($fieldConfiguration->before(), $renderers),
                $fieldConfiguration->name(),
                $fieldConfiguration->before()
            )
        );
    }

    /**
     * @param FieldConfiguration $fieldConfiguration
     * @param \Technodelight\Jira\Renderer\IssueRenderer[] $renderers
     * @return RendererConfigurationError
     */
    public static function fromFieldConfigurationWithAfter(FieldConfiguration $fieldConfiguration, array $renderers)
    {
        return new self(
            sprintf(
                'Cannot display %s field after "%s" field as it does not exists'
                . self::guessText($fieldConfiguration->after(), $renderers),
                $fieldConfiguration->name(),
                $fieldConfiguration->after()
            )
        );
    }

    /**
     * @param string $nonexistentRenderer
     * @param \Technodelight\Jira\Renderer\IssueRenderer[] $renderers
     * @return string
     */
    private static function guessText($nonexistentRenderer, array $renderers)
    {
        $guesses = self::guessWhat($nonexistentRenderer, $renderers);
        if (empty($guesses)) {
            return '';
        }

        return PHP_EOL . 'Maybe you\'ve meant something like ' . $guesses . '?';
    }

    /**
     * @param string $nonexistentRenderer
     * @param \Technodelight\Jira\Renderer\IssueRenderer[] $renderers
     * @return string
     */
    private static function guessWhat($nonexistentRenderer, array $renderers)
    {
        $list = array_keys($renderers);
        $search = new Fuse($list);
        $found = [];
        foreach ($search->search($nonexistentRenderer) as $idx) {
            $found[] = $list[$idx];
        }
        return join(', ', array_slice($found, 0, 2));
    }
}
