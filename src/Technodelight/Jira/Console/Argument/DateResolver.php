<?php

namespace Technodelight\Jira\Console\Argument;

use \DateTime;
use Symfony\Component\Console\Input\InputInterface;
use Technodelight\Jira\Configuration\ApplicationConfiguration;

class DateResolver implements Resolver
{
    const NAME = 'date';
    const MONDAY = 1;

    private $configuration;
    private $referenceDate;

    public function __construct(ApplicationConfiguration $configuration, DateTime $referenceDate = null)
    {
        $this->configuration = $configuration;
        $this->referenceDate = $referenceDate ?: new DateTime;
    }

    public function argument(InputInterface $input, $argumentName = self::NAME)
    {
        if (!$input->hasArgument($argumentName)) {
            return null;
        }
        return $this->resolve($input->getArgument($argumentName));
    }

    public function option(InputInterface $input, $optionName = self::NAME)
    {
        if (!$input->hasOption($optionName)) {
            return null;
        }
        return $this->resolve($input->getOption($optionName));
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
