<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 4/19/17
 * Time: 12:59 PM
 */

namespace DataConsolidation\CustomNodesBundle\Utils;


use DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\ConsolidationState;
use DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\DataSourceToDiagramMapping;
use DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\DiagramConfig;

class DoctrineEntityMappingHelper extends DoctrineEntityHelper
{
    /**
     * TODO
     *
     * @param DataSourceToDiagramMapping $dataSourceToDiagramMapping
     * @param $dataSourceEntity
     * @param $diagramEntity
     */
    protected function translateDataSourceToDiagramEntity(DataSourceToDiagramMapping $dataSourceToDiagramMapping, $dataSourceEntity, $diagramEntity)
    {
        // Obtain the fully qualified class names for the data source and diagram entities.
        $dataSourceFullyQualifiedClassName = get_class($dataSourceEntity);
        $diagramFullyQualifiedClassName = get_class($diagramEntity);

        // Go through all field mappings and set the relevant values to the diagram entity.
        $fieldMappings = $dataSourceToDiagramMapping->getFieldMappings();
        if (!empty($fieldMappings)) {
            foreach ($fieldMappings as $fieldMapping) {
                // Get the getter and setter names.
                $dataSourceGetterName = $fieldMapping->getSourceGetter();
                $diagramSetterName = $fieldMapping->getTargetSetter();

                // Make sure that the getter name is valid.
                if (!is_callable(array($dataSourceEntity, $dataSourceGetterName))) {
                    throw new \Exception(sprintf("The data source entity '%s' does not have a callable getter method '%s", $dataSourceFullyQualifiedClassName, $dataSourceGetterName));
                }
                // Make sure that the setter is valid.
                if (!is_callable(array($diagramEntity, $diagramSetterName))) {
                    throw new \Exception(sprintf("The data source entity '%s' does not have a callable setter method '%s", $diagramFullyQualifiedClassName, $diagramSetterName));
                }

                // Fetch the value through the getter.
                $dataSourceFieldValue = call_user_func(array($dataSourceEntity, $dataSourceGetterName));
                // Set the value through the setter.
                call_user_func(array($diagramEntity, $diagramSetterName), $dataSourceFieldValue);
            }
        }

        // Set the diagram entity source to the fully qualified name of the data source entity.
        $this->setDiagramEntityDataSource($diagramEntity, $dataSourceFullyQualifiedClassName);
        // Mark that the diagram entity's consolidation type is NONE (this is not a resampled value).
        $this->setDiagramEntityConsolidationType($diagramEntity, ConsolidationState::CONSOLIDATION_TYPE_NONE);
    }

    /**
     * TODO
     *
     * @param DataSourceToDiagramMapping $dataSourceToDiagramMapping
     * @param int $limit
     */
    public function translateDataSourceToDiagramEntities(DataSourceToDiagramMapping $dataSourceToDiagramMapping, $limit = 0)
    {
        if (!is_int($limit) || $limit < 0) {
            throw new \Exception(sprintf("Invalid limit %s. Expected a non-negative integer.", $limit));
        }

        if ($limit == 0) {
            // If the limit is 0, that means no limit should be applied.
            $limit = null;
        }

        // Get the fully qualified class names for the data source and diagram Doctrine entities.
        $dataSourceFullyQualifiedClassName = $dataSourceToDiagramMapping->getDataSource();
        $diagramFullyQualifiedClassName = $dataSourceToDiagramMapping->getDiagram();

        // Get the entity managers for the Doctrine entities.
        $dataSourceEm = $this->getEntityManager($dataSourceFullyQualifiedClassName);
        $diagramEm = $this->getEntityManager($diagramFullyQualifiedClassName);

        // Attempt to get the repositories for the Doctrine entities.
        $dataSourceRepository = $dataSourceEm->getRepository($dataSourceFullyQualifiedClassName);
        $diagramRepository = $diagramEm->getRepository($diagramFullyQualifiedClassName);

        // Get the last known processed diagram entity measurement time with this data source.
        $lastDiagramMeasurementTimeFromDataSource = $this->findLastDiagramMeasurementTimeFromDataSource($diagramRepository, $dataSourceFullyQualifiedClassName);

        // Get N amount of data source entries whose measurement time is later than the last known one.
        $dataSourceEntities = $this->findDataSourceEntitiesLaterThan($dataSourceToDiagramMapping, $dataSourceEm, $lastDiagramMeasurementTimeFromDataSource, $limit);

        $translatedEntitiesCount = 0;

        if (!empty($dataSourceEntities)) {
            try {
                foreach ($dataSourceEntities as $dataSourceEntity) {
                    // Create an empty diagram entity.
                    $diagramEntity = new $diagramFullyQualifiedClassName();
                    // Translate the data source entity fields into the diagram entity.
                    $this->translateDataSourceToDiagramEntity($dataSourceToDiagramMapping, $dataSourceEntity, $diagramEntity);
                    // Mark that the diagram entity is to be persisted to the database.
                    $diagramEm->persist($diagramEntity);
                    $translatedEntitiesCount++;
                }
            }
            catch (\Exception $e) {
                // Make sure to apply the database changes for the diagram entities that were marked to be persisted.
                $diagramEm->flush();
                // Re-throw the exception.
                throw $e;
            }

            // Once done going through all entities that were translated, apply the changes to the database.
            $diagramEm->flush();
        }

        // Return some information as a result. This is useful when using this method from a Symfony Command.
        $result = array(
            // Include the last known measurement time for the data source prior to the translation performed just now.
            'last_known_measurement_time' => $lastDiagramMeasurementTimeFromDataSource,
            // Include the number of translated data source to diagram entities.
            'translated_entities_count' => $translatedEntitiesCount,
        );

        return $result;
    }

