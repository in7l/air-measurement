<?php

namespace DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * DataSourceToDiagramMapping
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\DataSourceToDiagramMappingRepository")
 * @UniqueEntity(
 *     fields={"dataSource", "diagram"},
 *     message="This data source + diagram combination already exists."
 * )
 */
class DataSourceToDiagramMapping
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
     * @var string
     *
     * @ORM\Column(name="dataSource", type="string", length=500)
     */
    private $dataSource;

    /**
     * @var string
     *
     * @ORM\Column(name="diagram", type="string", length=500)
     */
    private $diagram;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="FieldMapping", mappedBy="dataSourceToDiagramMapping", cascade={"persist", "remove"}, orphanRemoval=true)
     * @Assert\Valid()
     */
    protected $fieldMappings;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="ConsolidationState", mappedBy="dataSourceToDiagramMapping", cascade={"persist", "remove"}, orphanRemoval=true)
     * @Assert\Valid()
     */
    protected $consolidationStates;

    public function __construct()
    {
        $this->fieldMappings = new ArrayCollection();
        $this->consolidationStates = new ArrayCollection();

        // Define default consolidation states that are needed for each diagram.
        $consolidationTypes = ConsolidationState::getValidConsolidationTypes();
        // There is no need to create a ConsolidationState for the CONSOLIDATION_TYPE_NONE (non-resampled data).
        $consolidationTypes = array_values(array_diff($consolidationTypes, array(ConsolidationState::CONSOLIDATION_TYPE_NONE)));
        foreach ($consolidationTypes as $consolidationType) {
            // Create a new ConsolidationState object with the proper consolidation type, and associate it with this DataSourceToDiagramMapping.
            $consolidationState = new ConsolidationState();
            $consolidationState->setConsolidationType($consolidationType);
            $this->addConsolidationState($consolidationState);
        }
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
     * Set dataSource
     *
     * @param string $dataSource
     * @return DataSourceToDiagramMapping
     */
    public function setDataSource($dataSource)
    {
        $this->dataSource = $dataSource;

        return $this;
    }

    /**
     * Get dataSource
     *
     * @return string 
     */
    public function getDataSource()
    {
        return $this->dataSource;
    }

    /**
     * Set diagram
     *
     * @param string $diagram
     * @return DataSourceToDiagramMapping
     */
    public function setDiagram($diagram)
    {
        $this->diagram = $diagram;

        return $this;
    }

    /**
     * Get diagram
     *
     * @return string 
     */
    public function getDiagram()
    {
        return $this->diagram;
    }

    /**
     * @return ArrayCollection
     */
    public function getFieldMappings()
    {
        return $this->fieldMappings;
    }

    /**
     * @param ArrayCollection $fieldMappings
     */
    public function setFieldMappings($fieldMappings)
    {
        $this->fieldMappings = $fieldMappings;
    }

    /**
     * Adds a new field mapping
     *
     * @param FieldMapping $fieldMapping
     */
    public function addFieldMapping(FieldMapping $fieldMapping)
    {
        $this->fieldMappings->add($fieldMapping);
        $fieldMapping->setDataSourceToDiagramMapping($this);
    }

    /**
     * Removes an existing field mapping
     *
     * @param FieldMapping $fieldMapping
     */
    public function removeFieldMapping(FieldMapping $fieldMapping)
    {
        $this->fieldMappings->removeElement($fieldMapping);
        $fieldMapping->setDataSourceToDiagramMapping(null);
    }

    /**
     * @return ArrayCollection
     */
    public function getConsolidationStates()
    {
        return $this->consolidationStates;
    }

    /**
     * @param ArrayCollection $consolidationStates
     */
    public function setConsolidationStates($consolidationStates)
    {
        $this->consolidationStates = $consolidationStates;
    }

    /**
     * Adds a new field mapping
     *
     * @param ConsolidationState $consolidationState
     */
    public function addConsolidationState(ConsolidationState $consolidationState)
    {
        $this->consolidationStates->add($consolidationState);
        $consolidationState->setDataSourceToDiagramMapping($this);
    }

    /**
     * Removes an existing field mapping
     *
     * @param ConsolidationState $consolidationState
     */
    public function removeConsolidationState(ConsolidationState $consolidationState)
    {
        $this->consolidationStates->removeElement($consolidationState);
        $consolidationState->setDataSourceToDiagramMapping(null);
    }

    /**
     * @return string A short name to identify this DataSourceToDiagramMapping.
     */
    public function getShortName()
    {
        // Extract the last string component (the class name) of the data source and diagram namespaces.
        $dataSourceBaseName = basename(str_replace('\\', '/', $this->dataSource));
        $diagramBaseName = basename(str_replace('\\', '/', $this->diagram));

        // Build a short name for this mapping.
        $shortName = $dataSourceBaseName . ' -> ' . $diagramBaseName;

        return $shortName;
    }
}
