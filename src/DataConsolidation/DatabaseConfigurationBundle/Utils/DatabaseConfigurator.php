<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 3/11/16
 * Time: 2:12 PM
 */

namespace DataConsolidation\DatabaseConfigurationBundle\Utils;

use DataConsolidation\CustomNodesBundle\Utils\DoctrineEntityHelper;
use DataConsolidation\DatabaseConfigurationBundle\Entity\DatabaseConnectionConfiguration;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Dumper;

/**
 * Class DatabaseConfigurator
 * @package DataConsolidation\DatabaseConfigurationBundle\Utils
 *
 * Allows dynamic configuration of database connections.
 */
class DatabaseConfigurator
{

    const DATABASE_CONNECTION_NAME_PREFIX = 'data_source.db_';
    const DATABASE_CONFIG_FILE_NAME ='database_config.yml';
    const DATABASE_PARAMETERS_FILE_NAME = 'database_parameters.yml';
    const PARAMETER_SUFFIX_DRIVER = 'driver';
    const PARAMETER_SUFFIX_HOST = 'host';
    const PARAMETER_SUFFIX_PORT = 'port';
    const PARAMETER_SUFFIX_DATABASE_NAME = 'dbname';
    const PARAMETER_SUFFIX_USER = 'user';
    const PARAMETER_SUFFIX_PASSWORD = 'password';
    const CACHE_DATABASE_CONFIG_VALUE = 'database_config';
    const CACHE_PARAMETERS_VALUE = 'parameters';

    private $configDirectory;
    private $fileLocator;
    // YAML file parser (for reading yml files).
    private $parser;
    // YAML file dumper (for writing yml files).
    private $dumper;
    // An optional doctrine entity helper service.
    private $doctrineEntityHelper;
    // Cached values used to reduce the number of config file readings and parsings in a single request.
    // These will be updated automatically whenever the written value changes.
    private $cachedValues;

    public function __construct()
    {
        $this->configDirectory = __DIR__ . '/../Resources/config';
        $this->fileLocator = new FileLocator($this->configDirectory);
        $this->parser = new Parser();
        $this->dumper = new Dumper();
    }

    /**
     * Dependency injection for doctrine entity helper.
     * If this service is present, new database configurations will automatically map the entity managers to the proper custom nodes bundle's entities.
     *
     * @param DoctrineEntityHelper $doctrineEntityHelper The doctrine entity helper service.
     */
    public function setDoctrineEntityHelper(DoctrineEntityHelper $doctrineEntityHelper)
    {
        $this->doctrineEntityHelper = $doctrineEntityHelper;
    }

    /**
     * Fetches all doctrine database connection names defined in this bundle's config files.
     *
     * @return array The names of the database connections.
     */
    public function getDatabaseConnectionNames()
    {
        $databaseConfig = $this->getDatabaseConfigValue();
        if (empty($databaseConfig['doctrine']['dbal']['connections']) || !is_array($databaseConfig['doctrine']['dbal']['connections'])) {
            // Invalid or empty configuration.
            return array();
        }

        $databaseConnectionNames = array_keys($databaseConfig['doctrine']['dbal']['connections']);
        return $databaseConnectionNames;
    }

    /**
     * Fetches the doctrine entity manager names defined in this bundle's config files.
     *
     * @return array The names of the doctrine entity managers.
     */
    public function getEntityManagerNames()
    {
        $databaseConfig = $this->getDatabaseConfigValue();
        if (empty($databaseConfig['doctrine']['orm']['entity_managers']) || !is_array($databaseConfig['doctrine']['orm']['entity_managers'])) {
            // Invalid or empty configuration.
            return array();
        }

        $databaseConnectionNames = array_keys($databaseConfig['doctrine']['orm']['entity_managers']);
        return $databaseConnectionNames;
    }

    /**
     * Determines the name of the connection that an entity manager uses.
     *
     * @param string $entityManagerName The name of the entity manager.
     *
     * @return String The connection name associated with the entity manager.
     *
     * @throws \InvalidArgumentException When there is no defined connection for the specified entity manager.
     */
    public function getConnectionForEntityManager($entityManagerName)
    {
        $databaseConfig = $this->getDatabaseConfigValue();
        if (empty($databaseConfig['doctrine']['orm']['entity_managers'][$entityManagerName]['connection'])) {
            throw new \InvalidArgumentException(sprintf("There is no defined connection for entity manager '%s'.", $entityManagerName));
        }

        $connectionName = $databaseConfig['doctrine']['orm']['entity_managers'][$entityManagerName]['connection'];
        return $connectionName;
    }

