<?php

namespace Propbot\DataProvider;

use Propbot\Client\GuzzleClient;
use Propbot\Model\Property;
use Propbot\Model\SlackField;

/**
 * Gets the different kinds of internet service at the address.
 *
 * This uses an undocumented JSONP API that requires some data finagling to get the response into our desired format.
 */
class ChorusDataProvider implements DataProvider
{
    public function getName(): string
    {
        return 'Chorus Broadband Lookup';
    }

    public function enabled(): bool
    {
        return true;
    }

    public function getSlackComponents(Property $property): array
    {
        $client = (new GuzzleClient())->getClient();
        $res = $client->get('https://chorus-viewer.wivolo.com/viewer-chorus/jsonp/location-details', [
            'query' => [
                'lat' => $property->getLatitude(),
                'lng' => $property->getLongitude(),
                'debug' => '0',
                'zoom' => '1',
                'maplayers' => '3',
                'search_type' => 'X',
                'callback' => 'A',
            ],
        ]);

        $res = (string) $res->getBody();

        // Strip the JSONP callback
        $res = substr($res, strpos($res, '('));
        $json = json_decode(trim($res, '();'));

        if (isset($json->services) && $json->success) {
            $services = $json->services;
            if (!$services->fibre->available) {
                $f = 'No';
                if (!empty($services->fibre->timing)) {
                    $f .= ' - '.$services->fibre->timing;
                }
            } else {
                $f = 'Yes';
                if (!empty($services->fibre->speed)) {
                    $f .= ' - '.$services->fibre->speed;
                }
            }

            if (!$services->vdsl->available) {
                $v = 'No';
                if (!empty($services->vdsl->timing)) {
                    $v .= ' - '.$services->vdsl->timing;
                }
            } else {
                $v = 'Yes';
                if (!empty($services->vdsl->speed)) {
                    $v .= ' - '.$services->vdsl->speed;
                }
            }

            return [
                SlackField::create('Has Fibre?', $f),
                SlackField::create('Has VDSL?', $v),
            ];
        }

        return [];
    }
}
