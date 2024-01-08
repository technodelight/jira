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
    private const CONTENT_W_LINEBREAK = "%s:\n%s";
    public const MODEL = 'gpt-3.5-turbo';
    private const TOKEN_LENGTH_SOFT_LIMIT = 4000;
    private Client $client;

    public function __construct(
        private readonly AppConfig $config,
        private readonly GitConfiguration $gitConfiguration,
        private readonly GitBranchnameGenerator $branchnameGenerator
    ) {
        $this->client = OpenAI::client($config->apiKey(), $config->organization());
    }

    public function branchName(Issue $issue): string
    {
        $tokensCount = 0;

        return $this->callApiWithMessages($this->assembleContent(
            [
                '' => ['system', strtr('You are an assistant to generate GIT branch names from the context'
                    . ' given by the user. The branch pattern looks like this sample: {pattern}, with a maximum'
                    . ' preferred total length of {maxChars} characters. The user gives to the issue key and the other'
                    . ' contextual information you need to use to generate a meaningful and short branch name. The'
                    . ' branch name should reflect the intention of fixing or implementing a given feature, depending'
                    . ' on the issue type. You should only reply with the branch name.',
                    [
                        '{pattern}' => $this->branchnameGenerator->fromIssue($issue),
                        '{maxChars}' => $this->gitConfiguration->maxBranchNameLength()
                    ])],
                'issue key' => ['user', $issue->issueKey()->issueKey()],
                'summary' => ['user', $issue->summary()],
                'description' => ['user', $issue->description()],
                'issue type' => ['user', $issue->issueType()->name()],
            ],
            $tokensCount,
            fn($key, $value) => sprintf(self::CONTENT_W_LINEBREAK, $key, trim($value))
        ));
    }

    public function summarize(Issue $issue): string
    {
        $tokenCounts = 0;

        return $this->callApiWithMessages($this->assembleContent(
            [
                '' => ['system', 'You are an assistant to summarize JIRA issues based on context'
                    . ' given by the user. The user gives you information about the given issue'
                    . ' and you need to summarize it in a compact and meaningful way.'],
                'summary' => ['user', $issue->summary()],
                'description' => ['user', $issue->description()],
                'acceptance criteria' => ['user', $issue->findField('Acceptance Criteria')]
            ],
            $tokenCounts,
            fn($key, $value) => sprintf(self::CONTENT_W_LINEBREAK, $key, trim($value))
        ));
    }

    public function advise(Issue $issue, ?string $additionalContext): string
    {
        $tokenCounts = 0;

        return $this->callApiWithMessages($this->assembleContent(
            [
                '' => ['system', 'You are a helpful assistant to advise on solutions for JIRA issues,'
                    . ' based on context from the user. The user can input the issue name, description, acceptance'
                    . ' criteria and comments, if present. You need to advise up to 3 possible solutions and '
                    . ' highlight which one is the most likely to solve the problem. The user may specify'
                    . ' additional context. You can add your own proposed solution based on the previously'
                    . ' described problems. The user will provide the context in individual messages.'],
                'summary' => ['user', $issue->summary()],
                'description' => ['user', $issue->description()],
                'acceptance criteria' => ['user', $issue->findField('Acceptance Criteria')],
                'additional context' => ['user', $additionalContext],
                'comments' => ['user', $issue->comments() ? $this->assembleCommentsString($issue) : null],
            ],
            $tokenCounts,
            fn($key, $value) => sprintf(self::CONTENT_W_LINEBREAK, $key, trim($value))
        ));
    }

    public function summarizeComments(Issue $issue): string
    {
        $tokenCounts = 0;
        return $this->callApiWithMessages($this->assembleContent(
            [
                '' => ['system', 'You are an assistant to summarize comments.'
                    . ' You need to summarize it in a compact and meaningful way.'
                    . ' You can use the last previously provided information about the JIRA issue.'],
                'comments' => ['user', $issue->comments() ? $this->assembleCommentsString($issue) : null],
            ],
            $tokenCounts,
            fn($key, $value) => sprintf(self::CONTENT_W_LINEBREAK, $key, trim($value))
        ));
    }

    private function assembleContent(array $fields, int &$tokenCounts, ?callable $assembler = null): array
    {
        $contents = [];
        foreach ($fields as $key => $roleAndValue) {
            [$role, $value] = $roleAndValue + ['user', ''];
            if (null !== $value && !empty(trim($value))) {
                $content = $value;
                if ($role === 'user') {
                    $content = $assembler($key, $value);
                }
                if (($tokenCounts + $this->guessTokenCount($content)) < self::TOKEN_LENGTH_SOFT_LIMIT) {
                    $contents[] = [
                        'role' => $role,
                        'content' => $content
                    ];
                    $tokenCounts+= $this->guessTokenCount($content);
                } else {
                    $this->log('skip appending field ' . $key . ' due to length token limitation');
                }
            }
        }

        return $contents;
    }

    private function assembleCommentsString(Issue $issue): string
    {
        return 'comments:' . PHP_EOL . join(PHP_EOL, array_map(
                fn(Comment $comment) => $comment->author()->displayName() . ': ' . $comment->body(), $issue->comments()
                , $issue->comments()));
    }

    private function callApiWithMessages(array $messages): string
    {
        $parameters = [
            'model' => $this->config->model(),
            'messages' => $messages,
        ];

        $response = $this->client->chat()->create($parameters);
        $this->log($response);

        return $response['choices'][0]['message']['content'] ?? '';
    }

    private function guessTokenCount(string $content): int
    {
        $words = preg_split('~\s+~', $content);

        // rule of thumb advised by chatGPT documentation
        return count($words) * 4;
    }

    private function log($var): void
    {
        if (in_array('--debug', $_SERVER['argv'])) {
            file_put_contents('php://stderr', var_export($var, true) . PHP_EOL, FILE_APPEND);
        }
    }
}
