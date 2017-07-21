<?php

namespace Technodelight\Jira\Helper;

use Symfony\Component\Console\Output\OutputInterface;

class JiraTagConverter
{
    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private $output;
    /**
     * @var \Technodelight\Jira\Helper\ColorExtractor
     */
    private $colorExtractor;

    public function __construct(OutputInterface $output, ColorExtractor $colorExtractor)
    {
        $this->output = $output;
        $this->colorExtractor = $colorExtractor;
    }

    public function convert($body)
    {
        try {
            $this->convertCode($body);
            $this->convertColor($body);
            $this->convertBoldUnderscore($body);
            $this->convertMentions($body);
            $this->convertPanel($body);
            $formattedBody = $this->mergeDefinitions($body);
            $this->tryFormatter($formattedBody);
            return $formattedBody;
        } catch (\Exception $exception) {
            return $body;
        }
    }

    private function convertCode(&$body)
    {
        // code block
        if ($numOfMatches = preg_match_all('~({code})(.*)({code})~smu', $body, $matches)) {
            for ($i = 0; $i < $numOfMatches; $i++) {
                $body = str_replace(
                    $matches[1][$i].$matches[2][$i].$matches[3][$i],
                    '<fg=yellow>'.$matches[2][$i].'</>',
                    $body
                );
            }
        }
    }

    private function convertColor(&$body)
    {
        // color
        if ($numOfMatches = preg_match_all('~({color[^}]*})(.+)({color})~smu', $body, $matches)) {
            for ($i = 0; $i < $numOfMatches; $i++) {
                $body = str_replace(
                    $matches[0][$i],
                    $this->formatColor($matches[2][$i], $matches[1][$i]),
                    $body
                );
            }
        }
    }

    private function convertBoldUnderscore(&$body)
    {
        // bold
        if ($numOfMatches = preg_match_all('~(\*)([^*]+)(\*)~smu', $body, $matches)) {
            for ($i = 0; $i < $numOfMatches; $i++) {
                $body = str_replace(
                    $matches[1][$i].$matches[2][$i].$matches[3][$i],
                    '<options=bold>'.$matches[2][$i].'</>',
                    $body
                );
            }
        }
        // underscore
        if ($numOfMatches = preg_match_all('~(_)([^_]+)(_)~smu', $body, $matches)) {
            for ($i = 0; $i < $numOfMatches; $i++) {
                $body = str_replace(
                    $matches[1][$i].$matches[2][$i].$matches[3][$i],
                    '<options=underscore>'.$matches[2][$i].'</>',
                    $body
                );
            }
        }
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

    private function convertPanel(&$body)
    {
        // remove panels
        $body = str_replace('{panel}', '', $body);
    }

    /**
     * @param string $body
     */
    private function tryFormatter($body)
    {
        // try formatting the body and ignore if an error happens
        $this->output->getFormatter()->format($body);
    }

    private function mergeDefinitions($body)
    {
        // merge multiple closing tags
        $body = preg_replace('~(</>[ ]*)+~', '</>', $body);

        // collect all definitions terminated by a closing tag
        $defs = [];
        $def = [];
        $startTag = false;
        $currentDef = '';
        for ($i = 0; $i < strlen($body); $i++) {
            $char = $body[$i];
            // start def
            if ($char == '<') {
                $startTag = $i;
                $currentDef = '';
                continue;
            }
            // end of prev defs
            if ($char == '/') {
                $defs[] = $def;
                $def = [];
                $startTag = false;
                continue;
            }
            // end def
            if ($char == '>' && $startTag !== false) {
                $def[$startTag] = $currentDef;
                $startTag = false;
                continue;
            }
            if ($startTag !== false) {
                $currentDef.= $char;
            }
        }

        // replace old definitions with new ones
        foreach ($defs as $def) {
            $byType = [];
            $newDefinition = [];
            foreach ($def as $definition) {
                $preparedDefs = $this->prepareDef($definition);
                foreach($preparedDefs as $preparedDef) {
                    $byType[$preparedDef['type']] = array_merge(
                        isset($byType[$preparedDef['type']]) ? $byType[$preparedDef['type']] : [],
                        $preparedDef['options']
                    );
                }
            }

            if (isset($byType['fg'])) {
                $newDefinition[] = 'fg=' . implode(',', $byType['fg']);
            }
            if (isset($byType['bg'])) {
                $newDefinition[] = 'bg=' . implode(',', $byType['bg']);
            }
            if (isset($byType['options'])) {
                $newDefinition[] = 'options=' . implode(',', $byType['options']);
            }

            if (!empty($newDefinition)) {
                $newDefinition = '<' . implode(';', $newDefinition) . '>';

                $body = preg_replace('~<' . (implode('>[ ]*<', array_map('preg_quote', $def))) . '>~', $newDefinition, $body);
            }
        }
        return $body;
    }

    private function formatColor($string, $colorDef)
    {
        return sprintf('<fg=%s>%s</>', $this->extractProperColor($colorDef), $string);
    }

    private function extractProperColor($colorDef)
    {
        list(, $colorName) = explode(':', trim($colorDef, '{}'), 2) + ['', 'default'];
        return $this->colorExtractor->extractColor($colorName);
    }

    private function prepareDef($def)
    {
        $parts = explode(';', $def);
        $preparedDef = [];
        foreach ($parts as $k => $part) {
            $params = explode('=', $part);
            if (isset($params[1])) {
                $options = array_unique(explode(',', $params[1]));
            } else {
                $options = [];
            }
            $preparedDef[] = [
                'type' => $params[0],
                'options' => $options,
            ];
        }
        return $preparedDef;
    }
}