    /**
     * Fetches all database connection configurations defined in this bundle's config.
     *
     * @return Array of \DataConsolidation\DatabaseConfigurationBundle\Entity\DatabaseConnectionConfiguration objects/
     */
    public function getAllDatabaseConnectionConfigurations()
    {
        $connectionNames = $this->getDatabaseConnectionNames();
        $databaseConnectionConfigurations = array();
        foreach ($connectionNames as $connectionName) {
            $databaseConnectionConfigurations[] = $this->getDatabaseConnectionConfiguration($connectionName);
        }

        return $databaseConnectionConfigurations;
    }

    /**
     * Fetches database connection configuration based on the specified connection name.
     *
     * @param string $connectionName The name of the connection for which the information should be fetched.
     *
     * @return \DataConsolidation\DatabaseConfigurationBundle\Entity\DatabaseConnectionConfiguration The database connection configuration.
     *
     * @throws \InvalidConfigurationException When some of the database info parameters is missing.
     */
    public function getDatabaseConnectionConfiguration($connectionName)
    {
        $databaseParameters = $this->getDatabaseParametersValue();
        if (empty($databaseParameters['parameters']) || !is_array($databaseParameters['parameters'])) {
            throw new \InvalidConfigurationException(sprintf("There are no defined database parameters. Attempted to fetch connection info for '%s'.", $connectionName));
        }

        // Fetch the database configuration parameter names. All of them are required (though some could be null).
        $requiredParametersList = $this->getParameterNames($connectionName);

        // Resolve the parameter names to actual values.
        $parameters = array();
        foreach ($requiredParametersList as $outputName => $parameterName) {
            if (!array_key_exists($parameterName, $databaseParameters['parameters'])) {
                throw new \InvalidArgumentException(sprintf("Missing database info parameter '%s' for connection name '%s'.", $outputName, $connectionName));
            }
            $parameters[$outputName] = $databaseParameters['parameters'][$parameterName];
        }

        $databaseConnectionConfiguration = new DatabaseConnectionConfiguration();
        $databaseConnectionConfiguration->setConnectionAlias($connectionName);
        $databaseConnectionConfiguration->setDriver($parameters['driver']);
        $databaseConnectionConfiguration->setHost($parameters['host']);
        $databaseConnectionConfiguration->setPort($parameters['port']);
        $databaseConnectionConfiguration->setDbName($parameters['dbname']);
        $databaseConnectionConfiguration->setUser($parameters['user']);
        $databaseConnectionConfiguration->setPassword($parameters['password']);

        return $databaseConnectionConfiguration;
    }

    /**
     * Adds a new database connection configuration to the config files of this bundle.
     *
     * @param \DataConsolidation\DatabaseConfigurationBundle\Entity\DatabaseConnectionConfiguration $databaseConnectionConfiguration The database connection configuration object to be added.
     *  The connectionAlias property of this object will be updated to the value of the connection name that was determined to be unused at the time when this db connection config is being added.
     */
    public function addDatabaseConnection(DatabaseConnectionConfiguration $databaseConnectionConfiguration)
    {
        // Get a new connection name to be used.
        $unusedConnectionName = $this->getNextUnusedDatabaseConnectionName();
        // Set the connectionAlias for this database configuration object.
        $databaseConnectionConfiguration->setConnectionAlias($unusedConnectionName);
        // Re-use the edit database connection functionality.
        $this->editDatabaseConnection($databaseConnectionConfiguration);
    }

