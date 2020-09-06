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
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:47.0) Gecko/20100101 Firefox/47.0',
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
