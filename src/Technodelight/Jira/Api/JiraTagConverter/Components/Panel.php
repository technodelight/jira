<?php

namespace Technodelight\Jira\Api\JiraTagConverter\Components;

use Symfony\Component\Console\Output\BufferedOutput;

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
        $out = new BufferedOutput();
        $out->writeln('');

        $table = new PrettyTable($out);
        $table->addRow([$this->replacement()]);
        $table->render();

        return $out->fetch();
    }

    private function replacement()
    {
        return str_replace(
            '{panel}',
            '',
            join(PHP_EOL, array_map('trim', explode(PHP_EOL, $this->source)))
        );
    }
}
