<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 4/2/16
 * Time: 11:59 PM
 */

namespace DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ImportedDatabaseSchema used for ImportDatabaseSchemaFlow. This is a form flow's model.
 *
 * @package DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base
 */
class ImportedDatabaseSchema
{
    /**
     * @var string
     *
     * @Assert\NotBlank(
     *     groups={"importDatabaseSchemaFlowStep1"}
     * )
     */
    private $entityManagerName;

    /**
     * @var string
     *
     * @Assert\NotBlank(
     *     groups={"importDatabaseSchemaFlowStep2"}
     * )
     * @Assert\Regex(
     *     groups={"importDatabaseSchemaFlowStep2"},
     *     pattern="/^[_a-zA-Z][_a-zA-Z0-9]*$/",
     *     message = "The table name should contain only alphanumeric characters and underscore and it should not start with a digit."
     * )
     */
    private $tableName;

    /**
     * @var NodeConfig
     *
     * @Assert\Valid()
     */
    private $nodeConfig;

    /**
     * @return string
     */
    public function getEntityManagerName()
    {
        return $this->entityManagerName;
    }

    /**
     * @param string $entityManagerName
     */
    public function setEntityManagerName($entityManagerName)
    {
        $this->entityManagerName = $entityManagerName;
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
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
    public function setNodeConfig($nodeConfig)
    {
        $this->nodeConfig = $nodeConfig;
    }


}