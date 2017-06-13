# Slack Flat Finder Bot

* Uses the Trade Me API to grab new rental properties that have been recently listed
* Checks if fibre and VDSL are available by querying Chorus
* Includes travel times to various locations

We run this as a cron job every six hours, and include the travel times to our offices. I wrote it late one night so I don't really care if it's too messy.

## Requirements
* PHP 7.0 
* Trade Me API Key [(register an application)](https://www.trademe.co.nz/MyTradeMe/Api/RegisterNewApplication.aspx)
* Google Distance Matrix API Key [(get a key)](https://developers.google.com/maps/documentation/distance-matrix/start#get-a-key)

## Installation
* Update composer
* Update config.php
* Create a cron job for whatever interval in hours you want to get new notifications, and set that interval for `$settings['new_properties_since']`


## Configuration
```
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
        'search' => <see example config>
    ],
    'google' => [
        'key' => '<google api key for distance matrix>',
        'transport' => '<walking|driving|bicycling|transit>',
        'addresses' => [
            '<name>' => '<address>',
        ]
    ]
];

```

Example config for Trade Me rental listings in Wellington City, with 3-4 bedrooms and a maximum rent per week of $630
```
[
    'photo_size'       => 'Large',
    'bedrooms_min'     => '3',
    'bedrooms_max'     => '4',
    'district'         => '47',
    'price_max'        => '630',
    'property_type'    => 'House,Townhouse,Apartment',
    'return_metadata'  => 'false',
    'sort_order'       => 'Default'
]
```
Reference: [http://developer.trademe.co.nz/api-reference/search-methods/rental-search/](http://developer.trademe.co.nz/api-reference/search-methods/rental-search/)
