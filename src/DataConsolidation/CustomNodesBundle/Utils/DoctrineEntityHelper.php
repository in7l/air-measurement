<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 3/22/16
 * Time: 7:48 PM
 */

namespace DataConsolidation\CustomNodesBundle\Utils;


use DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\DataSourceToDiagramMapping;
use DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\DiagramConfig;
use DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\FieldMapping;
use DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\JsonFieldMapping;
use DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\JsonToDiagramMapping;
use DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\NodeConfig;
use DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\NodeConfigField;
use DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\NodeConfigOptions;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\Driver\DatabaseDriver;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use Doctrine\ORM\Tools\EntityGenerator;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Handles functionality related to custom doctrine entities.
 *
 * @package DataConsolidation\CustomNodesBundle\Utils
 */
class DoctrineEntityHelper
{
    const DEFAULT_ENTITY_MANAGER_SANITIZED_NAME = 'DefaultEntityManager';
    const CUSTOM_ENTITY_MANAGERS_SANITIZED_NAME = 'CustomEntityManagers';
    const CUSTOM_ENTITY_NAMESPACE_COMPONENT = 'Custom';
    const DATA_SOURCE_NAMESPACE_COMPONENT = 'DataSource';
    const DIAGRAM_NAMESPACe_COMPONENT = 'Diagram';

    // Use a trait that allows setting a service container.
    use ContainerAwareTrait;

    // Cache for the database connections without a database name.
    private $connectionsCache;

    /**
     * Fetches the path to the src directory.
     *
     * @throws \RuntimeException If the service container could not be obtained.
     * @return string The full path to the src directory.
     */
    public function getBaseSrcDirectory()
    {
        if (empty($this->container)) {
            throw new \RuntimeException('Could not obtain service container.');
        }

        $srcDirectory = $this->container->getParameter('kernel.root_dir') . '/../src/';
        return $srcDirectory;
    }

    /**
     * Fetches the base directory for the default entity manager. It will contain both the Base and Custom entity directories.
     *
     * @return string The base directory.
     */
    public function getDefaultEntityManagerBaseDirectory()
    {
        $directory = __DIR__ . '/../Entity/' . self::DEFAULT_ENTITY_MANAGER_SANITIZED_NAME . '/';

        return $directory;
    }

    /**
     * Fetches the base directory for all the custom entity managers which have generated custom entities. It will contain entity manager-specific subdirectories.
     *
     * @return string The base directory.
     */
    public function getCustomEntityManagersBaseDirectory()
    {
        $directory = __DIR__ . '/../Entity/' . self::CUSTOM_ENTITY_MANAGERS_SANITIZED_NAME . '/';

        return $directory;
    }

    /**
     * Fetches the base directory for a custom entity manager. It will contain both the Custom entity directory.
     *
     * @param string $entityManagerName The name of the entity manager specifying the custom entity subdirectory.
     * @param bool $sanitizeEntityManagerName If set to true, the $entityManagerName parameter will be sanitized
     *      (it will be converted to the expected format that can be used in namespaces).
     *      If set to false, the $entityManagerName will not be modified.
     * @return string The base directory.
     */
    public function getCustomEntityManagerBaseDirectory($entityManagerName, $sanitizeEntityManagerName = true)
    {
        if ($sanitizeEntityManagerName) {
            // The entity manager name passed as an argument is not sanitized. It needs to be sanitized now.
            // Convert the entity manager name to camel case and remove any non-alphanumeric characters
            $entityManagerName = $this->getSanitizedEntityManagerName($entityManagerName);
        }

        // Get the common directory for all custom entity managers which have generated doctrine entities.
        $directory = $this->getCustomEntityManagersBaseDirectory();
        // Append the entity manager name to the path.
        $directory .= '/' . $entityManagerName . '/';

        return $directory;
    }

    /**
     * Fetches the directory containing data source custom entities for a specific entity manager.
     *
     * @param string $entityManagerName The name of the entity manager specifying the custom entity subdirectory.
     * @param bool $sanitizeEntityManagerName If set to true, the $entityManagerName parameter will be sanitized
     *      (it will be converted to the expected format that can be used in namespaces).
     *      If set to false, the $entityManagerName will not be modified.
     * @return string The directory path.
     */
    public function getDataSourceEntityDirectory($entityManagerName, $sanitizeEntityManagerName = true)
    {
        if ($sanitizeEntityManagerName) {
            // The entity manager name passed as an argument is not sanitized. It needs to be sanitized now.
            // Convert the entity manager name to camel case and remove any non-alphanumeric characters
            $entityManagerName = $this->getSanitizedEntityManagerName($entityManagerName);
        }

        $baseDirectory = '';
        if ($entityManagerName == self::DEFAULT_ENTITY_MANAGER_SANITIZED_NAME) {
            // This is the default entity manager.
            $baseDirectory = $this->getDefaultEntityManagerBaseDirectory();
        }
        else {
            // This is one of the custom entity managers.
            $baseDirectory = $this->getCustomEntityManagerBaseDirectory($entityManagerName, false);
        }
        $baseDirectory .= self::CUSTOM_ENTITY_NAMESPACE_COMPONENT . '/' . self::DATA_SOURCE_NAMESPACE_COMPONENT . '/';

        return $baseDirectory;
    }

    /**
     * Fetches the directory containing diagram custom entities for a specific entity manager.
     *
     * @param string $entityManagerName The name of the entity manager specifying the custom entity subdirectory.
     * @param bool $sanitizeEntityManagerName If set to true, the $entityManagerName parameter will be sanitized
     *      (it will be converted to the expected format that can be used in namespaces).
     *      If set to false, the $entityManagerName will not be modified.
     * @return string The directory path.
     */
    public function getDiagramEntityDirectory($entityManagerName, $sanitizeEntityManagerName = true)
    {
        if ($sanitizeEntityManagerName) {
            // The entity manager name passed as an argument is not sanitized. It needs to be sanitized now.
            // Convert the entity manager name to camel case and remove any non-alphanumeric characters
            $entityManagerName = $this->getSanitizedEntityManagerName($entityManagerName);
        }

        $baseDirectory = '';
        if ($entityManagerName == self::DEFAULT_ENTITY_MANAGER_SANITIZED_NAME) {
            // This is the default entity manager.
            $baseDirectory = $this->getDefaultEntityManagerBaseDirectory();
        }
        else {
            // This is one of the custom entity managers.
            $baseDirectory = $this->getCustomEntityManagerBaseDirectory($entityManagerName, false);
        }
        $baseDirectory .= self::CUSTOM_ENTITY_NAMESPACE_COMPONENT . '/' . self::DIAGRAM_NAMESPACe_COMPONENT . '/';

        return $baseDirectory;
    }

    /**
     * Fetches the base namespace for a custom doctrine entity managed by a certain entity manager.
     *
     * @param string $entityManagerName The name of the entity manager specifying the last part of the custom entity namespace.
     * @param bool $sanitizeEntityManagerName If set to true, the $entityManagerName parameter will be sanitized
     *      (it will be converted to the expected format that can be used in namespaces).
     *      If set to false, the $entityManagerName will not be modified.
     * @return string The custom entity namespace.
     */
    public function getCustomEntityBaseNamespace($entityManagerName, $sanitizeEntityManagerName = true)
    {
        if ($sanitizeEntityManagerName) {
            // The entity manager name passed as an argument is not sanitized. It needs to be sanitized now.
            // Convert the entity manager name to camel case and remove any non-alphanumeric characters
            $entityManagerName = $this->getSanitizedEntityManagerName($entityManagerName);
        }

        $namespace = 'DataConsolidation\\CustomNodesBundle\\Entity\\';
        if ($entityManagerName != self::DEFAULT_ENTITY_MANAGER_SANITIZED_NAME) {
            // This is a custom entity manager so the namespace is built a bit differently.
            $namespace .= self::CUSTOM_ENTITY_MANAGERS_SANITIZED_NAME . '\\';
        }
        $namespace .= $entityManagerName . '\\' . self::CUSTOM_ENTITY_NAMESPACE_COMPONENT;

        return $namespace;
    }

    /**
     * Fetches the namespace for a data source doctrine entity managed by a certain entity manager.
     *
     * @param string $entityManagerName The name of the entity manager specifying the last part of the custom entity namespace.
     * @param bool $sanitizeEntityManagerName If set to true, the $entityManagerName parameter will be sanitized
     *      (it will be converted to the expected format that can be used in namespaces).
     *      If set to false, the $entityManagerName will not be modified.
     * @return string The custom entity namespace.
     */
    public function getDataSourceEntityNamespace($entityManagerName, $sanitizeEntityManagerName = true)
    {
        $namespace = $this->getCustomEntityBaseNamespace($entityManagerName, $sanitizeEntityManagerName);
        $namespace .= '\\' . self::DATA_SOURCE_NAMESPACE_COMPONENT;

        return $namespace;
    }

    /**
     * Fetches the namespace for a diagram doctrine entity managed by a certain entity manager.
     *
     * @param string $entityManagerName The name of the entity manager specifying the last part of the custom entity namespace.
     * @param bool $sanitizeEntityManagerName If set to true, the $entityManagerName parameter will be sanitized
     *      (it will be converted to the expected format that can be used in namespaces).
     *      If set to false, the $entityManagerName will not be modified.
     * @return string The custom entity namespace.
     */
    public function getDiagramEntityNamespace($entityManagerName, $sanitizeEntityManagerName = true)
    {
        $namespace = $this->getCustomEntityBaseNamespace($entityManagerName, $sanitizeEntityManagerName);
        $namespace .= '\\' . self::DIAGRAM_NAMESPACe_COMPONENT;

        return $namespace;
    }

