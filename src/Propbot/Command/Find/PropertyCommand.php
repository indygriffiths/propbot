<?php

namespace Propbot\Command\Find;

use Maknz\Slack\Attachment;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PropertyCommand extends BaseFindCommand
{
    protected static $defaultName = 'find:properties';

    /**
     * @var array Parameters used for filtering Trade Me results
     */
    protected static $searchParameters = [
        'adjacent-suburbs' => 'Indicates whether the search should include listings in adjacent suburbs. Only used if a suburb is specified.',
        'bathrooms-max' => 'Maximum number of bathrooms.',
        'bathrooms-min' => 'Minimum number of bathrooms.',
        'bedrooms-max' => 'Maximum number of bedrooms.',
        'bedrooms-min' => 'Minimum number of bedrooms.',
        'district' => 'The ID of a district to search within.',
        'latitude-max' => 'Maximum latitude. This parameter cannot be used on it’s own – latitude_min, latitude_max, longitude_min and longitude_max must all be supplied in order to geographically constrain the results.',
        'latitude-min' => 'Minimum latitude. This parameter cannot be used on it’s own – latitude_min, latitude_max, longitude_min and longitude_max must all be supplied in order to geographically constrain the results.',
        'longitude-max' => 'Maximum longitude. This parameter cannot be used on it’s own – latitude_min, latitude_max, longitude_min and longitude_max must all be supplied in order to geographically constrain the results.',
        'longitude-min' => 'Minimum longitude. This parameter cannot be used on it’s own – latitude_min, latitude_max, longitude_min and longitude_max must all be supplied in order to geographically constrain the results.',
        'member-listing' => 'Filters the listings to only those with the given seller.',
        'open-homes' => 'If true, returns listings with upcoming open homes. Defaults to false.',
        'price-max' => 'Maximum price, in NZD.',
        'price-min' => 'Minimum price, in NZD.',
        'property-type' => 'A comma-separated list of property types. Valid values are “Apartment”, “House”, “Section”, “Townhouse”, “Unit”, “Dwelling” (Lifestyle) or “BareLand” (Lifestyle).',
        'region' => 'Specifies the ID of the region to search within. Note that there are multiple region lists, this parameter accepts a property region ID.',
        'search-string' => 'One or more keywords to use in a search query.',
        'suburb' => 'The ID of a suburb to search within. Searching within multiple suburbs is supported; separate each suburb ID with a comma.',
    ];

    protected function configure()
    {
        parent::configure();

        $this->setDescription('Search for available residential properties for sale and post them to Slack');

        foreach (self::$searchParameters as $param => $description) {
            $this->addOption($param, null, InputOption::VALUE_OPTIONAL, $description);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $output->writeln(sprintf('Searching for Trade Me residential properties listed since %s', date('Y-m-d H:i:s', trim($this->lastRun))));

        $client = $this->getSlackClient();
        if (!$client) {
            throw new \Exception('The parameters required for the Slack client are not configured');
        }

        $searchParameters = [
            'date_from' => date("Y-m-d\TH:i:s", $this->lastRun),
            'photo_size' => 'Large',
            'return_metadata' => 'false',
            'sort_order' => 'Default',
        ];

        foreach ($input->getOptions() as $param => $value) {
            if (!$value || !in_array($param, array_keys(self::$searchParameters))) {
                continue;
            }

            $searchParameters[str_replace('-', '_', $param)] = $value;
        }

        $results = $this->getTradeMeSearchResults('Residential', $searchParameters);
        $output->writeln(sprintf('Found %s properties', count($results)));

        foreach ($results as $property) {
            $fields = [];
            $theProperty = $this->buildProperty($property, $fields, $output);
            $output->writeln(sprintf('   Fields: <comment>%s</comment>', json_encode($fields)), OutputInterface::VERBOSITY_DEBUG);

            $message = $client->createMessage();
            $message->attach(
                new Attachment([
                    'fallback' => $theProperty->getTitle(),
                    'title' => $theProperty->getTitle(),
                    'title_link' => $theProperty->getUrl(),
                    'image_url' => $theProperty->getPicture(),
                    'fields' => $fields,
                ])
            )
                    ->setText(sprintf(
                        'New residential listing: %s, %s',
                        $theProperty->getTitle(),
                        $theProperty->getPrice()
                    ))
                    ->send();

            $output->writeln('   <info>Sent Slack message successfully</info>');
            $output->writeln('');
        }

        $this->updateLastRun();

        return 0;
    }
}
