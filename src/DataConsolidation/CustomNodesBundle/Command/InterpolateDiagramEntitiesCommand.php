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
 * This command is used for upsampling of diagram data.
 */
class InterpolateDiagramEntitiesCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            // the name of the command (the part after "app/console")
            ->setName('app:interpolate-diagram')

            // the short description shown while running "php app/console list"
            ->setDescription('Interpolates data for a single diagram.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows you to upsample data for a single diagram.')

            // Add arguments and options to the command.
            ->addArgument('diagram', InputArgument::REQUIRED, 'The fully qualified class name of the diagram entity.')
            ->addArgument('limit', InputArgument::REQUIRED, 'The maximum amount of entities to be processed per data source per consolidation type.')
            ->addOption(
                'data-source',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Diagram entities of a specific data source to be processed.'
            )
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lockHandler = new LockHandler('interpolate.lock');
        if (!$lockHandler->lock()) {
            $output->writeln("Another instance of this script is running. Exiting.");

            return 0;
        }

        // Retrieve the mapping-id argument.
        $diagramName = $input->getArgument('diagram');

        // Retrieve the limit argument.
        $limit = $input->getArgument('limit');
        if (intval($limit) != $limit || $limit < 0) {
            throw new \Exception(sprintf("Invalid limit '%s'. Expected a non-negative integer", $limit));
        }
        $limit = intval($limit);

        $dataSource = $input->getOption('data-source');

        // Attempt to find the data source to diagram mapping with the specified id.
        $em = $this->getContainer()->get('doctrine')->getManager();
        $repository = $em->getRepository('DataConsolidation\\CustomNodesBundle\\Entity\\DefaultEntityManager\\Base\\DataSourceToDiagramMapping');
        $dataSourceToDiagramMappings = $repository->findAllByDiagramAndDataSourceNames($diagramName, $dataSource);
        if (empty($dataSourceToDiagramMappings)) {
            $output->writeln(sprintf("There are no DataSourceToDiagram mappings found for diagram '%s'.", $diagramName));
            return;
        }

        $doctrineEntityMappingHelper = $this->getContainer()->get('data_consolidation.custom_nodes.doctrine_entity_mapping_helper');

        foreach ($dataSourceToDiagramMappings as $dataSourceToDiagramMapping) {
            $dataSourceFullyQualifiedClassName = $dataSourceToDiagramMapping->getDataSource();
            $diagramFullyQualifiedClassName = $dataSourceToDiagramMapping->getDiagram();
            $output->writeln(sprintf("Upsampling entities for data source '%s' to diagram '%s'.", $dataSourceFullyQualifiedClassName, $diagramFullyQualifiedClassName));

            $result = $doctrineEntityMappingHelper->upsampleDiagramEntitiesByConsolidationType($dataSourceToDiagramMapping, ConsolidationState::CONSOLIDATION_TYPE_MINUTE, $limit);
            $output->writeln(sprintf("Read %d and generated %d amount of entities.", $result['read'], $result['generated']));

            $output->writeln("Finishing upsampling the diagram.");
        }
    }

}