    /**
     * TODO
     *
     * @param $diagramRepository
     * @param $dataSourceFullyQualifiedClassName
     * @return mixed
     */
    protected function findLastDiagramEntityFromDataSource($diagramRepository, $dataSourceFullyQualifiedClassName)
    {
        $criteria = array(
            DiagramConfig::CONFIG_FIELD_NAME_SOURCE => $dataSourceFullyQualifiedClassName,
        );
        $orderBy = array(
            DiagramConfig::CONFIG_FIELD_NAME_DATETIME => 'DESC',
        );
        $limit = 1;
        $diagramEntities = $diagramRepository->findBy($criteria, $orderBy, $limit);
        $diagramEntity = reset($diagramEntities);

        return $diagramEntity;
    }

    /**
     * TODO
     *
     * @param $diagramRepository
     * @param $dataSourceFullyQualifiedClassName
     * @return mixed|null
     * @throws \Exception
     */
    protected function findLastDiagramMeasurementTimeFromDataSource($diagramRepository, $dataSourceFullyQualifiedClassName)
    {
        // Get the last known diagram entity created from this data source.
        $lastDiagramEntityFromDataSource = $this->findLastDiagramEntityFromDataSource($diagramRepository, $dataSourceFullyQualifiedClassName);

        if (empty($lastDiagramEntityFromDataSource)) {
            // No diagram entries from this data source.
            return null;
        }

        // Get the measurement time of the found entity.
        $lastMeasurementTime = $this->getDiagramEntityMeasurementTime($lastDiagramEntityFromDataSource);
        return $lastMeasurementTime;
    }

    /**
     * TODO
     *
     * @param $diagramEntity
     * @param $dataSourceFullyQualifiedClassName
     */
    public function setDiagramEntityDataSource($diagramEntity, $dataSourceFullyQualifiedClassName)
    {
        $diagramFullyQualifiedClassName = get_class($diagramEntity);

        $diagramSourceFieldName = DiagramConfig::CONFIG_FIELD_NAME_SOURCE;
        // Convert the property name to lower camel case format, since this is how getReflectionSetters() works.
        $allowedDiagramPropertyNames = array(
            $this->getLowerCamelCasePropertyName($diagramSourceFieldName),
        );
        // Attempt to get a ReflectionMethod for the source field setter.
        $diagramReflectionSetters = $this->getReflectionSetters($diagramFullyQualifiedClassName, $allowedDiagramPropertyNames);
        $diagramSourceFieldReflectionSetter = reset($diagramReflectionSetters);
        if (empty($diagramSourceFieldReflectionSetter)) {
            throw new \Exception(sprintf("Could not find a setter for property name '%s' in class '%s'.", $diagramSourceFieldName, $diagramFullyQualifiedClassName));
        }
        $diagramSourceFieldReflectionSetter->invoke($diagramEntity, $dataSourceFullyQualifiedClassName);
    }

    /**
     * TODO
     *
     * @param $diagramEntity
     * @return mixed
     * @throws \Exception
     */
    public function getDiagramEntityMeasurementTime($diagramEntity)
    {
        $diagramFullyQualifiedClassName = get_class($diagramEntity);

        $diagramMeasurementTimeFieldName = DiagramConfig::CONFIG_FIELD_NAME_DATETIME;
        // Convert the property name to lower camel case format, since this is how getReflectionSetters() works.
        $allowedDiagramPropertyNames = array(
            $this->getLowerCamelCasePropertyName($diagramMeasurementTimeFieldName),
        );
        // Attempt to get a ReflectionMethod for the measurement time field getter.
        $diagramReflectionGetters = $this->getReflectionGetters($diagramFullyQualifiedClassName, $allowedDiagramPropertyNames);
        $diagramMeasurementTimeFieldReflectionGetter = reset($diagramReflectionGetters);
        if (empty($diagramMeasurementTimeFieldReflectionGetter)) {
            throw new \Exception(sprintf("Could not find a getter for property name '%s' in class '%s'.", $diagramMeasurementTimeFieldName, $diagramFullyQualifiedClassName));
        }
        // Attempt to get the measurement time value.
        $measurementTime = $diagramMeasurementTimeFieldReflectionGetter->invoke($diagramEntity);

        return $measurementTime;
    }

    /**
     * TODO
     *
     * @param $diagramEntity
     * @return mixed
     * @throws \Exception
     */
    public function setDiagramEntityMeasurementTime($diagramEntity, $measurementTime)
    {
        $diagramFullyQualifiedClassName = get_class($diagramEntity);

        $diagramMeasurementTimeFieldName = DiagramConfig::CONFIG_FIELD_NAME_DATETIME;
        // Convert the property name to lower camel case format, since this is how getReflectionSetters() works.
        $allowedDiagramPropertyNames = array(
            $this->getLowerCamelCasePropertyName($diagramMeasurementTimeFieldName),
        );
        // Attempt to get a ReflectionMethod for the measurement time field setter.
        $diagramReflectionSetters = $this->getReflectionSetters($diagramFullyQualifiedClassName, $allowedDiagramPropertyNames);
        $diagramMeasurementTimeFieldReflectionSetter = reset($diagramReflectionSetters);
        if (empty($diagramMeasurementTimeFieldReflectionSetter)) {
            throw new \Exception(sprintf("Could not find a setter for property name '%s' in class '%s'.", $diagramMeasurementTimeFieldName, $diagramFullyQualifiedClassName));
        }
        $diagramMeasurementTimeFieldReflectionSetter->invoke($diagramEntity, $measurementTime);
    }

