<?php

namespace Technodelight\Jira\Template;

use Technodelight\Simplate;
use UnexpectedValueException;

class Template
{
    public static function fromFile($relativePath)
    {
        $path = __DIR__ . '/../../../' . $relativePath;
        if (!is_readable($path)) {
            throw new UnexpectedValueException(sprintf('File %s could not be opened', $path));
        }

        return Simplate::fromFile($path);
    }
}
