<?php

use Behat\Behat\Context\Context;
use Fixture\ApplicationConfiguration;
use Fixture\Tempo\TestHttpClient;

class TempoContext implements Context
{
    const FIXTURE_PATH = '/fixtures/tempo/';

    /**
     * @Given Tempo responds to :method :url with :file
     */
    public function tempoRespondsWith($method, $url, $filename)
    {
        TestHttpClient::$fixtures[strtolower($method)][$url] = json_decode(
            file_get_contents(__DIR__ . self::FIXTURE_PATH . $filename . '.json'),
            true
        );
    }

    /**
     * @Given Tempo should have been called with :method :url
     */
    public function tempoShouldHaveBeenCalledWith($method, $url)
    {
        $found = false;
        foreach (TestHttpClient::$requests[strtolower($method)] as $reqData) {
            if ($reqData['url'] == $url) {
                $found = $reqData;
            }
        }

        if (!$found) {
            throw new UnexpectedValueException(sprintf(
                'Tempo URL %s "%s" should have been called, but were not',
                strtoupper($method),
                $url
            ));
        }
    }

    /**
     * @Given Tempo is enabled
     */
    public function tempoIsEnabled()
    {
        ApplicationConfiguration::$useTempo = true;
    }
}
