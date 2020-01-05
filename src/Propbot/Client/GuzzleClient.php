<?php

namespace Propbot\Client;

use GuzzleHttp\Client;

/**
 * Wrapper class around the standard Guzzle class.
 */
class GuzzleClient
{
    protected $client;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->client = new Client(array_merge([
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'propbot/1.0',
            ],
        ], $options));

        return $this;
    }

    /**
     * @return Client|GuzzleClient
     */
    public function getClient()
    {
        return $this->client;
    }
}
