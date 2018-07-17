<?php

namespace Technodelight\Jira\Helper\JiraTagConverter;

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\TableStyle;

class Panel
{
    private $source = '';

    public function appendSource($source)
    {
        $this->source.= $source;
    }

    public function source()
    {
        return $this->source;
    }

    public function __toString()
    {
        $output = '';
        $style = new TableStyle;
        $replacement = $this->replacement();
        $panelLength = $this->panelLength($replacement);

        $this->writeln($output, '');
        $this->writeBorder($output, $style, $panelLength);
        $this->writeContent($output, $style, $replacement, $panelLength);
        $this->writeBorder($output, $style, $panelLength);

        return $output;
    }

    private function replacement()
    {
        return str_replace(
            '{panel}',
            '',
            join(PHP_EOL, array_map('trim', explode(PHP_EOL, $this->source)))
        );
    }

    private function panelLength($replacement)
    {
        $lines = explode(PHP_EOL, $replacement);
        $max = 0;
        foreach ($lines as $line) {
            $max = max($max, strlen($line));
        }
        return $max + 2;
    }

    /**
     * @param string $output
     * @param TableStyle $style
     * @param int $panelLength
     */
    private function writeBorder(&$output, TableStyle $style, $panelLength)
    {
        $this->writeln(
            $output,
            $style->getCrossingChar()
            . str_repeat($style->getHorizontalBorderChar(), $panelLength)
            . $style->getCrossingChar()
        );
    }

    /**
     * @param string $output
     * @param TableStyle $style
     * @param string $content
     * @param int $panelLength
     */
    private function writeContent(&$output, TableStyle $style, $content, $panelLength)
    {
        $formatter = new OutputFormatter(true);
        $lines = explode(PHP_EOL, $content);
        foreach ($lines as $line) {
            $padLengthDiff = strlen($line) - Helper::strlenWithoutDecoration($formatter, $line);
            $this->writeln(
                $output,
                $style->getVerticalBorderChar()
                . str_pad(sprintf(' %s ', $line), $panelLength + $padLengthDiff, ' ', STR_PAD_RIGHT)
                . $style->getVerticalBorderChar()
            );
        }
    }

    private function writeln(&$output, $string)
    {
        $output = $output . $string . PHP_EOL;
    }
}
