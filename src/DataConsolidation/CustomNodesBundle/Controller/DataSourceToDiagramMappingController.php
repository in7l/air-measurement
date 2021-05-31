<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 10/20/16
 * Time: 3:02 AM
 */

namespace DataConsolidation\CustomNodesBundle\Controller;


use DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\ConsolidationState;
use DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\DataSourceToDiagramMapping;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Response;

class DataSourceToDiagramMappingController extends Controller
{

    /**
     * Administrative view of all actions related to data source to diagram mappings.
     */
    public function indexAction()
    {
        return $this->render('DataConsolidationCustomNodesBundle:DataSourceToDiagramMapping:index.html.twig');
    }

    /**
     * Displays a form for adding a new data source to diagram mapping.
     */
    public function addAction()
    {
        $formData = new DataSourceToDiagramMapping(); // Your form data class. Has to be an object, won't work properly with an array.

        $flow = $this->get('data_consolidation.custom_nodes.form.flow.data_source_to_diagram_mapping'); // must match the flow's service id
        $flow->bind($formData);

        // form of the current step
        $form = $flow->createForm();
        if ($flow->isValid($form)) {
            $flow->saveCurrentStepData($form);

            if ($flow->nextStep()) {
                // form for the next step
                $form = $flow->createForm();
            } else {
                // flow finished
                // Remove empty FieldMapping objects from the DataSourceToDiagramMapping.
                $doctrineEntityHelper = $this->get('data_consolidation.custom_nodes.doctrine_entity_helper');
                $doctrineEntityHelper->removeEmptyFieldMappingsFromDataSourceToDiagramMapping($formData);

                // Persist the DataSourceToDiagramMapping.
                $em = $this->getDoctrine()->getManager();
                $em->persist($formData);
                $em->flush();

                $flow->reset(); // remove step data from the session

                // Add a flash message marking the successful addition of the new DataSourceToDiagramMapping.
                $dataSourceToDiagramMappingId = $formData->getId();
                $this->addFlash('notice', sprintf("Added a new data source to diagram mapping with id '%d' and short name '%s'.", $dataSourceToDiagramMappingId, $formData->getShortName()));

                return $this->redirectToRoute('data_consolidation.custom_nodes.data_source_to_diagram_mapping.view', array(
                    'mapping_id' => $dataSourceToDiagramMappingId,
                ));
            }
        }

        return $this->render('DataConsolidationCustomNodesBundle:default:mapping_form_flow.html.twig', array(
            'form' => $form->createView(),
            'flow' => $flow,
            'page_title' => 'Add a data source to diagram mapping',
        ));
    }

    /**
     * Displays a form for editing an existing data source to diagram mapping.
     *
     * @param integer $mapping_id The database id of the data source to diagram mapping.
     */
    public function editAction($mapping_id)
    {
        // Attempt to find the data source to diagram mapping with the specified id.
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('DataConsolidation\\CustomNodesBundle\\Entity\\DefaultEntityManager\\Base\\DataSourceToDiagramMapping');
        $dataSourceToDiagramMapping = $repository->find($mapping_id);
        if (!$dataSourceToDiagramMapping) {
            throw $this->createNotFoundException(sprintf("The data to diagram mapping with id '%d' does not exist.", $mapping_id));
        }

        $formData = $dataSourceToDiagramMapping; // Your form data class. Has to be an object, won't work properly with an array.

        $flow = $this->get('data_consolidation.custom_nodes.form.flow.data_source_to_diagram_mapping'); // must match the flow's service id
        $flow->bind($formData);

        // form of the current step
        $form = $flow->createForm();
        if ($flow->isValid($form)) {
            $flow->saveCurrentStepData($form);

            if ($flow->nextStep()) {
                // form for the next step
                $form = $flow->createForm();
            } else {
                // flow finished
                // Remove empty FieldMapping objects from the DataSourceToDiagramMapping.
                $doctrineEntityHelper = $this->get('data_consolidation.custom_nodes.doctrine_entity_helper');
                $doctrineEntityHelper->removeEmptyFieldMappingsFromDataSourceToDiagramMapping($formData);

                // Persist the DataSourceToDiagramMapping.
                $em = $this->getDoctrine()->getManager();
                $em->persist($formData);
                $em->flush();

                $flow->reset(); // remove step data from the session

                // Add a flash message marking the successful addition of the new DataSourceToDiagramMapping.
                $dataSourceToDiagramMappingId = $formData->getId();
                $this->addFlash('notice', sprintf("Edited data source to diagram mapping with id '%d' and short name '%s'.", $dataSourceToDiagramMappingId, $formData->getShortName()));

                return $this->redirectToRoute('data_consolidation.custom_nodes.data_source_to_diagram_mapping.view', array(
                    'mapping_id' => $mapping_id,
                ));
            }
        }

        return $this->render('DataConsolidationCustomNodesBundle:default:mapping_form_flow.html.twig', array(
            'form' => $form->createView(),
            'flow' => $flow,
            'page_title' => sprintf("Edit data source to diagram mapping: '%s'", $dataSourceToDiagramMapping->getShortName()),
            'tab_items' => $this->getTabItems('edit', $mapping_id),
        ));
    }

