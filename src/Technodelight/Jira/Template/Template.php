<?php

namespace Technodelight\Jira\Template;

use UnexpectedValueException;

class Template
{
    protected $template;

    public function __construct($template)
    {
        $this->template = $template;
    }

    public static function fromFile($relativePath)
    {
        $relativePath = str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $path = realpath(__DIR__ . '/../../../' . $relativePath);
        if (!is_readable($path)) {
            throw new UnexpectedValueException(sprintf('File %s could not be opened', $path));
        }
        return new self(file_get_contents($path));
    }

    public function render(array $variables = [])
    {
        $keys = array_map(
            function($key) {
                return sprintf('{{ %s }}', $key);
            },
            array_keys($variables)
        );

        return strtr($this->template, array_combine($keys, array_values($variables)));
    }

}
