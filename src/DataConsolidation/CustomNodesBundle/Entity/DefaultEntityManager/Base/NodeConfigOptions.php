<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 3/21/16
 * Time: 4:35 PM
 */

namespace DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base;

class NodeConfigOptions implements \JsonSerializable
{
    /**
     * @var bool
     */
    private $primaryKey;

    /**
     * Generated value strategy (for primary keys).
     *
     * @var string
     */
    private $strategy;

    /**
     * @var bool
     */
    private $nullable;

    /**
     * @var bool
     */
    private $unique;

    /**
     * @var bool
     */
    private $visibleInContentList;

    /**
     * @var string
     */
    private $columnName;

    /**
     * @var int
     */
    private $precision;

    /**
     * @var int
     */
    private $scale;

    /**
     * @var int
     */
    private $length;

    public function __construct()
    {
        $this->primaryKey = false;
        $this->strategy = null;
        $this->nullable = false;
        $this->unique = false;
        $this->visibleInContentList = false;
        $this->columnName = null;
        $this->precision = null;
        $this->scale = null;
        $this->length = null;
    }

    /**
     * @return boolean
     */
    public function isPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * @param boolean $primaryKey
     */
    public function setPrimaryKey($primaryKey)
    {
        $this->primaryKey = $primaryKey;
    }

    /**
     * @return string
     */
    public function getStrategy()
    {
        return $this->strategy;
    }

    /**
     * @param string $strategy
     */
    public function setStrategy($strategy)
    {
        $this->strategy = $strategy;
    }

    /**
     * @return boolean
     */
    public function isNullable()
    {
        return $this->nullable;
    }

    /**
     * @param boolean $nullable
     */
    public function setNullable($nullable)
    {
        $this->nullable = $nullable;
    }

    /**
     * @return boolean
     */
    public function isUnique()
    {
        return $this->unique;
    }

    /**
     * @param boolean $unique
     */
    public function setUnique($unique)
    {
        $this->unique = $unique;
    }

    /**
     * @return boolean
     */
    public function isVisibleInContentList()
    {
        return $this->visibleInContentList;
    }

    /**
     * @param boolean $visibleInContentList
     */
    public function setVisibleInContentList($visibleInContentList)
    {
        $this->visibleInContentList = $visibleInContentList;
    }

    /**
     * @return string
     */
    public function getColumnName()
    {
        return $this->columnName;
    }

    /**
     * @param string $columnName
     */
    public function setColumnName($columnName)
    {
        $this->columnName = $columnName;
    }

    /**
     * @return int
     */
    public function getPrecision()
    {
        return $this->precision;
    }

    /**
     * @param int $precision
     */
    public function setPrecision($precision)
    {
        $this->precision = $precision;
    }

    /**
     * @return int
     */
    public function getScale()
    {
        return $this->scale;
    }

    /**
     * @param int $scale
     */
    public function setScale($scale)
    {
        $this->scale = $scale;
    }

    /**
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param int $length
     */
    public function setLength($length)
    {
        $this->length = $length;
    }


    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
       return array(
            'primaryKey' => $this->primaryKey,
            'strategy' => $this->strategy,
            'nullable' => $this->nullable,
            'unique' => $this->unique,
            'visibleInContentList' => $this->visibleInContentList,
            'columnName' => $this->columnName,
            'precision' => $this->precision,
            'scale' => $this->scale,
            'length' => $this->length,
       );
    }

    /**
     * Builds a NodeConfigOptions object from array.
     *
     * @param array $data A decoded JSON data representing the options object
     *
     * @return NodeConfigOptions
     */
    public static function buildFromArray($data)
    {
        $nodeConfigOptions = new NodeConfigOptions();

        if (isset($data['primaryKey'])) {
            $nodeConfigOptions->setPrimaryKey($data['primaryKey']);
        }
        if (isset($data['strategy'])) {
            $nodeConfigOptions->setStrategy($data['strategy']);
        }
        if (isset($data['nullable'])) {
            $nodeConfigOptions->setNullable($data['nullable']);
        }
        if (isset($data['unique'])) {
            $nodeConfigOptions->setUnique($data['unique']);
        }
        if (isset($data['visibleInContentList'])) {
            $nodeConfigOptions->setVisibleInContentList($data['visibleInContentList']);
        }
        if (isset($data['columnName'])) {
            $nodeConfigOptions->setColumnName($data['columnName']);
        }
        if (isset($data['precision'])) {
            $nodeConfigOptions->setPrecision($data['precision']);
        }
        if (isset($data['scale'])) {
            $nodeConfigOptions->setScale($data['scale']);
        }
        if (isset($data['length'])) {
            $nodeConfigOptions->setLength($data['length']);
        }

        return $nodeConfigOptions;
    }

}
