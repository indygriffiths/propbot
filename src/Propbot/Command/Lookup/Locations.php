<?php

namespace Propbot\Command\Lookup;

use Propbot\Client\TradeMeClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Locations extends Command
{
    protected static $defaultName = 'lookup:locations';

    protected function configure()
    {
        $this->setDescription('Search for a region, district, and suburb to get the relevant IDs needed for using the <info>find:*</info> commands');
        $this->addOption('name', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Place names to look up', []);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (empty($input->getOption('name'))) {
            throw new \InvalidArgumentException('You must specify at least one --name option');
        }

        $output->writeln('Fetching Trade Me localities');

        $client = (new TradeMeClient())->getClient();
        $result = $client->get('Localities.json');
        $result = json_decode($result->getBody());

        $localityIds = [];
        $districtIds = [];
        $suburbIds = [];

        foreach ($result as $locality) {
            if (in_array($locality->Name, $input->getOption('name'))) {
                $localityIds[sprintf('<options=bold,underscore>%s</>', $locality->Name)] = $locality->LocalityId;
            }

            foreach ($locality->Districts as $district) {
                if (in_array($district->Name, $input->getOption('name'))) {
                    $districtIds[sprintf('%s / <options=bold,underscore>%s</>', $locality->Name, $district->Name)] = $district->DistrictId;
                }

                foreach ($district->Suburbs as $suburb) {
                    if (in_array($suburb->Name, $input->getOption('name'))) {
                        $suburbIds[sprintf('%s / %s / <options=bold,underscore>%s</>', $locality->Name, $district->Name, $suburb->Name)] = $suburb->SuburbId;
                    }
                }
            }
        }

        $table = new Table($output);
        $table->setHeaders(['Location', 'Locality ID', 'District ID', 'Suburb ID']);
        foreach ($localityIds as $path => $id) {
            $table->addRow([
                $path,
                $id,
            ]);
        }
        foreach ($districtIds as $path => $id) {
            $table->addRow([
                $path,
                null,
                $id,
            ]);
        }
        foreach ($suburbIds as $path => $id) {
            $table->addRow([
                $path,
                null,
                null,
                $id,
            ]);
        }

        $table->render();

        if (count($districtIds) > 1) {
            $output->writeln('<comment>⚠️  Multiple districts were found, but you can only filter by one district at a time.</comment>');
        }

        $output->writeln('');
        $output->writeln('To use these in your <info>find:*</info> commands, use the following arguments:');
        $output->write('  ');

        if (!empty($districtIds)) {
            $output->write(sprintf(' --district "%s"', reset($districtIds)));
        }
        if (!empty($localityIds)) {
            $output->write(sprintf(' --region "%s"', implode(',', $localityIds)));
        }
        if (!empty($suburbIds)) {
            $output->write(sprintf(' --suburb "%s"', implode(',', $suburbIds)));
        }

        $output->writeln('');
        $output->writeln('');

        return 0;
    }
}
