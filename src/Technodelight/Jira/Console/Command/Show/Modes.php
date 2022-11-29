<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\Command\Show;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Configuration\ApplicationConfiguration\RendererConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\RendererConfiguration\FieldConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\RenderersConfiguration;
use Technodelight\Jira\Helper\TerminalDimensionProvider;
use Technodelight\Jira\Helper\Wordwrap;
use Technodelight\JiraTagConverter\Components\PrettyTable;

class Modes extends Command
{
    private const PADDING_WIDTH_WITH_SEPARATORS = 8;

    public function __construct(
        private readonly RenderersConfiguration $renderersConfiguration,
        private readonly Wordwrap $wordwrapper,
        private readonly TerminalDimensionProvider $terminalDimensionProvider
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('show:modes')
            ->setDescription('Show configured issue rendering modes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = new PrettyTable($output);
        $table->setHeaders(['Name', 'Fields']);
        foreach ($this->renderersConfiguration->modes() as $mode) {
            $table->addRow([
                $mode->name(),
                $this->wordwrapper->wrap(
                    $this->fields($mode->fields()),
                    $this->calculateWrapWidth($mode)
                )
            ]);
        }
        $table->render();

        return self::SUCCESS;
    }

    private function fields(array $fields): string
    {
        $fieldNames = [];
        foreach ($fields as $field) {
            $fieldNames[] = $field->name();
        }

        return implode(', ', $fieldNames);
    }

    private function calculateWrapWidth(RendererConfiguration $mode): int
    {
        return $this->terminalDimensionProvider->width()
            - strlen($mode->name())
            - self::PADDING_WIDTH_WITH_SEPARATORS;
    }
}
