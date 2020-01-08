<?php

namespace Technodelight\Jira\Renderer\Issue\CustomField;

use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\JiraTagConverter\JiraTagConverter;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\Issue\CustomField;

class Factory
{
    /**
     * @var TemplateHelper
     */
    private $templateHelper;
    /**
     * @var Api
     */
    private $api;
    /**
     * @var JiraTagConverter
     */
    private $tagConverter;

    public function __construct(TemplateHelper $templateHelper, Api $api, JiraTagConverter $tagConverter)
    {
        $this->templateHelper = $templateHelper;
        $this->api = $api;
        $this->tagConverter = $tagConverter;
    }

    public function fromFieldName($fieldName, $inline, $formatter = null)
    {
        return new CustomField(
            $this->templateHelper,
            $this->api,
            $formatter ?: new DefaultFormatter($this->tagConverter),
            $fieldName,
            $inline
        );
    }
}
