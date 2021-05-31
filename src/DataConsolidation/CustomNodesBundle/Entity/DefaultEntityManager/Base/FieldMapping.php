<?php

namespace DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base;

use Doctrine\ORM\Mapping as ORM;

/**
 * FieldMapping
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\FieldMappingRepository")
 */
class FieldMapping
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var DataSourceToDiagramMapping
     *
     * @ORM\ManyToOne(targetEntity="DataSourceToDiagramMapping", inversedBy="fieldMappings")
     */
    private $dataSourceToDiagramMapping;

    /**
     * @var string
     *
     * @ORM\Column(name="sourceGetter", type="string", length=255)
     */
    private $sourceGetter;

    /**
     * @var string
     *
     * @ORM\Column(name="targetGetter", type="string", length=255)
     */
    private $targetGetter;

    /**
     * @var string
     *
     * @ORM\Column(name="targetSetter", type="string", length=255)
     */
    private $targetSetter;


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
     * Set sourceGetter
     *
     * @param string $sourceGetter
     * @return FieldMapping
     */
    public function setSourceGetter($sourceGetter)
    {
        $this->sourceGetter = $sourceGetter;

        return $this;
    }

    /**
     * Get sourceGetter
     *
     * @return string 
     */
    public function getSourceGetter()
    {
        return $this->sourceGetter;
    }

    /**
     * Set targetGetter
     *
     * @param string $targetGetter
     * @return FieldMapping
     */
    public function setTargetGetter($targetGetter)
    {
        $this->targetGetter = $targetGetter;

        return $this;
    }

    /**
     * Get targetGetter
     *
     * @return string 
     */
    public function getTargetGetter()
    {
        return $this->targetGetter;
    }

    /**
     * @return string
     */
    public function getTargetSetter()
    {
        return $this->targetSetter;
    }

    /**
     * @param string $targetSetter
     */
    public function setTargetSetter($targetSetter)
    {
        $this->targetSetter = $targetSetter;
    }

    /**
     * @return DataSourceToDiagramMapping
     */
    public function getDataSourceToDiagramMapping()
    {
        return $this->dataSourceToDiagramMapping;
    }

    /**
     * @param DataSourceToDiagramMapping $dataSourceToDiagramMapping
     */
    public function setDataSourceToDiagramMapping(DataSourceToDiagramMapping $dataSourceToDiagramMapping = null)
    {
        if ($dataSourceToDiagramMapping === null && $this->dataSourceToDiagramMapping) {
            // The data source to diagram mapping is being removed.
            // Remove the current field mapping from the data source to diagram mapping if it contains it.
            if ($this->dataSourceToDiagramMapping->getFieldMappings()->contains($this)) {
                $this->dataSourceToDiagramMapping->removeFieldMapping($this);
            }
        }
        elseif ($dataSourceToDiagramMapping) {
            // A data source to diagram mapping is being added. Add the field mapping to it.
            if (!$dataSourceToDiagramMapping->getFieldMappings()->contains($this)) {
                $dataSourceToDiagramMapping->addFieldMapping($this);
            }
        }

        $this->dataSourceToDiagramMapping = $dataSourceToDiagramMapping;
    }

}
