<?php

namespace Technodelight\Jira\Renderer\Elements;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Helper\Helper;
use Technodelight\Jira\Api\SymfonyRgbOutputFormatter\PaletteOutputFormatterStyle;

class Badge
{
    /**
     * @var string
     */
    private $content;
    /**
     * @var string
     */
    private $foregroundColor;
    /**
     * @var string
     */
    private $backgroundColor;
    /**
     * @var array
     */
    private $options;
    /**
     * @var OutputFormatterInterface
     */
    private $formatter;

    public function __construct($content, $foregroundColor = null, $backgroundColor = null, array $options = [], OutputFormatterInterface $formatter = null)
    {
        $this->content = $content;
        $this->foregroundColor = $foregroundColor;
        $this->backgroundColor = $backgroundColor;
        $this->options = $options;
        $this->formatter = $formatter ?: new PaletteOutputFormatterStyle;
    }

    public function content()
    {
        return $this->content;
    }

    public function foregroundColor()
    {
        return $this->foregroundColor;
    }

    public function backgroundColor()
    {
        return $this->backgroundColor;
    }

    public function length()
    {
        $string = preg_replace("/\033\[[^m]*m/", '', $this->__toString());

        return Helper::strlen($string);
    }

    public function rawLength()
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