    /**
     * Sanitizes an entity manager name so that it can be used in a namespace.
     *
     * This method will convert the name to camel case and it will remove any non-alphanumeric characters.
     * It will also replace some reserved keywords such as 'default'.
     *
     * @param string $entityManagerName The entity manager name to be sanitized.
     * @return string The sanitized entity manager name.
     */
    public function getSanitizedEntityManagerName($entityManagerName) {
        // Convert the first letter after '-', '_', and '.' to upper case
        // and simultaneously remove the matched character before the lowercase letter.
        $entityManagerName = preg_replace_callback('/[\-_.]([a-z])/', function ($matches) {
            return strtoupper($matches[1]);
        }, $entityManagerName);
        // Remove any non-alphanumeric characters.
        $entityManagerName = preg_replace('/[^a-zA-Z0-9]+/', '', $entityManagerName);
        // Make sure the first character is a letter and that it is uppercase.
        $entityManagerName = preg_replace_callback('/^\d*([a-zA-Z])/', function ($matches) {
            return strtoupper($matches[1]);
        }, $entityManagerName);
        // If the entity manager name is 'Default', change it to something else because this is a reserved keyword in namespaces.
        if ($entityManagerName == 'Default') {
            $entityManagerName = self::DEFAULT_ENTITY_MANAGER_SANITIZED_NAME;
        }

        return $entityManagerName;
    }

    /**
     * Unsanitizes an entity manager name so that it appears as it is originally defined in the configuration.
     *
     * @throws \Exception If the entity manager name could not be unsanitized.
     * @param string $sanitizedEntityManagerName The entity manager name to be unsanitized.
     * @return string The unsanitized entity manager name.
     */
    public function getUnsanitizeEntityManagerName($sanitizedEntityManagerName)
    {
        // Get all unsanitized entity manager names.
        $unsanitizedEntityManagerNames = $this->getAllEntityManagerNames();
        // Try to sanitize each one of those entity manager names.
        foreach ($unsanitizedEntityManagerNames as $unsanitizedEntityManagerName) {
            $sanitizedName = $this->getSanitizedEntityManagerName($unsanitizedEntityManagerName);
            if ($sanitizedName == $sanitizedEntityManagerName) {
                // Found a match. Return the unsanitized entity manager name.
                return $unsanitizedEntityManagerName;
            }
        }

        // If this point is reached, no match was found, so the sanitization process could not be reversed.
        throw new \Exception(sprintf("Could not unsanitize entity manager name '%s'. The entity manager might no longer exist.", $sanitizedEntityManagerName));
    }

    /**
     * Builds a list of entity managers which have custom data source entities created for them.
     *
     * @return array of unsanitized entity manager names.
     */
    public function getEntityManagersWithDataSourceEntities()
    {
        // Get all entity managers.
        $unsanitizedEntityManagerNames = $this->getAllEntityManagerNames();
        // Identify which entity managers have data source entities.
        $unsanitizedEntityManagerNamesWithExistingEntities = array();
        foreach ($unsanitizedEntityManagerNames as $unsanitizedEntityManagerName) {
            // Get the directory where the entity manager would have its data source entities.
            $directory = $this->getDataSourceEntityDirectory($unsanitizedEntityManagerName);

            // Count how many files are in the directory.
            try {
                $finder = new Finder();
                $entityCount = $finder->files()->in($directory)->count();
                if ($entityCount > 0) {
                    // This entity manager has data source entities.
                    $unsanitizedEntityManagerNamesWithExistingEntities[] = $unsanitizedEntityManagerName;
                }
            }
            catch (\Exception $e) {
                // The directory does not exist or it is unreadable.
                // Silently ignore this as it is normal in most cases.
            }
        }

        return $unsanitizedEntityManagerNamesWithExistingEntities;
    }


    /**
     * Builds a list of entity managers which have custom diagram entities created for them.
     *
     * @return array of unsanitized entity manager names.
     */
    public function getEntityManagersWithDiagramEntities()
    {
        // Get all entity managers.
        $unsanitizedEntityManagerNames = $this->getAllEntityManagerNames();
        // Identify which entity managers have data source entities.
        $unsanitizedEntityManagerNamesWithExistingEntities = array();
        foreach ($unsanitizedEntityManagerNames as $unsanitizedEntityManagerName) {
            // Get the directory where the entity manager would have its data source entities.
            $directory = $this->getDiagramEntityDirectory($unsanitizedEntityManagerName);

            // Count how many files are in the directory.
            try {
                $finder = new Finder();
                $entityCount = $finder->files()->in($directory)->count();
                if ($entityCount > 0) {
                    // This entity manager has data source entities.
                    $unsanitizedEntityManagerNamesWithExistingEntities[] = $unsanitizedEntityManagerName;
                }
            }
            catch (\Exception $e) {
                // The directory does not exist or it is unreadable.
                // Silently ignore this as it is normal in most cases.
            }
        }

        return $unsanitizedEntityManagerNamesWithExistingEntities;
    }

    /**
     * Fetches a list of entity managers' names available in the application.
     *
     * @return string[] A list of unsanitized entity manager names.
     */
    protected function getAllEntityManagerNames()
    {
        // Get all custom entity managers that exist in the database configuration.
        $databaseConfigurator = $this->container->get('data_consolidation.database_configurator');
        $entityManagerNames = $databaseConfigurator->getEntityManagerNames();
        // Also prepend the default entity manager to that list.
        array_unshift($entityManagerNames, 'default');

        return $entityManagerNames;
    }

    /**
     * Fetches the data source entities managed by a certain entity manager.
     *
     * @param string $entityManagerName The unsanitized name of the entity manager, as defined in the configuration.
     * @return array of fully qualified class names.
     */
    public function getDataSourceEntityNamesForEntityManager($entityManagerName)
    {
        // Obtain the namespace for the custom entities managed by this entity manager.
        // Also append a trailing backslash to it so it can be used more easily later on.
        $namespace = $this->getDataSourceEntityNamespace($entityManagerName) . '\\';
        // Get the entity names for that entity manager and namespace prefix.
        $fullyQualifiedClassNames = $this->getEntityNamesForEntityManager($entityManagerName, $namespace);

        return $fullyQualifiedClassNames;
    }

    /**
     * Fetches the diagram entities managed by a certain entity manager.
     *
     * @param string $entityManagerName The unsanitized name of the entity manager, as defined in the configuration.
     * @return array of fully qualified class names.
     */
    public function getDiagramEntityNamesForEntityManager($entityManagerName)
    {
        // Obtain the namespace for the custom entities managed by this entity manager.
        // Also append a trailing backslash to it so it can be used more easily later on.
        $namespace = $this->getDiagramEntityNamespace($entityManagerName) . '\\';
        // Get the entity names for that entity manager and namespace prefix.
        $fullyQualifiedClassNames = $this->getEntityNamesForEntityManager($entityManagerName, $namespace);

        return $fullyQualifiedClassNames;
    }

    /**
     * Fetches the custom entities managed by a certain entity manager.
     *
     * @param string $entityManagerName The unsanitized name of the entity manager, as defined in the configuration.
     * @param $namespacePrefix string A namespace prefix that all found entities should share.
     * @return array of fully qualified class names.
     */
    protected function getEntityNamesForEntityManager($entityManagerName, $namespacePrefix)
    {
        // Get the Doctrine entity manager with the specified name.
        $em = $this->container->get('doctrine')->getManager($entityManagerName);
        // Get all fully qualified class names managed by this entity manager.
        $metas = $em->getMetadataFactory()->getAllMetadata();
        $fullyQualifiedClassNames = array();
        foreach ($metas as $meta) {
            $fullyQualifiedClassNames[] = $meta->getName();
        }

        // Filter out the class names which do not start with the specified namespace prefix.
        $fullyQualifiedClassNames = array_filter($fullyQualifiedClassNames, function ($fullyQualifiedClassName) use ($namespacePrefix) {
            if (strpos($fullyQualifiedClassName, $namespacePrefix) === 0) {
                // This class is of the correct namespace.
                return true;
            }
            else {
                // This class is not of the correct namespace. Filter it out.
                return false;
            }
        });

        return $fullyQualifiedClassNames;
    }

    /**
     * Fetches the data source entities managed by all entity managers.
     *
     * @param boolean $groupByEntityManagerName optional If set to TRUE, the results will be grouped in subarrays with key names the unsanitized entity manager names.
     * @return array of fully qualified class names if $groupByEntityManagerName was set to FALSE,
     *  or an array of subarrays with key names the unsanitized entity manager names and elements the fully qualified class names.
     */
    public function getAllDataSourceEntityNames($groupByEntityManagerName = false)
    {
        // Get all entity managers with custom data source entities.
        $unsanitizedEntityManagerNames = $this->getEntityManagersWithDataSourceEntities();

        // Build a list of fully qualified custom entity class names for each entity manager.
        $fullyQualifiedClassNames = array();
        foreach ($unsanitizedEntityManagerNames as $entityManagerName) {
            $classNames = $this->getDataSourceEntityNamesForEntityManager($entityManagerName);
            if (!$groupByEntityManagerName) {
                // Just add the class name normally to the results array.
                $fullyQualifiedClassNames = array_merge($fullyQualifiedClassNames, $classNames);
            }
            else {
                // Place the class name under the proper entity manager element in the results array.
                $fullyQualifiedClassNames[$entityManagerName] = $classNames;
            }
        }

        return $fullyQualifiedClassNames;
    }

