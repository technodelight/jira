#!/usr/bin/php
<?php

require __DIR__ . '/vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

use GuzzleHttp\Client as GuzzleClient;

class Credentials
{
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

    private function __construct(array $ini)
    {
        $this->username = $ini['username'];
        $this->password = $ini['password'];
        $this->domain = $ini['domain'];
        $this->project = $ini['project'];
    }

    public static function instance()
    {
        static $instance;
        if (!$instance) {
            $instance = new self(self::iniFile());
        }

        return $instance;
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

    private static function iniFile()
    {
        $iniPath = __DIR__ . '/jira.ini';
        if (!is_file($iniPath)) {
            throw new UnexpectedValueException('No configuration found!');
        }

        if (!is_readable($iniPath)) {
            throw new UnexpectedValueException('Cannot read configuration!');
        }

        $perms = substr(sprintf('%o', fileperms($iniPath)), -4);
        if ($perms !== '0600') {
            throw new UnexpectedValueException(
                sprintf('Configuration cannot be readable by others! %s should be 0600)', $perms)
            );
        }

        return parse_ini_file($iniPath);
    }
}

class Client
{
    private $_client;

    public function __construct()
    {
        $this->_client = new GuzzleClient(
            [
                'base_url' => $this->apiUrl(),
                'defaults' => [
                    'auth' => [Credentials::instance()->username(), Credentials::instance()->password()]
                ]
            ]
        );
    }

    public function user()
    {
        return $this->_client->get('myself')->json();
    }

    public function project($code)
    {
        return $this->_client->get('project/' . $code)->json();
    }

    public function search($jql)
    {
        $response = $this->_client->get('search', ['query' => ['jql' => $jql]]);
        return SearchResultList::fromArray($response->json());
    }

    public function inprogressIssues($projectCode)
    {
        $query = sprintf('project = "%s" and assignee = currentUser() and status = "In Progress"', $projectCode);
        return $this->search($query);
    }

    private function apiUrl()
    {
        return sprintf(
            'https://%s/rest/api/2/',
            Credentials::instance()->domain()
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
    private $replace = [' ', ':'];
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

    private function remove($string)
    {
        return str_replace($this->remove, '', $string);
    }

    private function replace($string)
    {
        return str_replace($this->replace, $this->separator, $string);
    }

    private function cleanup($string)
    {
        $string = preg_replace('~[^A-Za-z0-9/-]~', '', $string);
        return preg_replace('~[' . preg_quote($this->separator) . ']+~', $this->separator, $string);
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

function renderDefect(Issue $issue)
{
$template = <<<EOL
description:
    %s
environment: %s
reporter: %s

EOL;
    return sprintf(
        $template,
        tabulate($issue->description()),
        $issue->environment(),
        $issue->reporter()
    );
}

function renderIssue(Issue $issue)
{
$template = <<<EOL
%s: %s (estimate: %s, spent: %s)
    %s
%s
branches:
    %s

EOL;
    $git = new GitHelper;
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

    $content = sprintf(
        $template,
        $issue->ticketNumber(),
        $issue->issueType(),
        $dateHelper->secondsToHuman($issue->estimate()),
        $dateHelper->secondsToHuman($issue->timeSpent()),
        $issue->summary(),
        $additionalInfo,
        $branches
    );
    return $content . PHP_EOL;
}

try {
    $client = new Client;
    $issues = $client->inprogressIssues(Credentials::instance()->project());
    foreach ($issues as $issue) {
        print renderIssue($issue) . PHP_EOL;
    }
} catch (Exception $exception) {
    print $exception . PHP_EOL;
}
