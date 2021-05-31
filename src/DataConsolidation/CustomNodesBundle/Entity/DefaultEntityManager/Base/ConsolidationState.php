<?php

namespace DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base;

use Doctrine\ORM\Mapping as ORM;

/**
 * ConsolidationState
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\ConsolidationStateRepository")
 */
class ConsolidationState
{
    const CONSOLIDATION_TYPE_NONE = 0;
    const CONSOLIDATION_TYPE_MINUTE = 1;
    const CONSOLIDATION_TYPE_HOUR = 2;
    const CONSOLIDATION_TYPE_DAY = 3;
    const CONSOLIDATION_TYPE_MONTH = 4;

    /**
     * Lists the valid consolidation type values.
     *
     * NOTE: It is important that the results are ordered in hierarchical order: from highest sample rate to lowest.
     *
     * @return int[] The valid consolidation types.
     */
    public static function getValidConsolidationTypes()
    {
        $validConsolidationTypes = array(
            self::CONSOLIDATION_TYPE_NONE,
            self::CONSOLIDATION_TYPE_MINUTE,
            self::CONSOLIDATION_TYPE_HOUR,
            self::CONSOLIDATION_TYPE_DAY,
            self::CONSOLIDATION_TYPE_MONTH,
        );

        return $validConsolidationTypes;
    }

    /**
     * Determines the needed source consolidation type, which is of the next higher sample rate compared to the desired target consolidation type.     *
     *
     * @param integer $targetConsolidationType The desired target consolidation type for which a source consolidation type (one with a higher sample rate) should be found.
     * @return integer|bool The CONSOLIDATION_TYPE value to be used as a data source for the specified $targetConsolidationType.
     *  If no such data source consolidation type is found, then boolean false is returned.
     */
    public static function getSourceConsolidationType($targetConsolidationType)
    {
        $consolidationTypes = self::getValidConsolidationTypes();
        $targetConsolidationTypeKey = array_search($targetConsolidationType, $consolidationTypes, true);
        if ($targetConsolidationTypeKey === false || $targetConsolidationTypeKey === 0) {
            // No source consolidation type available for the specified target consolidation type.
            return false;
        }

        // Get the consolidation type with the next higher sample rate.
        $sourceConsolidationTypeKey  = $targetConsolidationTypeKey - 1;
        $sourceConsolidationType = $consolidationTypes[$sourceConsolidationTypeKey];
        return $sourceConsolidationTypeKey;
    }

    /**
     * TODO
     *
     * @param $consolidationType
     * @return bool|float|int
     */
    public static function getTimeIntervalByConsolidationType($consolidationType) {
        switch ($consolidationType) {
            case self::CONSOLIDATION_TYPE_MINUTE:
                return 60;
                break;
            case self::CONSOLIDATION_TYPE_HOUR:
                return (60 * 60);
                break;
            case self::CONSOLIDATION_TYPE_DAY:
                return (24 * 60 * 60);
                break;
            case self::CONSOLIDATION_TYPE_MONTH:
                return ((365.25 / 12) * (24 * 60 * 60));
                break;
            default:
                return false;
        }
    }

    /**
     * TODO
     *
     * @param $consolidationType
     * @param \DateTime $measurementTime
     * @return \DateTime
     */
    public static function getClosestRoundedTimeByConsolidationType($consolidationType, \DateTime $measurementTime)
    {
        $measurementTimestamp = $measurementTime->getTimestamp();
        $measurementTimezone = $measurementTime->getTimezone();
        $timeInterval = self::getTimeIntervalByConsolidationType($consolidationType);
        $roundedTimestamp = round($measurementTimestamp / $timeInterval) * $timeInterval;
        $roundedTime = new \DateTime();
        $roundedTime->setTimestamp($roundedTimestamp);
        $roundedTime->setTimezone($measurementTimezone);

        return $roundedTime;
    }

