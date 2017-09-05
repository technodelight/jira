<?php

namespace Technodelight\Jira\Template;

use Technodelight\Jira\Domain\Worklog;
use Technodelight\Jira\Domain\WorklogCollection;
use Technodelight\Jira\Console\Application;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Simplate;

class WorklogRenderer
{
    /**
     * @var TemplateHelper
     */
    private $templateHelper;

    /**
     * @var string
     */
    private $viewsDir;

    public function __construct(Application $app, TemplateHelper $templateHelper)
    {
        $this->viewsDir = $app->directory('views');
        $this->templateHelper = $templateHelper;
    }

    /**
     * @param Worklog[] $worklogs
     */
    public function renderWorklogs(WorklogCollection $worklogs)
    {
        $template = Simplate::fromFile($this->viewsDir . DIRECTORY_SEPARATOR . 'Commands/worklog.template');

        $output = [];
        foreach ($worklogs as $record) {
            $output[] = $template->render(
                [
                    'worklogId' => $record->id(),
                    'author' => $record->author(),
                    'timeSpent' => $record->timeSpent(),
                    'date' => $record->date()->format('Y-m-d H:i:s'),
                    'comment' => $this->templateHelper->tabulate(wordwrap($record->comment()), 8),
                ]
            );
        }

        return implode(
            PHP_EOL . PHP_EOL,
            array_map(
                function($renderedLog) {
                    return implode(
                        PHP_EOL,
                        array_filter(array_map('rtrim', explode(PHP_EOL, $renderedLog)))
                    );
                },
                $output
            )
        );
    }
}
