<?php

declare(strict_types=1);

namespace Technodelight\ChatGptExtension\Api;

use OpenAI;
use OpenAI\Client;
use Technodelight\ChatGptExtension\Configuration\AppConfig;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\GitConfiguration;
use Technodelight\Jira\Domain\Comment;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\GitBranchnameGenerator;

class Api
{
    private const MODEL = 'gpt-3.5-turbo';
    private Client $client;

    public function __construct(
        AppConfig $config,
        private readonly GitConfiguration $gitConfiguration,
        private readonly GitBranchnameGenerator $branchnameGenerator
    ) {
        $this->client = OpenAI::client($config->apiKey(), $config->organization());
    }

    public function branchName(Issue $issue): string
    {
        $response = $this->client->chat()->create([
            'messages' => [
                [
                    'role' => 'system',
                    'content' => strtr('You are an assistant to generate GIT branch names from the context'
                    . ' given by the user. The branch pattern looks like this sample: {pattern}, with a maximum'
                    . ' preferred total length of {maxChars} characters. The user gives to the issue key and the other'
                    . ' contextual information you need to use to generate a meaningful and short branch name. The'
                    . ' branch name should reflect the intention of fixing or implementing a given feature, depending'
                    . ' on the issue type. You only need to reply with the branch name.',
                        [
                            '{pattern}' => $this->branchnameGenerator->fromIssue($issue),
                            '{maxChars}' => $this->gitConfiguration->maxBranchNameLength()
                        ])
                ],
                [
                    'role' => 'user',
                    'content' => strtr(
                        'Issue key: {issueKey}, summary: {summary}, description: {description},'
                        . ' issue type: {issueType}',
                        [
                            '{issueKey}' => $issue->issueKey()->issueKey(),
                            '{summary}' => $issue->summary(),
                            '{description}' => $issue->description(),
                            '{issueType}' => $issue->issueType()->name()
                        ]
                    )
                ]
            ],
            'model' => self::MODEL
        ]);
        $this->log($response);

        return $response['choices'][0]['message']['content'] ?? '';
    }

    public function summarize(Issue $issue): string
    {
        $response = $this->client->chat()->create([
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an assistant to summarize JIRA issues based on context'
                        . ' given by the user. The user gives you information about the given issue'
                        . ' and you need to summarize it in a compact and meaningful way.'
                ],
                [
                    'role' => 'user',
                    'content' => strtr(
                        'summary: {summary}{description}{ac}',
                        [
                            '{summary}' => $issue->summary(),
                            '{description}' => ($description = $issue->description())
                                ? ', description: ' . $description : '',
                            '{ac}' => ($ac = $issue->findField('Acceptance Criteria'))
                                ? ', acceptance criteria:' . $ac : ''
                        ]
                    )
                ]
            ],
            'model' => self::MODEL
        ]);
        $this->log($response);

        return $response['choices'][0]['message']['content'] ?? '';
    }

    public function advise(Issue $issue, ?string $additionalContext): string
    {
        $response = $this->client->chat()->create([
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful assistant to advise on solutions for JIRA issues,'
                        . ' based on context from the user. The user can input the issue name, description, acceptance'
                        . ' critera and comments, if present. You need to advise up to 3 possible solutions and '
                        . ' highlight which one is the most likely to solve the problem. The user may specify'
                        . ' additional context.'
                ],
                [
                    'role' => 'user',
                    'content' => strtr(
                        'summary: {summary}{description}{ac}{comments}{additionalContext}',
                        [
                            '{summary}' => '"' . $issue->summary() . '"',
                            '{description}' => ($description = $issue->description())
                                ? ', description: "' . $description : '"',
                            '{ac}' => ($ac = $issue->findField('Acceptance Criteria'))
                                ? ', acceptance criteria: "' . $ac : '"',
                            '{comments}' => $issue->comments() ? ', ' . $this->assembleCommentsString($issue) : '',
                            '{additionalContext}' => $additionalContext
                                ? PHP_EOL. 'additional context:' . $additionalContext
                                : ''
                        ]
                    )
                ]
            ],
            'model' => self::MODEL
        ]);
        $this->log($response);

        return $response['choices'][0]['message']['content'] ?? '';
    }

    public function summarizeComments(Issue $issue): string
    {
        $response = $this->client->chat()->create([
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an assistant to summarize comments.'
                        . ' You need to summarize it in a compact and meaningful way.'
                        . ' You can use the last previously provided information about the JIRA issue.'
                ],
                [
                    'role' => 'user',
                    'content' => $this->assembleCommentsString($issue)
                ]
            ],
            'model' => self::MODEL
        ]);
        $this->log($response);

        return $response['choices'][0]['message']['content'] ?? '';
    }

    private function log($var): void
    {
        if (in_array('--debug', $_SERVER['argv'])) {
            file_put_contents('php://stderr', var_export($var, true) . PHP_EOL, FILE_APPEND);
        }
    }

    /**
     * @param Issue $issue
     * @return string
     */
    public function assembleCommentsString(Issue $issue): string
    {
        return 'comments:' . PHP_EOL . join(PHP_EOL, array_map(
                fn(Comment $comment) => $comment->author()->displayName() . ': ' . $comment->body(), $issue->comments()
                , $issue->comments()));
    }
}