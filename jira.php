#!/usr/bin/php
<?php

require __DIR__ . '/vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

use GuzzleHttp\Client as GuzzleClient;

class Configuration
{
    const CONFIG_FILENAME = 'jira.ini';

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $domain;

    /**
     * @var string
     */
    private $project;

    protected function __construct(array $ini = [])
    {
        $this->username = $this->setIniField($ini, 'username');
        $this->password = $this->setIniField($ini, 'password');
        $this->domain = $this->setIniField($ini, 'domain');
        $this->project = $this->setIniField($ini, 'project');
    }

    public static function initFromDirectory($iniFilePath)
    {
        return new self(self::parseIniFile($iniFilePath));
    }

    public function username()
    {
        return $this->username;
    }

    public function password()
    {
        return $this->password;
    }

    public function domain()
    {
        return $this->domain;
    }

    public function project()
    {
        return $this->project;
    }

    public function merge(Configuration $configuration)
    {
        $fields = array_keys(get_object_vars($this));
        foreach ($fields as $field) {
            if ($value = $configuration->$field()) {
                $this->$field = $value;
            }
        }
    }

    protected function setIniField(array $ini, $field)
    {
        if (!empty($ini[$field])) {
            return $ini[$field];
        }
    }

    protected static function parseIniFile($iniFilePath)
    {
        $iniFile = $iniFilePath . DIRECTORY_SEPARATOR . self::CONFIG_FILENAME;
        if (!is_file($iniFile)) {
            throw new UnexpectedValueException('No configuration found!');
        }

        if (!is_readable($iniFile)) {
            throw new UnexpectedValueException('Cannot read configuration!');
        }

        $perms = substr(sprintf('%o', fileperms($iniFile)), -4);
        if ($perms !== '0600') {
            throw new UnexpectedValueException(
                sprintf('Configuration cannot be readable by others! %s should be 0600)', $perms)
            );
        }

        return parse_ini_file($iniFile);
    }
}

class GlobalConfiguration extends Configuration
{
    public static function initFromDirectory($iniFilePath)
    {
        if (!is_file($iniFilePath . DIRECTORY_SEPARATOR . self::CONFIG_FILENAME)) {
            return new self();
        }

        return new self(self::parseIniFile($iniFilePath));
    }
}

class Client
{
    /**
     * @var GuzzleClient
     */
    private $client;

    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(Configuration $config)
    {
        $this->httpClient = new GuzzleClient(
            [
                'base_url' => $this->apiUrl($config->domain()),
                'defaults' => [
                    'auth' => [$config->username(), $config->password()]
                ]
            ]
        );
    }

    public function user()
    {
        return $this->httpClient->get('myself')->json();
    }

    public function project($code)
    {
        return $this->httpClient->get('project/' . $code)->json();
    }

    public function inprogressIssues($projectCode)
    {
        $query = sprintf('project = "%s" and assignee = currentUser() and status = "In Progress"', $projectCode);
        return $this->search($query);
    }

    public function todoIssues($projectCode)
    {
        $query = sprintf(
            'project = "%s" and status = "Open" and Sprint in openSprints() and issuetype in ("%s") ORDER BY priority DESC',
            $projectCode,
            implode('", "', ['Defect', 'Bug', 'Technical Sub-Task'])
        );
        return $this->search($query);
    }

    private function search($jql)
    {
        $response = $this->httpClient->get('search', ['query' => ['jql' => $jql]]);
        return SearchResultList::fromArray($response->json());
    }

    private function apiUrl($projectDomain)
    {
        return sprintf(
            'https://%s/rest/api/2/',
            $projectDomain
        );
    }
}

class SearchResultList implements Iterator
{
    private $startAt, $maxResults, $total, $issues;

    public function __construct($startAt, $maxResults, $total, $issues)
    {
        $this->startAt = $startAt;
        $this->maxResults = $maxResults;
        $this->total = $total;
        $this->issues = $issues;
    }

    public static function fromArray($resultArray)
    {
        return new self(
            $resultArray['startAt'],
            $resultArray['maxResults'],
            $resultArray['total'],
            $resultArray['issues']
        );
    }