    /**
     * Fetches the diagram entities managed by all entity managers.
     *
     * @param boolean $groupByEntityManagerName optional If set to TRUE, the results will be grouped in subarrays with key names the unsanitized entity manager names.
     * @return array of fully qualified class names if $groupByEntityManagerName was set to FALSE,
     *  or an array of subarrays with key names the unsanitized entity manager names and elements the fully qualified class names.
     */
    public function getAllDiagramEntityNames($groupByEntityManagerName = false)
    {
        // Get all entity managers with custom diagram entities.
        $unsanitizedEntityManagerNames = $this->getEntityManagersWithDiagramEntities();

        // Build a list of fully qualified custom entity class names for each entity manager.
        $fullyQualifiedClassNames = array();
        foreach ($unsanitizedEntityManagerNames as $entityManagerName) {
            $classNames = $this->getDiagramEntityNamesForEntityManager($entityManagerName);
            if (!$groupByEntityManagerName) {
                // Just add the class name normally to the results array.
                $fullyQualifiedClassNames = array_merge($fullyQualifiedClassNames, $classNames);
            }
            else {
                // Place the class name under the proper entity manager element in the results array.
                $fullyQualifiedClassNames[$entityManagerName] = $classNames;
            }
        }

        return $fullyQualifiedClassNames;
    }

    /**
     * Generates a data source entity from a node configuration object.
     *
     * The generated entity will be written to disk, to the custom entity directory.
     *
     * @param NodeConfig $nodeConfig
     * @param string $entityManagerName The name of the entity manager associated with the doctrine entity.
     *
     * @throws \RuntimeException If the service container could not be obtained.
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException When the metadata for the specified class failed to be generated.
     * @throws \Exception When the doctrine manager is not an EntityManager instance.
     *  This may happen if in the future doctrine starts using a different class for managing entities.
     */
    public function generateDataSourceEntityFromNodeConfig(NodeConfig $nodeConfig, $entityManagerName)
    {
        // Obtain the fully qualified class name for the entity.
        $fullyQualifiedClassName = $this->getFullyQualifiedDataSourceEntityClassName($nodeConfig->getName(), $entityManagerName);
        $this->generateEntityFromNodeConfig($fullyQualifiedClassName);
    }

    /**
     * Generates a diagram entity from a node configuration object.
     *
     * The generated entity will be written to disk, to the custom entity directory.
     *
     * @param NodeConfig $nodeConfig
     * @param string $entityManagerName The name of the entity manager associated with the doctrine entity.
     *
     * @throws \RuntimeException If the service container could not be obtained.
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException When the metadata for the specified class failed to be generated.
     * @throws \Exception When the doctrine manager is not an EntityManager instance.
     *  This may happen if in the future doctrine starts using a different class for managing entities.
     */
    public function generateDiagramEntityFromNodeConfig(NodeConfig $nodeConfig, $entityManagerName)
    {
        // Obtain the fully qualified class name for the entity.
        $fullyQualifiedClassName = $this->getFullyQualifiedDiagramEntityClassName($nodeConfig->getName(), $entityManagerName);
        $this->generateEntityFromNodeConfig($fullyQualifiedClassName);
    }

    /**
     * Generates a doctrine entity from a node configuration object.
     *
     * The generated entity will be written to disk, to the custom entity directory.
     *
     * @param string $fullyQualifiedClassName The fully qualified class name of the custom entity.
     *
     * @throws \RuntimeException If the service container could not be obtained.
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException When the metadata for the specified class failed to be generated.
     * @throws \Exception When the doctrine manager is not an EntityManager instance.
     *  This may happen if in the future doctrine starts using a different class for managing entities.
     */
    protected function generateEntityFromNodeConfig($fullyQualifiedClassName)
    {
        if (empty($this->container)) {
            throw new \RuntimeException('Could not obtain service container.');
        }

        // Base directory for the generated entity classes.
        // Note that the entity namespace will control the subdirectory structure.
        $customEntityDirectory = $this->getBaseSrcDirectory();

        $em = $this->container->get('doctrine')->getManager();
        if (!($em instanceof EntityManager)) {
            throw new \Exception("Got an invalid entity manager when trying to generate an entity.");
        }

        // Set a custom driver that generates doctrine entity ClassMetadata based on custom node configuration.
        $driver = new CustomNodesDriver($em);
        $em->getConfiguration()->setMetadataDriverImpl($driver);

        // Setup the factory for generating ClassMetadata.
        $cmf = new DisconnectedClassMetadataFactory();
        $cmf->setEntityManager($em);
        // Setup the class name with a proper namespace for this custom node config.
        $classMetadata = $cmf->getMetadataFor($fullyQualifiedClassName);

        $generator = new EntityGenerator();
        $generator->setUpdateEntityIfExists(true);
        $generator->setGenerateStubMethods(true);
        $generator->setGenerateAnnotations(true);
        // Fully regenerate existing entities, since the changes to them may be major.
        $generator->setRegenerateEntityIfExists(true);
        // Do not make backups. The user has already been warned that the entity will be overwritten.
        $generator->setBackupExisting(false);
        $generator->writeEntityClass($classMetadata, $customEntityDirectory);
    }

    /**
     * Creates a node configuration by reading an existing table in a database.
     *
     * @param string $entityManagerName The name of the entity manager used for connecting to the database.
     * @param string $tableName The name of the database table from which the schema should be imported.
     * @param NodeConfig|null $nodeConfig An optional nodeConfig which will get the table data populated.
     *      Be careful with specifying such a parameter, since it will not clear additional properties that this function does not normally handle.
     * @return NodeConfig The modified nodeConfig object passed as a parameter,
     *  or the newly-created object based on the database schema.
     */
    public function createNodeConfigFromDatabaseSchema($entityManagerName, $tableName, NodeConfig $nodeConfig = null)
    {
        if (empty($this->container)) {
            throw new \RuntimeException('Could not obtain service container.');
        }
        $em = $this->container->get('doctrine')->getManager($entityManagerName);

        $databaseDriver = new DatabaseDriver($em->getConnection()->getSchemaManager());
        $em->getConfiguration()->setMetadataDriverImpl($databaseDriver);
        $cmf = new DisconnectedClassMetadataFactory();
        $cmf->setEntityManager($em);
        $metadata = $cmf->getAllMetadata();

        // Try to find the proper class metadata based on table name.
        $selectedMetadata = null;
        foreach ($metadata as $classMetadata) {
            if ($classMetadata->getTableName() == $tableName) {
                // Found the proper metadata.
                $selectedMetadata = $classMetadata;
                break;
            }
        }

        if (!$selectedMetadata) {
            // Could not find metadata.
            throw new \Exception(sprintf("Failed to obtain class metadata for table name '%s' and entity manager '%s'.", $tableName, $entityManagerName));
        }

        // Fetch the available generator strategy constants with their corresponding values.
        $generatorStrategies = array(
            'NONE' => null,
            'AUTO' => null,
            'SEQUENCE' => null,
            'IDENTITY' => null,
            'UUID' => null,
        );
        foreach ($generatorStrategies as $strategyName => $value) {
            $generatorStrategies[$strategyName] = constant('Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_' . $strategyName);
        }

        // Build the node config based on the class metadata.
        if (!$nodeConfig) {
            // No node config specified as an argument. Create one now.
            $nodeConfig = new NodeConfig();
        }
        $nodeConfig->setName($tableName);
        $nodeConfig->setTableName($tableName);
        // Add node config fields based on the table columns.
        $fieldNames = $selectedMetadata->getFieldNames();
        foreach ($fieldNames as $fieldName) {
            $nodeConfigField = new NodeConfigField();
            $nodeConfigField->setName($selectedMetadata->getColumnName($fieldName));
            $nodeConfigField->setType($selectedMetadata->getTypeOfField($fieldName));

            // Read field-specific options and build an object based on them.
            $nodeConfigOptions = new NodeConfigOptions();
            if ($selectedMetadata->isIdentifier($fieldName)) {
                $nodeConfigOptions->setPrimaryKey(true);

                // Try to determine the correct generator strategy.
                $generatorStrategyName = array_search($selectedMetadata->generatorType, $generatorStrategies);
                if (!empty($generatorStrategyName)) {
                    $nodeConfigOptions->setStrategy($generatorStrategyName);
                }
            }
            if ($selectedMetadata->isNullable($fieldName)) {
                $nodeConfigOptions->setNullable(true);
            }
            // The class metadata provides a method for checking if the field is unique but in practice it does not seem to do anything.
            // Check also with a custom method if there is a unique constraint for the field.
            if ($selectedMetadata->isUniqueField($fieldName) || $this->isUniqueField($fieldName, $selectedMetadata)) {
                $nodeConfigOptions->setUnique(true);
            }
            $fieldMapping = $selectedMetadata->getFieldMapping($fieldName);
            if (isset($fieldMapping['precision'])) {
                $nodeConfigOptions->setPrecision($fieldMapping['precision']);
            }
            if (isset($fieldMapping['scale'])) {
                $nodeConfigOptions->setScale($fieldMapping['scale']);
            }
            if (isset($fieldMapping['length'])) {
                $nodeConfigOptions->setLength($fieldMapping['length']);
            }

            $nodeConfigField->setOptions($nodeConfigOptions, false);
            $nodeConfig->addField($nodeConfigField);
        }

        return $nodeConfig;
    }

    /**
     * Attempts to extract the entity manager name from the namespace of a custom doctrine entity class.
     *
     * @param string $fullyQualifiedClassName The fully qualified entity class name (prefixed by the proper namespace).
     * @throws \RuntimeException If the entity manager name could not be extracted.
     * @return string The sanitized entity manager name for the class.
     */
    public function getSanitizedEntityManagerNameFromClassName($fullyQualifiedClassName)
    {
        // Example fully qualified class names:
        // #1: From default entity manager namespace:
        // DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Custom\Diagram\Diagram1
        // #2: From a custom entity manager namespace:
        // DataConsolidation\CustomNodesBundle\Entity\CustomEntityManagers\DataSourceDb1\Custom\DataSource\DataSource1

        // Split the fully qualified class name into components separated by a backslash.
        $namespaceComponents = explode('\\', $fullyQualifiedClassName);
        // The namespace components are supposed to be 7 for the default entity manager, or 8 for a custom entity manager.
        if (count($namespaceComponents) == 7 && $namespaceComponents[3] == self::DEFAULT_ENTITY_MANAGER_SANITIZED_NAME) {
            // The class belongs to the default entity manager.
            return $namespaceComponents[3];
        }
        elseif (count($namespaceComponents) == 8) {
            // The class belongs to a custom entity manager.
            return $namespaceComponents[4];
        }
        else {
            throw new \RuntimeException(sprintf("Failed to extract sanitized entity manager name from class name '%s'.", $fullyQualifiedClassName));
        }
    }

