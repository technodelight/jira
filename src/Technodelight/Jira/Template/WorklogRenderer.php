<?php

namespace Technodelight\Jira\Template;

use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Template\Template;

class WorklogRenderer
{
    /**
     * @var TemplateHelper
     */
    private $templateHelper;

    public function __construct()
    {
        $this->templateHelper = new TemplateHelper;
    }

    /**
     * @param Worklog[] $worklogs
     */
    public function renderWorklogs(array $worklogs)
    {
        $template = Template::fromFile('Technodelight/Jira/Resources/views/Commands/worklog.template');

        $output = [];
        foreach ($worklogs as $record) {
            $output[] = $template->render(
                [
                    'author' => $record->author(),
                    'timeSpent' => $record->timeSpent(),
                    'date' => $record->date(),
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
