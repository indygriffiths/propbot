<?php

namespace Propbot\Command\Find;

use HaydenPierce\ClassFinder\ClassFinder;
use Maknz\Slack\Client;
use Propbot\Client\TradeMeClient;
use Propbot\DataProvider\DataProvider;
use Propbot\Model\Property;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BaseFindCommand extends Command
{
    /**
     * @var DataProvider[]
     */
    protected $dataProviders = [];

    /**
     * @var int
     */
    protected $lastRun = 0;

    /**
     * @var string
     */
    private $lastRunFile;

    /**
     * @throws \Exception
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Work out when the command was last run
        $this->lastRunFile = sprintf('%s/%s.lastrun', $input->getOption('config-dir'), (new \ReflectionClass($this))->getShortName());
        $this->lastRun = strtotime('now -1 hour');
        if (file_exists($this->lastRunFile)) {
            $this->lastRun = file_get_contents($this->lastRunFile);
        }

        // Get all data providers used for populating the Slack message
        $this->dataProviders = ClassFinder::getClassesInNamespace('Propbot\\DataProvider');
    }

    /**
     * Updates the last run timestamp so that the next command invocation will only fetch properties listed since this date.
     */
    protected function updateLastRun()
    {
        file_put_contents($this->lastRunFile, time());
    }

    /**
     * Searches Trade Me and returns the list of properties.
     *
     * @return array
     */
    protected function getTradeMeSearchResults(string $endpoint, array $options = [])
    {
        $client = (new TradeMeClient())->getClient();
        $result = $client->get(sprintf('Search/Property/%s.json', $endpoint), [
            'query' => $options,
        ]);

        $json = json_decode($result->getBody());

        if (property_exists($json, 'List')) {
            return $json->List;
        }

        return [];
    }

    /**
     * Builds the fields for the current property.
     *
     * @param object $property
     *
     * @return Property
     */
    protected function buildProperty($property, array &$fields, OutputInterface $output)
    {
        $theProperty = new Property($property);
        $fields = $theProperty->getSlackFields();

        $output->writeln(sprintf(' * Processing "%s" %s', $theProperty->getTitle(), $theProperty->getUrl()));

        foreach ($this->dataProviders as $dataProvider) {
            /**
             * @var DataProvider
             */
            $dataProvider = new $dataProvider();
            if (!$dataProvider->enabled()) {
                $output->writeln(sprintf('   <comment>* Skipping %s as it\'s not enabled</comment>', $dataProvider->getName()), OutputInterface::VERBOSITY_VERBOSE);

                continue;
            }

            $output->writeln(sprintf('   * Adding new fields using %s', $dataProvider->getName()), OutputInterface::VERBOSITY_VERBOSE);
            $fields = array_merge($fields, $dataProvider->getSlackComponents($theProperty));
        }

        return $theProperty;
    }

    /**
     * @return bool|Client
     */
    protected function getSlackClient()
    {
        if (empty($_ENV['SLACK_WEBHOOK_URL'])) {
            return false;
        }

        $options = [
            'link_names' => true,
        ];

        if (!empty($_ENV['SLACK_WEBHOOK_USERNAME'])) {
            $options['username'] = $_ENV['SLACK_WEBHOOK_USERNAME'];
        }

        if (!empty($_ENV['SLACK_WEBHOOK_CHANNEL'])) {
            $options['channel'] = $_ENV['SLACK_WEBHOOK_CHANNEL'];
        }

        return new Client($_ENV['SLACK_WEBHOOK_URL'], $options);
    }
}
