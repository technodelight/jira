<?php

namespace spec\Technodelight\Jira\Api\JiraTagConverter;

use PhpSpec\ObjectBehavior;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;
use Technodelight\Jira\Api\JiraTagConverter\Components\PrettyTable;

class JiraTagConverterSpec extends ObjectBehavior
{
    function it_does_not_convert_anything()
    {
        $this->convert(new NullOutput(), 'some string')->shouldReturn('some string');
    }

    function it_converts_code_block()
    {
        $this->convert(new NullOutput(), '{code}something{code}')->shouldReturn('<comment>something</>');
    }

    function it_converts_bold_and_underscore()
    {
        $this->convert(new NullOutput(), '*bold*')->shouldReturn('<options=bold>bold</>');
        $this->convert(new NullOutput(), '_underscore_')->shouldReturn('<options=underscore>underscore</>');
    }

    function it_converts_mentions()
    {
        $this->convert(new NullOutput(), '[~technodelight]')->shouldReturn('<fg=cyan>technodelight</>');
    }

    function it_converts_panels()
    {
        $panelSource = <<<EOL
{panel}
something
{panel}
EOL;
        $panelParsed = <<<EOL

┌───────────┐
│           │
│ something │
│           │
└───────────┘

EOL;

        $this->convert(new NullOutput(), $panelSource)->shouldReturn($panelParsed);
    }

    function it_converts_tables()
    {
        $table = <<<EOF
||Attribute Name ||Attribute PIM ID||New values||Values to be removed||
|Chemistry required|724|Processless| |
|Test|234| | |

test
||Attribute Name ||Attribute PIM ID||New values||Values to be removed||
|Chemistry required|724|Processless| |
|Test|234| | |
EOF;

        $bufferedOutput = new BufferedOutput();
        $tableRenderer = new PrettyTable($bufferedOutput);
        $tableRenderer
            ->setRows([
                ['Attribute Name', 'Attribute PIM ID', 'New values', 'Values to be removed'],
                new TableSeparator(),
                ['Chemistry required', '724', 'Processless', ' '],
                new TableSeparator(),
                ['Test', '234', ' ', ' ']
            ]);
        $tableRenderer->render();
        $renderedTable = $bufferedOutput->fetch();

        $this->convert(new NullOutput(), $table)->shouldReturn($renderedTable.PHP_EOL.'test'.PHP_EOL.trim($renderedTable));
    }

    function it_merges_definitions()
    {
        $this->convert(new NullOutput(), '*_BOLD UNDERSCORED_* *_BOLD UNDERSCORED_*')
             ->shouldReturn('<options=bold,underscore>BOLD UNDERSCORED BOLD UNDERSCORED</>');
        $this->convert(new NullOutput(), '*_BOLDUNDERSCORE_* _UNDERSCORE_')
             ->shouldReturn('<options=bold,underscore>BOLDUNDERSCORE</> <options=underscore>UNDERSCORE</>');
        $this->convert(new NullOutput(), '*_B_*' . PHP_EOL . '*_B_*')
            ->shouldReturn('<options=bold,underscore>B</>'.PHP_EOL.'<options=bold,underscore>B</>');
    }

}