    /**
     * Fetches the short (unqualified) name of a class.
     *
     * @param string $className A class name which may be fully qualified or unqualified.
     * @return string The short (unqualified_ class name.
     */
    public function getShortClassName($className)
    {
        // Find the last occurrence of a backslash.
        $lastPosition = strrpos($className, '\\');
        if ($lastPosition === FALSE) {
            // Looks like this is not a fully qualified class name. Just return it as it is.
            return $className;
        }
        else {
            // Extract everything after the last occurrence of the backslash.
            return substr($className, $lastPosition + 1);
        }
    }

    /**
     * Generates a fully qualified class name for a data source doctrine entity.
     *
     * @param string $unqualifiedClassName The class name of the entity, without any prefixed namespace.
     * @param string $entityManagerName The name of the entity manager associated with the doctrine entity.
     * @param bool $sanitizeEntityManagerName If set to true, the $entityManagerName parameter will be sanitized
     *      (it will be converted to the expected format that can be used in namespaces).
     *      If set to false, the $entityManagerName will not be modified.
     * @return string The fully qualified entity class name (prefixed by the proper namespace).
     */
    public function getFullyQualifiedDataSourceEntityClassName($unqualifiedClassName, $entityManagerName, $sanitizeEntityManagerName = true)
    {
        $customEntityNamespace = $this->getDataSourceEntityNamespace($entityManagerName, $sanitizeEntityManagerName);
        $fullyQualifiedClassName = $customEntityNamespace . '\\' . $unqualifiedClassName;
        return $fullyQualifiedClassName;
    }

    /**
     * Generates a fully qualified class name for a diagram doctrine entity.
     *
     * @param string $unqualifiedClassName The class name of the entity, without any prefixed namespace.
     * @param string $entityManagerName The name of the entity manager associated with the doctrine entity.
     * @param bool $sanitizeEntityManagerName If set to true, the $entityManagerName parameter will be sanitized
     *      (it will be converted to the expected format that can be used in namespaces).
     *      If set to false, the $entityManagerName will not be modified.
     * @return string The fully qualified entity class name (prefixed by the proper namespace).
     */
    public function getFullyQualifiedDiagramEntityClassName($unqualifiedClassName, $entityManagerName, $sanitizeEntityManagerName = true)
    {
        $customEntityNamespace = $this->getDiagramEntityNamespace($entityManagerName, $sanitizeEntityManagerName);
        $fullyQualifiedClassName = $customEntityNamespace . '\\' . $unqualifiedClassName;
        return $fullyQualifiedClassName;
    }

    /**
     * Checks if there is a generated data source doctrine entity.
     *
     * @param string $unqualifiedClassName The class name of the entity, without any prefixed namespace.
     * @param string $entityManagerName The name of the entity manager associated with the doctrine entity.
     * @param bool $sanitizeEntityManagerName If set to true, the $entityManagerName parameter will be sanitized
     *      (it will be converted to the expected format that can be used in namespaces).
     *      If set to false, the $entityManagerName will not be modified.
     * @return bool true if the entity exists, or false otherwise.
     */
    public function dataSourceEntityExists($unqualifiedClassName, $entityManagerName, $sanitizeEntityManagerName = true)
    {
        $customEntityDirectory = $this->getDataSourceEntityDirectory($entityManagerName, $sanitizeEntityManagerName);
        $fs = new Filesystem();
        $path = $customEntityDirectory . $unqualifiedClassName . '.php';
        $fileExists = $fs->exists($path);

        return $fileExists;
    }

    /**
     * Checks if there is a generated diagram doctrine entity.
     *
     * @param string $unqualifiedClassName The class name of the entity, without any prefixed namespace.
     * @param string $entityManagerName The name of the entity manager associated with the doctrine entity.
     * @param bool $sanitizeEntityManagerName If set to true, the $entityManagerName parameter will be sanitized
     *      (it will be converted to the expected format that can be used in namespaces).
     *      If set to false, the $entityManagerName will not be modified.
     * @return bool true if the entity exists, or false otherwise.
     */
    public function diagramEntityExists($unqualifiedClassName, $entityManagerName, $sanitizeEntityManagerName = true)
    {
        $customEntityDirectory = $this->getDiagramEntityDirectory($entityManagerName, $sanitizeEntityManagerName);
        $fs = new Filesystem();
        $path = $customEntityDirectory . $unqualifiedClassName . '.php';
        $fileExists = $fs->exists($path);

        return $fileExists;
    }

    /**
     * Generates a generated data source doctrine entity.
     *
     * @param string $unqualifiedClassName The class name of the entity, without any prefixed namespace.
     * @param string $entityManagerName The name of the entity manager associated with the doctrine entity.
     * @param bool $sanitizeEntityManagerName If set to true, the $entityManagerName parameter will be sanitized
     *      (it will be converted to the expected format that can be used in namespaces).
     *      If set to false, the $entityManagerName will not be modified.
     */
    public function deleteDataSourceEntity($unqualifiedClassName, $entityManagerName, $sanitizeEntityManagerName = true)
    {
        $customEntityDirectory = $this->getDataSourceEntityDirectory($entityManagerName, $sanitizeEntityManagerName);
        $fs = new Filesystem();
        $path = $customEntityDirectory . $unqualifiedClassName . '.php';
        $fs->remove($path);

        // Check if there are anymore files in the directory.
        $finder = new Finder();
        $finder->in($customEntityDirectory);
        if ($finder->count() < 1) {
            // Remove the directory itself.
            $fs->remove($customEntityDirectory);
        }
    }

    /**
     * Generates a generated diagram doctrine entity.
     *
     * @param string $unqualifiedClassName The class name of the entity, without any prefixed namespace.
     * @param string $entityManagerName The name of the entity manager associated with the doctrine entity.
     * @param bool $sanitizeEntityManagerName If set to true, the $entityManagerName parameter will be sanitized
     *      (it will be converted to the expected format that can be used in namespaces).
     *      If set to false, the $entityManagerName will not be modified.
     */
    public function deleteDiagramEntity($unqualifiedClassName, $entityManagerName, $sanitizeEntityManagerName = true)
    {
        $customEntityDirectory = $this->getDiagramEntityDirectory($entityManagerName, $sanitizeEntityManagerName);
        $fs = new Filesystem();
        $path = $customEntityDirectory . $unqualifiedClassName . '.php';
        $fs->remove($path);

        // Check if there are anymore files in the directory.
        $finder = new Finder();
        $finder->in($customEntityDirectory);
        if ($finder->count() < 1) {
            // Remove the directory itself.
            $fs->remove($customEntityDirectory);
        }
    }

    /**
     * Checks if an entity's database exists.
     *
     * @param string $fullyQualifiedClassName The fully qualified entity class name (prefixed by the proper namespace).
     * @return bool true if the entity database exists, or false otherwise.
     */
    public function entityDatabaseExists($fullyQualifiedClassName)
    {
        $em = $this->getEntityManager($fullyQualifiedClassName);

        $connection = $em->getConnection();
        // Obtain a temporary connection that does not specify a database name, similar to the cli doctrine create database command.
        // This is necessary because if the database does not exist, listing the existing databases will throw an exception.
        $tmpConnection = $this->getConnectionWithoutDatabaseName($connection);
        // Get a schema manager based on the temporary connection that does not specify a database name.
        $tmpSchemaManager = $tmpConnection->getSchemaManager();

        // Check if the database to be used by this entity manager exists.
        $databases = $tmpSchemaManager->listDatabases();
        $databaseName = $connection->getDatabase();
        if (in_array($databaseName, $databases)) {
            // The database exists.
            return true;
        }
        else {
            // The database does not exist.
            return false;
        }
    }

    /**
     * Creates a database for an entity.
     *
     * NOTE: It is expected that it has been checked that the database does not exist before calling this method.
     *
     * @param string $fullyQualifiedClassName The fully qualified entity class name (prefixed by the proper namespace).
     */
    public function createDatabase($fullyQualifiedClassName)
    {
        $em = $this->getEntityManager($fullyQualifiedClassName);
        $connection = $em->getConnection();
        // Obtain a temporary connection that does not specify a database name, similar to the cli doctrine create database command.
        // This is necessary because if the database does not exist, it is impossible to create it with the entity manager's connection.
        $tmpConnection = $this->getConnectionWithoutDatabaseName($connection);
        // Get a schema manager based on the temporary connection that does not specify a database name.
        $tmpSchemaManager = $tmpConnection->getSchemaManager();

        // Get the database name from the original connection.
        $databaseName = $connection->getDatabase();

        // Create the database.
        $tmpSchemaManager->createDatabase($databaseName);
    }

    /**
     * Deletes a database for an entity.
     *
     * NOTE: It is expected that it has been checked that the database exists before calling this method.
     *
     * @param string $fullyQualifiedClassName The fully qualified entity class name (prefixed by the proper namespace).
     */
    public function dropDatabase($fullyQualifiedClassName)
    {
        $em = $this->getEntityManager($fullyQualifiedClassName);
        $connection = $em->getConnection();
        // Obtain a temporary connection that does not specify a database name, similar to the cli doctrine create database command.
        // This is necessary because it is impossible to drop a database with a schema manager that is currently connected to it.
        $tmpConnection = $this->getConnectionWithoutDatabaseName($connection);
        // Get a schema manager based on the temporary connection that does not specify a database name.
        $tmpSchemaManager = $tmpConnection->getSchemaManager();

        // Get the database name from the original connection.
        $databaseName = $connection->getDatabase();

        // Drop the database.
        $tmpSchemaManager->dropDatabase($databaseName);
    }

