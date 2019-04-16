<?php

namespace Technodelight\Jira\Renderer\Action;

use Symfony\Component\Console\Output\OutputInterface;

class ActionRenderer implements Renderer
{
    /**
     * @var Renderer[]
     */
    private $renderers;

    public function __construct(array $renderers = [])
    {
        $this->renderers = $renderers;
    }

    public function canProcess(Result $result): bool
    {
        return true;
    }

    public function render(OutputInterface $output, Result $result): int
    {
        foreach ($this->renderers as $renderer) {
            if ($renderer->canProcess($result)) {
                return $renderer->render($output, $result);
            }
        }

        $output->writeln(sprintf('cannot process result of type %s :/', get_class($result)));
        return 0;
    }
}
