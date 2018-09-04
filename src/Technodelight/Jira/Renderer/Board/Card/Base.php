<?php

namespace Technodelight\Jira\Renderer\Board\Card;

use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Helper\Wordwrap;
use Technodelight\Jira\Renderer\Board\Renderer;
use Technodelight\Jira\Renderer\IssueRenderer;

abstract class Base implements IssueRenderer
{
    const BLOCK_WIDTH = Renderer::BLOCK_WIDTH;

    /**
     * @var Api
     */
    protected $api;
    /**
     * @var TemplateHelper
     */
    protected $templateHelper;
    /**
     * @var Wordwrap
     */
    protected $wordwrap;

    public function __construct(Api $api, TemplateHelper $templateHelper, Wordwrap $wordwrap)
    {
        $this->api = $api;
        $this->templateHelper = $templateHelper;
        $this->wordwrap = $wordwrap;
    }
}