    /**
     * Edits a database connection configuration to the config files of this bundle.
     *
     * @param \DataConsolidation\DatabaseConfigurationBundle\Entity\DatabaseConnectionConfiguration $databaseConnectionConfiguration The database connection configuration object to be edited.
     *
     * @throws \InvalidConfigurationException When the existing configuration in this bundle is not correctly formatted, and thus it is not possible to edit the database configurations.
     */
    public function editDatabaseConnection(DatabaseConnectionConfiguration $databaseConnectionConfiguration)
    {
        // Get the config value and check if it is properly formatted.
        $databaseConfig = $this->getDatabaseConfigValue();
        if (!empty($databaseConfig['doctrine']['dbal']['connections']) && !is_array($databaseConfig['doctrine']['dbal']['connections'])) {
            throw new \InvalidConfigurationException("Invalid 'connections' element in database config. Expected an array.");
        }
        if (!empty($databaseConfig['doctrine']['orm']['entity_managers']) && !is_array($databaseConfig['doctrine']['orm']['entity_managers'])) {
            throw new \InvalidConfigurationException("Invalid 'entity_managers' element in database config. Expected an array.");
        }
        // Get the parameters value and check if it is properly formatted.
        $databaseParameters = $this->getDatabaseParametersValue();
        if (!empty($databaseParameters['parameters']) && !is_array($databaseParameters['parameters'])) {
            throw new \InvalidConfigurationException("Invalid 'parameters' element in database parameters config. Expected an array.");
        }

        // Declare the database connection and add parameter placeholders.
        $connectionAlias = $databaseConnectionConfiguration->getConnectionAlias();
        // Get the database configuration parameter names WITH the '%' characters.
        $databaseConfigConnection = $this->getParameterNames($connectionAlias, TRUE);
        // Add some extra hard-coded parameters.
        $databaseConfigConnection['charset'] = 'UTF8';
        $databaseConfig['doctrine']['dbal']['connections'][$connectionAlias] = $databaseConfigConnection;

        // Declare the entity manager to be associated with the new connection.
        $databaseConfig['doctrine']['orm']['entity_managers'][$connectionAlias] = array(
            'connection' => $connectionAlias,
        );
        // Add an entity manager to custom nodes bundle mapping if the proper service is available.
        if ($this->doctrineEntityHelper) {
            $databaseConfig['doctrine']['orm']['entity_managers'][$connectionAlias]['mappings'] = array(
                'DataConsolidationCustomNodesBundle' => array(
                    'prefix' => $this->doctrineEntityHelper->getCustomEntityBaseNamespace($connectionAlias),
                ),
            );
        }

        // Get the database configuration parameter names without the '%' characters.
        $parameterNames = $this->getParameterNames($connectionAlias);
        // Define the actual database parameters to be stored to the parameters yml file.
        $parameterValueMapping = array(
            $parameterNames['driver'] => $databaseConnectionConfiguration->getDriver(),
            $parameterNames['host'] => $databaseConnectionConfiguration->getHost(),
            $parameterNames['port'] => $databaseConnectionConfiguration->getPort(),
            $parameterNames['dbname'] => $databaseConnectionConfiguration->getDbName(),
            $parameterNames['user'] => $databaseConnectionConfiguration->getUser(),
            $parameterNames['password'] => $databaseConnectionConfiguration->getPassword(),
        );
        foreach ($parameterValueMapping as $parameterPlacehodlerName => $value) {
            $databaseParameters['parameters'][$parameterPlacehodlerName] = $value;
        }

        // First write to the parameter config file and then to the database config file.
        // Both of these will throw an exception if they fail.
        $this->setDatabaseParametersValue($databaseParameters);
        $this->setDatabaseConfigValue($databaseConfig);
    }

    /**
     * Deletes a database connection configuration from the config files of this bundle.
     *
     * @param string $connectionName The name of the connection for which the information should be deleted.
     *
     * @throws \InvalidConfigurationException When the existing configuration in this bundle is not correctly formatted, and thus it is not possible to edit the database configurations.
     */
    public function removeDatabaseConnection($connectionName)
    {
        // Get the config value and check if it is properly formatted.
        $databaseConfig = $this->getDatabaseConfigValue();
        if (!empty($databaseConfig['doctrine']['dbal']['connections'][$connectionName]) && !is_array($databaseConfig['doctrine']['dbal']['connections'][$connectionName])) {
            throw new \InvalidConfigurationException(sprintf("Invalid 'connections.%s' element in database config. Expected an array.", $connectionName));
        }
        if (!empty($databaseConfig['doctrine']['orm']['entity_managers'][$connectionName]) && !is_array($databaseConfig['doctrine']['orm']['entity_managers'][$connectionName])) {
            throw new \InvalidConfigurationException(sprintf("Invalid 'entity_managers.%s' element in database config. Expected an array.", $connectionName));
        }
        // Get the parameters value and check if it is properly formatted.
        $databaseParameters = $this->getDatabaseParametersValue();
        if (!empty($databaseParameters['parameters']) && !is_array($databaseParameters['parameters'])) {
            throw new \InvalidConfigurationException("Invalid 'parameters' element in database parameters config. Expected an array.");
        }

        // Attempt to unset all the database configuration for the current connection.
        unset($databaseConfig['doctrine']['dbal']['connections'][$connectionName]);
        unset($databaseConfig['doctrine']['orm']['entity_managers'][$connectionName]);

        // Get the database configuration parameter names without the '%' characters.
        $parameterNames = $this->getParameterNames($connectionName);
        // Attempt to unset each of the placeholder parameters.
        foreach ($parameterNames as $parameterName) {
            unset($databaseParameters['parameters'][$parameterName]);
        }

        // First write to the the database config file and then to the parameter config file.
        // Both of these will throw an exception if they fail.
        $this->setDatabaseConfigValue($databaseConfig);
        $this->setDatabaseParametersValue($databaseParameters);
    }

