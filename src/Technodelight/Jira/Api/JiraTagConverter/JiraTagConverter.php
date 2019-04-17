<?php

namespace Technodelight\Jira\Api\JiraTagConverter;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraTagConverter\Components\Chunk;
use Technodelight\Jira\Api\JiraTagConverter\Components\TerminalHighlight;
use Technodelight\Jira\Api\SymfonyRgbOutputFormatter\PaletteOutputFormatterStyle;
use Technodelight\Jira\Api\JiraTagConverter\Components\DelimiterBasedStringParser;
use Technodelight\Jira\Api\JiraTagConverter\Components\PanelParser;
use Technodelight\Jira\Api\JiraTagConverter\Components\SymfonyStyleDefinitionMerger;
use Technodelight\Jira\Api\JiraTagConverter\Components\TableParser;

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
        'issueKeys' => true,
        'code' => true,
        'quote' => true,
        'bold_underscore' => true,
        'color' => true,
        'mentions' => true,
        'attachments' => true,
        'images' => false,
        'tables' => true,
        'lines' => true,
        'panels' => true,
        'lists' => true,
        'headings' => true,
        'emojis' => true,
        'palette' => PaletteOutputFormatterStyle::class,
        'terminalWidth' => null,
        'tabulation' => 0,
    ];
    private $prevOpts;

    public function __construct(array $options = [])
    {
        $this->options = $options + $this->defaultOptions;
    }

    public function convert(OutputInterface $output, $body, array $opts = [])
    {
        try {
            if ($opts) {
                $this->setTempOpts($opts);
            }
            $this->shouldDo('code') && $this->convertCode($body);
            $this->shouldDo('quote') && $this->convertQuote($body);
            $this->shouldDo('bold_underscore') && $this->convertBoldUnderscore($body);
            $this->shouldDo('color') && $this->convertColor($body);
            $this->shouldDo('mentions') && $this->convertMentions($body);
            $this->shouldDo('attachments') && $this->convertAttachments($body);
            $this->shouldDo('images') && $this->convertImages($body);
            $this->shouldDo('lines') && $this->convertLines($body);
            $this->shouldDo('panels') && $this->convertPanels($body);
            $this->shouldDo('lists') && $this->convertLists($body);
            $this->shouldDo('headings') && $this->convertHeadings($body);
            $this->shouldDo('emojis') && $this->convertEmojis($body);
            $this->shouldDo('tables') && $this->convertTables($body);
            $formattedBody = $this->mergeDefinitions($body);
            // try formatting the body and ignore if an error happens
            $output->getFormatter()->format($body);
            $this->restoreOpts();
            return $formattedBody; // success! return the formatted body
        } catch (\Exception $exception) {
            $this->restoreOpts();

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
            $strippedString = trim(preg_replace('~{code(:[^}]+)?}~', '', $replace), PHP_EOL);
            $syntax = '';
            if (preg_match('~^{code:([^}]+)}~', $replace, $matches)) {
                $syntax = $matches[1];
            }
            if (TerminalHighlight::isAvailable() && !empty($syntax)) {
                $codeBlock = TerminalHighlight::formatCode($strippedString, $syntax);
            } else {
                $codeBlock = '<comment>' . $strippedString . '</>';
            }

            $body = substr($body, 0, strpos($body, $replace))
            . $codeBlock
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

    private function convertQuote(&$body)
    {
        $parser = new DelimiterBasedStringParser('{quote}', '{quote}');
        $collected = $parser->parse($body);
        $quoteDecor = '│ ';
        foreach ($collected as $replace) {
            $rawQuoteBlock = substr($replace, strlen('{quote}'), strlen('{quote}') * -1);
            $quoteBlock = $quoteDecor . join(PHP_EOL . $quoteDecor, explode(PHP_EOL, trim($rawQuoteBlock, PHP_EOL)));

            $body = substr($body, 0, strpos($body, $replace))
                . $quoteBlock
                . substr($body, strpos($body, $replace) + strlen($replace));
        }
    }

    private function convertColor(&$body)
    {
        // color
        $parser = new DelimiterBasedStringParser('{color', 'color}');
        $collected = $parser->parse($body);
        $replacePairs = [];
        foreach ($collected as $replace) {
            if (preg_match('~({color[^}]*})(.*)({color})~', $replace, $matches)) {
                $replacePairs[$replace] = $this->formatColor($matches[2], $matches[1]);
            }
        }
        $body = strtr($body, $replacePairs);
    }

    private function convertBoldUnderscore(&$body)
    {
        $parser = new DelimiterBasedStringParser('*_', '_*');
        $matches = $parser->parse($body);
        foreach ($matches as $match) {
            $body = str_replace(
                $match, '<options=bold,underscore>' . substr($match, 2, -2) . '</>', $body
            );
        }
        $parser = new DelimiterBasedStringParser('_*', '*_');
        $matches = $parser->parse($body);
        foreach ($matches as $match) {
            $body = str_replace(
                $match, '<options=underscore,bold>' . substr($match, 2, -2) . '</>', $body
            );
        }

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
            $nextChar = $pos > 0 ? substr($body, $pos + 1, 1) : '';
            if (($char == $replaceChar) && ($startedAt === false) && $isTerminatingChar($prevChar)) {
                // tag started
                $startedAt = $pos;
            } else if (($startedAt !== false) && ($char == "\n" || $char == "\r")) {
                // tag terminated by new line, null the previous position and start searching again
                $startedAt = false;
            } else if (($char == $replaceChar) && ($startedAt !== false) && $isTerminatingChar($nextChar)) {
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
                    '🔗<fg=cyan>' . $matches[2][$i] . '</>',
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

    private function convertLines(&$body)
    {
        $lines = explode(PHP_EOL, $body);
        $maxLength = 1;
        foreach ($lines as $line) {
            $maxLength = max($maxLength, strlen(trim($line)));
        }
        if ($this->opt('terminalWidth') && $maxLength > $this->opt('terminalWidth')) {
            $maxLength = $this->opt('terminalWidth') - $this->opt('tabulation');
        }

        $body = preg_replace('~^-----*~m', str_repeat('─', $maxLength), $body);
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
                    . $padding . str_repeat('▔', $trimmedLength) . str_repeat(' ', $length - $trimmedLength) . '</>',
                    $body
                );
            }
        }
    }

    /**
     * @link https://confluence.atlassian.com/conf61/symbols-emoticons-and-special-characters-877187546.html#Symbols,EmoticonsandSpecialCharacters-Insertemoticons
     * @param $body
     */
    private function convertEmojis(&$body)
    {
        $body = strtr($body, [
           '(?)' => '❓ ',
           '(x)' => '❌ ',
           '(/)' => '✅ ',
           ':)' => '🙂',
           ':-)' => '🙂',
           ':D' => '😀',
           ':-D' => '😀',
           ';)' => '😉',
           ';-)' => '😉',
           ':(' => '🙁',
           ':-(' => '🙁',
           ':P' => '😛',
           ':-P' => '😛',
           '(y)' => '👍',
           '(n)' => '👎',
           '(on)' => '💡',
           '(*)' => '⭐',
           '(*y)' => '⭐',
           '(*r)' => '✴',
           '(*g)' => '❇',
           '(*b)' => '✳',
           '(i)' => 'ℹ',
           '(!)' => '⚠',
           '(+)' => '➕',
           '(-)' => '➖',
           '<3' => '❤',
           '</3' => '💔',
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

    private function setTempOpts($opts)
    {
        $this->prevOpts = $this->options;
        $this->options = array_merge($this->options, $opts);
    }

    private function restoreOpts()
    {
        if ($this->prevOpts) {
            $this->options = $this->prevOpts;
            $this->prevOpts = null;
        }
    }
}
