<?php

namespace Technodelight\Jira\Renderer\Issue\CustomField;

use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\Issue\CustomField;

class Factory
{
    /**
     * @var \Technodelight\Jira\Helper\TemplateHelper
     */
    private $templateHelper;
    /**
     * @var \Technodelight\Jira\Api\JiraRestApi\Api
     */
    private $api;

    public function __construct(TemplateHelper $templateHelper, Api $api)
    {
        $this->templateHelper = $templateHelper;
        $this->api = $api;
    }

    public function fromFieldName($fieldName, $inline, $formatter = null)
    {
        return new CustomField(
            $this->templateHelper,
            $this->api,
            $formatter ?: new DefaultFormatter,
            $fieldName,
            $inline
        );
    }
}