    /**
     * Checks if an entity's database table exists.
     *
     * @param string $fullyQualifiedClassName The fully qualified entity class name (prefixed by the proper namespace).
     * @return bool true if the entity's database table exists, or false otherwise.
     */
    public function entityTableExists($fullyQualifiedClassName)
    {
        $em = $this->getEntityManager($fullyQualifiedClassName);
        $connection = $em->getConnection();
        // Get a schema manager for the connection that specifies a database name.
        $schemaManager = $connection->getSchemaManager();
        // Obtain the table name from the entity's class metadata.
        $cmf = new DisconnectedClassMetadataFactory();
        $cmf->setEntityManager($em);
        $classMetadata = $cmf->getMetadataFor($fullyQualifiedClassName);
        $table = $classMetadata->getTableName();
        // Check if this database already contains the table for the doctrine entity.
        $tables = array($table);
        if ($schemaManager->tablesExist($tables)) {
            // The table exists.
            return true;
        }
        else {
            // The table does not exist.
            return false;
        }
    }

    /**
     * Fetches a list of database table names for a specified entity manager's connection.
     *
     * @param string $entityManagerName The name of the entity manager whose connection is to be used.
     * @return string[] A list of database table names.
     */
    public function getTableList($entityManagerName)
    {
        if (empty($this->container)) {
            throw new \RuntimeException('Could not obtain service container.');
        }
        $em = $this->container->get('doctrine')->getManager($entityManagerName);

        // Get a schema manager for the connection that specifies a database name.
        $connection = $em->getConnection();
        $schemaManager = $connection->getSchemaManager();
        // List the database tables names.
        $tableNames = $schemaManager->listTableNames();

        return $tableNames;
    }

    /**
     * Fetches the SQL required for updating the entity's database table schema.
     *
     * NOTE: It is expected that it has been checked that the entity database table exists before calling this method.
     *
     * @param string $fullyQualifiedClassName The fully qualified entity class name (prefixed by the proper namespace).
     * @return array SQL queries needed for updating the database schema.
     */
    public function getUpdateSchemaSql($fullyQualifiedClassName)
    {
        $em = $this->getEntityManager($fullyQualifiedClassName);
        $metadata = $em->getMetadataFactory()->getAllMetadata();

        // Obtain a schema tool used for creating or updating database tables.
        $schemaTool = new SchemaTool($em);
        // Get the SQL for updating the table(s).
        $queries = $schemaTool->getUpdateSchemaSql($metadata, true);

        return $queries;
    }

    /**
     * Updates the entity's database table schema.
     *
     * NOTE: It is expected that it has been checked that the entity database table exists before calling this method.
     *
     * @param string $fullyQualifiedClassName The fully qualified entity class name (prefixed by the proper namespace).
     */
    public function updateSchema($fullyQualifiedClassName)
    {
        $em = $this->getEntityManager($fullyQualifiedClassName);
        $metadata = $em->getMetadataFactory()->getAllMetadata();

        // Obtain a schema tool used for creating or updating database tables.
        $schemaTool = new SchemaTool($em);
        // Update the table schema.
        $schemaTool->updateSchema($metadata, true);
    }

    /**
     * Fetches the SQL required for creating the entity's database table schema.
     *
     * NOTE: It is expected that it has been checked that the entity database table does not exist before calling this method.
     *
     * @param string $fullyQualifiedClassName The fully qualified entity class name (prefixed by the proper namespace).
     * @return array SQL queries needed for creating the database schema.
     */
    public function getCreateSchemaSql($fullyQualifiedClassName)
    {
        $em = $this->getEntityManager($fullyQualifiedClassName);
        $classMetadata = $this->getClassMetadata($em, $fullyQualifiedClassName);
        $metadata = array($classMetadata);

        // Obtain a schema tool used for creating or updating database tables.
        $schemaTool = new SchemaTool($em);
        // Get the SQL for creating the table(s).
        $queries = $schemaTool->getCreateSchemaSql($metadata);

        return $queries;
    }

    /**
     * Creates the entity's database table schema.
     *
     * NOTE: It is expected that it has been checked that the entity database table does not exist before calling this method.
     *
     * @param string $fullyQualifiedClassName The fully qualified entity class name (prefixed by the proper namespace).
     */
    public function createSchema($fullyQualifiedClassName)
    {
        $em = $this->getEntityManager($fullyQualifiedClassName);
        $classMetadata = $this->getClassMetadata($em, $fullyQualifiedClassName);
        $metadata = array($classMetadata);

        // Obtain a schema tool used for creating or updating database tables.
        $schemaTool = new SchemaTool($em);
        // Create the table.
        $schemaTool->createSchema($metadata);
    }

    /**
     * Deletes the entity's database table schema.
     *
     * NOTE: It is expected that it has been checked that the entity database table exists before calling this method.
     *
     * @param string $fullyQualifiedClassName The fully qualified entity class name (prefixed by the proper namespace).
     */
    public function dropSchema($fullyQualifiedClassName)
    {
        $em = $this->getEntityManager($fullyQualifiedClassName);
        $classMetadata = $this->getClassMetadata($em, $fullyQualifiedClassName);
        $metadata = array($classMetadata);

        // Obtain a schema tool used for dropping the database tables.
        $schemaTool = new SchemaTool($em);
        // Drop the table.
        $schemaTool->dropSchema($metadata);
    }

    /**
     * Fetches reflection getters from a doctrine entity class.
     *
     * @param string $fullyQualifiedClassName The fully qualified entity class name (prefixed by the proper namespace).
     * @param array $allowedPropertyNames optional If specified, only certain properties' getters will be included in the result.
     *      If not specified, all getters will be included in the result.
     * @param boolean $returnMethodsNames optional If set to TRUE only the method names will be returned.
     *  If set to FALSE, the ReflectionMethod objects will be returned.
     * @return ReflectionMethod[]|string[] The selected reflection getters or their names. The result array keys will be the property names.
     */
    public function getReflectionGetters($fullyQualifiedClassName, array $allowedPropertyNames = array(), $returnMethodsNames = false)
    {
        // Attempt to create a reflection class for the specified custom entity.
        // This will throw an exception if the class is invalid.
        $refl = new \ReflectionClass($fullyQualifiedClassName);

        // Fetch the public method names of the reflection class.
        $publicReflMethods = $refl->getMethods(\ReflectionMethod::IS_PUBLIC);

        // The method name should start with 'has', 'get', or 'is', followed by an upper case letter.
        $getterPattern = '/^(?:get|has|is)([A-Z].*)/';

        $filterProperties = false;
        if (count($allowedPropertyNames) > 0) {
            // Only some specific property names are allowed.
            $filterProperties = true;
        }

        // Get the methods which are getters and are non-static.
        $reflGetterMethods = array();
        foreach ($publicReflMethods as $reflMethod) {
            $matches = array();
            // Check if the reflection method is non-static and that it matches the getter naming pattern.
            if (!$reflMethod->isStatic() && preg_match($getterPattern, $reflMethod->getName(), $matches)) {
                // This looks like a valid getter.
                // Extract the property name from the regex pattern matches. Make the first letter lowercase.
                $propertyName = lcfirst($matches[1]);
                if ($filterProperties) {
                    // Only specific property names were requested. Check if this is one of them.
                    if (in_array($propertyName, $allowedPropertyNames)) {
                        // This property name should be included in the result.
                        $reflGetterMethods[$propertyName] = $reflMethod;
                    }
                }
                else {
                    // No specific properties were requested, so just add this getter directly to the result.
                    $reflGetterMethods[$propertyName] = $reflMethod;
                }
            }
        }

        if ($returnMethodsNames) {
            // Return just the names of the methods instead of the ReflectionMethod objects.
            foreach ($reflGetterMethods as $propertyName => $reflMethod) {
                $reflGetterMethods[$propertyName] = $reflMethod->getName();
            }
        }

        return $reflGetterMethods;
    }

    /**
     * Selects the proper getter for a property.
     *
     * @param string $propertyName The name of the property whose getter is to be found.
     * @param array $getterNames A list of getter method names available for the class.
     * @return string|null The selected getter name if available, or NULL otherwise.
     */
    public function findGetterForProperty($propertyName, array $getterNames)
    {
        // The method name should start with 'has', 'get', or 'is', followed by the property name with a capitalized first letter.
        $propertyName = ucfirst($propertyName);
        $getterPattern = '/^(?:get|has|is)' . preg_quote($propertyName) . '$/';
        $selectedGetters = preg_grep($getterPattern, $getterNames);

        if (count($selectedGetters) > 0) {
            // Found at least one getter matching the proper pattern. Return the first one.
            return reset($selectedGetters);
        }
        else {
            // No getters available for this property.
            return NULL;
        }
    }