    /**
     * Generates a unique name for a database connection, that does not yet exist in the configuration.
     *
     * @return string A database connection name.
     */
    private function getNextUnusedDatabaseConnectionName()
    {
        $unusedDatabaseNumber = $this->getNextUnusedDatabaseConnectionNumber();
        $unusedConnectionName = self::DATABASE_CONNECTION_NAME_PREFIX . $unusedDatabaseNumber;
        return $unusedConnectionName;
    }

    /**
     * Generates a unique number for the database connection, that does not yet exist in the configuration.
     *
     * @return int A unique unused number for a database connection.
     */
    private function getNextUnusedDatabaseConnectionNumber()
    {
        $nextUnusedDatabaseNumber = 1;

        $databaseConnectionNames = $this->getDatabaseConnectionNames();
        sort($databaseConnectionNames);
        $lastDatabaseConnectionName = end($databaseConnectionNames);
        if (!empty($lastDatabaseConnectionName)) {
            // Found some existing database connection name.
            $lastUsedDatabaseNumber = $this->extractNumberFromDatabaseConnectionName($lastDatabaseConnectionName);
            $nextUnusedDatabaseNumber = $lastUsedDatabaseNumber + 1;
        }

        return $nextUnusedDatabaseNumber;
    }

    /**
     * Extracts the number from a database connection number.
     *
     * NOTE: It is expected that the database connection name is using the [DATABASE_CONNECTION_NAME_PREFIX]_[NUMBER] format.
     *
     * @param string $databaseConnectionName The database connection name from which the number should be extracted.
     *
     * @return int The extracted database connection number
     *
     * @throws \InvalidArgumentException If the number could not be extracted from the database connection name.
     */
    private function extractNumberFromDatabaseConnectionName($databaseConnectionName)
    {
        $databaseNamePrefix = self::DATABASE_CONNECTION_NAME_PREFIX;
        // Remove the prefix from the connection name.
        $databaseNumber = str_replace($databaseNamePrefix, '', $databaseConnectionName);
        // Attempt to confirm that the extracted component is an integer.
        if (intval($databaseNumber) != $databaseNumber) {
            throw new \InvalidArgumentException(sprintf("Failed to extract number from database connection name '%s'.", $databaseConnectionName));
        }
        $databaseNumber = intval($databaseNumber);
        return $databaseNumber;
    }

    /**
     * @param string $connectionName The name of the connection for which the config parameters names should be fetched.
     * @param bool $includePrefixAndSuffix If set to TRUE, the parameter names will include the '%' char as a prefix and suffix.
     *
     * @return array Parameter names array with the following keys:
     *  'driver',
     *  'host',
     *  'port',
     *  'dbname',
     *  'user',
     *  'password',
     */
    private function getParameterNames($connectionName, $includePrefixAndSuffix = FALSE) {
        $c = '';
        if ($includePrefixAndSuffix) {
            // Include an extra character as a prefix and suffix.
            $c = '%';
        }

        $parameterNames = array(
            'driver' => $c . $connectionName . '.' . self::PARAMETER_SUFFIX_DRIVER . $c,
            'host' => $c . $connectionName . '.' . self::PARAMETER_SUFFIX_HOST . $c,
            'port' => $c . $connectionName . '.' . self::PARAMETER_SUFFIX_PORT . $c,
            'dbname' => $c . $connectionName . '.' . self::PARAMETER_SUFFIX_DATABASE_NAME . $c,
            'user' => $c . $connectionName . '.' . self::PARAMETER_SUFFIX_USER . $c,
            'password' => $c . $connectionName . '.' . self::PARAMETER_SUFFIX_PASSWORD . $c,
        );

        return $parameterNames;
    }

