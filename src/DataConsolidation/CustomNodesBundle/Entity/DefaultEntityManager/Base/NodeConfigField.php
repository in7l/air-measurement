<?php

namespace DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base;

use DataConsolidation\CustomNodesBundle\Form\Type\NodeConfigOptionsFormType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * NodeConfigField
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\NodeConfigFieldRepository")
 */
class NodeConfigField
{
    /**
     * @return array List of valid database field types.
     */
    public static function getValidFieldTypes()
    {
        // Get all field types supported by doctrine.
        $databaseTypes = \Doctrine\Dbal\Types\Type::getTypesMap();
        // Use only the keys.
        $databaseTypes = array_keys($databaseTypes);

        // These should probably be filtered out to fewer possible values in the future.
        return $databaseTypes;
    }

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var NodeConfig
     *
     * @ORM\ManyToOne(targetEntity="nodeConfig", inversedBy="fields")
     */
    private $nodeConfig;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Length(
     *     min = 1,
     *     max = 255,
     *     minMessage = "The field name should be at least {{ limit }} characters long",
     *     maxMessage = "The field name cannot be longer than {{ limit }} characters"
     * )
     * @Assert\Regex(
     *     pattern="/^[_a-zA-Z][_a-zA-Z0-9]*$/",
     *     message = "The name should contain only alphanumeric characters and underscore and it should not start with a digit."
     * )
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255)
     * @Assert\Choice(callback = "getValidFieldTypes")
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="options", type="json_array", nullable=true)
     */
    private $options;

    /**
     * @var array
     *
     * Used for caching an options JSON string's NodeConfigOptions object representation.
     */
    private $optionsCache;

    /**
     * @var bool
     *
     * @ORM\Column(name="mutable", type="boolean", nullable=true)
     *
     * If the field is mutable, users are able to delete it as well as adjust its properties.
     * If it is immutable, none of this is allowed, with some small exceptions (e.g. adjusting certain field options).
     */
    private $mutable;

    /**
     * NodeConfigField constructor.
     */
    public function __construct()
    {
        // Fields are mutable by default, unless explicitly specified otherwise.
        $this->mutable = true;
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
     * @return NodeConfig
     */
    public function getNodeConfig()
    {
        return $this->nodeConfig;
    }

    /**
     * @param NodeConfig $nodeConfig
     */
    public function setNodeConfig(NodeConfig $nodeConfig = null)
    {
        if ($nodeConfig === null && $this->nodeConfig) {
            // The node config is being removed.
            // Remove the current field from the node config if it contains it.
            if ($this->nodeConfig->getFields()->contains($this)) {
                $this->nodeConfig->removeField($this);
            }
        }
        elseif ($nodeConfig) {
            // A node config is being added. Add the current field to it.
            if (!$nodeConfig->getFields()->contains($this)) {
                $nodeConfig->addField($this);
            }
        }

        $this->nodeConfig = $nodeConfig;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return NodeConfigField
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
     * Set type
     *
     * @param string $type
     * @return NodeConfigField
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set options
     *
     * @param string|NodeConfigOptions $options
     * @param bool $forceEncodeToJson When set to TRUE, if the $options parameter is a NodeConfigOptions,
     *  it will always be encoded to a JSON string. When set to false, it will be left as it is.
     * @return NodeConfigField
     */
    public function setOptions($options, $forceEncodeToJson = true)
    {
        if ($forceEncodeToJson && is_object($options) && $options instanceof NodeConfigOptions) {
            // Serialize the node config options object as JSON.
            $options = json_encode($options);
        }
        $this->options = $options;

        return $this;
    }

    /**
     * Get options
     *
     * @param bool $convertToObject Whether to convert the JSON-encoded options to a NodeConfigOptions object.
     *
     * @return string|NodeConfigOptions
     *
     * @throws \RuntimeException When the options property could not be converted to a NodeConfigOptions object.
     */
    public function getOptions($convertToObject = false)
    {
        if ($convertToObject && is_string($this->options)) {
            // Check if the options cache contains a NodeConfigOptions object that was generated based on the current options value.
            $optionsHash = md5($this->options);
            if (!isset($this->optionsCache[$optionsHash])) {
                // No cached options object available. Build it now.
                $data = json_decode($this->options, true);
                if (json_last_error() != JSON_ERROR_NONE) {
                    throw new \RuntimeException('Failed to convert options to NodeConfigOptions object');
                }
                $nodeConfigOptions = NodeConfigOptions::buildFromArray($data);

                // Store the built object to the cache variable, clearing any old cached objects that may exist.
                $this->optionsCache = array(
                    $optionsHash => $nodeConfigOptions,
                );
            }

            return $this->optionsCache[$optionsHash];
        }
        else {
            return $this->options;
        }
    }

    /**
     * @return boolean
     */
    public function isMutable()
    {
        return $this->mutable;
    }

    /**
     * @param boolean $mutable
     */
    public function setMutable($mutable)
    {
        $this->mutable = $mutable;
    }

    /**
     * @Assert\IsTrue(message="Some of the field options specify an invalid precision.")
     */
    public function hasValidPrecision()
    {
        $nodeConfigOptions = $this->getOptions(true);
        $precision = $nodeConfigOptions->getPrecision();
        $type = $this->getType();
        $typesAllowingPrecision = array(
            'decimal',
        );

        if (!is_null($precision) && !in_array($type, $typesAllowingPrecision)) {
            // This field is not allowed to specify precision but it still did.
            // The user may have tried disabling client-side validations.
            return false;
        }

        if (!is_null($precision) && !is_int($precision)) {
            // Invalid type.
            return false;
        }

        if (!is_null($precision) && $precision < 1) {
            // Too small value.
            return false;
        }

        return true;
    }

    /**
     * @Assert\IsTrue(message="Some of the field options specify an invalid scale.")
     */
    public function hasValidScale()
    {
        $nodeConfigOptions = $this->getOptions(true);
        $scale = $nodeConfigOptions->getScale();
        $precision = $nodeConfigOptions->getPrecision();
        $type = $this->getType();
        $typesAllowingScale = array(
            'decimal',
        );

        if (!is_null($scale) && !in_array($type, $typesAllowingScale)) {
            // This field is not allowed to specify scale but it still did.
            // The user may have tried disabling client-side validations.
            return false;
        }

        if (!is_null($scale) && !is_int($scale)) {
            // Invalid type.
            return false;
        }

        if (!is_null($scale) && $scale < 1) {
            // Too small value.
            return false;
        }

        if ($scale > $precision) {
            // Scale must not be greater than precision.
            return false;
        }

        return true;
    }

    /**
     * @Assert\IsTrue(message="Some of the field options specify an invalid length.")
     */
    public function hasValidLength()
    {
        $nodeConfigOptions = $this->getOptions(true);
        $length = $nodeConfigOptions->getLength();
        $type = $this->getType();
        $typesAllowingLength = array(
            'string',
        );

        if (!is_null($length) && !in_array($type, $typesAllowingLength)) {
            // This field is not allowed to specify length but it still did.
            // The user may have tried disabling client-side validations.
            return false;
        }

        if (!is_null($length) && !is_int($length)) {
            // Invalid type.
            return false;
        }

        if (!is_null($length) && $length < 1) {
            // Too small value.
            return false;
        }

        return true;
    }

    /**
     * @Assert\IsTrue(message="Some of the field options specify an invalid generated value strategy.")
     */
    public function hasValidGeneratedValueStrategy()
    {
        $nodeConfigOptions = $this->getOptions(true);
        $allowedStrategies = array(
            'NONE',
            'AUTO',
            'SEQUENCE',
            'IDENTITY',
            'UUID',
        );

        $strategy = $nodeConfigOptions->getStrategy();
        if ($strategy === null) {
            // null is identical to 'NONE'.
            return true;
        }
        elseif (in_array($strategy, $allowedStrategies)) {
            // The strategy is in the list of allowed values.
            return true;
        }
        else {
            // Invalid strategy.
            return false;
        }
    }

    /**
     * @Assert\IsFalse(message="Some of the field options specify a generated value strategy while they are not marked as primary fields.")
     */
    public function hasStrategyForNonPrimaryField()
    {
        $nodeConfigOptions = $this->getOptions(true);
        if (!$nodeConfigOptions->isPrimaryKey() && $nodeConfigOptions->getStrategy() !== NULL) {
            // Has a specified strategy for a non-primary field.
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * @Assert\IsTrue(message="Some of the field options specify a column name that is not valid. The column name should contain only alphanumeric characters and underscore and it should not start with a digit.")
     */
    public function hasValidOptionsColumnName()
    {
        $nodeConfigOptions = $this->getOptions(true);
        $columnName = $nodeConfigOptions->getColumnName();
        if (isset($columnName)) {
            // A column name was specified for this node config field.
            // Validate that the column name matches the proper pattern.
            $pattern = '/^[_a-zA-Z][_a-zA-Z0-9]*$/';
            if (!preg_match($pattern, $columnName)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Converts the options JSON string to a NodeConfigOptions object.
     *
     * NOTE: This method is useful when building a form for an existing NodeConfigField.
     * It should generally not be used for other purposes, since Doctrine expects the options
     * to be a JSON-encoded string.
     */
    public function convertOptionsToObject()
    {
        // Convert the options to an object.
        $nodeConfigOptions = $this->getOptions(true);
        // Set the options property without encoding it back to JSON.
        $this->setOptions($nodeConfigOptions, false);
    }

    /**
     * Converts the NodeConfigOptions objects to JSON string.
     *
     * NOTE: This method is useful when parsing a form submission that had existing NodeConfigOptions embedded in it.
     * It can be used before persisting a NodeConfigField to the database, since doctrine requires the options to be JSON-encoded.
     */
    public function convertOptionsToJson()
    {
        // Get the options. They could be a NodeConfigOptions object or a JSON string.
        $nodeConfigOptions = $this->getOptions();
        // Set the options property and force encode them to JSON.
        $this->setOptions($nodeConfigOptions);
    }
}
