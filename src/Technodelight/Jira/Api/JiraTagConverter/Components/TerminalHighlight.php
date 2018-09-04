<?php

namespace Technodelight\Jira\Api\JiraTagConverter\Components;

class TerminalHighlight
{
    private static $tool;

    public static function isAvailable()
    {
        static $flag;
        if (!isset($flag)) {
            $hasHighlight = self::hasTool('highlight');
            $hasBat = self::hasTool('bat');
            $flag = $hasHighlight || $hasBat;
            self::$tool = $hasBat ? 'bat' : ($hasHighlight ? 'highlight' : '');
        }

        return $flag;
    }

    /**
     * @param string $code
     * @param string $syntax
     * @return string
     */
    public static function formatCode($code, $syntax)
    {
        switch (self::$tool) {
            case 'highlight':
                return self::formatWithHighlight($code, $syntax);
            case 'bat':
                return self::formatWithBat($code, $syntax);
        }

        return '';
    }

    /**
     * @param string $code
     * @param string $syntax
     * @return string
     */
    private static function formatWithHighlight($code, $syntax)
    {
        $args = '--out-format xterm256 --line-numbers --quiet --force --style molokai --syntax=' . $syntax;
        $file = self::tempFile($code);
        exec(
            'highlight --input=' . $file . ' ' . $args . ' 2> /dev/null',
            $out
        );
        unlink($file);

        return join(PHP_EOL, $out);
    }

    /**
     * @param string $code
     * @param string $syntax
     * @return string
     */
    private static function formatWithBat($code, $syntax)
    {
        $args = '--wrap never --color always --language ' . $syntax;
        $file = self::tempFile($code);
        exec(
            'bat ' . $file . ' ' . $args . ' 2> /dev/null',
            $out
        );
        unlink($file);

        return join(PHP_EOL, $out);
    }

    /**
     * @param string $tool
     * @return bool
     */
    private static function hasTool($tool)
    {
        exec(sprintf('which %s 2>&1 > /dev/null', $tool), $out, $return);

        return ($return == 0);
    }

    /**
     * @param $code
     * @return bool|string
     */
    private static function tempFile($code)
    {
        $strippedString = trim($code, PHP_EOL);
        $file = tempnam(sys_get_temp_dir(), 'termhigh');
        file_put_contents($file, $strippedString);

        return $file;
    }
}