    public function current()
    {
        $item = current($this->issues);
        return Issue::fromArray($item);
    }

    public function next()
    {
        return next($this->issues);
    }

    public function key()
    {
        return key($this->issues);
    }

    public function rewind()
    {
        reset($this->issues);
    }

    public function valid()
    {
        $item = current($this->issues);
        return $item !== false;
    }
}

class DateHelper
{
    private $jiraFormat = 'Y-m-dTH:i:s.000+0100';
    private $secondsMap = [
        'd' => 27000, // 7h 30m
        'h' => 3600,
        'm' => 60,
        's' => 1,
    ];

    public static function dateTimeFromJira($dateString)
    {
        return \DateTime::createFromFormat($dateString, $this->jiraFormat);
    }

    public function secondsToHuman($seconds)
    {
        if ($seconds === 0) {
            return 'none';
        }

        $human = [];
        foreach ($this->secondsMap as $stringRepresentation => $amount) {
            if ($seconds < 1) {
                break;
            }
            $value = floor($seconds / $amount);
            $seconds-= ($value * $amount);
            if ($value >= 1) {
                $human[] = sprintf('%d%s', $value, $stringRepresentation);
            }
        }
        return implode(' ', $human);
    }
}

class Issue
{
    private $id, $link, $key, $fields;

    public function __construct($id, $link, $key, $fields)
    {
        $this->id = $id;
        $this->link = $link;
        $this->key = $key;
        $this->fields = $fields;
    }

    public function ticketNumber()
    {
        return $this->key;
    }

    public function summary()
    {
        return $this->findField('summary');
    }

    public function description()
    {
        return $this->findField('description');
    }

    public function created()
    {
        $field = $this->findField('created');
        if ($field) {
            $field = DateHelper::dateTimeFromJira($field);
        }

        return $field;
    }

    public function environment()
    {
        return $this->findField('environment');
    }

    public function reporter()
    {
        $field = $this->findField('reporter');
        if ($field) {
            return $field['displayName'] ?: '<unknown>';
        }
        return '<unknown>';
    }

    public function creator()
    {
        $field = $this->findField('creator');
        if ($field) {
            return $field['displayName'] ?: '<unknown>';
        }
        return '<unknown>';
    }

    public function progress()
    {
        return $this->findField('progress');
    }

    public function estimate()
    {
        return (int) $this->findField('timeestimate');
    }

    public function timeSpent()
    {
        return (int) $this->findField('timespent');
    }

    public function issueType()
    {
        if ($field = $this->findField('issuetype')) {
            return $field['name'];
        }
    }

    public function url()
    {
        $uriParts = parse_url($this->link);
        return sprintf(
            '%s://%s/browse/%s',
            $uriParts['scheme'],
            $uriParts['host'],
            $this->key
        );
    }

    public function components()
    {
        if ($comps = $this->findField('compnents')) {
            $names = [];
            foreach ($comps as $field) {
                $names[] = $field['name'];
            }

            return $names;
        }
    }

    public static function fromArray($resultArray)
    {
        return new self($resultArray['id'], $resultArray['self'], $resultArray['key'], $resultArray['fields']);
    }

    private function findField($name)
    {
        return isset($this->fields[$name]) ? $this->fields[$name] : false;
    }
}

class GitBranchnameGenerator
{
    private $remove = ['BE', 'FE'];
    private $replace = [' ', ':', '/', ','];
    private $jiraPattern = 'feature/%s-%s';
    private $separator = '-';

    public function fromIssue(Issue $issue)
    {
        return $this->cleanup(
            sprintf(
                $this->jiraPattern,
                $issue->ticketNumber(),
                strtolower($this->replace($this->remove($issue->summary())))
            )
        );
    }

    private function remove($summary)
    {
        return str_replace($this->remove, '', $summary);
    }

    private function replace($summary)
    {
        return str_replace($this->replace, $this->separator, $summary);
    }

    private function cleanup($branchName)
    {
        $branchName = preg_replace('~[^A-Za-z0-9/-]~', '', $branchName);
        return preg_replace(
            '~[' . preg_quote($this->separator) . ']+~',
            $this->separator,
            trim($branchName, $this->separator)
        );
    }
}

