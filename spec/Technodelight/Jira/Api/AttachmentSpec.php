<?php

namespace spec\Technodelight\Jira\Api;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Technodelight\Jira\Api\Issue;

class AttachmentSpec extends ObjectBehavior
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

    function it_can_be_created_from_array(Issue $issue)
    {
        $this->beConstructedFromArray($this->attachment, $issue);
        $this->id()->shouldReturn('12345');
        $this->author()->shouldReturn('User Name');
        $this->created()->shouldHaveType(\DateTime::class);
        $this->size()->shouldReturn(203872);
        $this->filename()->shouldReturn('attachment-filename.pdf');
        $this->url()->shouldReturn('https://fixture.jira.phar/secure/attachment/12345/attachment-filename.pdf');
        $this->issue()->shouldReturn($issue);
    }
}
