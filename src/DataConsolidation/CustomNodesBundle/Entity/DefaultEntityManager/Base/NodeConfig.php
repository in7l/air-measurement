<?php

namespace DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * NodeConfig
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\NodeConfigRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="config_type", type="string")
 * @ORM\DiscriminatorMap({"node" = "NodeConfig", "diagram" = "DiagramConfig"})
 * @UniqueEntity("name")
 */
class NodeConfig
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * The name cannot contain underscores, because Symfony's class autoloader does not allow that.
     *
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     * @Assert\NotBlank()
     * @Assert\Length(
     *     min = 1,
     *     max = 255,
     *     minMessage = "The node name should be at least {{ limit }} characters long",
     *     maxMessage = "The node name cannot be longer than {{ limit }} characters"
     * )
     * @Assert\Regex(
     *     pattern="/^[a-zA-Z][a-zA-Z0-9]*$/",
     *     message = "The name should contain only alphanumeric characters and it should not start with a digit."
     * )
     */
    protected $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="table_name", type="string", length=255, nullable=true)
     * @Assert\Length(
     *     min = 1,
     *     max = 255,
     *     minMessage = "The table name should be at least {{ limit }} characters long",
     *     maxMessage = "The table name cannot be longer than {{ limit }} characters"
     * )
     * @Assert\Regex(
     *     pattern="/^[_a-zA-Z][_a-zA-Z0-9]*$/",
     *     message = "The table name should contain only alphanumeric characters and underscore and it should not start with a digit."
     * )
     */
    protected $tableName;

    /**
     * @var array
     *
     * @ORM\Column(name="target_entity_managers", type="json_array", nullable=true)
     * @Assert\Type(
     *     type="array",
     *     message="The value {{ value }} is not a valid {{ type }}."
     * )
     */
    protected $targetEntityManagers;

    /**
     * @var array
     *
     * @ORM\OneToMany(targetEntity="NodeConfigField", mappedBy="nodeConfig", cascade={"persist", "remove"}, orphanRemoval=true)
     * @Assert\Valid()
     */
    protected $fields;

    public function __construct()
    {
        $this->fields = new ArrayCollection();
    }


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return NodeConfig
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return null|string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @param null|string $tableName
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * @return array
     */
    public function getTargetEntityManagers()
    {
        return $this->targetEntityManagers;
    }

    /**
     * @param array $targetEntityManagers
     */
    public function setTargetEntityManagers($targetEntityManagers)
    {
        $this->targetEntityManagers = $targetEntityManagers;
    }

    /**
     * Used for distinguishing between base NodeConfig instances and DiagramConfig subclasses.
     * The returned value is the same as the value in the DiscriminatorColumn of the Doctrine entity.
     *
     * @return string The type of the configuration.
     */
    public function getConfigType()
    {
        return 'NodeConfig';
    }

    /**
     * Used for distinguishing between base NodeConfig instances and DiagramConfig subclasses.
     *
     * @return boolean TRUE If this is a data source node (and not a diagram config), or FALSE otherwise.
     */
    public function isDataSourceNode() {
        $configType = $this->getConfigType();
        if ($configType == 'NodeConfig') {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * Set fields
     *
     * @param NodeConfigField[] $fields
     * @return NodeConfig
     */
    public function setFields($fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Get fields
     *
     * @return NodeConfigField[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Adds a new field
     *
     * @param NodeConfigField $field
     */
    public function addField(NodeConfigField $field)
    {
        $this->fields->add($field);
        $field->setNodeConfig($this);
    }

    /**
     * Removes an existing field
     *
     * @param NodeConfigField $field
     */
    public function removeField(NodeConfigField $field)
    {
        $this->fields->removeElement($field);
        $field->setNodeConfig(null);
    }

    /**
     * Adds a new target entity manager.
     *
     * @param string $entityManagerName The name of the entity manager.
     */
    public function addTargetEntityManager($entityManagerName)
    {
        $this->targetEntityManagers[] = $entityManagerName;
    }

    /**
     * Removes an existing target entity manager.
     *
     * @param string $entityManagerName The name of the entity manager.
     */
    public function removeTargetEntityManager($entityManagerName)
    {
        // Search for this entity manager.
        $index = array_search($entityManagerName, $this->targetEntityManagers);
        if ($index !== false) {
            // The entity manager was found. Remove it from the array of target EMs.
            unset($this->targetEntityManagers[$index]);

            if (count($this->targetEntityManagers) < 1) {
                // No more target entity managers. Set the value to null.
                $this->targetEntityManagers = null;
            }
            else {
                // Re-index the array to preserve the proper numeric values.
                $this->targetEntityManagers = array_values($this->targetEntityManagers);
            }
        }
    }

    /**
     * Fetches the node config field names which are marked as visible in content list.
     *
     * @return array of string field names of fields which are marked as visible in content list.
     */
    public function getContentListFieldNames($convertToLowerCase = false)
    {
        // Go through all the field options and get the ones which are marked as visible in the content list.
        $contentListFields = array();
        foreach ($this->fields as $field) {
            if ($field->getOptions(true)->isVisibleInContentList()) {
                $fieldName = $field->getName();
                if ($convertToLowerCase) {
                    $fieldName = strtolower($fieldName);
                }
                $contentListFields[] = $fieldName;
            }
        }

        return $contentListFields;
    }

    /**
     * Fetches the node config field names which are marked as visible in content list.
     *
     * @return array of string field names of fields which are marked as visible in content list.
     */
    public function getPrimaryKeyFieldNames($convertToLowerCase = false)
    {
        // Go through all the field options and get the ones which are marked as primary key.
        $primaryKeyFields = array();
        foreach ($this->fields as $field) {
            if ($field->getOptions(true)->isPrimaryKey()) {
                $fieldName = $field->getName();
                if ($convertToLowerCase) {
                    $fieldName = strtolower($fieldName);
                }
                $primaryKeyFields[] = $fieldName;
            }
        }

        return $primaryKeyFields;
    }

    /**
     * Checks if there is at least one primary key for this node configuration.
     *
     * @Assert\IsTrue(message="There should be at least one primary key field.")
     */
    public function hasPrimaryKey()
    {
        // Check if there is at least one primary key field.
        $primaryKeyFields = $this->getPrimaryKeyFieldNames();
        if (count($primaryKeyFields) > 0) {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * Checks if there is at least one field marked as visible in content list.
     *
     * @Assert\IsTrue(message="There should be at least one field that is marked as visible in content list.")
     */
    public function hasFieldsVisibleInContentList()
    {
        // Check if there is at least one field visible in content list.
        $contentListFields = $this->getContentListFieldNames();
        if (count($contentListFields) > 0) {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * Checks if the target entity managers are unique.
     *
     * @Assert\IsTrue(message="There should be no duplicate target entity managers.")
     */
    public function hasUniqueTargetEntityManagers()
    {
        if (!is_array($this->targetEntityManagers)) {
            // No target entity managers exist.
            return true;
        }
        elseif (count($this->targetEntityManagers) == count(array_unique($this->targetEntityManagers))) {
            // The unique element count matches the total element count, meaning that there are no duplicates.
            return true;
        }
        else {
            // Some duplicate target entity managers were found.
            return false;
        }
    }

    /**
     * Checks if the field names are unique.
     *
     * @Assert\IsTrue(message="There should be no duplicate field names.")
     */
    public function hasUniqueFieldNames()
    {
        if ($this->fields->count() < 2) {
            // There are fewer than 2 fields. There couldn't possibly be duplicate field names.
            return true;
        }
        else {
            // Convert the fields property to array. It is easier to use php's ready array methods in this way.
            $fields = $this->fields->toArray();
            // Get all field names.
            $fieldNames = array_map(function($field) {
               return $field->getName();
            }, $fields);
            // Filter out duplicate field names.
            $uniqueFieldNames = array_unique($fieldNames, SORT_REGULAR);
            if (count($fieldNames) != count($uniqueFieldNames)) {
                // Some field names were filtered out, meaning that there were duplicates.
                return false;
            }

            // If this point is reached, no duplicates were found..
            return true;
        }
    }

    /**
     * Checks if the effective field column names are unique.
     *
     * @Assert\IsTrue(message="There should be no duplicate column names. Note that fields without a specified column name use the field name as a column name.")
     */
    public function hasUniqueEffectiveColumnNames()
    {
        if ($this->fields->count() < 2) {
            // There are fewer than 2 fields. There couldn't possibly be duplicate column names.
            return true;
        }
        else {
            // Convert the fields property to array. It is easier to use php's ready array methods in this way.
            $fields = $this->fields->toArray();
            // Get all effective column names.
            $columnNames = array_map(function($field) {
                // Attempt to use the column name with a priority.
                $columnName = $field->getOptions(true)->getColumnName();
                if (isset($columnName)) {
                    return $columnName;
                }
                else {
                    // No specified column name. Use the field name.
                    return $field->getName();
                }
            }, $fields);

            // Filter out duplicate column names.
            $uniqueColumnNames = array_unique($columnNames, SORT_REGULAR);
            if (count($columnNames) != count($uniqueColumnNames)) {
                // Some field names were filtered out, meaning that there were duplicates.
                return false;
            }

            // If this point is reached, no duplicates were found..
            return true;
        }
    }

    /**
     * Converts the NodeConfigFields' options to objects.
     * This is needed e.g. when building the form for an existing object loaded from the database.
     */
    public function convertFieldOptionsToObjects()
    {
        // Convert the options of the node config fields to objects. This is needed when building the form.
        $nodeConfigFields = $this->getFields();
        foreach ($nodeConfigFields as $nodeConfigField) {
            $nodeConfigField->convertOptionsToObject();
        }
    }

    /**
     * Converts the NodeConfigFields' options to JSON.
     * This is needed for properly persisting the data to the database.
     */
    public function convertFieldOptionsToJson()
    {
        $nodeConfigFields = $this->getFields();
        foreach ($nodeConfigFields as $nodeConfigField) {
            $nodeConfigField->convertOptionsToJson();
        }
    }
}
