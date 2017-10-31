<?php

namespace Technodelight\Jira\Helper;

use Technodelight\Jira\Api\Shell\Command;
use Technodelight\Jira\Api\Shell\NativeShell;

class ITermVersion
{
    private $parsed = false;
    private $version;

    public function __toString()
    {
        return $this->guessVersion();
    }

    private function guessVersion()
    {
        if ($this->parsed) {
            return $this->version;
        }

        $this->parsed = true;
        if ($version = getenv('TERM_PROGRAM_VERSION')) {
            return $this->version = $version;
        } else if (is_file('/Applications/iTerm.app/Contents/Info.plist')) {
            $cmd = Command::create('cat')
                ->withArgument('/Applications/iTerm.app/Contents/Info.plist')
                ->pipe(
                    Command::create('grep')
                       ->withArgument('CFBundleVersion')
                       ->withShortOption('A1')
                );
            $shell = new NativeShell();
            $out = $shell->exec($cmd);
            $xml = simplexml_load_string('<root>' . join('', $out) . '</root>');
            return $this->version = (string) $xml->string;
        }

        return $this->version = '';
    }
}
