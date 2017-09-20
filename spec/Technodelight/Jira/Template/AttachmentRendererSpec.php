<?php

namespace spec\Technodelight\Jira\Template;

use PhpSpec\ObjectBehavior;
use Technodelight\Jira\Domain\Attachment;
use Technodelight\Jira\Domain\Issue;

class AttachmentRendererSpec extends ObjectBehavior
{
    private $attachment = [
        "self" => "https://fixture.jira.phar/rest/api/2/attachment/12345",
        "id" => "12345",
        "filename" => "attachment-filename.pdf",
        "author" => [
            "self" => "https://fixture.jira.phar/rest/api/2/user?username=User.Name",
            "name" => "User.Name",
            "key" => "user.name",
            "accountId" => "111111:88888888-4444-4444-4444-999999999999",
            "emailAddress" => "User.Name@fixture.jira.phar",
            "displayName" => "User Name",
            "active" => true,
            "timeZone" => "Europe/London",
        ],
        "created" => "2017-07-24T16:18:21.156+0200",
        "size" => 203872,
        "mimeType" => "application/pdf",
        "content" => "https://fixture.jira.phar/secure/attachment/12345/attachment-filename.pdf",
    ];

    function it_can_render_attachments(Issue $issue)
    {
        $issue->issueKey()->willReturn('JIRA-123');
        $attachment = Attachment::fromArray($this->attachment, Issue::fromArray(['key' => 'JIRA-123', 'id' => 'JIRA-123', 'self' => '', 'fields' => []]));
        $this->renderAttachment([$attachment])->shouldRender('User Name');
        $this->renderAttachment([$attachment])->shouldRender('attachment-filename.pdf');
    }

    public function getMatchers()
    {
        return [
            'render' => function($out, $expected) {
                return strpos($out, $expected) !== false;
            }
        ];
    }
}
