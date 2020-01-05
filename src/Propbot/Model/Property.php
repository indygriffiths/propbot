<?php

namespace Propbot\Model;

use Propbot\Util;

/**
 * Class for managing Trade Me properties.
 */
class Property
{
    /**
     * @var \stdClass Object from Trade Me
     */
    public $result;

    public function __construct($property = null)
    {
        $this->result = $property;
    }

    public function getTitle()
    {
        return $this->result->Title ?? 'Property';
    }

    public function getId()
    {
        return $this->result->ListingId ?? 0;
    }

    public function getUrl()
    {
        return sprintf('https://trademe.co.nz/%s', $this->result->ListingId);
    }

    public function getPicture()
    {
        return $this->result->PictureHref ?? '';
    }

    public function getSuburb()
    {
        return $this->result->Suburb ?? '';
    }

    public function getLatitude()
    {
        return $this->result->GeographicLocation->Latitude ?? 0;
    }

    public function getLongitude()
    {
        return $this->result->GeographicLocation->Longitude ?? 0;
    }

    public function getAddress()
    {
        return $this->result->Address ?? '';
    }

    public function getPrice()
    {
        return $this->result->PriceDisplay ?? '';
    }

    public function getAvailableFrom()
    {
        return $this->result->AvailableFrom ?? '';
    }

    public function getWhiteware()
    {
        return $this->result->Whiteware ?? '';
    }

    public function getBedrooms()
    {
        return $this->result->Bedrooms ?? '';
    }

    public function getRateableValue()
    {
        if (!isset($this->result->RateableValue)) {
            return '';
        }

        return sprintf('$%s', number_format($this->result->RateableValue));
    }

    public function getBathrooms()
    {
        return $this->result->Bathrooms ?? '';
    }

    public function getPropertyType()
    {
        return $this->result->PropertyType ?? '';
    }

    public function getOpenHomes()
    {
        if (empty($this->result->OpenHomes ?? [])) {
            return false;
        }

        $return = '';
        foreach ($this->result->OpenHomes as $times) {
            $start = Util::netDate($times->Start);
            $end = Util::netDate($times->End);

            $calLink = Util::calendarLink('Open Home', $this->getAddress(), $this->getUrl(), $start, $end);

            $return .= sprintf(
                "%s until %s <%s|(add to calendar)>\n",
                date('l jS M g:ia', $start),
                date('g:ia', $end),
                $calLink
            );
        }

        return $return;
    }

    /**
     * @return array The Slack fields used for the property
     */
    public function getSlackFields()
    {
        $results = [
            SlackField::create('Location', sprintf(
                '%s %s <https://maps.google.com/maps?z=12&t=m&q=loc:%s+%s|(open in Google Maps)>',
                $this->getAddress(),
                $this->getSuburb(),
                $this->getLatitude(),
                $this->getLongitude()
            ), false),
            SlackField::create('Price', $this->getPrice()),
            SlackField::create('RV', $this->getRateableValue()),
            SlackField::create('Available From', $this->getAvailableFrom()),
            SlackField::create('Property Type', $this->getPropertyType()),
            SlackField::create('Furnishings', $this->getWhiteware()),
            SlackField::create('Bedrooms', $this->getBedrooms()),
            SlackField::create('Bathrooms', $this->getBathrooms()),
            SlackField::create('Open Homes', $this->getOpenHomes(), false),
        ];

        return array_filter($results);
    }
}
