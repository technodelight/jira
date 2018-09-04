<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\GitConfiguration;

use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;

class BranchNameGeneratorConfiguration implements RegistrableConfiguration
{
    /**
     * @var array
     */
    private $defaultPatterns = [
        'preg_match("~^Release ~", issue.summary())' => ['pattern' => 'release/{clean(substr(issue.summary(), 8))}'],
        'preg_match("~.*~", issue.summary())' => ['pattern' => 'feature/{issueKey}-{summary}'],
    ];
    /**
     * @var array "regex" => ["pattern" => "pattern/{templeStuff}"]
     */
    private $patterns;
    /**
     * @var string
     */
    private $separator;
    /**
     * @var string
     */
    private $whitelist;
    /**
     * @var string[]
     */
    private $remove;
    /**
     * @var string[]
     */
    private $replace;
    /**
     * @var array
     */
    private $config;

    public static function fromArray(array $config)
    {
        $instance = new self;
        $instance->config = $config;
        $instance->patterns = !empty($config['patterns']) ? $config['patterns'] : $instance->defaultPatterns;
        $instance->separator = $config['separator'];
        $instance->whitelist = $config['whitelist'];
        $instance->remove = $config['remove'];
        $instance->replace = $config['replace'];

        return $instance;
    }

    public function patterns()
    {
        $patterns = [];
        foreach ($this->patterns as $regex => $def) {
            $patterns[$regex] = $def['pattern'];
        }
        return $patterns;
    }

    public function separator()
    {
        return $this->separator;
    }

    public function whitelist()
    {
        return $this->whitelist;
    }

    public function remove()
    {
        return $this->remove;
    }

    public function replace()
    {
        return $this->replace;
    }

    public function servicePrefix()
    {
        return 'branch_name_generator';
    }

    /**
     * @return array
     */
    public function configAsArray()
    {
        return $this->config;
    }

    private function __construct()
    {
    }
}
