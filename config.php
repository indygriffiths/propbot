<?php
$settings = [
    'new_properties_since' => '<1-24>',
    'slack' => [
        'webhook_url' => '<slack webhook url>',
        'username' => '<webhook username>',
        'channel' => '<channel>',
        'link_names' => true
    ],
    'trademe' => [
        'consumer_key' => '<trade me consumer key',
        'consumer_secret' => '<trade me consumer secret>>',
        'search' => [
            'photo_size'       => 'Large',
            'bedrooms_min'     => '3',
            'bedrooms_max'     => '4',
            'district'         => '47',
            'price_max'        => '630',
            'property_type'    => 'House,Townhouse,Apartment',
            'return_metadata'  => 'false',
            'sort_order'       => 'Default'
        ]
    ],
    'google' => [
        'key' => '<google api key for distance matrix>',
        'transport' => '<walking|driving|bicycling|transit>',
        'addresses' => [
            '<name>' => '<address>',
        ],
        'elevation' => [
            'enabled'     => true,
            'threshold'   => <elevation in meters>
        ]
    ]
];