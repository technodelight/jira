<?php

namespace Technodelight\Jira\Api\JiraRestApi;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\TransferStats;
use Symfony\Component\Console\Output\ConsoleOutput;
use Technodelight\Jira\Api\JiraRestApi\HttpClient\Config;

class HttpClient implements Client
{
    const API_PATH = '/rest/api/2/';

    /**
     * @var GuzzleClient
     */
    private $httpClient;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function post($url, $data = [])
    {
        try {
            $result = $this->httpClient()->post($url, ['json' => $data]);
            return json_decode($result->getBody(), true);
        } catch (GuzzleClientException $e) {
            throw ClientException::fromException($e);
        }
    }

    public function put($url, $data = [])
    {
        try {
            $result = $this->httpClient()->put($url, ['json' => $data]);
            return json_decode($result->getBody(), true);
        } catch (GuzzleClientException $e) {
            throw ClientException::fromException($e);
        }
    }

    public function get($url)
    {
        try {
            $result = $this->httpClient()->get($url);
            return json_decode($result->getBody(), true);
        } catch (GuzzleClientException $e) {
            throw ClientException::fromException($e);
        }
    }

    public function delete($url)
    {
        try {
            $result = $this->httpClient()->delete($url);
            return json_decode($result->getBody(), true);
        } catch (GuzzleClientException $e) {
            throw ClientException::fromException($e);
        }
    }

    public function multiGet(array $urls)
    {
        $promises = [];
        foreach ($urls as $url) {
            $promises[$url] = $this->httpClient()->getAsync($url);
        }

        $responses = Utils::settle($promises)->wait();
        $results = [];
        foreach ($responses as $url => $settle) {
            if ($settle['state'] != 'fulfilled') {
                throw new \UnexpectedValueException('Something went wrong while querying JIRA!');
            }
            /** @var \Psr\Http\Message\ResponseInterface $value */
            $value = $settle['value'];
            $results[$url] = json_decode((string) $value->getBody(), true);
        }

        return $results;
    }

    /**
     * @param string $jql
     * @param string|null $fields
     *
     * @return array
     */
    public function search($jql, $startAt = null, $fields = null, array $expand = null, array $properties = null)
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
            return json_decode($result->getBody(), true);
        } catch (GuzzleClientException $exception) {
            throw ClientException::fromException($exception);
        }
    }

    public function download($url, $filename, callable $progressFunction = null): void
    {
        if ($progressFunction) {
            $this->httpClient()->get(
                $url,
                array_filter([
                    'sink' => $filename,
                    'progress' => $progressFunction,
                ])
            );
            return;
        }

        $this->httpClient()->get($url, ['save_to' => $filename]);
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

    private function apiUrl($projectDomain)
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
            isset($parts['proto']) ? $parts['proto'] : 'https',
            $url,
            self::API_PATH
        );
    }

    /**
     * @return GuzzleClient
     */
    private function httpClient()
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

                        static $i = 0;
                        static $chars = ['|', '/', '-', '\\'];

                        printf("\033[1G\033[2K" . $chars[$i % 4] . PHP_EOL . "\033[1A");
                        $i++;
                    },
                    'stream' => true,
                    'on_stats' => function (TransferStats $stats) {
                        if (!in_array('--debug', $_SERVER['argv'])) {
                            return;
                        }
                        printf('%s: %s' . PHP_EOL, $stats->getEffectiveUri(), $stats->getTransferTime());

                        // You must check if a response was received before using the
                        // response object.
                        if ($stats->hasResponse()) {
                            printf('%s: %s' . PHP_EOL,
                                $stats->getResponse()->getStatusCode(),
                                $stats->getResponse()->getReasonPhrase()
                            );
                        } else {
                            // Error data is handler specific. You will need to know what
                            // type of error data your handler uses before using this
                            // value.
                            var_dump($stats->getHandlerErrorData());
                        }
                    }
                ]
            );
        }

        return $this->httpClient;
    }
}
