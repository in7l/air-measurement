<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 4/7/16
 * Time: 7:14 PM
 */

namespace DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class DiagramConfig represents a measurement diagram configuration.
 * @package DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\NodeConfigRepository")
 */
class DiagramConfig extends NodeConfig
{
    const CONFIG_FIELD_NAME_ID = 'id';
    const CONFIG_FIELD_NAME_SOURCE = 'source';
    const CONFIG_FIELD_NAME_DATETIME = 'measurement_time';
    const CONFIG_FIELD_NAME_CONSOLIDATION_TYPE = 'consolidation_type';

    /**
     * Fetches the node config field names which are reserved words for a diagram config and are thus not allowed to be added or removed as fields.
     *
     * @return string[] The reserved node config field names.
     */
    public static function getReservedConfigFieldNames()
    {
        $reservedFieldNames = array(
            self::CONFIG_FIELD_NAME_ID,
            self::CONFIG_FIELD_NAME_SOURCE,
            self::CONFIG_FIELD_NAME_DATETIME,
            self::CONFIG_FIELD_NAME_CONSOLIDATION_TYPE,
        );

        return $reservedFieldNames;
    }

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        // Call the parent constructor.
        parent::__construct();

        // Add the default entity manager.
        $this->addTargetEntityManager('default');

        // Add some compulsory fields for a diagram.

        // Measurements identifier field.
        $idField = new NodeConfigField();
        $idField->setName(self::CONFIG_FIELD_NAME_ID);
        $idField->setType('integer');
        $idField->setMutable(false);
        $idFieldOptions = new NodeConfigOptions();
        $idFieldOptions->setPrimaryKey(true);
        $idFieldOptions->setStrategy('AUTO');
        $idFieldOptions->setUnique(true);
//        $idFieldOptions->setVisibleInContentList(true);
        $idField->setOptions($idFieldOptions, false);
        $this->addReservedField($idField);

        // Data source field.
        $sourceField = new NodeConfigField();
        $sourceField->setName(self::CONFIG_FIELD_NAME_SOURCE);
        $sourceField->setType('string');
        $sourceField->setMutable(false);
        $sourceFieldOptions = new NodeConfigOptions();
        $sourceFieldOptions->setLength(500);
        $sourceFieldOptions->setVisibleInContentList(true);
        $sourceField->setOptions($sourceFieldOptions, false);
        $this->addReservedField($sourceField);

        // Datetime field.
        $datetimeField = new NodeConfigField();
        $datetimeField->setName(self::CONFIG_FIELD_NAME_DATETIME);
        $datetimeField->setType('datetime');
        $datetimeField->setMutable(false);
        $datetimeFieldOptions = new NodeConfigOptions();
        $datetimeFieldOptions->setVisibleInContentList(true);
        $datetimeField->setOptions($datetimeFieldOptions, false);
        $this->addReservedField($datetimeField);

        // Consolidation type field.
        $consolidationTypeField = new NodeConfigField();
        $consolidationTypeField->setName(self::CONFIG_FIELD_NAME_CONSOLIDATION_TYPE);
        $consolidationTypeField->setType('integer');
        $consolidationTypeField->setMutable(false);
        $consolidationTypeFieldOptions = new NodeConfigOptions();
        $consolidationTypeFieldOptions->setVisibleInContentList(true);
        $consolidationTypeField->setOptions($consolidationTypeFieldOptions, false);
        $this->addReservedField($consolidationTypeField);
    }

    /**
     * @inheritdoc
     */
    public function getConfigType()
    {
        return 'DiagramConfig';
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException if a field with reserved name is attempted to be added.
     */
    public function addField(NodeConfigField $field)
    {
        $reservedFieldNames = self::getReservedConfigFieldNames();
        $fieldName = $field->getName();
        if (in_array($fieldName, $reservedFieldNames)) {
            throw new \RuntimeException(sprintf("Cannot add a field with a reserved name '%s'.", $fieldName));
        }
        parent::addField($field);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException if a field with reserved name is attempted to be removed.
     */
    public function removeField(NodeConfigField $field)
    {
        $reservedFieldNames = self::getReservedConfigFieldNames();
        $fieldName = $field->getName();
        if (in_array($fieldName, $reservedFieldNames)) {
            throw new \RuntimeException(sprintf("Cannot remove a field with a reserved name '%s'.", $fieldName));
        }
        parent::removeField($field);
    }

    /**
     * This is a helper function that wraps the superclass's addField() method
     * and bypasses the additional checks added to the current class's addField() method.
     *
     * @param NodeConfigField $field The node config field to be added.
     */
    private function addReservedField(NodeConfigField $field)
    {
        parent::addField($field);
    }
}
