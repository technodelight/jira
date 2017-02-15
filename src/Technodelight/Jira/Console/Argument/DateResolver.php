<?php

namespace Technodelight\Jira\Console\Argument;

use \DateTime;
use Symfony\Component\Console\Input\InputInterface;
use Technodelight\Jira\Configuration\Configuration;
use Technodelight\Jira\Console\Argument\Date;

class DateResolver implements Resolver
{
    const NAME = 'date';
    const MONDAY = 1;

    private $configuration;
    private $referenceDate;

    public function __construct(Configuration $configuration, DateTime $referenceDate = null)
    {
        $this->configuration = $configuration;
        $this->referenceDate = $referenceDate ?: new DateTime;
    }

    public function argument(InputInterface $input)
    {
        if (!$input->hasArgument(self::NAME)) {
            return null;
        }
        return $this->resolve($input->getArgument(self::NAME));
    }

    public function option(InputInterface $input)
    {
        if (!$input->hasOption(self::NAME)) {
            return null;
        }
        return $this->resolve($input->getOption(self::NAME));
    }

    private function resolve($inputValue)
    {
        if (empty($inputValue)) {
            return Date::fromString($this->configuration->defaultWorklogTimestamp());
        }
        if ($this->configuration->yesterdayAsWeekday() && $this->referenceDate->format('N') == self::MONDAY) {
            $inputValue = str_replace('yesterday', 'last weekday', $inputValue);
        }

        return Date::fromString($inputValue);
    }
}