    /**
     * TODO
     *
     * @param DataSourceToDiagramMapping $dataSourceToDiagramMapping
     */
    protected function findDataSourceMeasurementTimePropertyName(DataSourceToDiagramMapping $dataSourceToDiagramMapping)
    {
        // Get the fully qualified class names for the data source and diagram Doctrine entities.
        $dataSourceFullyQualifiedClassName = $dataSourceToDiagramMapping->getDataSource();
        $diagramFullyQualifiedClassName = $dataSourceToDiagramMapping->getDiagram();

        // Get all property names of the data source entity class.
        $dataSourceEntityPropertyNames = $this->getReflectionPropertyNames($dataSourceFullyQualifiedClassName);

        // Get the measurement time setter method name of the diagram.
        $diagramMeasurementTimeFieldName = DiagramConfig::CONFIG_FIELD_NAME_DATETIME;
        // Convert the property name to lower camel case format, since this is how getReflectionSetters() works.
        $allowedDiagramPropertyNames = array(
            $this->getLowerCamelCasePropertyName($diagramMeasurementTimeFieldName),
        );
        // Attempt to get the name of the measurement time field setter.
        $diagramSetterNames = $this->getReflectionSetters($diagramFullyQualifiedClassName, $allowedDiagramPropertyNames, true);
        $diagramMeasurementTimeFieldSetterName = reset($diagramSetterNames);
        if (empty($diagramMeasurementTimeFieldSetterName)) {
            throw new \Exception(sprintf("Could not find a setter for property name '%s' in class '%s'.", $diagramMeasurementTimeFieldName, $diagramFullyQualifiedClassName));
        }

        // Go through the field mappings and find the one for the diagram measurement time, so that the data source getter name can be found.
        $dataSourceMeasurementTimeGetterName = null;
        $fieldMappings = $dataSourceToDiagramMapping->getFieldMappings();
        if (!empty($fieldMappings)) {
            foreach ($fieldMappings as $fieldMapping) {
                if ($fieldMapping->getTargetSetter() == $diagramMeasurementTimeFieldSetterName) {
                    // This is the relevant FieldMapping object.
                    // Get the data source getter.
                    $dataSourceMeasurementTimeGetterName = $fieldMapping->getSourceGetter();
                    // No need to search further.
                    break;
                }
            }
        }

        // If no measurement time getter could be found in the data source, this is not ok. Throw an exception.
        if (empty($dataSourceMeasurementTimeGetterName)) {
            throw new \Exception(sprintf("Could not find the measurement time getter for data source entity of class '%s'.", $dataSourceFullyQualifiedClassName));
        }

        // Attempt to find which one of the data source entity property names has a getter used for the measurement time.
        foreach ($dataSourceEntityPropertyNames as $propertyName) {
            // Convert the property name to lower camel case format, since this is how getReflectionSetters() works.
            $convertedPropertyName = $this->getLowerCamelCasePropertyName($propertyName);
            $getterNames = $this->getReflectionGetters($dataSourceFullyQualifiedClassName, array($convertedPropertyName), true);
            $getterName = reset($getterNames);
            if ($getterName == $dataSourceMeasurementTimeGetterName) {
                // Found the correct property name that holds the measurement time value.
                return $propertyName;
            }
        }

        // If this point is reached, then no relevant property was found. This is not a normal behavior, so throw an exception.
        throw new \Exception(sprintf("Could not find the data source property name holding the measurement time value for data source entity of class '%s'.", $dataSourceFullyQualifiedClassName));
    }

    /**
     * TODO
     *
     * @param DataSourceToDiagramMapping $dataSourceToDiagramMapping
     * @param $dataSourceEm
     * @param $measurementTime
     * @param $limit
     * @return mixed
     * @throws \Exception
     */
    protected function findDataSourceEntitiesLaterThan(DataSourceToDiagramMapping $dataSourceToDiagramMapping, $dataSourceEm, $measurementTime, $limit)
    {
        // Get the name of the property that holds the measurement time data in the data source.
        $measurementTimeFieldName = $this->findDataSourceMeasurementTimePropertyName($dataSourceToDiagramMapping);

        // Get the fully qualified class names for the data source Doctrine entity.
        $dataSourceFullyQualifiedClassName = $dataSourceToDiagramMapping->getDataSource();

        // Find entities that have a measurement time later than the one provided as a parameter.
        $qb = $dataSourceEm->createQueryBuilder();
        $qb = $qb->select('ds')
            ->from($dataSourceFullyQualifiedClassName, 'ds');
        // Apply a measurement time WHERE condition only if a valid parameter was provided.
        if (!empty($measurementTime)) {
            $qb = $qb->where('ds.' . $measurementTimeFieldName . ' > :last_known_datetime');
        }
        // Order the results in ascending order of their measurement time.
        $qb = $qb->orderBy('ds.' . $measurementTimeFieldName, 'ASC');
        if (!empty($measurementTime)) {
            $qb->setParameter('last_known_datetime', $measurementTime);
        }
        $query = $qb->getQuery();

        // Apply a limit only if a valid one was provided. Otherwise fetch all results.
        if (!empty($limit)) {
            $query = $query->setMaxResults($limit);
        }

        $results = $query->getResult();

        return $results;
    }

    /**
     * TODO
     *
     * @param $diagramEntity
     * @param $consolidationType
     * @throws \Exception
     */
    public function setDiagramEntityConsolidationType($diagramEntity, $consolidationType)
    {
        $validConsolidationTypes = ConsolidationState::getValidConsolidationTypes();
        // Make sure the consolidationType provided as a parameter is valid.
        if (!in_array($consolidationType, $validConsolidationTypes, true)) {
            throw new \Exception(sprintf("Invalid consolidation type '%s'.", $consolidationType));
        }

        $diagramFullyQualifiedClassName = get_class($diagramEntity);

        $consolidationTypeFieldName = DiagramConfig::CONFIG_FIELD_NAME_CONSOLIDATION_TYPE;
        // Convert the property name to lower camel case format, since this is how getReflectionSetters() works.
        $allowedDiagramPropertyNames = array(
            $this->getLowerCamelCasePropertyName($consolidationTypeFieldName),
        );
        // Attempt to get a ReflectionMethod for the source field setter.
        $diagramReflectionSetters = $this->getReflectionSetters($diagramFullyQualifiedClassName, $allowedDiagramPropertyNames);
        $diagramConsolidationTypeFieldReflectionSetter = reset($diagramReflectionSetters);
        if (empty($diagramConsolidationTypeFieldReflectionSetter)) {
            throw new \Exception(sprintf("Could not find a setter for property name '%s' in class '%s'.", $consolidationTypeFieldName, $diagramFullyQualifiedClassName));
        }
        $diagramConsolidationTypeFieldReflectionSetter->invoke($diagramEntity, $consolidationType);
    }

