<?php

namespace Propbot\Client;

/**
 * Class for managing HTTP requests to the Trade Me API.
 */
class TradeMeClient extends GuzzleClient
{
    public function __construct($options = [])
    {
        parent::__construct(array_merge([
            'base_uri' => 'https://api.trademe.co.nz/v1/',
            'headers' => [
                'Authorization' => sprintf(
                    'OAuth oauth_consumer_key="%s", oauth_signature_method="PLAINTEXT", oauth_signature="%s&"',
                    $_ENV['TRADEME_CONSUMER_KEY'],
                    $_ENV['TRADEME_CONSUMER_SECRET']
                ),
            ],
        ], $options));
    }
}