    /**
     * Fetches reflection setters from a doctrine entity class.
     *
     * @param string $fullyQualifiedClassName The fully qualified entity class name (prefixed by the proper namespace).
     * @param array $allowedPropertyNames optional If specified, only certain properties' setters will be included in the result.
     *      If not specified, all setters will be included in the result.
     * @param boolean $returnMethodsNames optional If set to TRUE only the method names will be returned.
     *  If set to FALSE, the ReflectionMethod objects will be returned.
     * @return ReflectionMethod[]|string[] The selected reflection getters or their names. The result array keys will be the property names.
     */
    public function getReflectionSetters($fullyQualifiedClassName, array $allowedPropertyNames = array(), $returnMethodsNames = false)
    {
        // Attempt to create a reflection class for the specified custom entity.
        // This will throw an exception if the class is invalid.
        $refl = new \ReflectionClass($fullyQualifiedClassName);

        // Fetch the public method names of the reflection class.
        $publicReflMethods = $refl->getMethods(\ReflectionMethod::IS_PUBLIC);

        // The method name should start with 'set', followed by an upper case letter.
        $setterPattern = '/^(?:set)([A-Z].*)/';

        $filterProperties = false;
        if (count($allowedPropertyNames) > 0) {
            // Only some specific property names are allowed.
            $filterProperties = true;
        }

        // Get the methods which are setters and are non-static.
        $reflSetterMethods = array();
        foreach ($publicReflMethods as $reflMethod) {
            $matches = array();
            // Check if the reflection method is non-static and that it matches the setter naming pattern.
            if (!$reflMethod->isStatic() && preg_match($setterPattern, $reflMethod->getName(), $matches)) {
                // This looks like a valid setter.
                // Extract the property name from the regex pattern matches. Make the first letter lowercase.
                $propertyName = lcfirst($matches[1]);
                if ($filterProperties) {
                    // Only specific property names were requested. Check if this is one of them.
                    if (in_array($propertyName, $allowedPropertyNames)) {
                        // This property name should be included in the result.
                        $reflSetterMethods[$propertyName] = $reflMethod;
                    }
                }
                else {
                    // No specific properties were requested, so just add this setter directly to the result.
                    $reflSetterMethods[$propertyName] = $reflMethod;
                }
            }
        }

        if ($returnMethodsNames) {
            // Return just the names of the methods instead of the ReflectionMethod objects.
            foreach ($reflSetterMethods as $propertyName => $reflMethod) {
                $reflSetterMethods[$propertyName] = $reflMethod->getName();
            }
        }

        return $reflSetterMethods;
    }

    /**
     * Selects the proper setter for a property.
     *
     * @param string $propertyName The name of the property whose setter is to be found.
     * @param array $setterNames A list of setter method names available for the class.
     * @return string|null The selected setter name if available, or NULL otherwise.
     */
    public function findSetterForProperty($propertyName, array $setterNames)
    {
        // The method name should start with 'set', followed by the property name with a capitalized first letter.
        $propertyName = ucfirst($propertyName);
        $setterPattern = '/^(?:get|has|is)' . preg_quote($propertyName) . '$/';
        $selectedSetters = preg_grep($setterPattern, $setterNames);

        if (count($selectedSetters) > 0) {
            // Found at least one setter matching the proper pattern. Return the first one.
            return reset($selectedSetters);
        }
        else {
            // No setters available for this property.
            return NULL;
        }
    }

    /**
     * Fetches the property names of a doctrine entity class.
     *
     * @param string $fullyQualifiedClassName The fully qualified entity class name (prefixed by the proper namespace).
     * @param boolean $convertNamesToCamelCase optional If set to TRUE, property names using the underscore format
     *  will be converted to camel case in the result.
     * @param array $propertiesToIgnore optional If specified, properties in that list will not be included in the end result.
     * @return string[] The names of the properties of the doctrine entity class.
     */
    public function getReflectionPropertyNames($fullyQualifiedClassName, $convertNamesToCamelCase = false, array $propertiesToIgnore = array())
    {
        // Attempt to create a reflection class for the specified custom entity.
        // This will throw an exception if the class is invalid.
        $refl = new \ReflectionClass($fullyQualifiedClassName);

        // Fetch the property names of the custom entity.
        $reflProperties = $refl->getProperties();
        $propertyNames = array();
        foreach ($reflProperties as $reflProperty) {
            $propertyName = $reflProperty->getName();

            // Only add that property to the results if it was not explicitly requested to exclude it.
            if (!in_array($propertyName, $propertiesToIgnore)) {
                $propertyNames[] = $propertyName;
            }
        }

        if ($convertNamesToCamelCase) {
            // Convert the property names to lower camel case.
            $propertyNames = array_map(function ($propertyName) {
                return $this->getLowerCamelCasePropertyName($propertyName);
            }, $propertyNames);
        }

        return $propertyNames;
    }

    /**
     * Converts a property name that may contain underscores to lower camel case.
     *
     * @param string $propertyName The property name to be converted to lower camel case.
     * @return string The lowe camel case property name.
     */
    public function getLowerCamelCasePropertyName($propertyName)
    {
        // Capitalize each word in the property name. Words are separated by '_'.
        // Replace the underscores with white spaces so that ucwords can do the job.
        $propertyName = ucwords(str_replace('_', ' ', $propertyName));
        // Remove all white spaces.
        $propertyName = str_replace(' ', '', $propertyName);
        // Make the first letter lowercase.
        $propertyName = lcfirst($propertyName);

        return $propertyName;
    }

    /**
     * Extracts information from diagram entities for the purpose of visualizing them to the client-side diagram.
     *
     * @param Object[] $diagramEntries Doctrine entries of the same diagram class.
     * @param array $propertiesToIgnore optional If specified, properties in that list will not be included in the end result.
     * @return array[] An array of associative arrays, each containing diagram entry data.
     */
    public function extractInformationFromDiagramEntries($diagramEntries, array $propertiesToIgnore = array())
    {
        if (!is_array($diagramEntries) || count($diagramEntries) < 1) {
            return array();
        }

        // Fetch the available properties for the diagram entries.
        $diagramEntry = reset($diagramEntries);
        $diagramClassName = get_class($diagramEntry);
        $diagramProperties = $this->getReflectionPropertyNames($diagramClassName, true, $propertiesToIgnore);
        // Fetch the getters for those properties.
        $diagramGetters = $this->getReflectionGetters($diagramClassName, $diagramProperties);

        // Extract the information from each diagram entry.
        $diagramDataEntries = array();
        foreach ($diagramEntries as $diagramEntry) {
            $diagramDataEntries[] = $this->extractInformationFromDiagramEntry($diagramEntry, $diagramGetters);
        }

        return $diagramDataEntries;
    }

    /**
     * Extracts information from a diagram entity for the purpose of visualizing them to the client-side diagram.
     *
     * @param Object $diagramEntry A Doctrine entry of a diagram class.
     * @param \ReflectionMethod[] A list of reflection methods used for fetching the diagram properties.
     * @return array[] An associative array of diagram entry data.
     */
    protected function extractInformationFromDiagramEntry($diagramEntry, array $diagramGetters)
    {
        $diagramDataEntry = array();
        foreach ($diagramGetters as $propertyName => $getter) {
            $value = $getter->invoke($diagramEntry);
            // Convert datetimes to unix timestamps.
            if ($value instanceof \DateTime) {
                $value = $value->getTimestamp();
            }
            // Only use the unqualified class name from the data source field, not the one with the namespace.
            if ($propertyName == DiagramConfig::CONFIG_FIELD_NAME_SOURCE) {
                $value = $this->getShortClassName($value);
            }
            $diagramDataEntry[$propertyName] = $value;
        }

        return $diagramDataEntry;
    }

    /**
     * Attempts to fetch a NodeConfig object associated with a doctrine entity class.
     *
     * @param string $unqualifiedClassName The class name of the entity, without any prefixed namespace.
     * @param string $entityManagerName The name of the entity manager associated with the doctrine entity.
     * @param bool $sanitizeEntityManagerName If set to true, the $entityManagerName parameter will be sanitized
     *      (it will be converted to the expected format that can be used in namespaces).
     *      If set to false, the $entityManagerName will not be modified.
     * @return \DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\NodeConfig|null
     */
    public function getNodeConfigForEntity($unqualifiedClassName, $entityManagerName, $sanitizeEntityManagerName = true)
    {
        if (empty($this->container)) {
            throw new \RuntimeException('Could not obtain service container.');
        }
        // Get the default entity manager that handles nodeConfigs.
        $em = $this->container->get('doctrine')->getManager();
        $repository = $em->getRepository('DataConsolidation\\CustomNodesBundle\\Entity\\DefaultEntityManager\\Base\\NodeConfig');
        // Find all nodeConfigs that match the entity name.
        $nodeConfigs = $repository->findBy(array(
            'name' => $unqualifiedClassName,
        ));

        if ($sanitizeEntityManagerName) {
            // The entity manager name is not sanitized.
            // Search for it directly in the node config's target entity managers.
            foreach ($nodeConfigs as $nodeConfig) {
                $targetEntityManagers = $nodeConfig->getTargetEntityManagers();
                if (in_array($entityManagerName, $targetEntityManagers)) {
                    // Found a nodeConfig with a target entity manager name matching the one passed as a parameter.
                    return $nodeConfig;
                }
            }
        }
        else {
            // The entity manager name has already been sanitized,
            // We need to sanitize each target entity manager in the found node configs in order to determine if there is a match.
            foreach ($nodeConfigs as $nodeConfig) {
                // Sanitize each of the target entity managers and see if it matches the one that has been passed as a parameter.
                $targetEntityManagers = $nodeConfig->getTargetEntityManagers();
                foreach ($targetEntityManagers as $targetEntityManager) {
                    if ($this->getSanitizedEntityManagerName($targetEntityManager) == $entityManagerName) {
                        // Found a nodeConfig with a target entity manager name matching the one passed as a parameter.
                        return $nodeConfig;
                    }
                }
            }
        }

        // If this point is reached, no valid node config has been found.
        return null;
    }

