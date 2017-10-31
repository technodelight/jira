<?php


namespace Technodelight\Jira\Configuration\Symfony;


use Technodelight\Jira\Configuration\ApplicationConfiguration;

class ConfigurationToParameterConverter
{
    const PREFIX = 'config';

    const CONCAT_PREFIX = '%s.%s';

    public function fromConfiguration(ApplicationConfiguration $configuration)
    {
        $parameters = [];
        foreach ($configuration->asArray() as $parameter => $value) {
            $parameters = array_merge(
                $parameters,
                $this->convertToParameter($value, sprintf(self::CONCAT_PREFIX, self::PREFIX, $parameter))
            );
        }
        return $parameters;
    }

    /**
     * @param mixed $value
     * @param string $prefix
     */
    private function convertToParameter($value, $prefix)
    {
        $parameters = [];
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $parameters = array_merge($parameters, $this->convertToParameter($v, sprintf(self::CONCAT_PREFIX, $prefix, $k)));
            }
        } else {
            $parameters[] = [$prefix => $value];
        }
        return $parameters;
    }
}
