<?php

namespace Technodelight\Jira\Console\Command\Show;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Renderer\Issue\RendererProvider;

class Renderers extends Command
{
    /**
     * @var RendererProvider
     */
    private $rendererProvider;

    public function setRendererProvider(RendererProvider $rendererProvider)
    {
        $this->rendererProvider = $rendererProvider;
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
        $table = new Table($output);
        $table->setHeaders(['Name', 'Class']);
        foreach ($this->rendererProvider->all() as $name => $renderer) {
            $table->addRow([$name, get_class($renderer)]);
        }
        $table->render();
    }
}
