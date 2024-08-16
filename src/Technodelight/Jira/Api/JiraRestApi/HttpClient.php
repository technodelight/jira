<?php

declare(strict_types=1);

namespace Technodelight\Jira\Api\JiraRestApi;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Technodelight\Jira\Api\JiraRestApi\HttpClient\Config;
use UnexpectedValueException;

/** @SuppressWarnings(PHPMD.StaticAccess) */
class HttpClient implements Client
{
    private const API_PATH = '/rest/api/2/';

    private GuzzleClient $httpClient;

    public function __construct(private readonly Config $config) {}

    public function post($url, $data = [])
    {
        try {
            $result = $this->httpClient()->post($url, ['json' => $data]);
            return json_decode((string)$result->getBody(), true);
        } catch (GuzzleClientException $e) {
            throw ClientException::fromException($e);
        }
    }

    public function put($url, $data = [])
    {
        try {
            $result = $this->httpClient()->put($url, ['json' => $data]);
            return json_decode($result->getBody()->getContents(), true);
        } catch (GuzzleClientException $e) {
            throw ClientException::fromException($e);
        }
    }

    public function get($url)
    {
        try {
            $result = $this->httpClient()->get($url);
            return json_decode($result->getBody()->getContents(), true);
        } catch (GuzzleClientException $e) {
            throw ClientException::fromException($e);
        }
    }

    public function delete($url)
    {
        try {
            $result = $this->httpClient()->delete($url);
            return json_decode($result->getBody()->getContents(), true);
        } catch (GuzzleClientException $e) {
            throw ClientException::fromException($e);
        }
    }

    public function multiGet(array $urls): array
    {
        $promises = [];
        foreach ($urls as $url) {
            $promises[$url] = $this->httpClient()->getAsync($url);
        }

        $responses = Utils::settle($promises)->wait();
        $results = [];
        foreach ($responses as $url => $settle) {
            if ($settle['state'] != 'fulfilled') {
                throw new UnexpectedValueException('Something went wrong while querying JIRA!');
            }
            /** @var ResponseInterface $value */
            $value = $settle['value'];
            $results[$url] = json_decode((string) $value->getBody(), true);
        }

        return $results;
    }

    /**
     * @param string $jql
     * @param null $startAt
     * @param null $fields
     * @param array|null $expand
     * @param array|null $properties
     * @return array
     * @throws GuzzleException
     */
    public function search($jql, $startAt = null, $fields = null, array $expand = null, array $properties = null): array
    {
        try {
            $result = $this->httpClient()->post(
                'search',
                [
                    'json' => array_filter([
                        'jql' => $jql,
                        'startAt' => $startAt,
                        'fields' => (array) $fields,
                        'expand' => $expand,
                        'properties' => $properties,
                    ])
                ]
            );
            return json_decode($result->getBody()->getContents(), true);
        } catch (GuzzleClientException $exception) {
            throw ClientException::fromException($exception);
        }
    }

    public function download($url, $filenameOrResource, callable $progressFunction = null): void
    {
        $response = $this->httpClient()->get(
            $url,
            array_filter([
                'progress' => $progressFunction
            ])
        );

        if (is_string($filenameOrResource)) {
            $filenameOrResource = fopen($filenameOrResource, 'w');
        }

        fwrite($filenameOrResource, (string)$response->getBody());
    }

    public function upload($url, $filename): void
    {
        $this->httpClient()->post($url, [
            'headers' => [
                'X-Atlassian-Token' => 'no-check'
            ],
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => fopen($filename, 'r'),
                    'filename' => pathinfo($filename, PATHINFO_BASENAME),
                    'headers' => [
                        'X-Atlassian-Token' => 'no-check'
                    ],
                ]
            ]
        ]);
    }

    private function apiUrl(string $projectDomain): string
    {
        $parts = parse_url($projectDomain);
        if (count($parts) === 1 && isset($parts['path'])) {
            $parts['host'] = $parts['path'];
            unset($parts['path']);
        }
        $url = join('', array_filter([
            isset($parts['user']) && isset($parts['pass']) ? $parts['user'] . ':' . $parts['pass'] . '@' : null,
            $parts['host'],
            isset($parts['port']) ? ':' . $parts['port'] : null,
        ]));

        return sprintf(
            '%s://%s%s',
            $parts['scheme'] ?? 'https',
            $url,
            self::API_PATH
        );
    }

    /** @SuppressWarnings(PHPMD) */
    private function httpClient(): GuzzleClient
    {
        if (!isset($this->httpClient)) {
            $this->httpClient = new GuzzleClient(
                [
                    'base_uri' => $this->apiUrl($this->config->domain()),
                    'auth' => [$this->config->username(), $this->config->password()],
                    'allow_redirects' => true,
                    'progress' => static function () {
                        if (in_array('--quiet', $_SERVER['argv']) || getopt('q')) {
                            return;
                        }

                        static $index = 0;
                        static $chars = ['|', '/', '-', '\\'];

                        printf("\033[1G\033[2K" . $chars[$index % 4] . PHP_EOL . "\033[1A");
                        $index++;
                    },
                    'stream' => true,
                    'on_stats' => function (TransferStats $stats) {
                        if (!in_array('--debug', $_SERVER['argv'])) {
                            return;
                        }
                        file_put_contents(
                            'php://stderr',
                            sprintf('%s: %s' . PHP_EOL, $stats->getEffectiveUri(), $stats->getTransferTime())
                        );

                        // You must check if a response was received before using the
                        // response object.
                        if ($stats->hasResponse()) {
                            file_put_contents('php://stderr', sprintf('%s: %s' . PHP_EOL,
                                $stats->getResponse()->getStatusCode(),
                                $stats->getResponse()->getReasonPhrase()
                            ));
                        } else {
                            // Error data is handler specific. You will need to know what
                            // type of error data your handler uses before using this
                            // value.
                            file_put_contents(
                                'php://stderr',
                                var_export($stats->getHandlerErrorData(), true)
                            );
                        }
                    }
                ]
            );
        }

        return $this->httpClient;
    }
}