    /**
     * Counts the number of database entries for a doctrine entity.
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $em The entity manager for the doctrine entity.
     * @param string $fullyQualifiedClassName The fully qualified entity class name (prefixed by the proper namespace).
     * @param Array $whereConditions optional An array where the keys are the database field names, and the values are subarrays with elements:
     *   'operator' => comparison operator
     *   'value' => one or more values
     * @return int The total number of entries for the specified doctrine entity.
     *
     * @throws \Exception If the $fullyQualifiedClassName does not exist.
     */
    public function getEntityCount($em, $fullyQualifiedClassName, array $whereConditions = array())
    {
        // Do some checks that the class name is in the proper format, to prevent potential security issues.
        if (!class_exists($fullyQualifiedClassName)) {
            throw new \Exception(sprintf("The class name '%s' is invalid."), $fullyQualifiedClassName);
        }

        // Create a count query for the specified entity.
        $qb = $em->createQueryBuilder();
        $qb = $qb->select($qb->expr()->count('o'))
            ->from($fullyQualifiedClassName, 'o');

        if (!empty($whereConditions)) {
            // Add where conditions.
            $i = 1;
            foreach ($whereConditions as $field => $whereCondition) {
                $operator = $whereCondition['operator'];
                $value = $whereCondition['value'];
                if (!is_array($value)) {
                    $qb = $qb->andWhere('o.' . $field . ' ' . $operator . ' ?' . $i);
                }
                elseif ($operator == 'BETWEEN') {
                    $qb = $qb->andWhere('o.' . $field . ' BETWEEN ?' . $i . ' AND ?' . ($i+1));
                    $i++;
                }
                else {
                    $qb = $qb->andWhere('o.' . $field . ' IN (?' . $i . ')');
                }
                $i++;
            }

            // Add parameters for the where conditions.
            $i = 1;
            foreach ($whereConditions as $whereCondition) {
                $operator = $whereCondition['operator'];
                $value = $whereCondition['value'];
                if ($operator != 'BETWEEN') {
                    $qb = $qb->setParameter($i, $value);
                }
                else {
                    $qb = $qb->setParameter($i, $value[0]);
                    $i++;
                    $qb = $qb->setParameter($i, $value[1]);
                }
                $i++;
            }
        }

        $query = $qb->getQuery();
        // Fetch the count.
        $count = $query->getSingleScalarResult();

        return $count;
    }

    /**
     * Queries for  database entries for a doctrine entity in a custom way.
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $em The entity manager for the doctrine entity.
     * @param string $fullyQualifiedClassName The fully qualified entity class name (prefixed by the proper namespace).
     * @param Array $whereConditions optional An array where the keys are the database field names, and the values are subarrays with elements:
     *   'operator' => comparison operator
     *   'value' => one or more values
     * @param array $orderByConditions
     * @param null $limit
     * @param null $offse
     * @return Array The result objects
     *
     * @throws \Exception If the $fullyQualifiedClassName does not exist.
     */
    public function queryForEntities($em, $fullyQualifiedClassName, array $whereConditions = array(), array $orderByConditions = array(), $limit = null, $offset = null)
    {
        // Do some checks that the class name is in the proper format, to prevent potential security issues.
        if (!class_exists($fullyQualifiedClassName)) {
            throw new \Exception(sprintf("The class name '%s' is invalid."), $fullyQualifiedClassName);
        }

        // Create a count query for the specified entity.
        $qb = $em->createQueryBuilder();
        $qb = $qb->select('o')
            ->from($fullyQualifiedClassName, 'o');

        if (!empty($whereConditions)) {
            // Add where conditions.
            $i = 1;
            foreach ($whereConditions as $field => $whereCondition) {
                $operator = $whereCondition['operator'];
                $value = $whereCondition['value'];
                if (!is_array($value)) {
                    $qb = $qb->andWhere('o.' . $field . ' ' . $operator . ' ?' . $i);
                }
                elseif ($operator == 'BETWEEN') {
                    $qb = $qb->andWhere('o.' . $field . ' BETWEEN ?' . $i . ' AND ?' . ($i+1));
                    $i++;
                }
                else {
                    $qb = $qb->andWhere('o.' . $field . ' IN (?' . $i . ')');
                }
                $i++;
            }

            // Add parameters for the where conditions.
            $i = 1;
            foreach ($whereConditions as $whereCondition) {
                $operator = $whereCondition['operator'];
                $value = $whereCondition['value'];
                if ($operator != 'BETWEEN') {
                    $qb = $qb->setParameter($i, $value);
                }
                else {
                    $qb = $qb->setParameter($i, $value[0]);
                    $i++;
                    $qb = $qb->setParameter($i, $value[1]);
                }
                $i++;
            }
        }

        // Order the results.
        foreach ($orderByConditions as $field => $order) {
            $qb = $qb->orderBy('o.' . $field, $order);
        }

        // Apply offset if necessary.
        if (!is_null($offset)) {
            $qb = $qb->setFirstResult($offset);
        }

        // Apply limit if necessary.
        if (!is_null($limit)) {
            $qb = $qb->setMaxResults($limit);
        }

        $query = $qb->getQuery();
        // Fetch the result.
        $result = $query->getResult();

        return $result;
    }

    /**
     * Fetches a list of distinct sources from the diagram entries.
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $em The entity manager for the doctrine entity.
     * @param string $fullyQualifiedClassName The fully qualified entity class name (prefixed by the proper namespace).
     * @param Boolean $useUnqualifiedNames optional If set to true, only the unqualified data source class names will be used.
     * @return string[] A list of source names for the diagram entries.
     *
     * @throws \Exception If the $fullyQualifiedClassName does not exist.
     */
    public function getDiagramDataSources($em, $fullyQualifiedClassName, $useUnqualifiedNames = false)
    {
        // Do some checks that the class name is in the proper format, to prevent potential security issues.
        if (!class_exists($fullyQualifiedClassName)) {
            throw new \Exception(sprintf("The class name '%s' is invalid."), $fullyQualifiedClassName);
        }

        // Create a count query for the specified entity.
        $qb = $em->createQueryBuilder();
        $query = $qb->select('o.source')
            ->distinct()
            ->from($fullyQualifiedClassName, 'o')
            ->getQuery();

        // Fetch the results.
        $results = $query->getResult();

        $diagramDataSources = array();
        if (is_array($results)) {
            foreach ($results as $result) {
                $dataSource = $result['source'];
                if ($useUnqualifiedNames) {
                    $dataSource = $this->getShortClassName($dataSource);
                }
                $diagramDataSources[] = $dataSource;
            }
        }

        return $diagramDataSources;
    }


    /**
     * Fetches the property names of a diagram. This excludes reserved field names available for all diagrams.
     *
     * @param string $fullyQualifiedClassName The fully qualified entity class name (prefixed by the proper namespace).
     * @return string[] The names of the properties of the diagram entity class. Property names using the underscore format
     *  will be converted to camel case in the result.
     */
    public function getDiagramProperties($fullyQualifiedClassName)
    {
        // List of properties which should not be included in the results.
        // These are normally "system fields" that the user should not interfere with.
        $propertiesToBeIgnored = DiagramConfig::getReservedConfigFieldNames();
        $diagramProperties = $this->getReflectionPropertyNames($fullyQualifiedClassName, true, $propertiesToBeIgnored);

        return $diagramProperties;
    }

    /**
     * Inspects the data source and diagram classes, and adds FieldMapping objects for the properties which are not mapped yet.
     * The empty FieldMapping objects will actually only be missing a source getter value.
     *
     * Optionally this method will also remove invalid field mappings.
     *
     * This method is useful when handling forms for DataSourceToDiagramMapping.
     *
     * @param \DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\DataSourceToDiagramMapping $dataSourceToDiagramMapping The data source to diagram mapping object to be modified.
     * @param Boolean $removeInvalidFieldMappings optional If set to TRUE, field mappings which no longer seem to be valid will be removed.
     */
    public function addEmptyFieldMappingsToDataSourceToDiagramMapping(DataSourceToDiagramMapping $dataSourceToDiagramMapping, $removeInvalidFieldMappings = true)
    {
        // Fetch the available getters and setters for the diagram.
        $diagramClassName = $dataSourceToDiagramMapping->getDiagram();
        // List of properties which should not be included in the results.
        // These are normally "system fields" that the user should not interfere with.
        $propertiesToBeIgnored = DiagramConfig::getReservedConfigFieldNames();
        // The measurement time field needs to have a data source to diagram mapping, so it is not to be ignored.
        $relevantReservedDiagramConfigFieldNames = array(
            DiagramConfig::CONFIG_FIELD_NAME_DATETIME,
        );
        $propertiesToBeIgnored = array_values(array_diff($propertiesToBeIgnored, $relevantReservedDiagramConfigFieldNames));

        $diagramProperties = $this->getReflectionPropertyNames($diagramClassName, true, $propertiesToBeIgnored);
        $diagramGetters = $this->getReflectionGetters($diagramClassName, $diagramProperties, true);
        $diagramSetters = $this->getReflectionSetters($diagramClassName, $diagramProperties, true);

        // List of properties which should not be included in the results.
        // These are normally "system fields" that the user should not interfere with.
        $propertiesToBeIgnored = array(
            'id',
        );
        // Fetch the available getters for the data source
        $dataSourceClassName = $dataSourceToDiagramMapping->getDataSource();
        $dataSourceProperties = $this->getReflectionPropertyNames($dataSourceClassName, true, $propertiesToBeIgnored);
        $dataSourceGetters = $this->getReflectionGetters($dataSourceClassName, $dataSourceProperties, true);

        // Figure out which diagram setters already have an existing field mapping,
        // as well as which ones don't. Also figure out any possibly no longer valid field mappings (e.g. due to the diagram schema being changed).
        $fieldMappings = $dataSourceToDiagramMapping->getFieldMappings();
        $existingDiagramSetters = array();

        foreach ($fieldMappings as $fieldMapping) {
            // A flag that indicates if the field mapping is valid. It will never be set to false if no validation is to be applied.
            $fieldMappingIsValid = true;
            // Get the target (diagram) setter in the field mapping.
            $targetSetter = $fieldMapping->getTargetSetter();
            // Check if that setter exists in the current diagram schema.
            $propertyName = array_search($targetSetter, $diagramSetters);
            if ($propertyName === false) {
                // This setter no longer exists in the diagram. Mark that the field mapping is invalid.
                $fieldMappingIsValid = false;
            }
            else {
                // This diagram setter already has a field mapping.
                // If invalid field mappings are supposed to be removed, do some extra validations.
                if ($removeInvalidFieldMappings) {
                    // Check if the diagram getter is valid.
                    $targetGetter = $fieldMapping->getTargetGetter();
                    if (empty($targetGetter) || !in_array($targetGetter, $diagramGetters)) {
                        // The field mapping either defines no target getter or an invalid one.
                        $fieldMappingIsValid = false;
                    }

                    // Check if the data source getter is valid.
                    $sourceGetter = $fieldMapping->getSourceGetter();
                    if (empty($sourceGetter) || !in_array($sourceGetter, $dataSourceGetters)) {
                        // The field mapping either defines no target getter or an invalid one.
                        $fieldMappingIsValid = false;
                    }
                }
            }

            if ($fieldMappingIsValid) {
                // This field mapping is valid.
                // Just mark that the diagram setter already has a field mapping.
                $existingDiagramSetters[$propertyName] = $targetSetter;
            }
            else {
                // This field mapping is invalid, so remove it from the array collection.
                $dataSourceToDiagramMapping->removeFieldMapping($fieldMapping);
            }
        }

        // Figure out which setters are missing a field mapping.
        $missingDiagramSetters = array_diff_assoc($diagramSetters, $existingDiagramSetters);

        // Now add field mappings without a specified data source getter for each of the diagram setters which have no defined mapping.
        foreach ($missingDiagramSetters as $propertyName => $targetSetter) {
            $targetGetter = $diagramGetters[$propertyName];
            // Define a new field mapping with the proper target setter and getter.
            $fieldMapping = new FieldMapping();
            $fieldMapping->setTargetSetter($targetSetter);
            $fieldMapping->setTargetGetter($targetGetter);
            // Add the field mapping to the DataSourceToDiagramMapping object.
            $dataSourceToDiagramMapping->addFieldMapping($fieldMapping);
        }
    }

