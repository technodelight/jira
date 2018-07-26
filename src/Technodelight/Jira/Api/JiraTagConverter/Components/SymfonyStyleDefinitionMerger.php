<?php

namespace Technodelight\Jira\Api\JiraTagConverter\Components;

class SymfonyStyleDefinitionMerger
{
    public static function findAndMergeDefs($body)
    {
        $body = self::mergeMultipleClosingTags($body);
        $defs = self::collectAllDefinitions($body);

        return self::replaceOldDefsWithMerged($body, $defs);
    }

    /**
     * merge multiple closing tags
     *
     * @param string $body
     * @return string
     */
    protected static function mergeMultipleClosingTags($body)
    {
        while (preg_match('~(</>[ ]*)+</>~u', $body)) {
            $body = preg_replace('~(</>[ ]*)+</>~u', '</>', $body);
        }

        return (string) $body;
    }

    /**
     * collect all definitions terminated by a closing tag
     *
     * @param string $body
     * @return array
     */
    protected static function collectAllDefinitions($body)
    {
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
            if ($char == '/' && $startTag !== false) {
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
                $currentDef .= $char;
            }
        }

        return $defs;
    }

    /**
     * @param string $def
     * @return array
     */
    protected static function prepareDef($def)
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

    /**
     * replace old definitions with new ones
     *
     * @param string $body
     * @param array $defs
     * @return string
     */
    protected static function replaceOldDefsWithMerged($body, array $defs = [])
    {
        foreach ($defs as $def) {
            $byType = [];
            $newDefinition = [];
            foreach ($def as $definition) {
                $preparedDefs = self::prepareDef($definition);
                foreach ($preparedDefs as $preparedDef) {
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
}
