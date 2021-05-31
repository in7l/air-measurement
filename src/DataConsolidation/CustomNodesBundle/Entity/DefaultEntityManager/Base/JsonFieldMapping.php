<?php

namespace DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base;

use Doctrine\ORM\Mapping as ORM;

/**
 * JsonFieldMapping
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\FieldMappingRepository")
 */
class JsonFieldMapping
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
     * @var JsonToDiagramMapping
     *
     * @ORM\ManyToOne(targetEntity="JsonToDiagramMapping", inversedBy="fieldMappings")
     */
    private $jsonToDiagramMapping;

    /**
     * @var string
     *
     * @ORM\Column(name="sourceGetter", type="string", length=500)
     */
    private $sourceGetter;

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
     * @return JsonFieldMapping
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
     * @return JsonFieldMapping
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
     * @return JsonToDiagramMapping
     */
    public function getJsonToDiagramMapping()
    {
        return $this->jsonToDiagramMapping;
    }

    /**
     * @param JsonToDiagramMapping $jsonToDiagramMapping
     */
    public function setJsonToDiagramMapping(JsonToDiagramMapping $jsonToDiagramMapping = null)
    {
        if ($jsonToDiagramMapping === null && $this->jsonToDiagramMapping) {
            // The json to diagram mapping is being removed.
            // Remove the current field mapping from the json to diagram mapping if it contains it.
            if ($this->jsonToDiagramMapping->getFieldMappings()->contains($this)) {
                $this->jsonToDiagramMapping->removeFieldMapping($this);
            }
        }
        elseif ($jsonToDiagramMapping) {
            // A json to diagram mapping is being added. Add the field mapping to it.
            if (!$jsonToDiagramMapping->getFieldMappings()->contains($this)) {
                $jsonToDiagramMapping->addFieldMapping($this);
            }
        }

        $this->jsonToDiagramMapping = $jsonToDiagramMapping;
    }

}
