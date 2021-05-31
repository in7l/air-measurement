<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 4/19/17
 * Time: 6:07 PM
 */

namespace DataConsolidation\CustomNodesBundle\Command;


use DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\ConsolidationState;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\LockHandler;

/**
 * Class ProcessDataSourceToDiagramMappingCommand
 * @package DataConsolidation\CustomNodesBundle\Command
 *
 * This command is used for downsampling of diagram data.
 */
class GenerateRandomDataCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            // the name of the command (the part after "app/console")
            ->setName('app:generate-data')

            // the short description shown while running "php app/console list"
            ->setDescription('Generates random data.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows you to generate random data for data sources.')

            // Add arguments and options to the command.
//            ->addArgument('diagram', InputArgument::REQUIRED, 'The fully qualified class name of the diagram entity.')
//            ->addArgument('limit', InputArgument::REQUIRED, 'The maximum amount of entities to be processed per data source.')
//            ->addOption(
//                'data-source',
//                'd',
//                InputOption::VALUE_OPTIONAL,
//                'Diagram entities of a specific data source to be processed.'
//            )
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lockHandler = new LockHandler('generate_random_data.lock');
        if (!$lockHandler->lock()) {
            $output->writeln("Another instance of this script is running. Exiting.");

            return 0;
        }

        $randomDataGenerator = $this->getContainer()->get('data_consolidation.custom_nodes.random_data_generator');
        $randomGeneratorSetups = $this->getRandomGeneratorSetups();
        foreach ($randomGeneratorSetups as $setup) {
            $output->writeln(sprintf("Generating sine data for data source '%s' with limit '%d'.", $setup['fullyQualifiedClassName'], $setup['limit']));
            $generatedEntriesCount = $randomDataGenerator->generateSineData($setup['fullyQualifiedClassName'], $setup['start'], $setup['limit'], $setup['intervalInSeconds'], $setup['measurementTimeSetter'], $setup['measurementTimeGetter'], $setup['measurementTimeField'], $setup['valueSetter'], $setup['valuesDeviation'], $setup['intervalDeviation'], $setup['min'], $setup['max']);
            $output->writeln(sprintf("Done generating %d entries for the data source.", $generatedEntriesCount));
        }
        $output->writeln('Finishing script run.');
    }

    protected function getRandomGeneratorSetups() {
        $setups = array(
            // DEV VALUES
//            array(
//                'fullyQualifiedClassName' => 'DataConsolidation\\CustomNodesBundle\\Entity\\CustomEntityManagers\\DataSourceDb1\\Custom\\DataSource\\AirQuality',
//                'start' => NULL,
//                'limit' => 60,
//                'intervalInSeconds' => 5 * 60,
//                'intervalDeviation' => 20,
//                'min' => 2,
//                'max' => 15,
//                'valuesDeviation' => 0.05,
//                'measurementTimeSetter' => 'setMeasured',
//                'measurementTimeGetter' => 'getMeasured',
//                'measurementTimeField' => 'measured',
//                'valueSetter' => 'setMg',
//            ),
            // PRODUCTION VALUES
            array(
                'fullyQualifiedClassName' => 'DataConsolidation\\CustomNodesBundle\\Entity\\CustomEntityManagers\\DataSourceDb2\\Custom\\DataSource\\Helsinki',
                'start' => NULL,
                'limit' => 60,
                'intervalInSeconds' => 5 * 60,
                'intervalDeviation' => 20,
                'min' => 4,
                'max' => 20,
                'valuesDeviation' => 0.05,
                'measurementTimeSetter' => 'setMeasurementTime',
                'measurementTimeGetter' => 'getMeasurementTime',
                'measurementTimeField' => 'measurement_time',
                'valueSetter' => 'setTemperature',
            ),
            array(
                'fullyQualifiedClassName' => 'DataConsolidation\\CustomNodesBundle\\Entity\\CustomEntityManagers\\DataSourceDb3\\Custom\\DataSource\\Espoo',
                'start' => NULL,
                'limit' => 60,
                'intervalInSeconds' => 10 * 60,
                'intervalDeviation' => 30,
                'min' => 6,
                'max' => 19,
                'valuesDeviation' => 0.03,
                'measurementTimeSetter' => 'setMeasured',
                'measurementTimeGetter' => 'getMeasured',
                'measurementTimeField' => 'measured',
                'valueSetter' => 'setDegreesC',
            ),
        );

        return $setups;
    }
}