    /**
     * TODO
     *
     * @param $diagramFullyQualifiedClassName
     * @param $consolidationType
     * @param null $startTime
     * @param null $endTime
     * @param int $limit
     * @param bool $startInclusive
     * @param bool $endInclusive
     * @param string $source
     * @return mixed
     */
    public function findDiagramEntitiesByConsolidationType($diagramFullyQualifiedClassName, $consolidationType, $startTime = NULL, $endTime = NULL, $limit = 0, $startInclusive = TRUE, $endInclusive = FALSE, $source = NULL)
    {
        // Get the entity manager for the diagram Doctrine entity.
        $diagramEm = $this->getEntityManager($diagramFullyQualifiedClassName);
        $measurementTimeFieldName = DiagramConfig::CONFIG_FIELD_NAME_DATETIME;
        $consolidationTypeFieldName = DiagramConfig::CONFIG_FIELD_NAME_CONSOLIDATION_TYPE;

        // Find entities that have a measurement time later than the one provided as a parameter.
        $qb = $diagramEm->createQueryBuilder();
        $qb = $qb->select('de')
            ->from($diagramFullyQualifiedClassName, 'de');
        // Set a condition for the consolidation type.
        $qb = $qb->where('de.' . $consolidationTypeFieldName . ' = :consolidation_type');
        // Apply a measurement time WHERE condition only if valid parameters were provided.
        if (!empty($startTime)) {
            $startComparisonOperator = '>';
            if ($startInclusive) {
                $startComparisonOperator = '>=';
            }
            $qb = $qb->andWhere('de.' . $measurementTimeFieldName . ' ' . $startComparisonOperator . ' ' . ':start_datetime');
        }
        if (!empty($endTime)) {
            $endComparisonOperator = '<';
            if ($endInclusive) {
                $endComparisonOperator = '<=';
            }
            $qb = $qb->andWhere('de.' . $measurementTimeFieldName . ' ' . $endComparisonOperator . ' ' . ':end_datetime');
        }

        // Add a data source condition if requested.
        if (!empty($source)) {
            $qb = $qb->andWhere('de.' . DiagramConfig::CONFIG_FIELD_NAME_SOURCE . ' = :source_class_name');
        }

        // Order the results in ascending order of their measurement time.
        $qb = $qb->orderBy('de.' . $measurementTimeFieldName, 'ASC');

        $qb = $qb->setParameter('consolidation_type', $consolidationType);
        if (!empty($startTime)) {
            $qb = $qb->setParameter('start_datetime', $startTime);
        }
        if (!empty($endTime)) {
            $qb = $qb->setParameter('end_datetime', $endTime);
        }
        if (!empty($source)) {
            $qb = $qb->setParameter('source_class_name', $source);
        }
        $query = $qb->getQuery();

        // Apply a limit only if a valid one was provided. Otherwise fetch all results.
        if (!empty($limit)) {
            $query = $query->setMaxResults($limit);
        }

        $results = $query->getResult();

        return $results;
    }