    /**
     * Removes FieldMapping objects which have no specified source getter (i.e. they're considered empty/unused).
     *
     * This method is useful when handling forms for DataSourceToDiagramMapping.
     *
     * @param \DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\DataSourceToDiagramMapping $dataSourceToDiagramMapping The data source to diagram mapping object to be modified.
     */
    public function removeEmptyFieldMappingsFromDataSourceToDiagramMapping(DataSourceToDiagramMapping $dataSourceToDiagramMapping)
    {
        // Go through all fieldMappings which do not have a source getter and remove them.
        $fieldMappings = $dataSourceToDiagramMapping->getFieldMappings();
        foreach ($fieldMappings as $fieldMapping) {
            $sourceGetter = $fieldMapping->getSourceGetter();
            if (empty($sourceGetter)) {
                $dataSourceToDiagramMapping->removeFieldMapping($fieldMapping);
            }
        }
    }

    /**
     * Inspects the diagram class, and adds JsonFieldMapping objects for the properties which are not mapped yet.
     * The empty JsonFieldMapping objects will actually only be missing a source getter value.
     *
     * Optionally this method will also remove invalid JSON field mappings.
     *
     * This method is useful when handling forms for JsonToDiagramMapping.
     *
     * @param \DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\JsonToDiagramMapping $jsonToDiagramMapping The data source to diagram mapping object to be modified.
     * @param Boolean $removeInvalidFieldMappings optional If set to TRUE, field mappings which no longer seem to be valid will be removed.
     */
    public function addEmptyJsonFieldMappingsToJsonToDiagramMapping(JsonToDiagramMapping $jsonToDiagramMapping, $removeInvalidFieldMappings = true)
    {
        // Fetch the available getters and setters for the diagram.
        $diagramClassName = $jsonToDiagramMapping->getDiagram();
        $diagramProperties = $this->getDiagramProperties($diagramClassName);
        $diagramSetters = $this->getReflectionSetters($diagramClassName, $diagramProperties, true);

        // Figure out which diagram setters already have an existing field mapping,
        // as well as which ones don't. Also figure out any possibly no longer valid field mappings (e.g. due to the diagram schema being changed).
        $fieldMappings = $jsonToDiagramMapping->getFieldMappings();
        $existingDiagramSetters = array();

        foreach ($fieldMappings as $fieldMapping) {
            // Get the target (diagram) setter in the field mapping.
            $targetSetter = $fieldMapping->getTargetSetter();
            // Check if that setter exists in the current diagram schema.
            $propertyName = array_search($targetSetter, $diagramSetters);
            if ($propertyName === false) {
                // This setter no longer exists in the diagram. Mark that the field mapping is invalid.
                if ($removeInvalidFieldMappings) {
                    // This field mapping is invalid, so remove it from the array collection.
                    $jsonToDiagramMapping->removeFieldMapping($fieldMapping);
                }
            }
            else {
                // This field mapping is valid.
                // Just mark that the diagram setter already has a field mapping.
                $existingDiagramSetters[$propertyName] = $targetSetter;
            }
        }

        // Figure out which setters are missing a field mapping.
        $missingDiagramSetters = array_diff_assoc($diagramSetters, $existingDiagramSetters);

        // Now add field mappings without a specified data source getter for each of the diagram setters which have no defined mapping.
        foreach ($missingDiagramSetters as $propertyName => $targetSetter) {
            // Define a new field mapping with the proper target setter and getter.
            $fieldMapping = new JsonFieldMapping();
            $fieldMapping->setTargetSetter($targetSetter);
            // Add the field mapping to the DataSourceToDiagramMapping object.
            $jsonToDiagramMapping->addFieldMapping($fieldMapping);
        }
    }

    /**
     * Removes JsonFieldMapping objects which have no specified source getter (i.e. they're considered empty/unused).
     *
     * This method is useful when handling forms for JsonToDiagramMapping.
     *
     * @param \DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\JsonToDiagramMapping $jsonToDiagramMapping The data source to diagram mapping object to be modified.
     */
    public function removeEmptyJsonFieldMappingsFromJsonToDiagramMapping(JsonToDiagramMapping $jsonToDiagramMapping)
    {
        // Go through all fieldMappings which do not have a source getter and remove them.
        $fieldMappings = $jsonToDiagramMapping->getFieldMappings();
        foreach ($fieldMappings as $fieldMapping) {
            $sourceGetter = $fieldMapping->getSourceGetter();
            if (empty($sourceGetter)) {
                $jsonToDiagramMapping->removeFieldMapping($fieldMapping);
            }
        }
    }

    /**
     * Fetches the entity manager for a doctrine entity.
     *
     * @param string $fullyQualifiedClassName The fully qualified entity class name (prefixed by the proper namespace).
     * @return \Doctrine\Common\Persistence\ObjectManager|null The entity manager.
     */
    public function getEntityManager($fullyQualifiedClassName)
    {
        if (empty($this->container)) {
            throw new \RuntimeException('Could not obtain service container.');
        }

        try {
            $em = $this->container->get('doctrine')->getManagerForClass($fullyQualifiedClassName);
        }
        catch (\Exception $e) {
            // The most common cause for an exception is that the entity name is not mapped to an entity mnanager,
            // This could be because cache needs to be cleared.
            throw new \RuntimeException(sprintf("Failed to get entity manager for class '%s'.", $fullyQualifiedClassName), 0, $e);
        }


        return $em;
    }

    /**
     * Creates a connection to a db server without a database name.
     *
     * This method is useful e.g. when listing databases or creating databases in a db server,
     * while the initial connection specifies a database name that does not yet exist.
     *
     * NOTE: Internally this function caches the connections it has created so that they can be re-used in the same script run.
     *
     * @param \Doctrine\DBAL\Connection $connection An existing connection to a database server that may specify a database name.
     * @return \Doctrine\DBAL\Connection The connection that does not specify a database name.
     */
    private function getConnectionWithoutDatabaseName(Connection $connection)
    {
        $params = $connection->getParams();
        unset($params['dbname']);
        // Calculate a connection hash based on the params without the dbname.
        // This is used in order to avoid creating a new connection multiple times when it would be unnecessary.
        $connectionHash = md5(serialize($params));
        if (!isset($this->connectionsCache[$connectionHash])) {
            // No cached connection exists. Create it now.
            $connectionWithoutDatabaseName = DriverManager::getConnection($params);
            $this->connectionsCache[$connectionHash] = $connectionWithoutDatabaseName;
        }

        return $this->connectionsCache[$connectionHash];
    }

    /**
     * Fetches the metadata for a generated doctrine entity, whose database schema has not necessarily been pushed to the database yet.
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $em The entity manager for the doctrine entity.
     * @param string $fullyQualifiedClassName The fully qualified entity class name (prefixed by the proper namespace).
     * @return \Doctrine\Common\Persistence\Mapping\ClassMetadata The class metadata for the doctrine entity.
     */
    private function getClassMetadata($em, $fullyQualifiedClassName)
    {
        // Obtain the class metadata for the entity.
        $cmf = new DisconnectedClassMetadataFactory();
        $cmf->setEntityManager($em);
        $classMetadata = $cmf->getMetadataFor($fullyQualifiedClassName);

        return $classMetadata;
    }

    /**
     * Helper for checking if a field has a unique constraint in a defined class metadata.
     *
     * This method exists because the DisconnectedClassMetadataFactory with a database driver does not always (or ever) determine which fields are unique.
     *
     * @param string $columnName The database table's column name.
     * @param ClassMetadataInfo $classMetadata The class metadata to be inspected.
     * @return bool true if the column name is included in some unique constraint, or false otherwise.
     */
    private function isUniqueField($columnName, $classMetadata)
    {
        if (!empty($classMetadata->table['uniqueConstraints'])) {
            // Go through the unique constraints defined for the class metadata.
            foreach ($classMetadata->table['uniqueConstraints'] as $constraintId => $constraintInfo) {
                // Check if the column name is mentioned in the constraint.
                // Technically there could be some kind of constraint that concerns combined columns but just consider the field unique in that case.
                if (!empty($constraintInfo['columns']) && in_array($columnName, $constraintInfo['columns'])) {
                    return true;
                }
            }
        }

        return false;
    }

}