class GitHelper
{
    public function branches($pattern = '')
    {
        $command = 'branch' . ($pattern ? sprintf('| grep "%s"', $pattern) : '');
        return $this->shell($command);
    }

    public function currentBranch()
    {
        $list = $this->branches('* ');
        return ltrim(end($list), '* ');
    }

    public function topLevelDirectory()
    {
        $tld = $this->shell('rev-parse --show-toplevel');
        return trim(end($tld));
    }

    private function shell($command)
    {
        $result = explode(PHP_EOL, shell_exec("git $command"));
        return array_filter(array_map('trim', $result));
    }
}

function tabulate($string, $pad = 4)
{
    return implode(
        PHP_EOL . str_repeat(' ', $pad),
        explode(PHP_EOL, $string)
    );
}

function render($template, $variables)
{
    $keys = array_map(
        function($key) {
            return sprintf('{{ %s }}', $key);
        },
        array_keys($variables)
    );

    return strtr($template, array_combine($keys, array_values($variables)));
}

function renderDefect(Issue $issue)
{
$template = <<<EOL
description:
    {{ description }}
environment: {{ environment }}
reporter: {{ reporter }}

EOL;

    return render(
        $template,
        [
            'description' => tabulate(wordwrap($issue->description())),
            'environment' => $issue->environment(),
            'reporter' => $issue->reporter(),
        ]
    );
}

function renderIssue(GitHelper $git, Issue $issue)
{
$template = <<<EOL
{{ issueNumber }}: {{ issueType }} (estimate: {{ estimate }}, spent: {{ spent }})
    {{ summary }}
{{ additionalInfo }}
link:
    {{ url }}
branches:
    {{ branches }}

EOL;
    $branchList = $git->branches($issue->ticketNumber());
    if (empty($branchList)) {
        $generator = new GitBranchnameGenerator;
        $branches = $generator->fromIssue($issue) . ' (generated)';
    } else {
        $branches = implode(PHP_EOL . '    ', $branchList);
    }

    $additionalInfo = '';
    if ($issue->issueType() == 'Defect') {
        $additionalInfo = renderDefect($issue);
    }
    $dateHelper = new DateHelper;

    $content = render(
        $template,
        [
            'issueNumber' => $issue->ticketNumber(),
            'issueType' => $issue->issueType(),
            'summary' => tabulate(wordwrap($issue->summary())),
            'estimate' => $dateHelper->secondsToHuman($issue->estimate()),
            'spent' => $dateHelper->secondsToHuman($issue->timeSpent()),
            'additionalInfo' => $additionalInfo,
            'branches' => $branches,
            'url' => $issue->url(),
        ]
    );

    return $content . PHP_EOL;
}

class Arguments
{
    /**
     * @var array
     */
    private $args;

    public function __construct()
    {
        $this->args = isset($_SERVER['argv']) ? $_SERVER['argv'] : [];
    }

    public function scriptName()
    {
        return $this->argument(0);
    }

    public function firstArgument()
    {
        return $this->argument(1);
    }

    private function argument($index)
    {
        return isset($this->args[$index]) ? $this->args[$index] : null;
    }
}

/**
 * @return SearchResultList
 */
function controller(Configuration $config, Client $client, Arguments $arguments)
{
    switch ($arguments->firstArgument()) {
        case 'todo':
            return $client->todoIssues($config->project());
        case 'inprogess':
        case 'in-progess':
        default:
            return $client->inprogressIssues($config->project());
    }
}

try {

    $git = new GitHelper;
    $arguments = new Arguments;

    // init configuration
    $config = GlobalConfiguration::initFromDirectory(getenv('HOME'));
    $projectConfig = Configuration::initFromDirectory($git->topLevelDirectory());
    $config->merge($projectConfig);

    // init client
    $client = new Client($config);

    // retrieve issues
    $issues = controller($config, $client, $arguments);

    // render
    foreach ($issues as $issue) {
        print renderIssue($git, $issue) . PHP_EOL;
    }

} catch (Exception $exception) {
    print $exception . PHP_EOL;
}