    /**
     * TODO
     *
     * @param DataSourceToDiagramMapping $dataSourceToDiagramMapping
     * @param $consolidationType
     * @param int $limit
     * @return array
     * @throws \Exception
     */
    public function downsampleDiagramEntitiesByConsolidationType(DataSourceToDiagramMapping $dataSourceToDiagramMapping, $consolidationType, $limit = 0)
    {
        // Fetch the diagram class name.
        $diagramFullyQualifiedClassName = $dataSourceToDiagramMapping->getDiagram();
        // Fetch the data source class name.
        $dataSourceFullyQualifiedClassName = $dataSourceToDiagramMapping->getDataSource();
        // Fetch the entity manager.
        $diagramEm = $this->getEntityManager($diagramFullyQualifiedClassName);

        // Find the consolidation type with the next higher sample rate.
        $sourceConsolidationType = ConsolidationState::getSourceConsolidationType($consolidationType);
        if ($sourceConsolidationType === false) {
            throw new \Exception(sprintf("Could not find a source consolidation type for consolidation type '%s'.", $consolidationType));
        }

        // Find the ConsolidationState for the current consolidation type.
        $lastMeasurementTime = NULL;
        $consolidationStates = $dataSourceToDiagramMapping->getConsolidationStates();
        $selectedConsolidationState = NULL;
        foreach ($consolidationStates as $consolidationState) {
            if ($consolidationState->getConsolidationType() == $consolidationType) {
                // Found the relevant consolidation state.
                $selectedConsolidationState = $consolidationState;
                // Use the last known measurement time as the starting time for the queries.
                $lastMeasurementTime = $consolidationState->getLastMeasurementTime();

                // Do not search further.
                break;
            }
        }

        if (empty($selectedConsolidationState)) {
            throw new \Exception(sprintf("Could not find a consolidation state object for consolidation type '%s'.", $consolidationType));
        }

        // If there is a specified limit, fetch that many entities after the last measurement time.
        // Otherwise fetch all entities that have not yet been processed.
        // The $lastMeasurementTime value should not be allowed in the results: so the start time should not be inclusive.
        $sourceDiagramEntities = $this->findDiagramEntitiesByConsolidationType($diagramFullyQualifiedClassName, $sourceConsolidationType, $lastMeasurementTime, NULL, $limit, false, false, $dataSourceFullyQualifiedClassName);

        $groupedEntitiesByTimeInterval = array();
        $generatedEntitiesCount = 0;
        if (!empty($sourceDiagramEntities)) {
            // Group the entities into time intervals.
            $this->groupDiagramEntitiesByTimeInterval($sourceDiagramEntities, $consolidationType, $groupedEntitiesByTimeInterval);

            // Fetch previous possibly processed values from the starting time interval.
            // Fetch the first interval's data,
            $firstInterval = reset($groupedEntitiesByTimeInterval);
            // Use the earliest possible time in the time interval as the start.
            $firstIntervalStartTime = $firstInterval['time']['start'];
            // Use the earliest entity's measurement time as the end.
            $firstIntervalFirstEntity = reset($firstInterval['entities']);
            $firstIntervalEndTime = $this->getDiagramEntityMeasurementTime($firstIntervalFirstEntity);
            // Now query for any possibly previously processed entities in the first time interval.
            $previouslyProcessedDiagramEntities = $this->findDiagramEntitiesByConsolidationType($diagramFullyQualifiedClassName, $sourceConsolidationType, $firstIntervalStartTime, $firstIntervalEndTime, 0, true, false, $dataSourceFullyQualifiedClassName);
            if (!empty($previouslyProcessedDiagramEntities)) {
                // Found some previously processed diagram entities that should be added to the entities grouped by time itnerval.
                $this->groupDiagramEntitiesByTimeInterval($previouslyProcessedDiagramEntities, $consolidationType, $groupedEntitiesByTimeInterval);
            }

            // If a limit was specified, fetch next values in the end time interval.
            if (!empty($limit)) {
                // Fetch the last interval's data,
                $lastInterval = end($groupedEntitiesByTimeInterval);
                // Fetch the last entry in the last interval.
                $lastIntervalLastEntity = end($lastInterval['entities']);
                // Fetch the last measurement time out of all entries processed.
                $lastHigherSampleRateEntryMeasurementTime = $this->getDiagramEntityMeasurementTime($lastIntervalLastEntity);

                // Use the latest possible time in the time interval as the end.
                $lastIntervalEndTime = $lastInterval['time']['end'];
                // Use the latest entity's measurement time as the start.
                $lastIntervalStartTime = $lastHigherSampleRateEntryMeasurementTime;
                // Now query for any possibly unprocessed entities in the last time interval.
                $unprocessedDiagramEntities = $this->findDiagramEntitiesByConsolidationType($diagramFullyQualifiedClassName, $sourceConsolidationType, $lastIntervalStartTime, $lastIntervalEndTime, 0, false, true, $dataSourceFullyQualifiedClassName);
                if (!empty($unprocessedDiagramEntities)) {
                    // Found some unprocessed entities diagram entities that should be added to the entities grouped by time itnerval.
                    $this->groupDiagramEntitiesByTimeInterval($unprocessedDiagramEntities, $consolidationType, $groupedEntitiesByTimeInterval);
                }
            }

            // Generate new diagram entities at the mid point of each interval.
            try {
                foreach ($groupedEntitiesByTimeInterval as $entitiesInterval) {
                    $diagramEntities = $entitiesInterval['entities'];
                    $roundedTime = $entitiesInterval['time']['rounded'];
                    // Calculate the average values for each time interval and generate an entity out of that.
                    $generatedDiagramEntity = $this->generateDiagramEntityWithAverageValues($dataSourceToDiagramMapping, $diagramEntities, $roundedTime, $consolidationType);
                    // Persist the entity to the database.
                    $diagramEm->persist($generatedDiagramEntity);
                    // Increase the count of how many entities were generated, for the purpose of reporting.
                    $generatedEntitiesCount++;
                }
            }
            catch (\Exception $e) {
                // Something went wrong. Make sure the changes done so far are applied to the database.
                $diagramEm->flush();
                // Re-throw the exception.
                throw $e;
            }
            // Apply the changes to the database.
            $diagramEm->flush();

            // Update the consolidation state marking the last higher sample rate entry's measurement time.
            // Fetch the last interval's data,
            $lastInterval = end($groupedEntitiesByTimeInterval);
            // Fetch the last entry in the last interval.
            $lastIntervalLastEntity = end($lastInterval['entities']);
            // Fetch the last measurement time out of all entries processed.
            $lastHigherSampleRateEntryMeasurementTime = $this->getDiagramEntityMeasurementTime($lastIntervalLastEntity);
            $selectedConsolidationState->setLastMeasurementTime($lastHigherSampleRateEntryMeasurementTime);
            // Fetch the entity manager for ConsolidationStates.
            $consolidationStateEm = $this->getEntityManager(get_class($selectedConsolidationState));
            // Persist the changes.
            $consolidationStateEm->persist($selectedConsolidationState);
            // Apply the changes to the database.
            $consolidationStateEm->flush();
        }

        // Count how many entities were read from the database.
        $readEntitiesCount = 0;
        foreach ($groupedEntitiesByTimeInterval as $timeInterval) {
            $readEntitiesCount += count($timeInterval['entities']);
        }

        // Return the amount of read db entities, and how many were stored.
        $result = array(
            'read' => $readEntitiesCount,
            'generated' => $generatedEntitiesCount,
        );

        return $result;
    }

    /**
     * TODO
     *
     * @param $diagramEntities
     * @param $consolidationType
     * @param $groupedEntitiesByTimeInterval
     * @throws \Exception
     */
    protected function groupDiagramEntitiesByTimeInterval($diagramEntities, $consolidationType, &$groupedEntitiesByTimeInterval)
    {
        foreach ($diagramEntities as $diagramEntity) {
            // Get the diagram entity measurement time.
            $measurementTime = $this->getDiagramEntityMeasurementTime($diagramEntity);
            // Get the rounded measurement time.
            $roundedTime = ConsolidationState::getClosestRoundedTimeByConsolidationType($consolidationType, $measurementTime);
            // Use the rounded time as the grouped entities array key.
            $groupKey = $roundedTime->format('c');

            if (empty($groupedEntitiesByTimeInterval[$groupKey])) {
                // This group is not defined yet.
                // Add some initial information to it about the time interval.
                $timeIntervalStartAndEndTimes = ConsolidationState::getTimeIntervalStartAndEndTimesByConsolidationType($consolidationType, $roundedTime);
                $timeInformation = array(
                    'rounded' => $roundedTime,
                    'start' => $timeIntervalStartAndEndTimes['start'],
                    'end' => $timeIntervalStartAndEndTimes['end'],
                );
                $groupedEntitiesByTimeInterval[$groupKey]['time'] = $timeInformation;

                // Initialize an array to hold the Diagram entities in this group.
                $groupedEntitiesByTimeInterval[$groupKey]['entities'] = array();
            }

            // Use the measurement time as the entity array key.
            $entityKey = $measurementTime->format('c');

            // Add the diagram entity to the group.
            $groupedEntitiesByTimeInterval[$groupKey]['entities'][$entityKey] = $diagramEntity;
            // Order the entities based on their measurement time ASC.
            ksort($groupedEntitiesByTimeInterval[$groupKey]['entities']);
        }

        // Order the grouped entities based on their key (the rounded time) ASC.
        ksort($groupedEntitiesByTimeInterval);
    }

