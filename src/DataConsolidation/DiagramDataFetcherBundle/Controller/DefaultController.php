<?php

namespace DataConsolidation\DiagramDataFetcherBundle\Controller;

use DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\ConsolidationState;
use DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\DiagramConfig;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * Lists all available diagrams.
     */
    public function listAction()
    {
        // Fetch information about existing diagram entities.
        $doctrineEntityHelper = $this->get('data_consolidation.custom_nodes.doctrine_entity_helper');
        $diagramEntitiesInfo = $doctrineEntityHelper->getAllDiagramEntityNames(true);

        // Format the information in the way required for displaying it.
        $diagramEntitiesDisplayInfo = array();
        foreach ($diagramEntitiesInfo as $unsanitizedEntityManagerName => $fullyQualifiedDiagramClassNames) {
            // Obtain the sanitized entity manager name.
            $sanitizedEntityManagerName = $doctrineEntityHelper->getSanitizedEntityManagerName($unsanitizedEntityManagerName);

            foreach ($fullyQualifiedDiagramClassNames as $fullyQualifiedDiagramClassName) {
                // Obtain the short class name for the diagram class.
                $className = $doctrineEntityHelper->getShortClassName($fullyQualifiedDiagramClassName);
                $diagramEntitiesDisplayInfo[$sanitizedEntityManagerName][] = $className;
            }
        }

        return $this->render('DataConsolidationDiagramDataFetcherBundle:Default:list.html.twig', array(
            'diagram_entities_info' => $diagramEntitiesDisplayInfo,
        ));
    }

    /**
     * Fetches JSON content to be visualized in a diagram.
     *
     * @param string $sanitized_entity_manager_name The sanitized entity manager name associated with the doctrine entity.
     * @param string $entity_name The name of the doctrine entity.
     */
    public function getContentAction($sanitized_entity_manager_name, $entity_name, Request $request)
    {
        // Get the results page.
        $page = $request->query->get('page', 1);
        if (intval($page) != $page || $page < 1) {
            throw new \Exception(sprintf("Invalid page: '%s'.", $page));
        }
        // Get the results limit.
        $defaultLimit = $this->getParameter('default_entries_per_page');
        $maximumLimit = $this->getParameter('maximum_entries_per_page');
        $limit = $request->query->get('limit', $defaultLimit);
        if (intval($limit) != $limit || $limit < 1 || $limit > $maximumLimit) {
            throw new \Exception(sprintf("Invalid page: '%s'. Allowed values are between %d and %d", $limit, 1, $maximumLimit));
        }

        $consolidationTypeParam = $request->query->get('consolidation-type');
        if (!is_null($consolidationTypeParam)) {
            $consolidationTypes = explode(',',$consolidationTypeParam);
            $validConsolidationTypes = ConsolidationState::getValidConsolidationTypes();
            foreach ($consolidationTypes as &$consolidationType) {
                $consolidationType = intval($consolidationType);
                if (!in_array($consolidationType, $validConsolidationTypes, true)) {
                    throw new \Exception(sprintf("Invalid consolidation type '%d'.", $consolidationType));
                }
            }

        }

        // Fetch start and end time GET parameters.
        $startTimeParam = $request->query->get('start');
        $endTimeParam = $request->query->get('end');
        $utcTimeZone = new \DateTimeZone("UTC");
        $localTimeZone = new \DateTimeZone("Europe/Helsinki");
        $startTime = NUll;
        if ($startTimeParam) {
            $startTime = new \DateTime($startTimeParam, $utcTimeZone);
//            $startTime->setTimezone($localTimeZone);
            $startTime = $startTime->format('Y-m-d H:i:s');
        }
        if ($endTimeParam) {
            $endTime = new \DateTime($endTimeParam, $utcTimeZone);
//            $endTime->setTimezone($localTimeZone);
            $endTime = $endTime->format('Y-m-d H:i:s');
        }

        $doctrineEntityHelper = $this->get('data_consolidation.custom_nodes.doctrine_entity_helper');
        $fullyQualifiedClassName = $doctrineEntityHelper->getFullyQualifiedDiagramEntityClassName($entity_name, $sanitized_entity_manager_name, false);

        // Check if the database for this entity exists.
        if (!$doctrineEntityHelper->entityDatabaseExists($fullyQualifiedClassName)) {
            return $this->createNotFoundException('The database for this diagram is not available.');
        }
        // Check if the table for this entity exists.
        elseif (!$doctrineEntityHelper->entityTableExists($fullyQualifiedClassName)) {
            return $this->createNotFoundException('The database table for this diagram is not available.');
        }

        // Get the entity repository for the generated doctrine entity.
        $em = $this->getDoctrine()->getManagerForClass($fullyQualifiedClassName);

        // Calculate the offset based on the limit and the page number.
        $offset = ($page - 1) * $limit;

        // Get the total number of content entries.
        // Determine the count query conditions depending on optional GET parameters.
        $countQueryConditions = array();
        if (!empty($consolidationTypes)) {
            $countQueryConditions[DiagramConfig::CONFIG_FIELD_NAME_CONSOLIDATION_TYPE] = array(
                'operator' => 'IN',
                'value' => $consolidationTypes,
            );
        }
        // Time filters.
        if (!empty($startTime) && !empty($endTime)) {
            $countQueryConditions[DiagramConfig::CONFIG_FIELD_NAME_DATETIME] = array(
                'operator' => 'BETWEEN',
                'value' => array($startTime, $endTime),
            );
        }
        elseif (!empty($startTime)) {
            $countQueryConditions[DiagramConfig::CONFIG_FIELD_NAME_DATETIME] = array(
                'operator' => '>=',
                'value' => $startTime
            );
        }
        elseif (!empty($endTime)) {
            $countQueryConditions[DiagramConfig::CONFIG_FIELD_NAME_DATETIME] = array(
                'operator' => '<=',
                'value' => $endTime,
            );
        }
        $totalCount = $doctrineEntityHelper->getEntityCount($em, $fullyQualifiedClassName, $countQueryConditions);

        $orderByConditions = array(
            DiagramConfig::CONFIG_FIELD_NAME_DATETIME => 'DESC',
        );
        $content = $doctrineEntityHelper->queryForEntities($em, $fullyQualifiedClassName, $countQueryConditions, $orderByConditions, $limit, $offset);

//        // Determine the search criteria depending on an optional GET parameter.
//        $searchCriteria = array();
//        if (!empty($consolidationTypes)) {
//            $searchCriteria[DiagramConfig::CONFIG_FIELD_NAME_CONSOLIDATION_TYPE] = $consolidationTypes;
//        }
//
//        // Determine the sort criteria.
//        // Sort by measurement time in descending order so that the latest data is fetched.
//        $sortCriteria = array(
//            DiagramConfig::CONFIG_FIELD_NAME_DATETIME => 'DESC',
//        );
//
//        // Get the content.
//        $repository = $em->getRepository($fullyQualifiedClassName);
//        $content = $repository->findBy($searchCriteria, $sortCriteria, $limit, $offset);

        // Extract the data from the diagram entries.
        $diagramDataEntries = $doctrineEntityHelper->extractInformationFromDiagramEntries($content);

        // Now sort the data in ascending order of the datetime, because this is how they are supposed to be visualized in the diagram.
        usort($diagramDataEntries, function ($a, $b) {
            return $a['measurementTime'] - $b['measurementTime'];
        });

        // From: http://symfony.com/doc/2.8/components/http_foundation.html#creating-a-json-response
        // To avoid XSSI JSON Hijacking, you should pass an associative array as the outer-most array to JsonResponse and not an indexed array so that the final result is an object (e.g. {"object": "not inside an array"}) instead of an array (e.g. [{"object": "inside an array"}]). Read the OWASP guidelines for more information.
        $response = new JsonResponse();
        $response->setData(array(
            'data' => $diagramDataEntries,
            'totalCount' => $totalCount,
        ));

        return $response;
    }

    /**
     * Visualizes a diagram to the user.
     *
     * @param string $sanitized_entity_manager_name The sanitized entity manager name associated with the doctrine entity.
     * @param string $entity_name The name of the doctrine entity.
     */
    public function showDiagramAction($sanitized_entity_manager_name, $entity_name)
    {
        $doctrineEntityHelper = $this->get('data_consolidation.custom_nodes.doctrine_entity_helper');
        $fullyQualifiedClassName = $doctrineEntityHelper->getFullyQualifiedDiagramEntityClassName($entity_name, $sanitized_entity_manager_name, false);

        // Check if the database for this entity exists.
        if (!$doctrineEntityHelper->entityDatabaseExists($fullyQualifiedClassName)) {
            return $this->createNotFoundException('The database for this diagram is not available.');
        }
        // Check if the table for this entity exists.
        elseif (!$doctrineEntityHelper->entityTableExists($fullyQualifiedClassName)) {
            return $this->createNotFoundException('The database table for this diagram is not available.');
        }

        // Get the entity repository for the generated doctrine entity.
        $em = $this->getDoctrine()->getManagerForClass($fullyQualifiedClassName);

        // Get all available data sources in this diagram.
        $diagramDataSources = $doctrineEntityHelper->getDiagramDataSources($em, $fullyQualifiedClassName, true);
        // Get all available value types in this diagram (diagram properties).
        $diagramProperties = $doctrineEntityHelper->getDiagramProperties($fullyQualifiedClassName);

        return $this->render('DataConsolidationDiagramDataFetcherBundle:Default:diagram.html.twig', array(
            'diagram_name' => $entity_name,
            'data_sources' => $diagramDataSources,
            'value_types' => $diagramProperties,
            'backend_url' => $this->generateUrl('data_consolidation.diagram_data_fetcher.get_content', array(
                'sanitized_entity_manager_name' => $sanitized_entity_manager_name,
                'entity_name' => $entity_name,
            )),
        ));
    }
}
