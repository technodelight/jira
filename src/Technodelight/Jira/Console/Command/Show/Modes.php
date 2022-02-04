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
    const PADDING_WIDTH_WITH_SEPARATORS = 8;
    private $renderersConfiguration;
    private $wordwrapper;
    private $terminalDimensionProvider;

    public function __construct(
        RenderersConfiguration $renderersConfiguration,
        Wordwrap $wordwrapper,
        TerminalDimensionProvider $terminalDimensionProvider
    ) {
        $this->renderersConfiguration = $renderersConfiguration;
        $this->wordwrapper = $wordwrapper;
        $this->terminalDimensionProvider = $terminalDimensionProvider;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('show:modes')
            ->setDescription('Show configured issue rendering modes');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
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
    }

    /**
     * @param FieldConfiguration[] $fields
     * @return string
     */
    private function fields(array $fields)
    {
        $fieldNames = [];
        foreach ($fields as $field) {
            $fieldNames[] = $field->name();
        }

        return implode(', ', $fieldNames);
    }

    /**
     * @param RendererConfiguration $mode
     * @return int
     */
    private function calculateWrapWidth(RendererConfiguration $mode)
    {
        return $this->terminalDimensionProvider->width() - strlen($mode->name()) - self::PADDING_WIDTH_WITH_SEPARATORS;
    }
}