    /**
     * TODO
     *
     * @param DataSourceToDiagramMapping $dataSourceToDiagramMapping
     * @param $diagramEntities
     * @param \DateTime $measurementTime
     * @param $consolidationType
     * @return null
     * @throws \Exception
     */
    protected function generateDiagramEntityWithAverageValues(DataSourceToDiagramMapping $dataSourceToDiagramMapping, $diagramEntities, \DateTime $measurementTime, $consolidationType)
    {
        $diagramFullyQualifiedClassName = $dataSourceToDiagramMapping->getDiagram();
        $dataSourceFullyQualifiedClassName = $dataSourceToDiagramMapping->getDataSource();

        // Search for an existing diagram entity with that measurement time, consolidation type, and data source.
        $endTimeTimestamp = $measurementTime->getTimestamp() + 1;
        $endTime = new \DateTime();
        $endTime->setTimestamp($endTimeTimestamp);
        $endTime->setTimezone($measurementTime->getTimezone());
        $generatedEntities = $this->findDiagramEntitiesByConsolidationType($diagramFullyQualifiedClassName, $consolidationType, $measurementTime, $endTime, 0, true, false, $dataSourceFullyQualifiedClassName);
        $generatedEntity = NULL;
        if (!empty($generatedEntities)) {
            // Found an existing entity.
            $generatedEntity = reset($generatedEntities);
        }
        if (!$generatedEntity) {
            // Create a new diagram entity of the relevant class.
            $generatedEntity = new $diagramFullyQualifiedClassName();
        }

        // Extract data from the diagram entries.
        $propertiesToIgnore = DiagramConfig::getReservedConfigFieldNames();
        $diagramDataEntries = $this->extractInformationFromDiagramEntries($diagramEntities, $propertiesToIgnore);

        // Calculate the average values.
        $averageValues = array();
        foreach ($diagramDataEntries as $diagramData) {
            foreach ($diagramData as $propertyName => $propertyValue) {
                if (!is_numeric($propertyValue)) {
                    // This property value is not numeric. No average value can be calculated. Skip it.
                    continue;
                }

                if (!isset($averageValues[$propertyName])) {
                    // This property name has not yet been handled. Initialize some values.
                    $averageValues[$propertyName] = array(
                        'sum' => 0,
                        'valuesCount' => 0,
                        'average' => 0,
                    );
                }

                $averageValues[$propertyName]['sum'] += $propertyValue;
                $averageValues[$propertyName]['valuesCount']++;
            }
        }
        foreach ($averageValues as &$valueData) {
            // Calculate the average value.
            $valueData['average'] = $valueData['sum'] / $valueData['valuesCount'];
        }

        // Set the average values to the generated entity.
        foreach ($averageValues as $propertyName => $valueData) {
            $average = $valueData['average'];

            // Attempt to get a ReflectionMethod for the property setter.
            $diagramReflectionSetters = $this->getReflectionSetters($diagramFullyQualifiedClassName, array($propertyName));
            $diagramReflectionSetter = reset($diagramReflectionSetters);
            if (empty($diagramReflectionSetter)) {
                throw new \Exception(sprintf("Could not find a setter for property name '%s' in class '%s'.", $propertyName, $diagramFullyQualifiedClassName));
            }

            $diagramReflectionSetter->invoke($generatedEntity, $average);
        }

        // Set the source field to the generated entity.
        $this->setDiagramEntityDataSource($generatedEntity, $dataSourceFullyQualifiedClassName);
        // Set the generated entity's consolidationType to the current one.
        $this->setDiagramEntityConsolidationType($generatedEntity, $consolidationType);
        // Set the measurement time of the generated entity to the rounded time of the interval.
        $this->setDiagramEntityMeasurementTime($generatedEntity, $measurementTime);

        return $generatedEntity;
    }

    /**
     * Linear interpolation function.
     *
     * @see https://en.wikipedia.org/wiki/Linear_interpolation#Linear_interpolation_between_two_known_points
     *
     * @param $x
     * @param $x0
     * @param $y0
     * @param $x1
     * @param $y1
     * @return mixed
     */
    public function linearInterpolate($x, $x0, $y0, $x1, $y1)
    {
        $y = $y0 + ($x - $x0) * (($y1 - $y0) / ($x1 - $x0));
        return $y;
    }

