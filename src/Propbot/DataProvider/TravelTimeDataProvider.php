<?php

namespace Propbot\DataProvider;

use Propbot\Client\GuzzleClient;
use Propbot\Model\Property;
use Propbot\Model\SlackField;

/**
 * A wrapper for the Google Maps API to work out the travel time from the property to a location.
 */
class TravelTimeDataProvider implements DataProvider
{
    public function getName(): string
    {
        return 'Google Maps Distance API';
    }

    /**
     * Used for determining whether the current data provider is set up and any config options are set.
     *
     * @return bool True if the data provider can be used
     */
    public function enabled(): bool
    {
        return !empty($_ENV['GOOGLE_KEY']) &&
               !empty($_ENV['GOOGLE_TRANSPORT_METHOD']) &&
               !empty($_ENV['GOOGLE_ADDRESSES']);
    }

    public function getSlackComponents(Property $property): array
    {
        $client = (new GuzzleClient([
            'base_uri' => 'https://maps.googleapis.com/maps/api/',
        ]))->getClient();

        $destinationAddresses = json_decode($_ENV['GOOGLE_ADDRESSES']);
        $slackComponents = [];

        foreach ($destinationAddresses as $addressObj) {
            $name = $addressObj->name;
            $address = $addressObj->address;

            $res = $client->get('distancematrix/json', [
                'query' => [
                    'units' => $_ENV['GOOGLE_DISTANCE_UNIT'] ?? 'metric',
                    'mode' => $_ENV['GOOGLE_TRANSPORT_METHOD'],
                    'origins' => sprintf('%s,%s', $property->getLatitude(), $property->getLongitude()),
                    'format' => 'json',
                    'key' => $_ENV['GOOGLE_KEY'],
                    'destinations' => $address,
                ],
            ]);

            $json = json_decode($res->getBody());

            if (isset($json->rows[0]->elements)) {
                $numElements = count($json->rows[0]->elements);
                for ($i = 0; $i < $numElements; ++$i) {
                    $place = $json->rows[0]->elements[$i];
                    if ($place->status === 'OK') {
                        $slackComponents[] = SlackField::create(
                            sprintf('Distance to %s', $name),
                            sprintf('%s - %s', $place->distance->text, $place->duration->text)
                        );
                    }
                }
            }
        }

        return $slackComponents;
    }
}
