<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 11/20/16
 * Time: 9:05 PM
 */

namespace DataConsolidation\CustomNodesBundle\Controller;


use DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\JsonToDiagramMapping;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class JsonToDiagramMappingController extends Controller
{
    /**
     * Administrative view of all actions related to JSON to diagram mappings.
     */
    public function indexAction()
    {
        return $this->render('DataConsolidationCustomNodesBundle:JsonToDiagramMapping:index.html.twig');
    }

    /**
     * Displays a form for adding a new JSON to diagram mapping.
     */
    public function addAction()
    {
        $formData = new JsonToDiagramMapping(); // Your form data class. Has to be an object, won't work properly with an array.

        $flow = $this->get('data_consolidation.custom_nodes.form.flow.json_to_diagram_mapping'); // must match the flow's service id
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

        return $this->render('DataConsolidationCustomNodesBundle:JsonToDiagramMapping:form_flow.html.twig', array(
            'form' => $form->createView(),
            'flow' => $flow,
            'page_title' => 'Add a JSON to diagram mapping',
        ));
    }

    /**
     * Handles listing of all known JSON to diagram mappings.
     */
    public function listAction()
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('DataConsolidation\\CustomNodesBundle\\Entity\\DefaultEntityManager\\Base\\JsonToDiagramMapping');

        // Get all JSON to diagram mappings.
        $jsonToDiagramMappings = $repository->findAll();

        return $this->render('DataConsolidationCustomNodesBundle:JsonToDiagramMapping:list.html.twig', array(
            'json_to_diagram_mappings' => $jsonToDiagramMappings,
        ));
    }

    /**
     * Fetches a JSON to diagram mapping and displays it.
     *
     * @param integer $mapping_id The database id of the JSON to diagram mapping.
     */
    public function viewAction($mapping_id)
    {
        // TODO

        /*
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
        */
    }
}