    /**
     * TODO
     *
     * @param DataSourceToDiagramMapping $dataSourceToDiagramMapping
     * @param \DateTime $measurementTime
     * @param $consolidationType
     * @param $diagramEntity1
     * @param $diagramEntity2
     * @return null
     * @throws \Exception
     */
    public function generateDiagramEntityWithLinearInterpolation(DataSourceToDiagramMapping $dataSourceToDiagramMapping, \DateTime $measurementTime, $consolidationType, $diagramEntity1, $diagramEntity2)
    {
        $diagramFullyQualifiedClassName = $dataSourceToDiagramMapping->getDiagram();
        $dataSourceFullyQualifiedClassName = $dataSourceToDiagramMapping->getDataSource();

        // Search for an existing diagram entity with that measurement time, consolidation type, and data source.
        $endTimeTimestamp = $measurementTime->getTimestamp() + 1;
        $endTime = new \DateTime();
        $endTime->setTimestamp($endTimeTimestamp);
        $endTime->setTimezone($measurementTime->getTimezone());
        $generatedEntities = $this->findDiagramEntitiesByConsolidationType($diagramFullyQualifiedClassName, $consolidationType, $measurementTime, $endTime, 0, true, false, $dataSourceFullyQualifiedClassName);
        $generatedEntity = NULL;
        if (!empty($generatedEntities)) {
            // Found an existing entity.
            $generatedEntity = reset($generatedEntities);
        }
        if (!$generatedEntity) {
            // Create a new diagram entity of the relevant class.
            $generatedEntity = new $diagramFullyQualifiedClassName();
        }

        $measurementTimestamp = $measurementTime->getTimestamp();
        // Extract the time values from the diagrams.
        $measurementTime1 = $this->getDiagramEntityMeasurementTime($diagramEntity1);
        $measurementTimestamp1 = $measurementTime1->getTimestamp();
        $measurementTime2 = $this->getDiagramEntityMeasurementTime($diagramEntity2);
        $measurementTimestamp2 = $measurementTime2->getTimestamp();

        if ($measurementTimestamp < $measurementTimestamp1 || $measurementTimestamp > $measurementTimestamp2) {
            // Generating an entity using linear interpolation outside of the bound range is not a good idea.
            throw new \Exception(sprintf("Cannot generate a diagram entity with linear interpolation, since the time %d is not between %d and %d.", $measurementTimestamp, $measurementTimestamp1, $measurementTimestamp2));
        }

        // Extract data from the diagram entries.
        $diagramEntities = array($diagramEntity1, $diagramEntity2);
        $propertiesToIgnore = DiagramConfig::getReservedConfigFieldNames();
        $diagramDataEntries = $this->extractInformationFromDiagramEntries($diagramEntities, $propertiesToIgnore);

        if (count($diagramDataEntries) != 2) {
            throw new \Exception("Failed to fetch diagram data entries.");
        }

        $propertyNames = array_keys($diagramDataEntries[0]);

        // Calculate the interpolated values.
        $interpolatedValues = array();
        foreach ($propertyNames as $propertyName) {
            // Fetch the values for both diagram entries.
            $value1 = $diagramDataEntries[0][$propertyName];
            $value2 = $diagramDataEntries[1][$propertyName];

            if (!is_numeric($value1) || !is_numeric($value2)) {
                // One or both of the properties are not numeric. No interpolated value can be calculated. Skip this.
                continue;
            }

            $interpolatedValues[$propertyName] = $this->linearInterpolate($measurementTimestamp, $measurementTimestamp1, $value1, $measurementTimestamp2, $value2);
        }

        // Set the average values to the generated entity.
        foreach ($interpolatedValues as $propertyName => $interpolatedValue) {
            // Attempt to get a ReflectionMethod for the property setter.
            $diagramReflectionSetters = $this->getReflectionSetters($diagramFullyQualifiedClassName, array($propertyName));
            $diagramReflectionSetter = reset($diagramReflectionSetters);
            if (empty($diagramReflectionSetter)) {
                throw new \Exception(sprintf("Could not find a setter for property name '%s' in class '%s'.", $propertyName, $diagramFullyQualifiedClassName));
            }

            $diagramReflectionSetter->invoke($generatedEntity, $interpolatedValue);
        }

        // Set the source field to the generated entity.
        $this->setDiagramEntityDataSource($generatedEntity, $dataSourceFullyQualifiedClassName);
        // Set the generated entity's consolidationType to the current one.
        $this->setDiagramEntityConsolidationType($generatedEntity, $consolidationType);
        // Set the measurement time of the generated entity to the rounded time of the interval.
        $this->setDiagramEntityMeasurementTime($generatedEntity, $measurementTime);

        return $generatedEntity;
    }

