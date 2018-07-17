<?php

namespace Technodelight\Jira\Helper;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\OutputFormatter\PaletteOutputFormatterStyle;
use Technodelight\Jira\Helper\JiraTagConverter\DelimiterBasedStringParser;
use Technodelight\Jira\Helper\JiraTagConverter\PanelParser;
use Technodelight\Jira\Helper\JiraTagConverter\SymfonyStyleDefinitionMerger;
use Technodelight\Jira\Helper\JiraTagConverter\TableParser;

class JiraTagConverter
{
    /**
     * @var array
     */
    private $options;
    /**
     * @var array
     */
    private $defaultOptions = [
        'code' => true,
        'bold_underscore' => true,
        'color' => true,
        'mentions' => true,
        'attachments' => true,
        'images' => false,
        'tables' => true,
        'panels' => true,
        'lists' => true,
        'headings' => true,
        'emojis' => true,
        'palette' => PaletteOutputFormatterStyle::class,
    ];

    public function __construct(array $options = [])
    {
        $this->options = $options + $this->defaultOptions;
    }

    public function convert(OutputInterface $output, $body)
    {
        try {
            $this->shouldDo('code') && $this->convertCode($body);
            $this->shouldDo('bold_underscore') && $this->convertBoldUnderscore($body);
            $this->shouldDo('color') && $this->convertColor($body);
            $this->shouldDo('mentions') && $this->convertMentions($body);
            $this->shouldDo('attachments') && $this->convertAttachments($body);
            $this->shouldDo('images') && $this->convertImages($body);
            $this->shouldDo('tables') && $this->convertTables($body);
            $this->shouldDo('panels') && $this->convertPanels($body);
            $this->shouldDo('lists') && $this->convertLists($body);
            $this->shouldDo('headings') && $this->convertHeadings($body);
            $this->shouldDo('emojis') && $this->convertEmojis($body);
            $formattedBody = $this->mergeDefinitions($body);
            // try formatting the body and ignore if an error happens
            $output->getFormatter()->format($body);
            return $formattedBody; // success! return the formatted body
        } catch (\Exception $exception) {
            return $body;
        }
    }

    private function shouldDo($opt)
    {
        return !empty($this->options[$opt]);
    }

    private function opt($opt)
    {
        return $this->options[$opt];
    }

    private function convertCode(&$body)
    {
        // code block
        $parser = new DelimiterBasedStringParser('{code', 'code}');
        $collected = $parser->parse($body);
        foreach ($collected as $replace) {
            $body = substr($body, 0, strpos($body, $replace))
                . '<comment>' . preg_replace('~{code(:[^}]+)?}~', '', $replace) . '</>'
                . substr($body, strpos($body, $replace) + strlen($replace));
        }

        // short code block
        $parser = new DelimiterBasedStringParser('{{', '}}');
        $collected = $parser->parse($body);

        foreach ($collected as $replace) {
            $body = str_replace(
                $replace,
                '<comment>' . substr($replace, 2, -2) . '</>',
                $body
            );
        }
    }

    private function convertColor(&$body)
    {
        // color
        $replacePairs = [];
        $startColor = false;
        $length = false;
        for ($i = 0; $i < strlen($body); $i++) {
            if ($body[$i] == '{') {
                // check if it's a color
                $peek = substr($body, $i + 1, strlen('color'));
                if ($peek == 'color' && $startColor !== false && $length === false) {
                    $length = $i + strlen('color}') - $startColor + 1;
                }
                if ($peek == 'color' && $startColor === false) {
                    $startColor = $i;
                }
            }

            if (preg_match('~({color[^}]*})(.*)({color})~', substr($body, $startColor, $length), $matches)) {
                $replacePairs[substr($body, $startColor, $length)] = $this->formatColor($matches[2], $matches[1]);
                $startColor = false;
                $length = false;
            }
        }

        $body = strtr($body, $replacePairs);
    }

    private function convertBoldUnderscore(&$body)
    {
        $this->parseAndReplaceWith($body, '*', '<options=bold>');
        $this->parseAndReplaceWith($body, '_', '<options=underscore>');
    }