    /**
     * TODO
     *
     * @param $consolidationType
     * @param \DateTime $measurementTime
     * @return array
     */
    public static function getTimeIntervalStartAndEndTimesByConsolidationType($consolidationType, \DateTime $measurementTime)
    {
        $roundedTime = self::getClosestRoundedTimeByConsolidationType($consolidationType, $measurementTime);
        $roundedTimeTimestamp = $roundedTime->getTimestamp();
        $roundedTimeTimezone = $roundedTime->getTimezone();

        $timeIntervalInSeconds = self::getTimeIntervalByConsolidationType($consolidationType);
        // Create two date times: one for the interval start, and one for the interval end. Set their timezones.
        $startTime = new \DateTime();
        $startTime->setTimezone($roundedTimeTimezone);
        $endTime = new \DateTime();
        $endTime->setTimezone($roundedTimeTimezone);

        // Now calculate the timestamp for the start and end time.
        $startTimestamp = $roundedTimeTimestamp - round($timeIntervalInSeconds / 2);
        $endTimestamp = $roundedTimeTimestamp + round($timeIntervalInSeconds / 2) - 1;

        // Adjust the datetime timestamps.
        $startTime->setTimestamp($startTimestamp);
        $endTime->setTimestamp($endTimestamp);

        $result = array(
            'start' => $startTime,
            'end' => $endTime,
        );
        
        return $result;
    }

    /**
     * TODO
     *
     * @param $consolidationType
     * @param \DateTime $measurementTime
     * @return \DateTime
     */
    public static function getNextTimeByConsolidationType($consolidationType, \DateTime $measurementTime)
    {
        // Get the time interval in seconds for this consolidation type.
        $timeInterval = self::getTimeIntervalByConsolidationType($consolidationType);
        // Add that amount of seconds to the measurement time.
        $nextMeasurementTime = clone $measurementTime;
        $intervalSpec = 'PT' . $timeInterval . 'S';
        $dateInterval = new \DateInterval($intervalSpec);
        $nextMeasurementTime->add($dateInterval);
        return $nextMeasurementTime;
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
     * @var DataSourceToDiagramMapping
     *
     * @ORM\ManyToOne(targetEntity="DataSourceToDiagramMapping", inversedBy="consolidationStates")
     */
    private $dataSourceToDiagramMapping;

    /**
     * @var integer
     *
     * @ORM\Column(name="consolidation_type", type="integer")
     */
    private $consolidationType;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_measurement_time", type="datetime", nullable=true)
     */
    private $lastMeasurementTime;


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
     * Set consolidationType
     *
     * @param integer $consolidationType
     * @return ConsolidationState
     */
    public function setConsolidationType($consolidationType)
    {
        $this->consolidationType = $consolidationType;

        return $this;
    }

    /**
     * Get consolidationType
     *
     * @return integer 
     */
    public function getConsolidationType()
    {
        return $this->consolidationType;
    }

    /**
     * Set lastMeasurementTime
     *
     * @param \DateTime $lastMeasurementTime
     * @return ConsolidationState
     */
    public function setLastMeasurementTime($lastMeasurementTime)
    {
        $this->lastMeasurementTime = $lastMeasurementTime;

        return $this;
    }

    /**
     * Get lastMeasurementTime
     *
     * @return \DateTime 
     */
    public function getLastMeasurementTime()
    {
        return $this->lastMeasurementTime;
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
            // Remove the current consolidation state from the data source to diagram mapping if it contains it.
            if ($this->dataSourceToDiagramMapping->getConsolidationStates()->contains($this)) {
                $this->dataSourceToDiagramMapping->removeConsolidationState($this);
            }
        }
        elseif ($dataSourceToDiagramMapping) {
            // A data source to diagram mapping is being added. Add the consolidation state to it.
            if (!$dataSourceToDiagramMapping->getConsolidationStates()->contains($this)) {
                $dataSourceToDiagramMapping->addConsolidationState($this);
            }
        }

        $this->dataSourceToDiagramMapping = $dataSourceToDiagramMapping;
    }
}
