<?php

namespace Technodelight\Jira\Api\Tempo2;

class NullClient implements Client
{
    /**
     * Gets a resource
     *
     * @param string $url
     * @param array $params
     * @return array
     */
    public function get($url, array $params = [])
    {
    }

    /**
     * Creates a resource
     *
     * @param string $url
     * @param array $data
     * @return array
     */
    public function post($url, array $data)
    {
    }

    /**
     * Updates a resource
     *
     * @param string $url
     * @param array $data
     * @return array
     */
    public function put($url, array $data)
    {
    }

    /**
     * Removes a resource
     *
     * @param string $url
     * @param array $params
     * @return array
     */
    public function delete($url, array $params = [])
    {
    }
}
