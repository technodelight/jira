<?php

namespace Technodelight\Jira\Helper;

class ColorExtractor
{
    private $colors = ['black', 'red', 'green', 'yellow', 'blue', 'magenta', 'cyan', 'white', 'grey'];

    public function extractColor($colorName)
    {
        if (!$color = $this->ensureProperColor($colorName)) {
            $color = $this->ensureProperColor(substr($colorName, 0, strpos($colorName, '-')));
        }

        return isset($color) ? $color : 'default';
    }

    private function ensureProperColor($colorName)
    {
        if (in_array(strtolower($colorName), $this->colors)) {
            return strtolower($colorName);
        }

        return false;
    }
}
