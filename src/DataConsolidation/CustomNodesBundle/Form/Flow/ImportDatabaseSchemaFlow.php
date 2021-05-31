<?php

namespace DataConsolidation\CustomNodesBundle\Form\Flow;

use Craue\FormFlowBundle\Form\FormFlow;
use DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\NodeConfig;
use DataConsolidation\DatabaseConfigurationBundle\Utils\DatabaseConfigurator;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ImportDatabaseSchemaFlow extends FormFlow
{
    // Use a trait that allows setting a service container.
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected function loadStepsConfig()
    {
        return array(
            array(
                'label' => 'Select database connection',
                'form_type' => 'DataConsolidation\CustomNodesBundle\Form\Type\ImportDatabaseSchemaFormType',
                'form_options' => array(
                    'validation_groups' => array(
                        'importDatabaseSchemaFlowStep1',
                    ),
                ),
            ),
            array(
                'label' => 'Select table',
                'form_type' => 'DataConsolidation\CustomNodesBundle\Form\Type\ImportDatabaseSchemaFormType',
                'form_options' => array(
                    'validation_groups' => array(
                        'importDatabaseSchemaFlowStep1',
                        'importDatabaseSchemaFlowStep2',
                    ),
                ),
            ),
            array(
                'label' => 'Review node configuration',
                'form_type' => 'DataConsolidation\CustomNodesBundle\Form\Type\ImportDatabaseSchemaFormType',
                'form_options' => array(
                    'validation_groups' => array(
                        'importDatabaseSchemaFlowStep1',
                        'importDatabaseSchemaFlowStep2',
                        'Default',
                    ),
                ),
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormOptions($step, array $options = array()) {
        $options = parent::getFormOptions($step, $options);

        $formData = $this->getFormData();

        switch ($step) {
            case 1:
                // Fetch all available entity managers.
                $entityManagerNames = array();
                if ($this->container) {
                    $databaseConfigurator = $this->container->get('data_consolidation.database_configurator');
                    $entityManagerNames = $databaseConfigurator->getEntityManagerNames();
                }
                if (empty($entityManagerNames)) {
                    throw new \Exception("Failed to find any existing entity managers.");
                }
                // Update the options for the form flow.
                $options['entityManagerNameChoices'] = array_combine($entityManagerNames, $entityManagerNames);
                break;
            case 2:
                // Attempt to find all table names in the database that the entity manager is connected to.
                $entityManagerName = $formData->getEntityManagerName();
                $tableNames = array();
                try {
                    if ($entityManagerName && $this->container) {
                        $doctrineEntityHelper = $this->container->get('data_consolidation.custom_nodes.doctrine_entity_helper');
                        $tableNames = $doctrineEntityHelper->getTableList($entityManagerName);
                    }
                    if (empty($tableNames)) {
                        // Failed to find any tables for the specified entity manager's db connection.
                        throw new \Exception("Empty table list.");
                    }
                }
                catch (\Exception $e) {
                    // Re-throw the exception by adding a bit more detailed error message.
                    throw new \Exception(sprintf("Failed to list tables for entity manager '%s'.", $entityManagerName), 0, $e);
                }
                // Update the options for the form flow.
                $options['tableNameChoices'] = array_combine($tableNames, $tableNames);
                break;
            case 3:
                // Attempt to create a node config based on the specified entity manager and table.
                $entityManagerName = $formData->getEntityManagerName();
                $tableName = $formData->getTableName();
                if ($entityManagerName && $tableName && $this->container) {
                    $doctrineEntityHelper = $this->container->get('data_consolidation.custom_nodes.doctrine_entity_helper');
                    $nodeConfig = new NodeConfig();
                    $nodeConfig->addTargetEntityManager($entityManagerName);
                    $doctrineEntityHelper->createNodeConfigFromDatabaseSchema($entityManagerName, $tableName, $nodeConfig);
                    $formData->setNodeConfig($nodeConfig);
                }
                else {
                    throw new \Exception(sprintf("Failed to create node configuration for table '%s' and entity manager '%s'.", $tableName, $entityManagerName));
                }
                break;
        }

        return $options;
    }

}
