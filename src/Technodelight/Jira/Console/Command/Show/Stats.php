<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\Command\Show;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\IssueStats\StatCollector;
use Technodelight\JiraTagConverter\Components\PrettyTable;

class Stats extends Command
{
    public function __construct(
        private readonly StatCollector $statCollector
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('show:stats')
        ;
    }

    /** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new PrettyTable($output);
        $table->setHeaders([
            'IssueKey', 'LastView', 'Count'
        ]);
        foreach ($this->statCollector->all()->orderByMostRecent() as $issueKey => $data) {
            $table->addRow([
                $issueKey,
                !empty($data['time']) ? date('Y-m-d H:i:s', $data['time']) : '',
                !empty($data['total']) ? $data['total'] : ''
            ]);
        }

        $table->render();
        return self::SUCCESS;
    }
}
