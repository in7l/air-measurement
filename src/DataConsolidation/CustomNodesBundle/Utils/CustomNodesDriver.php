<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 3/18/16
 * Time: 5:19 PM
 */

namespace DataConsolidation\CustomNodesBundle\Utils;


use DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\NodeConfig;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

/**
 * A custom doctrine driver used for generating entities from NodeConfig objects.
 *
 * @package DataConsolidation\CustomNodesBundle\Utils
 */
class CustomNodesDriver implements MappingDriver
{
    protected $manager;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritDoc}
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        // The class name passed as an argument may contain also the namespace.
        $classWithNamespace = $className;
        $lastDelimiterIndex = strrpos($classWithNamespace, '\\');
        if ($lastDelimiterIndex !== false) {
            // Extract the class name from the namespaced class string.
            $className = substr($classWithNamespace, $lastDelimiterIndex + 1);
        }
        // Otherwise the classname passed as a parameter does not include a namespace.

        $repository = $this->manager->getRepository('DataConsolidation\\CustomNodesBundle\\Entity\\DefaultEntityManager\\Base\\NodeConfig');
        $nodeConfig = $repository->findOneByName($className);
        if (!$nodeConfig) {
            throw $this->createNotFoundException(sprintf("The node config for class '%s' does not exist.", $classWithNamespace));
        }

        $this->getClassMetadataFromNodeConfig($metadata, $nodeConfig);
    }

    /**
     * {@inheritDoc}
     */
    public function getAllClassNames()
    {
        $repository = $this->manager->getRepository('DataConsolidation\\CustomNodesBundle\\Entity\\DefaultEntityManager\\Base\\NodeConfig');
        $nodeConfigs = $repository->findAll();
        $classNames = array();
        foreach ($nodeConfigs as $nodeConfig) {
            $classNames[] = $nodeConfig->getName();
        }

        return $classNames;
    }

    /**
     * {@inheritDoc}
     */
    public function isTransient($className)
    {
        // Returning the same result as DatabaseDriver which handles generating entities by db reverse engineering.
        return TRUE;
    }

    protected function getClassMetadataFromNodeConfig(ClassMetadata $metadata, NodeConfig $nodeConfig)
    {
        $builder = new ClassMetadataBuilder($metadata);

        // If the node config specifies a different table name than the node name itself, then use that.
        $tableName = $nodeConfig->getTableName();
        if (!empty($tableName)) {
            $builder->setTable($tableName);
        }

        $nodeConfigFields = $nodeConfig->getFields();
        foreach ($nodeConfigFields as $nodeConfigField) {
            // Fetch the field options as an object.
            $nodeConfigOptions = $nodeConfigField->getOptions(true);

            // Create a new field for this entity.
            $fieldBuilder = $builder->createField($nodeConfigField->getName(), $nodeConfigField->getType());
            if ($nodeConfigOptions->isPrimaryKey()) {
                // This is a primary key field.
                $fieldBuilder->isPrimaryKey();
            }
            if ($nodeConfigOptions->getStrategy()) {
                // There is a specified generated value strategy.
                $fieldBuilder->generatedValue($nodeConfigOptions->getStrategy());
            }
            if ($nodeConfigOptions->isNullable()) {
                // The field is allowed to have null values.
                $fieldBuilder->nullable();
            }
            if ($nodeConfigOptions->isUnique()) {
                // The field is required to have unique values.
                $fieldBuilder->unique();
            }
            if ($nodeConfigOptions->getPrecision()) {
                // There is a specified precision (for a decimal field).
                $fieldBuilder->precision($nodeConfigOptions->getPrecision());
            }
            if ($nodeConfigOptions->getScale() !== null) {
                // There is a specified scale (for a decimal field).
                $fieldBuilder->scale($nodeConfigOptions->getScale());
            }
            if ($nodeConfigOptions->getLength()) {
                // There is a specified length (for a string field).
                $fieldBuilder->length($nodeConfigOptions->getLength());
            }
            if ($nodeConfigOptions->getColumnName()) {
                // There is a specified column name, that differs from the field name itself.
                $fieldBuilder->columnName($nodeConfigOptions->getColumnName());
            }
            // Finalize the field and attach it to the metadata.
            $fieldBuilder->build();
        }
    }
}