    /**
     * Handles listing of all known data source to diagram mappings.
     */
    public function listAction()
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('DataConsolidation\\CustomNodesBundle\\Entity\\DefaultEntityManager\\Base\\DataSourceToDiagramMapping');

        // Get all data source to diagram mappings..
        $dataSourceToDiagramMappings = $repository->findAll();

        return $this->render('DataConsolidationCustomNodesBundle:DataSourceToDiagramMapping:list.html.twig', array(
            'data_source_to_diagram_mappings' => $dataSourceToDiagramMappings,
        ));
    }

    /**
     * Handles listing of all known data source to diagram mappings for a specific diagram.
     *
     * @param string $sanitized_entity_manager_name The sanitized entity manager name associated with the doctrine entity.
     * @param string $entity_name The name of the doctrine entity.
     */
    public function listForDiagramAction($sanitized_entity_manager_name, $entity_name)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('DataConsolidation\\CustomNodesBundle\\Entity\\DefaultEntityManager\\Base\\DataSourceToDiagramMapping');
        // Obtain the fully qualified class name for the diagram.
        $doctrineEntityHelper = $this->get('data_consolidation.custom_nodes.doctrine_entity_helper');
        $fullyQualifiedClassName = $doctrineEntityHelper->getFullyQualifiedDiagramEntityClassName($entity_name, $sanitized_entity_manager_name, false);

        // Get all data source to diagram mappings..
        $dataSourceToDiagramMappings = $repository->findBy(
            array('diagram' => $fullyQualifiedClassName)
        );

        return $this->render('DataConsolidationCustomNodesBundle:DataSourceToDiagramMapping:list.html.twig', array(
            'data_source_to_diagram_mappings' => $dataSourceToDiagramMappings,
            'display_add_link' => true,
            'diagram_name' => $entity_name,
        ));
    }

    /**
     * Fetches a data source to diagram mapping and displays it.
     *
     * @param integer $mapping_id The database id of the data source to diagram mapping.
     */
    public function viewAction($mapping_id)
    {
        // Attempt to find the data source to diagram mapping with the specified id.
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('DataConsolidation\\CustomNodesBundle\\Entity\\DefaultEntityManager\\Base\\DataSourceToDiagramMapping');
        $dataSourceToDiagramMapping = $repository->find($mapping_id);
        if (!$dataSourceToDiagramMapping) {
            throw $this->createNotFoundException(sprintf("The data to diagram mapping with id '%d' does not exist.", $mapping_id));
        }

        // Extract some information regarding the diagram and the data source entities, so some links can be displayed in the output.
        $diagramEntityInfo = null;
        $dataSourceEntityInfo = null;
        $doctrineEntityHelper = $this->get('data_consolidation.custom_nodes.doctrine_entity_helper');
        try {
            $diagramFullyQualifiedClassName = $dataSourceToDiagramMapping->getDiagram();
            $diagramEntityInfo = array(
                'entity_type' => 'diagram',
                'sanitized_entity_manager_name' => $doctrineEntityHelper->getSanitizedEntityManagerNameFromClassName($diagramFullyQualifiedClassName),
                'entity_name' => $doctrineEntityHelper->getShortClassName($diagramFullyQualifiedClassName),
            );
        }
        catch (\Exception $e) {
            // Something is wrong with the fully qualified class name of the diagram.
            // Silently ignore this error: a link to the generated doctrine entity will simply not be displayed.
        }

        try {
            $dataSourceFullyQualifiedClassName = $dataSourceToDiagramMapping->getDataSource();
            $dataSourceEntityInfo = array(
                'entity_type' => 'data-source',
                'sanitized_entity_manager_name' => $doctrineEntityHelper->getSanitizedEntityManagerNameFromClassName($dataSourceFullyQualifiedClassName),
                'entity_name' => $doctrineEntityHelper->getShortClassName($dataSourceFullyQualifiedClassName),
            );
        }
        catch (\Exception $e) {
            // Something is wrong with the fully qualified class name of the data source.
            // Silently ignore this error: a link to the generated doctrine entity will simply not be displayed.
        }

        return $this->render('DataConsolidationCustomNodesBundle:DataSourceToDiagramMapping:view.html.twig', array(
            'data_source_to_diagram_mapping' => $dataSourceToDiagramMapping,
            'diagram_entity_route_params' => $diagramEntityInfo,
            'data_source_entity_route_params' => $dataSourceEntityInfo,
            'tab_items' => $this->getTabItems('view', $mapping_id),
        ));
    }

    /**
     * Confirmation form for deleting a data source to diagram mapping.
     *
     * @param integer $mapping_id The database id of the data source to diagram mapping.
     */
    public function deleteAction($mapping_id)
    {
        // Attempt to find the data source to diagram mapping with the specified id.
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('DataConsolidation\\CustomNodesBundle\\Entity\\DefaultEntityManager\\Base\\DataSourceToDiagramMapping');
        $dataSourceToDiagramMapping = $repository->find($mapping_id);
        if (!$dataSourceToDiagramMapping) {
            throw $this->createNotFoundException(sprintf("The data to diagram mapping with id '%d' does not exist.", $mapping_id));
        }

        // Use the contrib ConfirmBundle to display a confirmation for the deletion.
        $options = array(
            'message' => sprintf("Are you sure you want to delete the data source to diagram mapping with id '%d' and name '%s'?", $mapping_id, $dataSourceToDiagramMapping->getShortName()),
            'warning' => 'The deletion cannot be undone!',
            'confirm_button_text' => 'Delete',
            'confirm_action' => array($this, 'delete'),
            'confirm_action_args' => array(
                'dataSourceToDiagramMapping' => $dataSourceToDiagramMapping,
            ),
            'cancel_link_text' => 'Cancel',
            'cancel_url' => $this->generateUrl('data_consolidation.custom_nodes.data_source_to_diagram_mapping.view', array(
                'mapping_id' => $mapping_id,
            )),
        );

        return $this->forward('ConfirmBundle:Confirm:confirm', array('options' => $options));
    }

    /**
     * Helper for the deleteAction confirmation.
     *
     * Handles the actual deletion of a data source to diagram mapping, once the user has confirmed that is what they intend to do.
     *
     * @param array $args Arguments forwarded from the deletion confirmation. It is expected that this contains the following keys:
     *  'dataSourceToDiagramMapping' => The DataSourceToDiagramMapping object.
     */
    public function delete($args)
    {
        if (empty($args['dataSourceToDiagramMapping'])) {
            throw $this->createNotFoundException('Invalid deletion arguments.');
        }
        $dataSourceToDiagramMapping = $args['dataSourceToDiagramMapping'];
        $dataSourceToDiagramMappingId = $dataSourceToDiagramMapping->getId();
        $shortName = $dataSourceToDiagramMapping->getShortName();
        $em = $this->getDoctrine()->getManager();
        $em->remove($dataSourceToDiagramMapping);
        $em->flush();

        // Add a flash message marking the successful data source to diagram mapping deletion.
        $this->addFlash('notice', sprintf("Deleted the data source to diagram mapping with id '%d' and name '%s'.", $dataSourceToDiagramMappingId, $shortName));

        return $this->redirectToRoute('data_consolidation.custom_nodes.data_source_to_diagram_mapping.list');
    }

    /**
     * TODO
     *
     * This is a test action.
     *
     * @param integer $mapping_id The database id of the data source to diagram mapping.
     */
    public function mapAction($mapping_id)
    {
        // Attempt to find the data source to diagram mapping with the specified id.
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('DataConsolidation\\CustomNodesBundle\\Entity\\DefaultEntityManager\\Base\\DataSourceToDiagramMapping');
        $dataSourceToDiagramMapping = $repository->find($mapping_id);
        if (!$dataSourceToDiagramMapping) {
            throw $this->createNotFoundException(sprintf("The data to diagram mapping with id '%d' does not exist.", $mapping_id));
        }

        $doctrineEntityMappingHelper = $this->get('data_consolidation.custom_nodes.doctrine_entity_mapping_helper');
        $doctrineEntityMappingHelper->translateDataSourceToDiagramEntities($dataSourceToDiagramMapping);

        return new Response('ok');
    }

    /**
     * TODO
     *
     * This is a test action.
     *
     * @param $mapping_id
     * @return Response
     */
    public function consolidateDataAction($mapping_id)
    {
        // Attempt to find the data source to diagram mapping with the specified id.
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('DataConsolidation\\CustomNodesBundle\\Entity\\DefaultEntityManager\\Base\\DataSourceToDiagramMapping');
        $dataSourceToDiagramMapping = $repository->find($mapping_id);
        if (!$dataSourceToDiagramMapping) {
            throw $this->createNotFoundException(sprintf("The data to diagram mapping with id '%d' does not exist.", $mapping_id));
        }

        $doctrineEntityMappingHelper = $this->get('data_consolidation.custom_nodes.doctrine_entity_mapping_helper');

        // Find diagram entities for a specific consolidation type.
        $diagramFullyQualifiedClassName = $dataSourceToDiagramMapping->getDiagram();
        $consolidationType = ConsolidationState::CONSOLIDATION_TYPE_NONE;
        $lastMeasurementTime = new \DateTime('2017-04-19 20:12:37');
        $endTime = new \DateTime('2017-04-19 20:13:28');
        $limit = 0;
        $diagramEntities = $doctrineEntityMappingHelper->findDiagramEntitiesByConsolidationType($diagramFullyQualifiedClassName, $consolidationType, $lastMeasurementTime, $endTime, $limit, false, true);
//        dump($diagramEntities);

//        dump(ConsolidationState::getSourceConsolidationType(ConsolidationState::CONSOLIDATION_TYPE_MONTH));
//        $now = new \DateTime();
//        dump($now);
//        dump(ConsolidationState::getClosestRoundedTimeByConsolidationType(ConsolidationState::CONSOLIDATION_TYPE_MINUTE, $now));
//        dump(ConsolidationState::getClosestRoundedTimeByConsolidationType(ConsolidationState::CONSOLIDATION_TYPE_HOUR, $now));
//        dump(ConsolidationState::getClosestRoundedTimeByConsolidationType(ConsolidationState::CONSOLIDATION_TYPE_DAY, $now));
//        dump(ConsolidationState::getClosestRoundedTimeByConsolidationType(ConsolidationState::CONSOLIDATION_TYPE_MONTH, $now));
        
//        dump(ConsolidationState::getTimeIntervalStartAndEndTimesByConsolidationType(ConsolidationState::CONSOLIDATION_TYPE_MINUTE, $now));
//        dump(ConsolidationState::getTimeIntervalStartAndEndTimesByConsolidationType(ConsolidationState::CONSOLIDATION_TYPE_HOUR, $now));
//        dump(ConsolidationState::getTimeIntervalStartAndEndTimesByConsolidationType(ConsolidationState::CONSOLIDATION_TYPE_DAY, $now));
//        dump(ConsolidationState::getTimeIntervalStartAndEndTimesByConsolidationType(ConsolidationState::CONSOLIDATION_TYPE_MONTH, $now));

        $result = $doctrineEntityMappingHelper->downsampleDiagramEntitiesByConsolidationType($dataSourceToDiagramMapping, ConsolidationState::CONSOLIDATION_TYPE_MINUTE);
        dump($result);

        return new Response('ok');
    }

    /**
     * This is a test action.
     *
     * @return Response
     */
    public function interpolateAction($mapping_id)
    {
        // Attempt to find the data source to diagram mapping with the specified id.
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('DataConsolidation\\CustomNodesBundle\\Entity\\DefaultEntityManager\\Base\\DataSourceToDiagramMapping');
        $dataSourceToDiagramMapping = $repository->find($mapping_id);
        if (!$dataSourceToDiagramMapping) {
            throw $this->createNotFoundException(sprintf("The data to diagram mapping with id '%d' does not exist.", $mapping_id));
        }

        $doctrineEntityMappingHelper = $this->get('data_consolidation.custom_nodes.doctrine_entity_mapping_helper');

//        $x0 = 1;
//        $y0 = 3;
//        $x1 = 3;
//        $y1 = 2;
//        $x = 2;
//        $y = $doctrineEntityMappingHelper->linearInterpolate($x, $x0, $y0, $x1, $y1);
//        dump($y);

        // Find diagram entities for a specific consolidation type.
//        $diagramFullyQualifiedClassName = $dataSourceToDiagramMapping->getDiagram();
//        $consolidationType = ConsolidationState::CONSOLIDATION_TYPE_NONE;
//        $limit = 2;
//        $diagramEntities = $doctrineEntityMappingHelper->findDiagramEntitiesByConsolidationType($diagramFullyQualifiedClassName, $consolidationType, NULL, NULL, $limit);
//        dump($diagramEntities);
//
//        $measurementTime = new \DateTime('2017-04-19 17:29:07');
//        $generatedEntity = $doctrineEntityMappingHelper->generateDiagramEntityWithLinearInterpolation($dataSourceToDiagramMapping, $measurementTime, ConsolidationState::CONSOLIDATION_TYPE_MINUTE, $diagramEntities[0], $diagramEntities[1]);
//        dump($generatedEntity);
//        $em->persist($generatedEntity);
//        $em->flush();
//
//        $now = new \DateTime();
//        dump($now);
//        $intervalSpec = 'PT' . (60 * 60). 'S';
//        $now->add(new \DateInterval($intervalSpec));
//        dump($now);

        $limit = 20;
        $result = $doctrineEntityMappingHelper->upsampleDiagramEntitiesByConsolidationType($dataSourceToDiagramMapping, ConsolidationState::CONSOLIDATION_TYPE_MINUTE, $limit);
        dump($result);

        return new Response('ok');
    }

    /**
     * Fetches tab items for the use in twig templates that mark different actions for a custom node configuration.
     *
     * @param string $currentAction The current action, e.g. 'view' or 'edit'.
     * @param integer $mappingId The database id of the data source to diagram mapping.
     *
     * @return array Tab items formatted in the way expected in the twig templates.
     */
    private function getTabItems($currentAction, $mappingId)
    {
        $tabItems = array(
            'view' => array(
                'url' => $this->generateUrl('data_consolidation.custom_nodes.data_source_to_diagram_mapping.view', array(
                    'mapping_id' => $mappingId,
                )),
                'name' => 'View',
            ),
            'edit' => array(
                'url' => $this->generateUrl('data_consolidation.custom_nodes.data_source_to_diagram_mapping.edit', array(
                    'mapping_id' => $mappingId,
                )),
                'name' => 'Edit'
            ),
            'delete' => array(
                'url' => $this->generateUrl('data_consolidation.custom_nodes.data_source_to_diagram_mapping.delete', array(
                    'mapping_id' => $mappingId,
                )),
                'name' => 'Delete'
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
}