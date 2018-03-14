<?php

namespace Technodelight\Jira\Console\Argument;

use \DateTime;
use Symfony\Component\Console\Input\InputInterface;
use Technodelight\Jira\Configuration\ApplicationConfiguration\ProjectConfiguration;

class DateResolver implements Resolver
{
    const NAME = 'date';
    const MONDAY = 1;

    private $configuration;
    private $referenceDate;

    public function __construct(ProjectConfiguration $configuration, DateTime $referenceDate = null)
    {
        $this->configuration = $configuration;
        $this->referenceDate = $referenceDate ?: new DateTime;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param string $argumentName
     * @return null|\Technodelight\Jira\Console\Argument\Date
     */
    public function argument(InputInterface $input, $argumentName = self::NAME)
    {
        if (!$input->hasArgument($argumentName)) {
            return null;
        }
        return $this->resolve($input->getArgument($argumentName));
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param string $optionName
     * @return null|\Technodelight\Jira\Console\Argument\Date
     */
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