    /**
     * @param string $body
     * @param string $replaceChar
     * @param string $wrapper
     * @return string
     */
    private function parseAndReplaceWith(&$body, $replaceChar, $wrapper)
    {
        $isTerminatingChar = function($char) {
            return preg_match('~[>}\s]~', $char) || empty($char);
        };
        $replacePairs = [];
        $startedAt = false;
        for ($pos = 0; $pos < strlen($body); $pos++) {
            $char = substr($body, $pos, 1);
            $prevChar = $pos > 0 ? substr($body, $pos - 1, 1) : '';
            if (($char == $replaceChar) && ($startedAt === false) && $isTerminatingChar($prevChar)) {
                // tag started
                $startedAt = $pos;
            } else if (($startedAt !== false) && ($char == "\n" || $char == "\r")) {
                // tag terminated by new line, null the previous position and start searching again
                $startedAt = false;
            } else if (($char == $replaceChar) && ($startedAt !== false)) {
                // tag closing found, add to replacements
                $text = substr($body, $startedAt, $pos - $startedAt + 1);
                if (trim($text, $replaceChar) == '') {
                    $startedAt = false;
                    continue;
                }
                $replacePairs[$text] = $wrapper . trim($text, $replaceChar) . '</>';
                $startedAt = false;
            }
        }

        $body = strtr($body, $replacePairs);
    }

    private function convertMentions(&$body)
    {
        // mentions
        if ($numOfMatches = preg_match_all('~(\[\~)([^]]+)(\])~smu', $body, $matches)) {
            for ($i = 0; $i < $numOfMatches; $i++) {
                $body = str_replace(
                    $matches[1][$i].$matches[2][$i].$matches[3][$i],
                    '<fg=cyan>' . $matches[2][$i] . '</>',
                    $body
                );
            }
        }
    }

    private function convertAttachments(&$body)
    {
        // attachments
        if ($numOfMatches = preg_match_all('~(\[\^)([^]]+)(\])~smu', $body, $matches)) {
            for ($i = 0; $i < $numOfMatches; $i++) {
                $body = str_replace(
                    $matches[1][$i].$matches[2][$i].$matches[3][$i],
                    '🔗 <fg=cyan>' . $matches[2][$i] . '</>',
                    $body
                );
            }
        }
    }

    private function convertImages(&$body)
    {
        if (preg_match_all('~!([^|!]+)(\|thumbnail)?!~', $body, $matches)) {
            $replacePairs = [];
            foreach ($matches[1] as $k => $embeddedImage) {
                $replacePairs[$matches[0][$k]] = '<comment>jira download ' . $embeddedImage . '</>';
            }
            $body = strtr($body, $replacePairs);
        }
    }

    private function convertTables(&$body)
    {
        $parser = new TableParser($body);
        $body = $parser->parseAndReplace();
    }

    private function convertPanels(&$body)
    {
        $parser = new PanelParser($body);
        $body = $parser->parseAndReplace();
    }

    private function convertLists(&$body)
    {
        // lists
        $body = preg_replace('~(\s+)### ~', '\1        • ', $body);
        $body = preg_replace('~(\s+)## ~', '\1    • ', $body);
        $body = preg_replace('~(\s+)# ~', '\1• ', $body);
        $body = preg_replace('~\*\*\* ~', '\1        • ', $body);
        $body = preg_replace('~\*\* ~', '\1    • ', $body);
        $body = preg_replace('~\* ~', '\1• ', $body);
    }

    private function convertHeadings(&$body)
    {
        // h[1-5].
        if ($numOfMatches = preg_match_all('~([ ]*)h[1-5]. (.*)~', $body, $matches)) {
            for ($i = 0; $i < $numOfMatches; $i++) {
                $wholeMatch = $matches[0][$i];
                $padding = $matches[1][$i];
                $heading = $matches[2][$i];
                $trimmedLength = strlen(trim($heading));
                $length = strlen($heading);
                $body = str_replace(
                    $wholeMatch,
                    $padding . '<fg=white;options=bold>' . $heading . PHP_EOL
                    . $padding . str_repeat('-', $trimmedLength) . str_repeat(' ', $length - $trimmedLength) . '</>',
                    $body
                );
            }
        }
    }

    private function convertEmojis(&$body)
    {
        $body = strtr($body, [
           '(?)' => '❓ ',
           '(x)' => '❌ ',
           '(/)' => '✅ ',
           ':)' => '😀',
           ':-)' => '😀',
           ';)' => '😉',
           ';-)' => '😉',
        ]);
    }

    private function mergeDefinitions($body)
    {
        return SymfonyStyleDefinitionMerger::findAndMergeDefs($body);
    }

    private function formatColor($string, $colorDef)
    {
        if ($this->shouldDo('strip_color')) {
            return $string;
        }

        $color = $this->extractColorFromDefinition($colorDef);
        $paletteClass = $this->opt('palette');
        /** @var PaletteOutputFormatterStyle $style */
        $style = is_object($paletteClass) ? $paletteClass : new $paletteClass;
        $style->setForeground($color);
        return $style->apply($string);
    }

    private function extractColorFromDefinition($colorDef)
    {
        list(, $color) = explode(':', trim($colorDef, '{}'), 2) + ['', 'white'];
        return $color;
    }
}
