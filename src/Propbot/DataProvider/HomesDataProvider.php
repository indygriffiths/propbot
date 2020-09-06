<?php

namespace Propbot\DataProvider;

use Propbot\Client\GuzzleClient;
use Propbot\Model\Property;
use Propbot\Model\SlackField;

class HomesDataProvider implements DataProvider
{
    private $client;

    public function __construct()
    {
        $this->client = (new GuzzleClient())->getClient();
    }

    public function getName(): string
    {
        return 'Homes.co.nz Lookup';
    }

    public function enabled(): bool
    {
        return true;
    }

    public function getSlackComponents(Property $property): array
    {
        $id = $this->getPropertyId(sprintf('%s, %s, %s', $property->getAddress(), $property->getSuburb(), $property->getDistrict()));
        if (empty($id)) {
            return [];
        }

        $property = $this->getPropertyFromId($id);
        if (!$property) {
            return [];
        }

        $url = sprintf('%s%s', 'https://homes.co.nz/address', $property->url);
        $property = $property->property_details;

        return [
            SlackField::create('Homes.co.nz RV', sprintf('%s', $property->display_capital_value_short)),
            SlackField::create(
                sprintf('Homes.co.nz estimation (%s)', date('M Y', strtotime($property->estimated_value_revision_date))),
                sprintf('<%s|%s (%s-%s)>', $url, $property->display_estimated_value_short, $property->display_estimated_lower_value_short, $property->display_estimated_upper_value_short)
            ),
        ];
    }

    /**
     * Gets the property ID from an address string.
     *
     * @param string $address address string of the property to get
     */
    private function getPropertyId(string $address): string
    {
        $res = $this->client->get('https://gateway.homes.co.nz/property/resolve', [
            'query' => [
                'address' => $address,
            ],
        ]);

        $res = json_decode($res->getBody());

        return $res->property_id ?? '';
    }

    /**
     * Gets  property from a Homes.co.nz ID string.
     *
     * @param string $id Homes.co.nz address ID.
     *
     * @return false|object
     */
    private function getPropertyFromId(string $id)
    {
        $res = $this->client->get('https://gateway.homes.co.nz/properties', [
            'query' => [
                'property_ids' => $id,
            ],
        ]);

        $res = json_decode($res->getBody());

        return $res->cards[0] ?? false;
    }
}
