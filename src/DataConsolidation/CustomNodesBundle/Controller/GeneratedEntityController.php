<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 3/22/16
 * Time: 6:39 PM
 */

namespace DataConsolidation\CustomNodesBundle\Controller;

use DataConsolidation\CustomNodesBundle\Utils\DoctrineEntityHelper;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GeneratedEntityController extends Controller
{
    /**
     * Handles listing of all directories that may contain generated doctrine entities.
     *
     * @param string $entity_type The custom doctrine entity type. Allowed values are:
     *  'data-source' and 'diagram'.
     */
    public function listAction($entity_type)
    {
        $doctrineEntityHelper = $this->get('data_consolidation.custom_nodes.doctrine_entity_helper');
        $unsanitizedEntityManagerNames = array();
        switch ($entity_type) {
            case 'data-source':
                $unsanitizedEntityManagerNames = $doctrineEntityHelper->getEntityManagersWithDataSourceEntities();
                break;
            case 'diagram':
                $unsanitizedEntityManagerNames = $doctrineEntityHelper->getEntityManagersWithDiagramEntities();
                break;
        }

        // Create an entities info array containing the sanitized and unsanitized entity manager names.
        $entityManagersInfo = array();
        foreach ($unsanitizedEntityManagerNames as $entityManagerName) {
            $sanitizedEntityManagerName = $doctrineEntityHelper->getSanitizedEntityManagerName($entityManagerName);
            $entityManagersInfo[] = array(
                'name' => $entityManagerName,
                'sanitized_name' => $sanitizedEntityManagerName,
            );
        }

        return $this->render('DataConsolidationCustomNodesBundle:GeneratedEntity:list.html.twig', array(
            'entityManagers' => $entityManagersInfo,
            'entityType' => $entity_type,
            'entityTypeDescription' => $this->getEntityTypeDescription($entity_type),
        ));
    }

    /**
     * Lists doctrine entities in a certain directory (which corresponds to the sanitized entity manager name).
     *
     * @param string $entity_type The custom doctrine entity type. Allowed values are:
     *  'data-source' and 'diagram'.
     * @param string $sanitized_entity_manager_name The sanitized entity manager name associated with the doctrine entity.
     */
    public function listEntitiesAction($entity_type, $sanitized_entity_manager_name)
    {
        // Unsanitize the entity manager name, since that is how the helper functions work.
        $doctrineEntityHelper = $this->get('data_consolidation.custom_nodes.doctrine_entity_helper');
        $unsanitizedEntityManagerName = $doctrineEntityHelper->getUnsanitizeEntityManagerName($sanitized_entity_manager_name);
        $fullyQualifiedClassNames = array();
        switch ($entity_type) {
            case 'data-source':
                $fullyQualifiedClassNames = $doctrineEntityHelper->getDataSourceEntityNamesForEntityManager($unsanitizedEntityManagerName);
                break;
            case 'diagram':
                $fullyQualifiedClassNames = $doctrineEntityHelper->getDiagramEntityNamesForEntityManager($unsanitizedEntityManagerName);
                break;
        }

        // Go through the fully qualified class names and get their short names.
        $generatedEntitiesInfo = array_map(function ($fullyQualifiedClassName) use ($doctrineEntityHelper) {
            $shortName = $doctrineEntityHelper->getShortClassName($fullyQualifiedClassName);
            $generatedEntitiesInfo = array(
                'name' => $shortName,
            );
            return $generatedEntitiesInfo;
        }, $fullyQualifiedClassNames);


        return $this->render('DataConsolidationCustomNodesBundle:GeneratedEntity:list_entities.html.twig', array(
            'generatedEntities' => $generatedEntitiesInfo,
            'sanitizedEntityManagerName' => $sanitized_entity_manager_name,
            'entityType' => $entity_type,
            'entityTypeDescription' => $this->getEntityTypeDescription($entity_type),
        ));
    }

    /**
     * Displays information about a custom generated doctrine entity.
     *
     * @param string $entity_type The custom doctrine entity type. Allowed values are:
     *  'data-source' and 'diagram'.
     * @param string $sanitized_entity_manager_name The sanitized entity manager name associated with the doctrine entity.
     * @param string $entity_name The name of the doctrine entity.
     */
    public function viewAction($entity_type, $sanitized_entity_manager_name, $entity_name)
    {
        $doctrineEntityHelper = $this->get('data_consolidation.custom_nodes.doctrine_entity_helper');
        $fullyQualifiedClassName = '';
        switch ($entity_type) {
            case 'data-source':
                $fullyQualifiedClassName = $doctrineEntityHelper->getFullyQualifiedDataSourceEntityClassName($entity_name, $sanitized_entity_manager_name, false);
                break;
            case 'diagram':
                $fullyQualifiedClassName = $doctrineEntityHelper->getFullyQualifiedDiagramEntityClassName($entity_name, $sanitized_entity_manager_name, false);
                break;
        }

        // Attempt to fetch the properties of the specified custom entity.
        $propertyNames = array();
        try {
            $propertyNames = $doctrineEntityHelper->getReflectionPropertyNames($fullyQualifiedClassName);
        }
        catch (\ReflectionException $e) {
            throw $this->createNotFoundException(sprintf("The entity with name '%s' and entity manager '%s' could not be found.", $entity_name, $sanitized_entity_manager_name), $e);
        }

        // Try to find if this entity has an existing NodeConfig object associated with it.
        $nodeConfigId = null;
        $nodeConfig = $doctrineEntityHelper->getNodeConfigForEntity($entity_name, $sanitized_entity_manager_name, false);
        if (!empty($nodeConfig)) {
            $nodeConfigId = $nodeConfig->getId();
        }

        return $this->render('DataConsolidationCustomNodesBundle:GeneratedEntity:view.html.twig', array(
            'unqualified_name' => $entity_name,
            'fully_qualified_name' => $fullyQualifiedClassName,
            'node_config_id' => $nodeConfigId,
            'properties' => $propertyNames,
            'entity_type' => $entity_type,
            'entityTypeDescription' => $this->getEntityTypeDescription($entity_type, true),
            'tab_items' => $this->getTabItems('view', $entity_type, $entity_name, $sanitized_entity_manager_name),
        ));
    }


    /**
     * Lists content of a doctrine entity.
     *
     * @param string $entity_type The custom doctrine entity type. Allowed values are:
     *  'data-source' and 'diagram'.
     * @param string $sanitized_entity_manager_name The sanitized entity manager name associated with the doctrine entity.
     * @param string $entity_name The name of the doctrine entity.
     * @param \Symfony\Component\HttpFoundation\Request $request;
     */
    public function listContentAction($entity_type, $sanitized_entity_manager_name, $entity_name, Request $request)
    {
        $page = $request->query->get('page', 1);
        if (intval($page) != $page || $page < 1) {
            throw new \Exception(sprintf("Invalid page: '%s'.", $page));
        }
        $doctrineEntityHelper = $this->get('data_consolidation.custom_nodes.doctrine_entity_helper');

        $fullyQualifiedClassName = '';
        switch ($entity_type) {
            case 'data-source':
                $fullyQualifiedClassName = $doctrineEntityHelper->getFullyQualifiedDataSourceEntityClassName($entity_name, $sanitized_entity_manager_name, false);
                break;
            case 'diagram':
                $fullyQualifiedClassName = $doctrineEntityHelper->getFullyQualifiedDiagramEntityClassName($entity_name, $sanitized_entity_manager_name, false);
                break;
        }

        // Get some text that describes this entity type.
        $entityTypeDescription = $this->getEntityTypeDescription($entity_type, true);

        // Check if the database for this entity exists.
        if (!$doctrineEntityHelper->entityDatabaseExists($fullyQualifiedClassName)) {
            return $this->render(':default:message.html.twig', array(
                'page_title' => sprintf('Missing database for %s: %s', $entityTypeDescription, $entity_name),
                'message' => 'The database for this entity does not exist.',
                'tab_items' => $this->getTabItems('list_content', $entity_type, $entity_name, $sanitized_entity_manager_name),
            ));
        }
        // Check if the table for this entity exists.
        elseif (!$doctrineEntityHelper->entityTableExists($fullyQualifiedClassName)) {
            return $this->render(':default:message.html.twig', array(
                'page_title' => sprintf('Missing database table for %s: %s', $entityTypeDescription, $entity_name),
                'message' => 'The database table for this entity does not exist.',
                'tab_items' => $this->getTabItems('list_content', $entity_type, $entity_name, $sanitized_entity_manager_name),
            ));
        }

        // Stores the field names that should be displayed in the content list page.
        $contentListFieldNames = array();
        // Stores the identifier field name that should be used for linking to a specific content.
        $identifierFieldName = null;

        // Try to find if this entity has an existing NodeConfig object associated with it.
        $nodeConfig = $doctrineEntityHelper->getNodeConfigForEntity($entity_name, $sanitized_entity_manager_name, false);
        if (!empty($nodeConfig)) {
            // Found a node config associated with this entity.
            // Check if it specifies any particular fields to be displayed in the content list.
            $contentListFieldNames = $nodeConfig->getContentListFieldNames(true);

            // There should also be primary fields for this node.
            $primaryKeyFieldNames = $nodeConfig->getPrimaryKeyFieldNames(true);
            if (!empty($primaryKeyFieldNames)) {
                // Got some primary keys.
                // Pick the first primary key as the identifier.
                $identifierFieldName = reset($primaryKeyFieldNames);
            }
        }

        // Get the getters for the generated doctrine entity class. Pick only the ones that are relevant for the content list page.
        $contentListReflGetterMethods = $doctrineEntityHelper->getReflectionGetters($fullyQualifiedClassName, $contentListFieldNames);
        // If there are multiple getters to be used per entity, prefix the values with the property name.
        $usePropertyNamePrefix = false;
        if (count($contentListReflGetterMethods) > 1) {
            $usePropertyNamePrefix = true;
        }

        // Attempt to obtain the getter for the identifier field.
        $identifierReflGetter = false;
        if (!empty($identifierFieldName)) {
            $identifierReflGetters = $doctrineEntityHelper->getReflectionGetters($fullyQualifiedClassName, array($identifierFieldName));
            $identifierReflGetter = reset($identifierReflGetters);
        }

        // Get the entity repository for the generated doctrine entity.
        $em = $this->getDoctrine()->getManagerForClass($fullyQualifiedClassName);

        // Get the maximum number of content list entries per page, specified in the bundle's parameters config file.
        $limit = $this->getParameter('content_list_entries_per_page');
        if (!is_int($limit) || $limit < 1) {
            // Invalid limit. Apply some sensible limit.
            $limit = 10;
        }
        // Calculate the offset based on the limit and the page number.
        $offset = ($page - 1) * $limit;
        // Get the total number of content entries.
        $totalCount = $doctrineEntityHelper->getEntityCount($em, $fullyQualifiedClassName);

        // Determine the sort criteria.
        $sortCriteria = null;
        if ($identifierFieldName) {
            $sortCriteria = array(
                $identifierFieldName => 'ASC',
            );
        }

        // Get the content.
        $repository = $em->getRepository($fullyQualifiedClassName);
        $content = $repository->findBy(array(), $sortCriteria, $limit, $offset);

        // Fetch the relevant content information to be displayed in the content list.
        $contentInfo = array();
        foreach ($content as $contentEntry) {
            // Build the content entry info.
            $contentEntryInfo = array(
                'contentListFields' => array(),
                'identifier' => null,
                'identifierFieldName' => null,
            );
            foreach ($contentListReflGetterMethods as $propertyName => $reflMethod) {
                $contentEntryInfo['contentListFields'][$propertyName] = $reflMethod->invoke($contentEntry);
            }

            if (!empty($identifierReflGetter)) {
                // An identifier getter is available. Fetch the identifier value.
                $contentEntryInfo['identifier'] = $identifierReflGetter->invoke($contentEntry);
                $contentEntryInfo['identifierFieldName'] = $identifierFieldName;
            }

            // Add the content entry info to the common array to be rendered in the template.
            $contentInfo[] = $contentEntryInfo;
        }

        // Get a pagination.
        $pagination = $this->getPagination($page, $limit, $totalCount);
        if (!empty($pagination)) {
            // Attach routes to pagination elements.
            $paginationRouteParams = array(
                'entity_type' => $entity_type,
                'sanitized_entity_manager_name' => $sanitized_entity_manager_name,
                'entity_name' => $entity_name,
            );
            $pagination = $this->attachRoutesToPagination($pagination, $page, 'data_consolidation.custom_nodes.generated_entities.list_content', $paginationRouteParams);
        }

        return $this->render('DataConsolidationCustomNodesBundle:GeneratedEntity:list_content.html.twig', array(
            'entity_type' => $entity_type,
            'sanitized_entity_manager_name' => $sanitized_entity_manager_name,
            'entity_name' => $entity_name,
            'entityTypeDescription' => $entityTypeDescription,
            'content_info' => $contentInfo,
            'use_property_name_prefix' => $usePropertyNamePrefix,
            'tab_items' => $this->getTabItems('list_content', $entity_type, $entity_name, $sanitized_entity_manager_name),
            'pagination' => $pagination,
        ));
    }

    /**
     * Displays a single content entry of a doctrine entity.
     *
     * @param string $entity_type The custom doctrine entity type. Allowed values are:
     *  'data-source' and 'diagram'.
     * @param string $sanitized_entity_manager_name The sanitized entity manager name associated with the doctrine entity.
     * @param string $entity_name The name of the doctrine entity.
     * @param string $identifier_name The name of the property by which the entry should be found.
     * @param mixed $identifier_value The value of the $identifier_name by which the entry should be found.
     */
    public function viewContentAction($entity_type, $sanitized_entity_manager_name, $entity_name, $identifier_name, $identifier_value)
    {
        $doctrineEntityHelper = $this->get('data_consolidation.custom_nodes.doctrine_entity_helper');
        $fullyQualifiedClassName = '';
        switch ($entity_type) {
            case 'data-source':
                $fullyQualifiedClassName = $doctrineEntityHelper->getFullyQualifiedDataSourceEntityClassName($entity_name, $sanitized_entity_manager_name, false);
                break;
            case 'diagram':
                $fullyQualifiedClassName = $doctrineEntityHelper->getFullyQualifiedDiagramEntityClassName($entity_name, $sanitized_entity_manager_name, false);
                break;
        }

        // Get the entity repository for the generated doctrine entity.
        $em = $this->getDoctrine()->getManagerForClass($fullyQualifiedClassName);
        $repository = $em->getRepository($fullyQualifiedClassName);

        // Get the property names of the entity.
        $propertyNames = $doctrineEntityHelper->getReflectionPropertyNames($fullyQualifiedClassName);
        // Try to find the correct property name, since the $identifier_name may have wrong letter cases.
        $index = array_search(strtolower($identifier_name), array_map('strtolower', $propertyNames));
        if ($index === false) {
            throw $this->createNotFoundException(sprintf("There is no valid property '%s' for entity with name '%s' and entity manager '%s'.", $identifier_name, $entity_name, $sanitized_entity_manager_name));
        }
        else {
            // Get the identifier name with proper letter cases.
            $identifier_name = $propertyNames[$index];
        }

        // Get the content.
        $contentEntry = $repository->findOneBy(array(
            $identifier_name => $identifier_value,
        ));

        if (empty($contentEntry)) {
            throw $this->createNotFoundException(sprintf("Could not find entry of entity with name '%s' and entity manager '%s'. Searched by field '%s' with value '%s'.", $entity_name, $sanitized_entity_manager_name, $identifier_name, $identifier_value));
        }

        // Build the content entry info that is to be rendered.
        $contentEntryInfo = array();
        // Get the getter methods.
        $reflGetterMethods = $doctrineEntityHelper->getReflectionGetters($fullyQualifiedClassName);
        foreach ($reflGetterMethods as $propertyName => $reflMethod) {
            $contentEntryInfo[$propertyName] = $reflMethod->invoke($contentEntry);
            if ($contentEntryInfo[$propertyName] instanceof \DateTime) {
                $contentEntryInfo[$propertyName] = $contentEntryInfo[$propertyName]->format('c');
            }
        }

        return $this->render('DataConsolidationCustomNodesBundle:GeneratedEntity:view_content.html.twig', array(
            'entity_type' => $entity_type,
            'content_entry_info' => $contentEntryInfo,
            'entity_name' => $entity_name,
            'entityTypeDescription' => $this->getEntityTypeDescription($entity_type, true),
        ));
    }

    /**
     * Confirmation form for deleting a generated doctrine entity.
     *
     * @param string $entity_type The custom doctrine entity type. Allowed values are:
     *  'data-source' and 'diagram'.
     * @param string $sanitized_entity_manager_name The sanitized entity manager name associated with the doctrine entity.
     * @param string $entity_name The name of the doctrine entity.
     */
    public function deleteAction($entity_type, $sanitized_entity_manager_name, $entity_name)
    {
        $doctrineEntityHelper = $this->get('data_consolidation.custom_nodes.doctrine_entity_helper');
        // A flag that determines if the generated doctrine entity exists.
        $entityExists = false;
        switch ($entity_type) {
            case 'data-source':
                $entityExists = $doctrineEntityHelper->dataSourceEntityExists($entity_name, $sanitized_entity_manager_name, false);
                break;
            case 'diagram':
                $entityExists = $doctrineEntityHelper->diagramEntityExists($entity_name, $sanitized_entity_manager_name, false);
                break;
        }

        if (!$entityExists) {
            throw $this->createNotFoundException(sprintf("The entity with name '%s' and entity manager '%s' could not be found.", $entity_name, $sanitized_entity_manager_name));
        }

        // Use the contrib ConfirmBundle to display a confirmation for the deletion.
        $options = array(
            'message' => sprintf("Are you sure you want to delete the %s '%s'?", $this->getEntityTypeDescription($entity_type, true), $entity_name),
            'warning' => 'The deletion cannot be undone! However, if there is an existing node configuration for this entity, the doctrine entity could be re-generated from it.',
            'confirm_button_text' => 'Delete',
            'confirm_action' => array($this, 'delete'),
            'confirm_action_args' => array(
                'entity_type' => $entity_type,
                'entity_name' => $entity_name,
                'sanitized_entity_manager_name' => $sanitized_entity_manager_name,
            ),
            'cancel_link_text' => 'Cancel',
            'cancel_url' => $this->generateUrl('data_consolidation.custom_nodes.generated_entities.view', array(
                'entity_type' => $entity_type,
                'sanitized_entity_manager_name' => $sanitized_entity_manager_name,
                'entity_name' => $entity_name,
            )),
        );

        return $this->forward('ConfirmBundle:Confirm:confirm', array('options' => $options));
    }

    /**
     * Helper for the deleteAction confirmation.
     *
     * Handles the actual deletion of a generated doctrine entity PHP class file, once the user has confirmed that is what they intend to do.
     *
     * @param array $args Arguments forwarded from the deletion confirmation. It is expected that this contains the following keys:
     *  'entity_name' => The name of the doctrine entity.
     *  'sanitized_entity_manager_name' => The sanitized entity manager name associated with the doctrine entity.
     *  'entity_type' => The doctrine entity type. Allowed values are 'data-source' and 'diagram'.
     */
    public function delete($args)
    {
        if (empty($args['entity_name']) || empty($args['sanitized_entity_manager_name']) || empty($args['entity_type'])) {
            throw $this->createNotFoundException('Invalid deletion arguments.');
        }

        $entityName = $args['entity_name'];
        $sanitizedEntityManagerName = $args['sanitized_entity_manager_name'];
        $entityType = $args['entity_type'];
        // Get some text that describes this entity type.
        $entityTypeDescription = $this->getEntityTypeDescription($entityType, true);

        $doctrineEntityHelper = $this->get('data_consolidation.custom_nodes.doctrine_entity_helper');
        // Delete the generated PHP doctrine entity class.
        switch ($entityType) {
            case 'data-source':
                $doctrineEntityHelper->deleteDataSourceEntity($entityName, $sanitizedEntityManagerName, false);
                break;
            case 'diagram':
                $doctrineEntityHelper->deleteDiagramEntity($entityName, $sanitizedEntityManagerName, false);
                break;
        }

        // Add a flash message marking the successful doctrine entity deletion.
        $this->addFlash('notice', sprintf("Deleted the %s '%s' of entity manager '%s'.", $entityTypeDescription, $entityName, $sanitizedEntityManagerName));

        return $this->redirectToRoute('data_consolidation.custom_nodes.generated_entities.list', array(
            'entity_type' => $entityType,
        ));
    }

    /**
     * Displays the confirmation pages for creating a database, creating a table or updating a table for an entity, depending on the current database state.
     *
     * @param string $entity_type The custom doctrine entity type. Allowed values are:
     *  'data-source' and 'diagram'.
     * @param string $sanitized_entity_manager_name The sanitized entity manager name associated with the doctrine entity.
     * @param string $entity_name The name of the doctrine entity.
     */
    public function updateDatabaseSchemaAction($entity_type, $sanitized_entity_manager_name, $entity_name)
    {
        // Get the namespaced class name for this entity.
        $doctrineEntityHelper = $this->get('data_consolidation.custom_nodes.doctrine_entity_helper');
        $fullyQualifiedClassName = '';
        switch ($entity_type) {
            case 'data-source':
                $fullyQualifiedClassName = $doctrineEntityHelper->getFullyQualifiedDataSourceEntityClassName($entity_name, $sanitized_entity_manager_name, false);
                break;
            case 'diagram':
                $fullyQualifiedClassName = $doctrineEntityHelper->getFullyQualifiedDiagramEntityClassName($entity_name, $sanitized_entity_manager_name, false);
                break;
        }

        // Get some text that describes this entity type.
        $entityTypeDescription = $this->getEntityTypeDescription($entity_type, true);

        // Check if the database for this entity exists.
        if (!$doctrineEntityHelper->entityDatabaseExists($fullyQualifiedClassName)) {
            // The database needs to be created.

            // Use the contrib ConfirmBundle to display a confirmation for the database creation.
            $options = array(
                'message' => sprintf("The database for %s '%s' does not exists. Would you like to create it?", $entityTypeDescription, $entity_name),
                'confirm_button_text' => 'Create database',
                'confirm_action' => array($this, 'createDatabase'),
                'confirm_action_args' => array(
                    'entity_type' => $entity_type,
                    'sanitized_entity_manager_name' => $sanitized_entity_manager_name,
                    'entity_name' => $entity_name,
                ),
                'cancel_link_text' => 'Cancel',
                'cancel_url' => $this->generateUrl('data_consolidation.custom_nodes.generated_entities.view', array(
                    'entity_type' => $entity_type,
                    'sanitized_entity_manager_name' => $sanitized_entity_manager_name,
                    'entity_name' => $entity_name,
                )),
            );

            return $this->forward('ConfirmBundle:Confirm:confirm', array('options' => $options));
        }

        // Check if the table for this entity exists.
        if ($doctrineEntityHelper->entityTableExists($fullyQualifiedClassName)) {
            // The table exists. Get the queries to check if it needs to be updated.
            $queries = $doctrineEntityHelper->getUpdateSchemaSql($fullyQualifiedClassName);
            if (empty($queries)) {
                // The database schema is up-to-date. Just render a message that indicates that.
                return $this->render(':default:message.html.twig', array(
                    'page_title' => sprintf('Update database schema for %s: %s', $entityTypeDescription, $entity_name),
                    'message' => 'The database schema is up-to-date.',
                    'tab_items' => $this->getTabItems('update_db_schema', $entity_type, $entity_name, $sanitized_entity_manager_name),
                ));
            }
            else {
                // Use the contrib ConfirmBundle to display a confirmation for the table update.
                $options = array(
                    'message' => sprintf("Would you like to update the database table for %s '%s'?", $entityTypeDescription, $entity_name),
                    'warning' => $this->getQueriesWarningMessage($queries, 'If the update fails that may be due to conflicting data. You may need to drop the database table and re-create it.'),
                    'confirm_button_text' => 'Update database table',
                    'confirm_action' => array($this, 'updateDatabaseTable'),
                    'confirm_action_args' => array(
                        'entity_type' => $entity_type,
                        'sanitized_entity_manager_name' => $sanitized_entity_manager_name,
                        'entity_name' => $entity_name,
                    ),
                    'cancel_link_text' => 'Cancel',
                    'cancel_url' => $this->generateUrl('data_consolidation.custom_nodes.generated_entities.view', array(
                        'entity_type' => $entity_type,
                        'sanitized_entity_manager_name' => $sanitized_entity_manager_name,
                        'entity_name' => $entity_name,
                    )),
                );

                return $this->forward('ConfirmBundle:Confirm:confirm', array('options' => $options));
            }
        }
        else {
            // The table does not exist. Get the queries for creating it.
            $queries = $doctrineEntityHelper->getCreateSchemaSql($fullyQualifiedClassName);

            // Use the contrib ConfirmBundle to display a confirmation for the table creation.
            $options = array(
                'message' => sprintf("Would you like to create the database table for %s '%s'?", $entityTypeDescription, $entity_name),
                'warning' => $this->getQueriesWarningMessage($queries),
                'confirm_button_text' => 'Create database table',
                'confirm_action' => array($this, 'createDatabaseTable'),
                'confirm_action_args' => array(
                    'entity_type' => $entity_type,
                    'sanitized_entity_manager_name' => $sanitized_entity_manager_name,
                    'entity_name' => $entity_name,
                ),
                'cancel_link_text' => 'Cancel',
                'cancel_url' => $this->generateUrl('data_consolidation.custom_nodes.generated_entities.view', array(
                    'entity_type' => $entity_type,
                    'sanitized_entity_manager_name' => $sanitized_entity_manager_name,
                    'entity_name' => $entity_name,
                )),
            );

            return $this->forward('ConfirmBundle:Confirm:confirm', array('options' => $options));
        }
    }

    /**
     * Helper for the updateDatabaseSchemaAction confirmation.
     *
     * Handles creating a database for a doctrine entity.
     * NOTE: It is expected that it has been checked that the database does not exist.
     *
     * @param array $args Arguments forwarded from the updateDatabaseSchemaAction confirmation. It is expected that this contains the following keys:
     *  'entity_name' => The name of the doctrine entity.
     *  'sanitized_entity_manager_name' => The sanitized entity manager name associated with the doctrine entity.
     *  'entity_type' => The doctrine entity type. Allowed values are 'data-source' and 'diagram'.
     */
    public function createDatabase($args)
    {
        if (empty($args['sanitized_entity_manager_name']) || empty($args['entity_name']) || empty($args['entity_type'])) {
            throw $this->createNotFoundException('Invalid database creation arguments.');
        }

        $sanitizedEntityManagerName = $args['sanitized_entity_manager_name'];
        $entityName = $args['entity_name'];
        $entityType = $args['entity_type'];
        // Get some text that describes this entity type.
        $entityTypeDescription = $this->getEntityTypeDescription($entityType, true);

        // Get the namespaced class name for this entity.
        $doctrineEntityHelper = $this->get('data_consolidation.custom_nodes.doctrine_entity_helper');
        $fullyQualifiedClassName = '';
        switch ($entityType) {
            case 'data-source':
                $fullyQualifiedClassName = $doctrineEntityHelper->getFullyQualifiedDataSourceEntityClassName($entityName, $sanitizedEntityManagerName, false);
                break;
            case 'diagram':
                $fullyQualifiedClassName = $doctrineEntityHelper->getFullyQualifiedDiagramEntityClassName($entityName, $sanitizedEntityManagerName, false);
                break;
        }

        // Create the database.
        $doctrineEntityHelper->createDatabase($fullyQualifiedClassName);

        // Add a flash message marking the successful database creation.
        $this->addFlash('notice', sprintf("Created the database for the %s '%s' of entity manager '%s'.", $entityTypeDescription, $entityName, $sanitizedEntityManagerName));

        return $this->redirectToRoute('data_consolidation.custom_nodes.generated_entities.update_db_schema', array(
            'entity_type' => $entityType,
            'sanitized_entity_manager_name' => $sanitizedEntityManagerName,
            'entity_name' => $entityName,
        ));
    }

    /**
     * Helper for the updateDatabaseSchemaAction confirmation.
     *
     * Handles updating a database table for a doctrine entity.
     * NOTE: It is expected that it has been checked that the table exists.
     *
     * @param array $args Arguments forwarded from the updateDatabaseSchemaAction confirmation. It is expected that this contains the following keys:
     *  'entity_name' => The name of the doctrine entity.
     *  'sanitized_entity_manager_name' => The sanitized entity manager name associated with the doctrine entity.
     *  'entity_type' => The doctrine entity type. Allowed values are 'data-source' and 'diagram'.
     */
    public function updateDatabaseTable($args)
    {
        if (empty($args['sanitized_entity_manager_name']) || empty($args['entity_name']) || empty($args['entity_type'])) {
            throw $this->createNotFoundException('Invalid database table update arguments.');
        }

        $sanitizedEntityManagerName = $args['sanitized_entity_manager_name'];
        $entityName = $args['entity_name'];
        $entityType = $args['entity_type'];
        // Get some text that describes this entity type.
        $entityTypeDescription = $this->getEntityTypeDescription($entityType, true);

        // Get the namespaced class name for this entity.
        $doctrineEntityHelper = $this->get('data_consolidation.custom_nodes.doctrine_entity_helper');
        $fullyQualifiedClassName = '';
        switch ($entityType) {
            case 'data-source':
                $fullyQualifiedClassName = $doctrineEntityHelper->getFullyQualifiedDataSourceEntityClassName($entityName, $sanitizedEntityManagerName, false);
                break;
            case 'diagram':
                $fullyQualifiedClassName = $doctrineEntityHelper->getFullyQualifiedDiagramEntityClassName($entityName, $sanitizedEntityManagerName, false);
                break;
        }

        // Update the database table schema.
        $doctrineEntityHelper->updateSchema($fullyQualifiedClassName);

        // Add a flash message marking the successful database table update.
        $this->addFlash('notice', sprintf("Updated the database table for the %s '%s' of entity manager '%s'.", $entityTypeDescription, $entityName, $sanitizedEntityManagerName));

        return $this->redirectToRoute('data_consolidation.custom_nodes.generated_entities.update_db_schema', array(
            'entity_type' => $entityType,
            'sanitized_entity_manager_name' => $sanitizedEntityManagerName,
            'entity_name' => $entityName,
        ));
    }

    /**
     * Helper for the updateDatabaseSchemaAction confirmation.
     *
     * Handles creating a database table for a doctrine entity.
     * NOTE: It is expected that it has been checked that the table does not exist.
     *
     * @param array $args Arguments forwarded from the updateDatabaseSchemaAction confirmation. It is expected that this contains the following keys:
     *  'entity_name' => The name of the doctrine entity.
     *  'sanitized_entity_manager_name' => The sanitized entity manager name associated with the doctrine entity.
     *  'entity_type' => The doctrine entity type. Allowed values are 'data-source' and 'diagram'.
     */
    public function createDatabaseTable($args)
    {
        if (empty($args['sanitized_entity_manager_name']) || empty($args['entity_name'])) {
            throw $this->createNotFoundException('Invalid database table creation arguments.');
        }

        $sanitizedEntityManagerName = $args['sanitized_entity_manager_name'];
        $entityName = $args['entity_name'];
        $entityType = $args['entity_type'];
        // Get some text that describes this entity type.
        $entityTypeDescription = $this->getEntityTypeDescription($entityType, true);

        // Get the namespaced class name for this entity.
        $doctrineEntityHelper = $this->get('data_consolidation.custom_nodes.doctrine_entity_helper');
        $fullyQualifiedClassName = '';
        switch ($entityType) {
            case 'data-source':
                $fullyQualifiedClassName = $doctrineEntityHelper->getFullyQualifiedDataSourceEntityClassName($entityName, $sanitizedEntityManagerName, false);
                break;
            case 'diagram':
                $fullyQualifiedClassName = $doctrineEntityHelper->getFullyQualifiedDiagramEntityClassName($entityName, $sanitizedEntityManagerName, false);
                break;
        }

        // Create the database table.
        $doctrineEntityHelper->createSchema($fullyQualifiedClassName);

        // Add a flash message marking the successful database table creation.
        $this->addFlash('notice', sprintf("Created the database table for the %s '%s' of entity manager '%s'.", $entityTypeDescription, $entityName, $sanitizedEntityManagerName));

        return $this->redirectToRoute('data_consolidation.custom_nodes.generated_entities.update_db_schema', array(
            'entity_type' => $entityType,
            'sanitized_entity_manager_name' => $sanitizedEntityManagerName,
            'entity_name' => $entityName,
        ));
    }

    /**
     * Displays the confirmation pages for deleting a table or dropping a database.
     *
     * @param string $entity_type The custom doctrine entity type. Allowed values are:
     *  'data-source' and 'diagram'.
     * @param string $sanitized_entity_manager_name The sanitized entity manager name associated with the doctrine entity.
     * @param string $entity_name The name of the doctrine entity.
     */
    public function deleteDatabaseSchemaAction($entity_type, $sanitized_entity_manager_name, $entity_name)
    {
        // Get the namespaced class name for this entity.
        $doctrineEntityHelper = $this->get('data_consolidation.custom_nodes.doctrine_entity_helper');
        $fullyQualifiedClassName = '';
        switch ($entity_type) {
            case 'data-source':
                $fullyQualifiedClassName = $doctrineEntityHelper->getFullyQualifiedDataSourceEntityClassName($entity_name, $sanitized_entity_manager_name, false);
                break;
            case 'diagram':
                $fullyQualifiedClassName = $doctrineEntityHelper->getFullyQualifiedDiagramEntityClassName($entity_name, $sanitized_entity_manager_name, false);
                break;
        }

        // Get some text that describes this entity type.
        $entityTypeDescription = $this->getEntityTypeDescription($entity_type, true);

        // Check if the database for this entity exists.
        if ($doctrineEntityHelper->entityDatabaseExists($fullyQualifiedClassName)) {
            // The database exists.

            // Check if the table for this entity exists.
            if ($doctrineEntityHelper->entityTableExists($fullyQualifiedClassName)) {
                // The table exists. Ask the user if they want to drop the table.

                // Use the contrib ConfirmBundle to display a confirmation for the table deletion.
                $options = array(
                    'message' => sprintf("Would you like to drop the database table for %s '%s' managed by entity manager '%s'?", $entityTypeDescription, $entity_name, $sanitized_entity_manager_name),
                    'warning' => 'All of the data for this entity will be lost! This action cannot be undone!',
                    'confirm_button_text' => 'Drop database table',
                    'confirm_action' => array($this, 'dropDatabaseTable'),
                    'confirm_action_args' => array(
                        'entity_type' => $entity_type,
                        'sanitized_entity_manager_name' => $sanitized_entity_manager_name,
                        'entity_name' => $entity_name,
                    ),
                    'cancel_link_text' => 'Cancel',
                    'cancel_url' => $this->generateUrl('data_consolidation.custom_nodes.generated_entities.view', array(
                        'entity_type' => $entity_type,
                        'sanitized_entity_manager_name' => $sanitized_entity_manager_name,
                        'entity_name' => $entity_name,
                    )),
                );

                return $this->forward('ConfirmBundle:Confirm:confirm', array('options' => $options));
            }
            else {
                // The table does not exist.
                if ($sanitized_entity_manager_name == DoctrineEntityHelper::DEFAULT_ENTITY_MANAGER_SANITIZED_NAME) {
                    // Display a message that it's not allowed to drop this database.
                    return $this->render(':default:message.html.twig', array(
                        'page_title' => sprintf('Delete database schema for %s: %s', $entityTypeDescription, $entity_name),
                        'message' => 'It is not allowed to drop the default database.',
                        'tab_items' => $this->getTabItems('delete_db_schema', $entity_type, $entity_name, $sanitized_entity_manager_name),
                    ));
                }
                else {
                    // Ask the user if they want to drop the database.
                    // Use the contrib ConfirmBundle to display a confirmation for dropping the database.
                    $options = array(
                        'message' => sprintf("Would you like to drop the database for %s '%s' managed by entity manager '%s'?", $entityTypeDescription, $entity_name, $sanitized_entity_manager_name),
                        'warning' => 'All of the data from the database will be lost! This includes data of other entities, as well as data that may be originating from other sources than this website! This action cannot be undone!',
                        'confirm_button_text' => 'Drop database',
                        'confirm_action' => array($this, 'dropDatabase'),
                        'confirm_action_args' => array(
                            'entity_type' => $entity_type,
                            'sanitized_entity_manager_name' => $sanitized_entity_manager_name,
                            'entity_name' => $entity_name,
                        ),
                        'cancel_link_text' => 'Cancel',
                        'cancel_url' => $this->generateUrl('data_consolidation.custom_nodes.generated_entities.view', array(
                            'entity_type' => $entity_type,
                            'sanitized_entity_manager_name' => $sanitized_entity_manager_name,
                            'entity_name' => $entity_name,
                        )),
                    );

                    return $this->forward('ConfirmBundle:Confirm:confirm', array('options' => $options));
                }
            }
        }
        else {
            // Neither the table, nor the database for this entity exist. Just render a message that indicates that.
            return $this->render(':default:message.html.twig', array(
                'page_title' => sprintf('Delete database schema for %s: %s', $entityTypeDescription, $entity_name),
                'message' => 'There is no table or database created for this entity.',
                'tab_items' => $this->getTabItems('delete_db_schema', $entity_type, $entity_name, $sanitized_entity_manager_name),
            ));
        }
    }

    /**
     * Helper for the deleteDatabaseSchemaAction confirmation.
     *
     * Handles deleting a database for a doctrine entity.
     * NOTE: It is expected that it has been checked that the database exists.
     *
     * @param array $args Arguments forwarded from the deleteDatabaseSchemaAction confirmation. It is expected that this contains the following keys:
     *  'entity_name' => The name of the doctrine entity.
     *  'sanitized_entity_manager_name' => The sanitized entity manager name associated with the doctrine entity.
     *  'entity_type' => The doctrine entity type. Allowed values are 'data-source' and 'diagram'.
     */
    public function dropDatabase($args)
    {
        if (empty($args['sanitized_entity_manager_name']) || empty($args['entity_name']) || empty($args['entity_type'])) {
            throw $this->createNotFoundException('Invalid drop drabase arguments.');
        }

        $sanitizedEntityManagerName = $args['sanitized_entity_manager_name'];
        $entityName = $args['entity_name'];
        $entityType = $args['entity_type'];
        $entityTypeDescription = $this->getEntityTypeDescription($entityType, true);

        // Get the namespaced class name for this entity.
        $doctrineEntityHelper = $this->get('data_consolidation.custom_nodes.doctrine_entity_helper');
        $fullyQualifiedClassName = '';
        switch ($entityType) {
            case 'data-source':
                $fullyQualifiedClassName = $doctrineEntityHelper->getFullyQualifiedDataSourceEntityClassName($entityName, $sanitizedEntityManagerName, false);
                break;
            case 'diagram':
                $fullyQualifiedClassName = $doctrineEntityHelper->getFullyQualifiedDiagramEntityClassName($entityName, $sanitizedEntityManagerName, false);
                break;
        }

        // Drop the database.
        $doctrineEntityHelper->dropDatabase($fullyQualifiedClassName);

        // Add a flash message marking the successful database deletion.
        $this->addFlash('notice', sprintf("Dropped the database for the %s '%s' of entity manager '%s'.", $entityTypeDescription, $entityName, $sanitizedEntityManagerName));

        return $this->redirectToRoute('data_consolidation.custom_nodes.generated_entities.delete_db_schema', array(
            'entity_type' => $entityType,
            'sanitized_entity_manager_name' => $sanitizedEntityManagerName,
            'entity_name' => $entityName,
        ));
    }

    /**
     * Helper for the deleteDatabaseSchemaAction confirmation.
     *
     * Handles deleting a database table for a doctrine entity.
     * NOTE: It is expected that it has been checked that the table exists.
     *
     * @param array $args Arguments forwarded from the deleteDatabaseSchemaAction confirmation. It is expected that this contains the following keys:
     *  'entity_name' => The name of the doctrine entity.
     *  'sanitized_entity_manager_name' => The sanitized entity manager name associated with the doctrine entity.
     *  'entity_type' => The doctrine entity type. Allowed values are 'data-source' and 'diagram'.
     */
    public function dropDatabaseTable($args)
    {
        if (empty($args['sanitized_entity_manager_name']) || empty($args['entity_name']) || empty($args['entity_type'])) {
            throw $this->createNotFoundException('Invalid drop database table arguments.');
        }

        $sanitizedEntityManagerName = $args['sanitized_entity_manager_name'];
        $entityName = $args['entity_name'];
        $entityType = $args['entity_type'];
        $entityTypeDescription = $this->getEntityTypeDescription($entityType, true);

        // Get the namespaced class name for this entity.
        $doctrineEntityHelper = $this->get('data_consolidation.custom_nodes.doctrine_entity_helper');
        $fullyQualifiedClassName = '';
        switch ($entityType) {
            case 'data-source':
                $fullyQualifiedClassName = $doctrineEntityHelper->getFullyQualifiedDataSourceEntityClassName($entityName, $sanitizedEntityManagerName, false);
                break;
            case 'diagram':
                $fullyQualifiedClassName = $doctrineEntityHelper->getFullyQualifiedDiagramEntityClassName($entityName, $sanitizedEntityManagerName, false);
                break;
        }

        // Drop the database table.
        $doctrineEntityHelper->dropSchema($fullyQualifiedClassName);

        // Add a flash message marking the successful database table deletion.
        $this->addFlash('notice', sprintf("Dropped the database table for the %s '%s' of entity manager '%s'.", $entityTypeDescription, $entityName, $sanitizedEntityManagerName));

        return $this->redirectToRoute('data_consolidation.custom_nodes.generated_entities.delete_db_schema', array(
            'entity_type' => $entityType,
            'sanitized_entity_manager_name' => $sanitizedEntityManagerName,
            'entity_name' => $entityName,
        ));
    }

    /**
     * Fetches tab items for the use in twig templates that mark different actions for a custom generated doctrine entity.
     *
     * @param string $currentAction The current action, e.g. 'view' or 'edit'.
     * @param string $entity_type The custom doctrine entity type. Allowed values are:
     *  'data-source' and 'diagram'.
     * @param int $entityName The custom generated doctrine entity name.
     * @param string $sanitizedEntityManagerName The sanitized entity manager name associated with the doctrine entity.
     *
     * @return array Tab items formatted in the way expected in the twig templates.
     */
    private function getTabItems($currentAction, $entity_type, $entityName, $sanitizedEntityManagerName)
    {
        $tabItems = array(
            'view' => array(
                'url' => $this->generateUrl('data_consolidation.custom_nodes.generated_entities.view', array(
                    'entity_type' => $entity_type,
                    'sanitized_entity_manager_name' => $sanitizedEntityManagerName,
                    'entity_name' => $entityName,
                )),
                'name' => 'View',
            ),
            'list_content' => array(
                'url' => $this->generateUrl('data_consolidation.custom_nodes.generated_entities.list_content', array(
                    'entity_type' => $entity_type,
                    'sanitized_entity_manager_name' => $sanitizedEntityManagerName,
                    'entity_name' => $entityName,
                )),
                'name' => 'List content',
            ),
            'delete' => array(
                'url' => $this->generateUrl('data_consolidation.custom_nodes.generated_entities.delete', array(
                    'entity_type' => $entity_type,
                    'sanitized_entity_manager_name' => $sanitizedEntityManagerName,
                    'entity_name' => $entityName,
                )),
                'name' => 'Delete',
            ),
            'update_db_schema' => array(
                'url' => $this->generateUrl('data_consolidation.custom_nodes.generated_entities.update_db_schema', array(
                    'entity_type' => $entity_type,
                    'sanitized_entity_manager_name' => $sanitizedEntityManagerName,
                    'entity_name' => $entityName,
                )),
                'name' => 'Update database schema',
            ),
            'delete_db_schema' => array(
                'url' => $this->generateUrl('data_consolidation.custom_nodes.generated_entities.delete_db_schema', array(
                    'entity_type' => $entity_type,
                    'sanitized_entity_manager_name' => $sanitizedEntityManagerName,
                    'entity_name' => $entityName,
                )),
                'name' => 'Delete database schema',
            ),
        );

        if ($entity_type == 'diagram') {
            // Add an extra tab item for diagrams.
            $tabItems['list_data_source_to_diagram_mappings'] = array(
                'url' => $this->generateUrl('data_consolidation.custom_nodes.data_source_to_diagram_mapping.list_for_diagram', array(
                    'sanitized_entity_manager_name' => $sanitizedEntityManagerName,
                    'entity_name' => $entityName,
                )),
                'name' => 'Data source to diagram mappings'
            );
        }

        // Mark the current action as active, if it is a valid one.
        $currentAction = strtolower($currentAction);
        if (!empty($tabItems[$currentAction])) {
            $tabItems[$currentAction]['active'] = TRUE;
            // Also change the URL to '#' since the user is on that page already.
            $tabItems[$currentAction]['url'] = '#';
        }

        return $tabItems;
    }

    /**
     * Creates a warning message for the confirmation page for creating or updating entity db table.
     *
     * @param array $queries The queries to be executed for the table creation or update.
     * @param string|null $message An optional message to be printed first.
     * @return string The confirmation warning message.
     */
    private function getQueriesWarningMessage(array $queries, $message = null)
    {
        $messageComponents = $queries;
        // Prepend a message before the queries.
        array_unshift($messageComponents, 'The following SQL statements will be executed:');

        if ($message) {
            // An additional message was specified as a parameter. Prepend it to the beginning.
            array_unshift($messageComponents, $message);
        }

        $message = implode("<br>\n", $messageComponents);
        return $message;
    }

    /**
     * Creates pagination elements to be rendered to a template.
     *
     * NOTE: The pagination will not include routes. This is done to make it more reusable.
     * Use the attachRoutesToPagination() method to add routes.
     *
     * @param int $page The current page.
     * @param int $limit The maximum elements to display per page.
     * @param int $totalCount The total amount of elements.
     * @return array|null A pagination elements array if pagination is necessary, or null otherwise.
     */
    private function getPagination($page, $limit, $totalCount)
    {
        // Convert the parameters to integers to be sure the arithmetics work as expected.
        $page = intval($page);
        $limit = intval($limit);
        if ($limit == 0) {
            // Limit should not be 0. Division by 0 is not allowed.
            $limit = 1;
        }
        $totalCount = intval($totalCount);

        if ($limit >= $totalCount) {
            // All entries can be displayed on a single page.
            return null;
        }

        // Determine the total number of pages by dividing the total by the limit, and rounding up the result.
        $totalPages = intval(ceil($totalCount / $limit));

        $pages = array(
            1,
            2,
            $page - 1,
            $page,
            $page + 1,
            $totalPages - 1,
            $totalPages,
        );

        // Remove duplicate pages.
        $pages = array_unique($pages);
        // Remove pages smaller than the first page and larger than the last page.
        $pages = array_filter($pages, function($page) use ($totalPages) {
           if ($page < 1 || $page > $totalPages) {
               return false;
           }
            else {
                return true;
            }
        });
        // Re-index the pages array since it should not be sparse.
        $pages = array_values($pages);

        // Go through all pages and if the difference between them is more than 1, add a '..' element.
        $pagination = array();
        $pagesArrCount = count($pages);
        for ($i = 0; $i < $pagesArrCount; $i++) {
            // Add the current page to the pagination array.
            $pagination[] = $pages[$i];
            // If there is another page, and if the next page is not greater than the current page by only 1, add '..' element.
            if (($i + 1) < $pagesArrCount && ($pages[$i] + 1) < $pages[$i + 1]) {
                $pagination[] = '...';
            }
        }

        // Prepend a 'previous' page if this is not the first page.
        if ($page > 1) {
            array_unshift($pagination, 'previous');
        }
        // Append a 'next' page if this is not the last page.
        if ($page < $totalPages) {
            $pagination[] = 'next';
        }

        return $pagination;
    }

    /**
     * Attaches routes to pagination elements.
     *
     * NOTE: The pagination elements are expected to be in the format returned by the getPagination() method.
     *
     * @param array $pagination The pagination elements array.
     * @param int $page The current page.
     * @param string $route The Symfony route that the pages should link to.
     * @param array $routeParams The common parameters needed for building the routes.
     * @return array of pagination elements, each having the following elements:
     *  'name' => The page number or label.
     *  'url' => The url to the specified route, or null if no route should be applied for that element.
     */
    private function attachRoutesToPagination(array $pagination, $page, $route, array $routeParams)
    {
        // Add urls to the pagination labels.
        $paginationWithRoutes = array();
        foreach ($pagination as $paginationName) {
            $paginationElement = array(
                'name' => $paginationName,
                'url' => null,
            );

            // Make sure there are no URLs attached to ellipsis pagination elements or to the current page element.
            if ($paginationName != '...' && $paginationName != $page) {
                // The pagination name identifies the actual page number, unless it is 'previous' or 'next'.
                $pageNumber = $paginationName;
                if ($pageNumber == 'previous') {
                    $pageNumber = $page - 1;
                } elseif ($pageNumber == 'next') {
                    $pageNumber = $page + 1;
                }

                // Copy the route params and add an extra one for the page.
                $urlParams = $routeParams;
                $urlParams['page'] = $pageNumber;
                $url = $this->generateUrl($route, $urlParams);
                $paginationElement['url'] = $url;
            }

            $paginationWithRoutes[] = $paginationElement;
        }

        return $paginationWithRoutes;
    }

    /**
     * Returns an entity type description normally visualized in the template.
     *
     * @param string $entity_type The custom doctrine entity type. Allowed values are:
     *  'data-source' and 'diagram'.
     * @param bool $singular optional If set to TRUE plural noun forms will be used. Otherwise singular forms will be used.
     * @return string The entity type description. normally visualized in the template.
     */
    private function getEntityTypeDescription($entity_type, $singular = false)
    {
        $prefix = '';
        switch ($entity_type) {
            case 'data-source':
                $prefix = 'data source ';
                break;
            case 'diagram':
                $prefix = 'diagram ';
                break;
        }

        if ($singular) {
            // Use singular forms.
            return ($prefix . 'doctrine entity');
        }
        else {
            // Use plural forms.
            return ($prefix . 'doctrine entities');
        }
    }

}
