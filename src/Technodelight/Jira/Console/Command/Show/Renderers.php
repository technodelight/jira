<?php

namespace Technodelight\Jira\Console\Command\Show;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraTagConverter\Components\PrettyTable;
use Technodelight\Jira\Renderer\Issue\RendererProvider;

class Renderers extends Command
{
    /**
     * @var RendererProvider
     */
    private $standardProvider;
    /**
     * @var RendererProvider
     */
    private $boardProvider;

    public function __construct(RendererProvider $standardProvider, RendererProvider $boardProvider)
    {
        $this->standardProvider = $standardProvider;
        $this->boardProvider = $boardProvider;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('show:renderers')
            ->setDescription('Show available renderers for for rendering issues')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
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
    }
}