    /**
     * Fetches the value of the database config file in this bundle.
     *
     * @return array The parsed database configuration value.
     */
    public function getDatabaseConfigValue()
    {
        // Attempt to read the database config value from cache.
        if (!isset($this->cachedValues[self::CACHE_DATABASE_CONFIG_VALUE])) {
            // No cached database config value. Read the config file and cache the value.
            $configFileName = self::DATABASE_CONFIG_FILE_NAME;
            $this->cachedValues[self::CACHE_DATABASE_CONFIG_VALUE] = $this->readConfigValue($configFileName);
        }

        return $this->cachedValues[self::CACHE_DATABASE_CONFIG_VALUE];
    }

    /**
     * Fetches the value of the database parameters file in this bundle.
     *
     * @return array The parsed database parameters value.
     */
    public function getDatabaseParametersValue()
    {
        // Attempt to read the parameters value from cache.
        if (!isset($this->cachedValues[self::CACHE_PARAMETERS_VALUE])) {
            // No cached parameters value. Read the config file and cache the value.
            $configFileName = self::DATABASE_PARAMETERS_FILE_NAME;
            $this->cachedValues[self::CACHE_PARAMETERS_VALUE] = $this->readConfigValue($configFileName);
        }

        return $this->cachedValues[self::CACHE_PARAMETERS_VALUE];
    }

    /**
     * Updates the database config file in this bundle.
     *
     * @param array $configValue The database config value to be dumped to the database config file.
     */
    private function setDatabaseConfigValue($configValue)
    {
        $configFileName = self::DATABASE_CONFIG_FILE_NAME;
        $this->writeConfigValue($configFileName, $configValue, 7);
        // Update the cached database config value.
        $this->cachedValues[self::CACHE_DATABASE_CONFIG_VALUE] = $configValue;
    }

    /**
     * Updates the database parameters file in this bundle.
     *
     * @param array $configValue The database parameters value to be dumped to the database parameters file.
     */
    private function setDatabaseParametersValue($configValue)
    {
        $configFileName = self::DATABASE_PARAMETERS_FILE_NAME;
        $this->writeConfigValue($configFileName, $configValue, 2);
        // Update the cached parameters value.
        $this->cachedValues[self::CACHE_PARAMETERS_VALUE] = $configValue;
    }

    /**
     * Reads a config file and parses the value.
     *
     * @param string $configFileName The name of the config file to be read.
     * @param bool $optionalConfig When set to TRUE, if the config file does not exist, no exception will be thrown.
     *
     * @return array The parsed configuration value.
     *
     * @throws \InvalidArgumentException When $optionalConfig is set to FALSE and the file does not exist.
     * @throws \RuntimeException When the file failed to be read (possibly due to insufficient permissions).
     */
    private function readConfigValue($configFileName, $optionalConfig = TRUE)
    {
        $filePath = NULL;
        try {
            $filePath = $this->fileLocator->locate($configFileName);
        }
        catch (\InvalidArgumentException $e) {
            // Most likely the file does not exist. This is OK if the config is optional.
            if ($optionalConfig) {
                return array();
            }
            else {
                // This config is not optional. Re-throw the exception.
                throw $e;
            }
        }

        $rawConfigValue = @file_get_contents($filePath);
        if ($rawConfigValue === FALSE) {
            // The file failed to be read.
            throw new \RuntimeException(sprintf("The config file '%s' failed to be read.", $filePath));
        }
        $configValue = $this->parser->parse($rawConfigValue);

        return $configValue;
    }

    /**
     * Writes a new value to a config file.
     *
     * @param string $configFileName The name of the config file where the new value should be written.
     * @param array $configValue The value to be written to the config file. This value will be dumped to YAML format.
     * @param int $inline The indentation level for the YAML output.
     *
     * @throws \RuntimeException When writing the new value to the configuration files of this bundle failed.
     */
    private function writeConfigValue($configFileName, $configValue, $inline = 10)
    {
        $filePath = $this->configDirectory . '/' . $configFileName;
        $rawConfigValue = $this->dumper->dump($configValue, $inline);
        $writeResult = @file_put_contents($filePath, $rawConfigValue);
        if ($writeResult === FALSE) {
            // Probably insufficient file permissions.
            throw new \RuntimeException(sprintf("Failed to write to the '%s' config file.", $configFileName));
        }
    }

}