    /**
     * TODO
     *
     * @param DataSourceToDiagramMapping $dataSourceToDiagramMapping
     * @param $consolidationType
     * @param int $limit
     * @return array
     * @throws \Exception
     */
    public function upsampleDiagramEntitiesByConsolidationType(DataSourceToDiagramMapping $dataSourceToDiagramMapping, $consolidationType, $limit = 0)
    {
        // Fetch the diagram class name.
        $diagramFullyQualifiedClassName = $dataSourceToDiagramMapping->getDiagram();
        // Fetch the data source class name.
        $dataSourceFullyQualifiedClassName = $dataSourceToDiagramMapping->getDataSource();
        // Fetch the entity manager.
        $diagramEm = $this->getEntityManager($diagramFullyQualifiedClassName);

        // Find the consolidation type with the next higher sample rate.
        $sourceConsolidationType = ConsolidationState::getSourceConsolidationType($consolidationType);
        if ($sourceConsolidationType === false) {
            throw new \Exception(sprintf("Could not find a source consolidation type for consolidation type '%s'.", $consolidationType));
        }

        // Find the ConsolidationState for the current consolidation type.
        $lastMeasurementTime = NULL;
        $consolidationStates = $dataSourceToDiagramMapping->getConsolidationStates();
        $selectedConsolidationState = NULL;
        foreach ($consolidationStates as $consolidationState) {
            if ($consolidationState->getConsolidationType() == $consolidationType) {
                // Found the relevant consolidation state.
                $selectedConsolidationState = $consolidationState;
                // Use the last known measurement time as the starting time for the queries.
                $lastMeasurementTime = $consolidationState->getLastMeasurementTime();

                // Do not search further.
                break;
            }
        }

        if (empty($selectedConsolidationState)) {
            throw new \Exception(sprintf("Could not find a consolidation state object for consolidation type '%s'.", $consolidationType));
        }

        // If there is a specified limit, fetch that many entities after the last measurement time.
        // Otherwise fetch all entities that have not yet been processed.
        // The $lastMeasurementTime value should not be allowed in the results: so the start time should not be inclusive.
//        dump($diagramFullyQualifiedClassName);
//        dump($sourceConsolidationType);
//        dump($lastMeasurementTime);
//        dump($limit);
        $sourceDiagramEntities = $this->findDiagramEntitiesByConsolidationType($diagramFullyQualifiedClassName, $sourceConsolidationType, $lastMeasurementTime, NULL, $limit, false, false, $dataSourceFullyQualifiedClassName);

        // Find the last possible diagram entity BEFORE or AT the $lastMeasurementTime.
        $previousSourceDiagramEntity = $this->findDiagramEntitiesByConsolidationType($diagramFullyQualifiedClassName, $sourceConsolidationType, NULL, $lastMeasurementTime, 1, false, true, $dataSourceFullyQualifiedClassName);
//        dump($previousSourceDiagramEntity);
        // Prepend the previous source diagram entity (if found) to the source diagram entities result.
        if (!empty($previousSourceDiagramEntity)) {
            $previousSourceDiagramEntity = reset($previousSourceDiagramEntity);
            array_unshift($sourceDiagramEntities, $previousSourceDiagramEntity);
        }

//        dump($sourceDiagramEntities);

        // Return the amount of read db entities, and how many were stored.
        // The source entities should be at least 2. Otherwise nothing can be done.
        if (count($sourceDiagramEntities) < 2) {
            // Return the amount of read db entities, and how many were stored.
            $result = array(
                'read' => count($sourceDiagramEntities),
                'generated' => 0,
            );
            return $result;
        }

        $generatedEntitiesCount = 0;

        // Find the earliest and latest source entity times.
        $earliestEntity = reset($sourceDiagramEntities);
        $earliestTime = $this->getDiagramEntityMeasurementTime($earliestEntity);
        // The earliest time should not be allowed to be earlier than the $lastMeasurementTime,
        if ($earliestTime < $lastMeasurementTime) {
            // Make the earliest time a second later than the last measurement time.
            $earliestTime = clone $lastMeasurementTime;
            $intervalSpec = 'PT1S';
            $dateInterval = new \DateInterval($intervalSpec);
            $earliestTime->add($dateInterval);
        }

        $lastEntity = end($sourceDiagramEntities);
        $lastTime = $this->getDiagramEntityMeasurementTime($lastEntity);

        // Generate a maximum of $limit amount of evenly spaced time intervals between the start and end time.
        $earliestRoundedTime = ConsolidationState::getClosestRoundedTimeByConsolidationType($consolidationType, $earliestTime);
        if ($earliestRoundedTime < $earliestTime) {
            // The earliest rounded time will be outside of the linear interpolation bounds.
            // Find the next available one.
            $earliestRoundedTime = ConsolidationState::getNextTimeByConsolidationType($consolidationType, $earliestRoundedTime);
        }

        $selectedRoundedTime = $earliestRoundedTime;
        $generatedTimes = array();
//        dump($selectedRoundedTime);
//        dump($lastTime);
        while ($selectedRoundedTime <= $lastTime && ($limit == 0 || count($generatedTimes) < $limit)) {
            $generatedTimes[] = $selectedRoundedTime;
            // Find the next time.
            $selectedRoundedTime = ConsolidationState::getNextTimeByConsolidationType($consolidationType, $selectedRoundedTime);
        }
//        dump($generatedTimes);
        
        // For each time interval, find the nearest two data source entities.
        // First, create an array with source diagram entity times.
        $sourceDiagramEntitiesTimes = array();
        foreach ($sourceDiagramEntities as $sourceDiagramEntity) {
            $measurementTime = $this->getDiagramEntityMeasurementTime($sourceDiagramEntity);
            $sourceDiagramEntitiesTimes[] = $measurementTime->format('c');
        }

        // Attempt to find where each rounded time would be placed in that array.
        try {
            foreach ($generatedTimes as $generatedTime) {
                // Obtain a copy of the array with the entity times.
                $entitiesTimesCopy = $sourceDiagramEntitiesTimes;
                // Place the current time in the array.
                $formattedGeneratedTime = $generatedTime->format('c');
                $entitiesTimesCopy[] = $formattedGeneratedTime;
                // Sort the array.
                sort($entitiesTimesCopy);
                // Search for the time that was just inserted to the array.
                $index = array_search($formattedGeneratedTime, $entitiesTimesCopy);

                // The source diagram entity at $index is the next closest measurement point in time.
                $nextClosestEntity = $sourceDiagramEntities[$index];
                // The one at $index-1 is the previous closest measurement point in time.
                $previousClosestEntity =  $sourceDiagramEntities[$index - 1];

                // Generate a new entity at the specified time interval.
                $generatedEntity = $this->generateDiagramEntityWithLinearInterpolation($dataSourceToDiagramMapping, $generatedTime, $consolidationType, $previousClosestEntity, $nextClosestEntity);
//                dump($generatedEntity);

                // Persist the changes.
                $diagramEm->persist($generatedEntity);
                // Increase the count of how many entities were generated, for the purpose of reporting.
                $generatedEntitiesCount++;
            }
        }
        catch (\Exception $e) {
            // Something went wrong. Make sure the changes done so far are applied to the database.
            $diagramEm->flush();
            // Re-throw the exception.
            throw $e;
        }

        // Apply the changes to the database.
        $diagramEm->flush();

        // Set the consolidation state's last measurement time to the last generated time.
        $lastGeneratedTime = end($generatedTimes);
        if ($lastGeneratedTime) {
            // Update the consolidation state marking the last higher sample rate entry's measurement time.
            $selectedConsolidationState->setLastMeasurementTime($lastGeneratedTime);
            // Fetch the entity manager for ConsolidationStates.
            $consolidationStateEm = $this->getEntityManager(get_class($selectedConsolidationState));
            // Persist the changes.
            $consolidationStateEm->persist($selectedConsolidationState);
            // Apply the changes to the database.
            $consolidationStateEm->flush();
        }


        // Count how many entities were read from the database.
        $readEntitiesCount = count($sourceDiagramEntities);

        // Return the amount of read db entities, and how many were stored.
        $result = array(
            'read' => $readEntitiesCount,
            'generated' => $generatedEntitiesCount,
        );

        return $result;
    }
}
