<?php

declare(strict_types=1);

namespace Technodelight\Jira\Helper;

class ColorExtractor
{
    private array $colors = ['black', 'red', 'green', 'yellow', 'blue', 'magenta', 'cyan', 'white', 'grey'];

    public function extractColor($colorName): string
    {
        $color = $this->ensureProperColor($colorName);
        if ($color !== null) {
            $color = $this->ensureProperColor(substr($colorName, 0, strpos($colorName, '-')));
        }

        return $color ?? 'default';
    }

    private function ensureProperColor($colorName): ?string
    {
        if (in_array(strtolower($colorName), $this->colors)) {
            return strtolower($colorName);
        }

        return null;
    }
}
