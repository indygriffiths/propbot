<?php

namespace Propbot\DataProvider;

use Propbot\Model\Property;

/**
 * Interface for any third-party source of data to include in the property listing.
 */
interface DataProvider
{
    public function getName(): string;

    /**
     * Used for determining whether the current data provider is set up and any config options are set.
     *
     * @return bool True if the data provider can be used
     */
    public function enabled(): bool;

    public function getSlackComponents(Property $property): array;
}
