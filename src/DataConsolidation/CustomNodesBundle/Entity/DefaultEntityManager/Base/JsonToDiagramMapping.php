<?php

namespace DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * JsonToDiagramMapping
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\JsonToDiagramMappingRepository")
 */
class JsonToDiagramMapping
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
     * @ORM\Column(name="sourceUrl", type="string", length=2000)
     */
    private $sourceUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="sourceDisplayName", type="string", length=500)
     */
    private $sourceDisplayName;

    /**
     * @var string
     *
     * @ORM\Column(name="diagram", type="string", length=500)
     */
    private $diagram;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="JsonFieldMapping", mappedBy="JsonToDiagramMapping", cascade={"persist", "remove"}, orphanRemoval=true)
     * @Assert\Valid()
     */
    protected $fieldMappings;

    public function __construct()
    {
        $this->fieldMappings = new ArrayCollection();
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
     * Set sourceUrl
     *
     * @param string $sourceUrl
     * @return JsonToDiagramMapping
     */
    public function setSourceUrl($sourceUrl)
    {
        $this->sourceUrl = $sourceUrl;

        return $this;
    }

    /**
     * Get sourceUrl
     *
     * @return string 
     */
    public function getSourceUrl()
    {
        return $this->sourceUrl;
    }

    /**
     * @return string
     */
    public function getSourceDisplayName()
    {
        return $this->sourceDisplayName;
    }

    /**
     * @param string $sourceDisplayName
     */
    public function setSourceDisplayName($sourceDisplayName)
    {
        $this->sourceDisplayName = $sourceDisplayName;
    }

    /**
     * Set diagram
     *
     * @param string $diagram
     * @return JsonToDiagramMapping
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
     * @param JsonFieldMapping $fieldMapping
     */
    public function addFieldMapping(JsonFieldMapping $fieldMapping)
    {
        $this->fieldMappings->add($fieldMapping);
        $fieldMapping->setJsonToDiagramMapping($this);
    }

    /**
     * Removes an existing field mapping
     *
     * @param JsonFieldMapping $fieldMapping
     */
    public function removeFieldMapping(JsonFieldMapping $fieldMapping)
    {
        $this->fieldMappings->removeElement($fieldMapping);
        $fieldMapping->setJsonToDiagramMapping(null);
    }
}
