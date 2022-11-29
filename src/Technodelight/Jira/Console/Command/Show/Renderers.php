<?php

namespace Technodelight\Jira\Console\Command\Show;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Renderer\Issue\RendererContainer;
use Technodelight\JiraTagConverter\Components\PrettyTable;

class Renderers extends Command
{
    public function __construct(
        private readonly RendererContainer $standardProvider,
        private readonly RendererContainer $boardProvider
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('show:renderers')
            ->setDescription('Show available renderers for for rendering issues');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = new PrettyTable($output);
        $table->setHeaders(['Name', 'Class', 'Type']);
        foreach ($this->standardProvider->all() as $name => $renderer) {
            $table->addRow([$name, get_class($renderer), 'Standard']);
        }
        foreach ($this->boardProvider->all() as $name => $renderer) {
            $table->addRow([$name, get_class($renderer), 'Board']);
        }
        $table->render();

        return self::SUCCESS;
    }
}
