<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 4/19/17
 * Time: 6:07 PM
 */

namespace DataConsolidation\CustomNodesBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\LockHandler;

/**
 * Class ProcessDataSourceToDiagramMappingCommand
 * @package DataConsolidation\CustomNodesBundle\Command
 *
 * This command is used for translating data source to diagram Doctrine entities in a single DataSourceToDiagramMapping.
 */
class ProcessDataSourceToDiagramMappingCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            // the name of the command (the part after "app/console")
            ->setName('app:process-entity-mapping')

            // the short description shown while running "php app/console list"
            ->setDescription('Processes a single DataSourceToDiagramMapping.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows you to translate data source Doctrine entities into diagram Doctrine entities, based on a single DataSourceToDiagramMapping configuration.')

            // Add arguments to the command.
            ->addArgument('mapping-id', InputArgument::REQUIRED, 'The ID of the DataSourceToDiagramMapping.')
            ->addArgument('limit', InputArgument::REQUIRED, 'The maximum amount of data source entities to be translated into diagram entities')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lockHandler = new LockHandler('process_mapping.lock');
        if (!$lockHandler->lock()) {
            $output->writeln("Another instance of this script is running. Exiting.");

            return 0;
        }

        // Retrieve the mapping-id argument.
        $mappingId = $input->getArgument('mapping-id');
        if (intval($mappingId) != $mappingId || $mappingId < 1) {
            throw new \Exception(sprintf("Invalid mapping id '%s'. Expected a positive integer", $mappingId));
        }
        $mappingId = intval($mappingId);

        // Retrieve the limit argument.
        $limit = $input->getArgument('limit');
        if (intval($limit) != $limit || $limit < 0) {
            throw new \Exception(sprintf("Invalid limit '%s'. Expected a non-negative integer", $limit));
        }
        $limit = intval($limit);

        // Attempt to find the data source to diagram mapping with the specified id.
        $em = $this->getContainer()->get('doctrine')->getManager();
        $repository = $em->getRepository('DataConsolidation\\CustomNodesBundle\\Entity\\DefaultEntityManager\\Base\\DataSourceToDiagramMapping');
        $dataSourceToDiagramMapping = $repository->find($mappingId);
        if (!$dataSourceToDiagramMapping) {
            throw new \Exception(sprintf("The data to diagram mapping with id '%d' does not exist.", $mappingId));
        }

        $doctrineEntityMappingHelper = $this->getContainer()->get('data_consolidation.custom_nodes.doctrine_entity_mapping_helper');

        $dataSourceFullyQualifiedClassName = $dataSourceToDiagramMapping->getDataSource();
        $diagramFullyQualifiedClassName = $dataSourceToDiagramMapping->getDiagram();
        $output->writeln(sprintf("Translating entities from data source '%s' to diagram '%s'.", $dataSourceFullyQualifiedClassName, $diagramFullyQualifiedClassName));

        $result = $doctrineEntityMappingHelper->translateDataSourceToDiagramEntities($dataSourceToDiagramMapping, $limit);

        $lastKnownMeasurementTime = 'NONE';
        if (!empty($result['last_known_measurement_time'])) {
            $lastKnownMeasurementTime = $result['last_known_measurement_time']->format('c');
        }

        $output->writeln(sprintf("Attempted to process data source entities measured later than '%s'.", $lastKnownMeasurementTime));
        $output->writeln(sprintf("Processed a total amount of %d entities. Maximum allowed amount was %d.", $result['translated_entities_count'], $limit));
    }

}