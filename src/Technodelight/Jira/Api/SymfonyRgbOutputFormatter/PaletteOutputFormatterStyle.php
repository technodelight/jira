<?php

namespace Technodelight\Jira\Api\SymfonyRgbOutputFormatter;

use InvalidArgumentException;
use function SSNepenthe\ColorUtils\color;
use SSNepenthe\ColorUtils\Colors\Rgb;
use SSNepenthe\ColorUtils\Exceptions\InvalidArgumentException as InvalidColorException;
use Symfony\Component\Console\Formatter\OutputFormatterStyleInterface;

class PaletteOutputFormatterStyle implements OutputFormatterStyleInterface
{
    private const UNSET_CODE = 0;
    private const UNSET_FG_CODE = 39;
    private const UNSET_BG_CODE = 49;

    private static array $availableOptions = [
        'bold' => ['set' => 1, 'unset' => 22],
        'italic' => ['set' => 3, 'unset' => 23],
        'underscore' => ['set' => 4, 'unset' => 24],
        'blink' => ['set' => 5, 'unset' => 25],
        'reverse' => ['set' => 7, 'unset' => 27],
        'conceal' => ['set' => 8, 'unset' => 28],
        'strikethrough' => ['set' => 9, 'unset' => 29],
    ];

    private ?Rgb $foreground = null;
    private ?Rgb $background = null;
    private array $options = [];

    public function setForeground(string $color = null): void
    {
        if (is_null($color)) {
            $this->foreground = null;
            return;
        }

        $this->foreground = $this->colorToRgb($color);
    }

    public function setBackground(string $color = null): void
    {
        if (is_null($color)) {
            $this->background = null;
            return;
        }

        $this->background = $this->colorToRgb($color);
    }

    public function setOption(string $option): void
    {
        if (!isset(static::$availableOptions[$option])) {
            throw new InvalidArgumentException(sprintf(
                'Invalid option specified: "%s". Expected one of (%s)',
                $option,
                implode(', ', array_keys(static::$availableOptions))
            ));
        }

        if (!in_array(static::$availableOptions[$option], $this->options, true)) {
            $this->options[] = static::$availableOptions[$option];
        }
    }

    public function unsetOption(string $option): void
    {
        if (!isset(static::$availableOptions[$option])) {
            throw new InvalidArgumentException(sprintf(
                'Invalid option specified: "%s". Expected one of (%s)',
                $option,
                implode(', ', array_keys(static::$availableOptions))
            ));
        }

        $pos = array_search(static::$availableOptions[$option], $this->options, true);
        if (false !== $pos) {
            unset($this->options[$pos]);
        }
    }

    public function setOptions(array $options): void
    {
        $this->options = [];

        foreach ($options as $option) {
            $this->setOption($option);
        }
    }

    public function apply(string $text): string
    {
        // ESC[ … 38;2;<r>;<g>;<b> … m Select RGB foreground color

        $setCodes = array();
        $unsetCodes = array();

        if (null !== $this->foreground) {
            $setCodes[] = 38;
            $setCodes[] = 2;
            $setCodes[] = $this->foreground->getRed();
            $setCodes[] = $this->foreground->getGreen();
            $setCodes[] = $this->foreground->getBlue();
            $unsetCodes[] = self::UNSET_FG_CODE;
        }
        if (null !== $this->background) {
            $setCodes[] = 48;
            $setCodes[] = 2;
            $setCodes[] = $this->background->getRed();
            $setCodes[] = $this->background->getGreen();
            $setCodes[] = $this->background->getBlue();
            $unsetCodes[] = self::UNSET_BG_CODE;
        }

        if (count($this->options)) {
            foreach ($this->options as $option) {
                $setCodes[] = $option['set'];
                $unsetCodes[] = $option['unset'];
            }
        }

        if (empty($setCodes)) {
            return $text;
        }

        return sprintf("\033[%sm%s\033[%sm", implode(';', $setCodes), $text, implode(';', $unsetCodes));
    }

    private function colorToRgb(string $colorDef): Rgb
    {
        try {
            if (strpos($colorDef, '-')) {
                $colorDef = substr($colorDef, 0, strpos($colorDef, '-'));
            }
            return color($colorDef)->getRgb();
        } catch (InvalidColorException $e) {
            return color('white')->getRgb();
        }
    }
}
