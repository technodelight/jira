<?php

namespace Technodelight\Jira\Renderer\Issue\CustomField;

use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Helper\Image as ImageRenderer;
use Technodelight\JiraTagConverter\JiraTagConverter;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\Issue\CustomField;

class Factory
{
    public function __construct(
        private readonly TemplateHelper $templateHelper,
        private readonly Api $api,
        private readonly JiraTagConverter $tagConverter,
        private readonly ImageRenderer $imageRenderer
    ) {
    }

    public function fromFieldName($fieldName, $inline, $formatter = null)
    {
        return new CustomField(
            $this->templateHelper,
            $this->api,
            $this->imageRenderer,
            $formatter ?: new DefaultFormatter($this->tagConverter),
            $fieldName,
            $inline
        );
    }
}
