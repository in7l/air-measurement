<?php

namespace DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base;

use DataConsolidation\CustomNodesBundle\DataConsolidationCustomNodesBundle;
use Doctrine\ORM\EntityRepository;

/**
 * NodeConfigRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class NodeConfigRepository extends EntityRepository
{
    /**
     * Searches for a single entity of the NodeConfig base class by id, excluding DiagramConfig entities.
     *
     * @param Integer $id The id of the data source node config entity to be found.
     * @return NodeConfig|null The data source node config object if found, or NULL otherwise.
     */
    public function findDataSourceNodeConfig($id)
    {
        // Make sure the id is an integer.
        $id = intval($id);
        $query = $this->_em->createQuery('SELECT nc FROM DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\NodeConfig nc
          WHERE nc.id = :id
          AND nc NOT INSTANCE OF DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\DiagramConfig');

        $query->setParameter('id', $id);
        $nodeConfig = $query->getOneOrNullResult();
        return $nodeConfig;
    }

    /**
     * Searches for entities of the NodeConfig base class, excluding DiagramConfig entities.
     *
     * @return array of NodeConfig objects.
     */
    public function findDataSourceNodeConfigs()
    {
        $query = $this->_em->createQuery('SELECT nc FROM DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\NodeConfig nc WHERE nc NOT INSTANCE OF DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\DiagramConfig');
        $nodeConfigs = $query->getResult();
        return $nodeConfigs;
    }

    /**
     * Searches for entities of the DataConfig subclass.
     *
     * @return array of DiagramConfig objects.
     */
    public function findDiagramConfigs()
    {
        $query = $this->_em->createQuery('SELECT nc FROM DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\NodeConfig nc WHERE nc INSTANCE OF DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\DiagramConfig');
        $nodeConfigs = $query->getResult();
        return $nodeConfigs;
    }
}
