<?php

namespace Technodelight\Jira\Console\Input\PullRequest;

use Technodelight\GitShell\ApiInterface as Api;
use Technodelight\CliEditorInput\CliEditorInput as EditApp;
use Technodelight\Jira\Console\Input\PullRequest\EditorInput\InputAssembler;
use Technodelight\Jira\Console\Input\PullRequest\EditorInput\OutputParser;
use Technodelight\Jira\Console\Input\PullRequest\EditorInput\PullRequest;
use Technodelight\Jira\Helper\HubHelper;

class EditorInput
{
    /**
     * @var EditApp
     */
    private $editor;
    /**
     * @var Api
     */
    private $git;
    /**
     * @var HubHelper
     */
    private $hub;

    public function __construct(EditApp $editor, Api $git, HubHelper $hub)
    {
        $this->editor = $editor;
        $this->git = $git;
        $this->hub = $hub;
    }

    public function gatherDataForPr($base, $head)
    {
        $input = new InputAssembler(
            $head,
            iterator_to_array($this->git->log($base, $head)),
            $this->hub->labels(),
            $this->hub->milestones(),
            $this->hub->assignees()
        );
        $output = $this->editor->edit(
            $input->title(),
            $input->content(),
            false
        );
        $parser = new OutputParser($output);
        $parser->parse();

        return new PullRequest($parser->title(), $parser->content(), $parser->labels(), $parser->milestone(), $parser->assignees());
    }
}
