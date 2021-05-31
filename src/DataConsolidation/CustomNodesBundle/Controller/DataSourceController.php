<?php

namespace DataConsolidation\CustomNodesBundle\Controller;

use DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\ImportedDatabaseSchema;
use DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\NodeConfig;
use DataConsolidation\CustomNodesBundle\Form\Type\NodeConfigFormType;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for data source node configs (i.e. instances of the base NodeConfig class, which are not DiagramConfigs).
 */
class DataSourceController extends Controller
{
    /**
     * Administrative view of all actions related to custom nodes.
     */
    public function indexAction()
    {
        return $this->render('DataConsolidationCustomNodesBundle:NodeConfig:index.html.twig');
    }


    /**
     * Handles listing of all known custom node configurations.
     */
    public function listAction()
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('DataConsolidation\\CustomNodesBundle\\Entity\\DefaultEntityManager\\Base\\NodeConfig');

        // Get all data source nodeConfigs.
        $nodeConfigs = $repository->findDataSourceNodeConfigs();

        return $this->render('DataConsolidationCustomNodesBundle:NodeConfig:list.html.twig', array(
            'node_configurations' => $nodeConfigs,
        ));
    }

    /**
     * Handles creating a new custom node.
     */
    public function addAction(Request $request)
    {
        $nodeConfig = new NodeConfig();
        $form = $this->createForm(NodeConfigFormType::class, $nodeConfig, array(
            'label' => false,
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Valid form submission.
            // Persist the node config, with all possible associated node config fields, to the database.
            $em = $this->getDoctrine()->getManager();
            $em->persist($nodeConfig);
            $em->flush();

            $customNodeConfigId = $nodeConfig->getId();

            // Add a flash message marking the successful addition of the new node config.
            $this->addFlash('notice', sprintf("Added a new data source node configuration with id '%d' and name '%s'.", $customNodeConfigId, $nodeConfig->getName()));
            return $this->redirectToRoute('data_consolidation.custom_nodes.data_source.view', array(
                'custom_node_config_id' => $customNodeConfigId,
            ));
        }

        return $this->render('DataConsolidationCustomNodesBundle:NodeConfig:add.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * Fetches a data source node config and displays it.
     *
     * @param int $custom_node_config_id The id of the custom node configuration.
     */
    public function viewAction($custom_node_config_id)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('DataConsolidation\\CustomNodesBundle\\Entity\\DefaultEntityManager\\Base\\NodeConfig');
        $nodeConfig = $repository->findDataSourceNodeConfig($custom_node_config_id);
        if (!$nodeConfig) {
            throw $this->createNotFoundException(sprintf("The data source node config with id '%d' does not exist.", $custom_node_config_id));
        }

        return $this->render('DataConsolidationCustomNodesBundle:NodeConfig:view.html.twig', array(
            'node_configuration' => $nodeConfig,
            'tab_items' => $this->getTabItems('view', $custom_node_config_id),
        ));
    }

    /**
     * Edits data source node config.
     *
     * @param int $custom_node_config_id The id of the custom node configuration.
     */
    public function editAction($custom_node_config_id, Request $request)
    {
        // Attempt to find the custom node config with the specified id.
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('DataConsolidation\\CustomNodesBundle\\Entity\\DefaultEntityManager\\Base\\NodeConfig');
        $nodeConfig = $repository->findDataSourceNodeConfig($custom_node_config_id);
        if (!$nodeConfig) {
            throw $this->createNotFoundException(sprintf("The data source node config with id '%d' does not exist.", $custom_node_config_id));
        }

        // Convert the options of the node config fields to objects. This is needed when building the form.
        $nodeConfig->convertFieldOptionsToObjects();

        // Create a form for the existing node config entity.
        $form = $this->createForm(NodeConfigFormType::class, $nodeConfig, array(
            'label' => false,
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Valid form submission.
            // Convert the options of the node config fields to JSON strings. This is needed for properly persisting the data.
            $nodeConfig->convertFieldOptionsToJson();

            // Persist the node config changes.
            $em->flush();

            // Add a flash message marking the successful change of the node config.
            $this->addFlash('notice', sprintf("Saved changes for data source node configuration with id '%d' and name '%s'.", $custom_node_config_id, $nodeConfig->getName()));

            return $this->redirectToRoute('data_consolidation.custom_nodes.data_source.view', array(
                'custom_node_config_id' => $custom_node_config_id,
            ));
        }

        return $this->render('DataConsolidationCustomNodesBundle:NodeConfig:edit.html.twig', array(
            'form' => $form->createView(),
            'node_configuration' => array(
                // Only add certain relevant fields. The rest will be rendered directly in the form.
                'name' => $nodeConfig->getName(),
            ),
            'tab_items' => $this->getTabItems('edit', $custom_node_config_id),
        ));
    }

    /**
     * Confirmation form for deleting a data source node configuration.
     *
     * @param int $custom_node_config_id The id of the custom node configuration.
     */
    public function deleteAction($custom_node_config_id)
    {
        // Attempt to find the custom node config with the specified id.
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('DataConsolidation\\CustomNodesBundle\\Entity\\DefaultEntityManager\\Base\\NodeConfig');
        $nodeConfig = $repository->findDataSourceNodeConfig($custom_node_config_id);
        if (!$nodeConfig) {
            throw $this->createNotFoundException(sprintf("The data source node config with id '%d' does not exist.", $custom_node_config_id));
        }

        // Use the contrib ConfirmBundle to display a confirmation for the deletion.
        $options = array(
            'message' => sprintf("Are you sure you want to delete the data source node configuration with id '%d' and name '%s'?", $custom_node_config_id, $nodeConfig->getName()),
            'warning' => 'The deletion cannot be undone!',
            'confirm_button_text' => 'Delete',
            'confirm_action' => array($this, 'delete'),
            'confirm_action_args' => array(
                'nodeConfig' => $nodeConfig,
            ),
            'cancel_link_text' => 'Cancel',
            'cancel_url' => $this->generateUrl('data_consolidation.custom_nodes.data_source.view', array(
                'custom_node_config_id' => $custom_node_config_id,
            )),
        );

        return $this->forward('ConfirmBundle:Confirm:confirm', array('options' => $options));
    }

    /**
     * Helper for the deleteAction confirmation.
     *
     * Handles the actual deletion of a data source node configuration, once the user has confirmed that is what they intend to do.
     *
     * @param array $args Arguments forwarded from the deletion confirmation. It is expected that this contains the following keys:
     *  'nodeConfig' => The NodeConfig object.
     */
    public function delete($args)
    {
        if (empty($args['nodeConfig'])) {
            throw $this->createNotFoundException('Invalid deletion arguments.');
        }
        $nodeConfig = $args['nodeConfig'];
        $customNodeConfigId = $nodeConfig->getId();
        $customNodeConfigName = $nodeConfig->getName();
        $em = $this->getDoctrine()->getManager();
        $em->remove($nodeConfig);
        $em->flush();

        // Add a flash message marking the successful custom node configuration deletion.
        $this->addFlash('notice', sprintf("Deleted the data source node configuration with id '%d' and name '%s'.", $customNodeConfigId, $customNodeConfigName));

        return $this->redirectToRoute('data_consolidation.custom_nodes.data_source.list');
    }

    /**
     * Confirmation form for generating doctrine entities based on a data source node configuration.
     *
     * @param int $custom_node_config_id The id of the custom node configuration.
     */
    public function generateEntitiesAction($custom_node_config_id)
    {
        // Attempt to find the custom node config with the specified id.
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('DataConsolidation\\CustomNodesBundle\\Entity\\DefaultEntityManager\\Base\\NodeConfig');
        $nodeConfig = $repository->findDataSourceNodeConfig($custom_node_config_id);
        if (!$nodeConfig) {
            throw $this->createNotFoundException(sprintf("The data source node config with id '%d' does not exist.", $custom_node_config_id));
        }

        $targetEntityManagersString = 'N/A';
        $targetEntityManagers = $nodeConfig->getTargetEntityManagers();
        if (!empty($targetEntityManagers)) {
            $targetEntityManagersString = implode(', ', $targetEntityManagers);
        }

        // Use the contrib ConfirmBundle to display a confirmation for the entity generation.
        $options = array(
            'message' => sprintf("Are you sure you want to generate doctrine entities for the data source node configuration with id '%d' and name '%s'? The target entity managers are: '%s'.", $custom_node_config_id, $nodeConfig->getName(), $targetEntityManagersString),
            'warning' => 'This will replace any previously existing doctrine entities with that name!',
            'confirm_button_text' => 'Generate entities',
            'confirm_action' => array($this, 'generateEntities'),
            'confirm_action_args' => array(
                'nodeConfig' => $nodeConfig,
            ),
            'cancel_link_text' => 'Cancel',
            'cancel_url' => $this->generateUrl('data_consolidation.custom_nodes.data_source.view', array(
                'custom_node_config_id' => $custom_node_config_id,
            )),
        );

        return $this->forward('ConfirmBundle:Confirm:confirm', array('options' => $options));
    }

    /**
     * Generates doctrine entities from a NodeConfig object.
     *
     * @param array $args Arguments forwarded from the entity generation confirmation. It is expected that this contains the following keys:
     *  'nodeConfig' => The NodeConfig object.
     */
    public function generateEntities($args)
    {
        
        if (empty($args['nodeConfig'])) {
            throw $this->createNotFoundException('Invalid generate entity arguments.');
        }
        $nodeConfig = $args['nodeConfig'];
        // Generate a custom entity from the node configuration object.
        $doctrineEntityHelper = $this->get('data_consolidation.custom_nodes.doctrine_entity_helper');
        $customNodeConfigId = $nodeConfig->getId();

        $targetEntityManagers = $nodeConfig->getTargetEntityManagers();
        if (empty($targetEntityManagers)) {
            // Add a flash message marking that nothing was generated because there are no target entity managers.
            $this->addFlash('warning', sprintf("No doctrine entities were generated for data source node configuration with id '%d' and name '%s' because there are no target entity managers.", $customNodeConfigId, $nodeConfig->getName()));
        }
        else {
            foreach ($targetEntityManagers as $entityManagerName) {
                $doctrineEntityHelper->generateDataSourceEntityFromNodeConfig($nodeConfig, $entityManagerName);
            }

            // Add a flash message marking the successful entity generation.
            $this->addFlash('notice', sprintf("Generated doctrine entities for data source node configuration with id '%d' and name '%s'. Target entity managers: '%s'.", $customNodeConfigId, $nodeConfig->getName(), implode(', ', $targetEntityManagers)));
        }

        return $this->redirectToRoute('data_consolidation.custom_nodes.data_source.view', array(
            'custom_node_config_id' => $customNodeConfigId,
        ));
    }

    /**
     * Imports an existing database schema in the form of a custom node configuration.
     */
    public function importAction()
    {
        $formData = new ImportedDatabaseSchema();

        // Get the form flow (form wizard) service for importing a database schema.
        $flow = $this->get('data_consolidation.custom_nodes.form.flow.import_database_schema');
        $flow->bind($formData);

        // form of the current step
        $form = $flow->createForm();
        if ($flow->isValid($form)) {
            $flow->saveCurrentStepData($form);

            if ($flow->nextStep()) {
                // form for the next step
                $form = $flow->createForm();
            } else {
                // Form flow finished
                // Get the node config from the form data.
                $nodeConfig = $formData->getNodeConfig();
                // Convert the options of the node config fields to JSON strings. This is needed for properly persisting the data.
                $nodeConfig->convertFieldOptionsToJson();

                // Persist the node config changes.
                $em = $this->getDoctrine()->getManager();
                $em->persist($nodeConfig);
                $em->flush();

                // Remove form flow step data from the session.
                $flow->reset();

                $customNodeConfigId = $nodeConfig->getId();

                // Add a flash message marking the successful addition of the new node config.
                $this->addFlash('notice', sprintf("Added a new data source node configuration with id '%d' and name '%s'.", $customNodeConfigId, $nodeConfig->getName()));
                return $this->redirectToRoute('data_consolidation.custom_nodes.data_source.view', array(
                    'custom_node_config_id' => $customNodeConfigId,
                ));
            }
        }

        return $this->render('DataConsolidationCustomNodesBundle:default:form_flow.html.twig', array(
            'form' => $form->createView(),
            'flow' => $flow,
            'page_title' => 'Import database schema as a data source node configuration',
        ));
    }

    /**
     * Fetches tab items for the use in twig templates that mark different actions for a custom node configuration.
     *
     * @param string $currentAction The current action, e.g. 'view' or 'edit'.
     * @param int $customNodeConfigId The custom node config identifier.
     *
     * @return array Tab items formatted in the way expected in the twig templates.
     */
    private function getTabItems($currentAction, $customNodeConfigId)
    {
        $tabItems = array(
            'view' => array(
                'url' => $this->generateUrl('data_consolidation.custom_nodes.data_source.view', array(
                    'custom_node_config_id' => $customNodeConfigId,
                )),
                'name' => 'View',
            ),
            'edit' => array(
                'url' => $this->generateUrl('data_consolidation.custom_nodes.data_source.edit', array(
                    'custom_node_config_id' => $customNodeConfigId,
                )),
                'name' => 'Edit'
            ),
            'delete' => array(
                'url' => $this->generateUrl('data_consolidation.custom_nodes.data_source.delete', array(
                    'custom_node_config_id' => $customNodeConfigId,
                )),
                'name' => 'Delete'
            ),
            'generate_entities' => array(
                'url' => $this->generateUrl('data_consolidation.custom_nodes.data_source.generate_entities', array(
                    'custom_node_config_id' => $customNodeConfigId,
                )),
                'name' => 'Generate entities'
            ),
        );

        // Mark the current action as active, if it is a valid one.
        $currentAction = strtolower($currentAction);
        if (!empty($tabItems[$currentAction])) {
            $tabItems[$currentAction]['active'] = TRUE;
            // Also change the URL to '#' since the user is on that page already.
            $tabItems[$currentAction]['url'] = '#';
        }

        return $tabItems;
    }

    public function testAction() {
//        $randomDataGenerator = $this->get('data_consolidation.custom_nodes.random_data_generator');
//        $fullyQualifiedClassName = 'DataConsolidation\\CustomNodesBundle\\Entity\\CustomEntityManagers\\DataSourceDb1\\Custom\\DataSource\\AirQuality';
//        $start = NULL;
//        $limit = 5;
//        $intervalInSeconds = 5 * 60;
//        $intervalDeviation = 20;
//        $min = 3;
//        $max = 10;
//        $valuesDeviation = 0.05;
//        $measurementTimeSetter = 'setMeasured';
//        $measurementTimeGetter = 'getMeasured';
//        $measurementTimeField = 'measured';
//        $valueSetter = 'setMg';
////        $randomDataGenerator->generateRandomData($fullyQualifiedClassName, $start, $limit, $intervalInSeconds, $intervalDeviation, $min, $max, $measurementTimeSetter, $valueSetter);
//        $randomDataGenerator->generateSineData($fullyQualifiedClassName, $start, $limit, $intervalInSeconds, $measurementTimeSetter, $measurementTimeGetter, $measurementTimeField, $valueSetter, $valuesDeviation, $intervalDeviation, $min, $max);

        return new Response('ok');
    }
}
