<?php

declare(strict_types=1);

namespace Technodelight\Jira\Renderer\Elements;

use Symfony\Component\Console\Formatter\OutputFormatterStyleInterface;
use Symfony\Component\Console\Helper\Helper;
use Technodelight\Jira\Api\SymfonyRgbOutputFormatter\PaletteOutputFormatterStyle;

class Badge
{
    public function __construct(
        private readonly string $content,
        private readonly ?string $foregroundColor = null,
        private readonly ?string $backgroundColor = null,
        private readonly array $options = [],
        private readonly OutputFormatterStyleInterface $formatter = new PaletteOutputFormatterStyle
    ) {}

    public function content(): string
    {
        return $this->content;
    }

    public function foregroundColor(): ?string
    {
        return $this->foregroundColor;
    }

    public function backgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    public function length(): int
    {
        $string = preg_replace("/\033\[[^m]*m/", '', $this->__toString());

        return Helper::length($string);
    }

    public function rawLength(): int
    {
        return mb_strlen($this->__toString());
    }

    public function __toString()
    {
        if ($this->foregroundColor) {
            $this->formatter->setForeground($this->foregroundColor);
        }
        if ($this->backgroundColor) {
            $this->formatter->setBackground($this->backgroundColor);
        }
        if ($this->options) {
            $this->formatter->setOptions($this->options);
        }

        return $this->formatter->apply(sprintf(' %s ', $this->content));
    }
}
