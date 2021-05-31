<?php

namespace AirMeasurement\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * QualityConditions
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AirMeasurement\AdminBundle\Entity\QualityConditionsRepository")
 */
class QualityConditions implements JsonSerializable
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
     * @var integer
     *
     * @ORM\Column(name="sensor_id", type="integer")
     */
    private $sensorId;

    /**
     * @var string
     *
     * @ORM\Column(name="good_measure_n_min", type="decimal", precision=10, scale=6, nullable=true)
     */
    private $goodMeasureNMin;

    /**
     * @var boolean
     *
     * @ORM\Column(name="good_measure_n_min_inclusive", type="boolean", nullable=true)
     */
    private $goodMeasureNMinInclusive;

    /**
     * @var string
     *
     * @ORM\Column(name="good_measure_n_max", type="decimal", precision=10, scale=6, nullable=true)
     */
    private $goodMeasureNMax;

    /**
     * @var boolean
     *
     * @ORM\Column(name="good_measure_n_max_inclusive", type="boolean", nullable=true)
     */
    private $goodMeasureNMaxInclusive;

    /**
     * @var string
     *
     * @ORM\Column(name="good_measure_pa_min", type="decimal", precision=10, scale=6, nullable=true)
     */
    private $goodMeasurePaMin;

    /**
     * @var boolean
     *
     * @ORM\Column(name="good_measure_pa_min_inclusive", type="boolean", nullable=true)
     */
    private $goodMeasurePaMinInclusive;

    /**
     * @var string
     *
     * @ORM\Column(name="good_measure_pa_max", type="decimal", precision=10, scale=6, nullable=true)
     */
    private $goodMeasurePaMax;

    /**
     * @var boolean
     *
     * @ORM\Column(name="good_measure_pa_max_inclusive", type="boolean", nullable=true)
     */
    private $goodMeasurePaMaxInclusive;

    /**
     * @var string
     *
     * @ORM\Column(name="good_measure_mg_min", type="decimal", precision=10, scale=6, nullable=true)
     */
    private $goodMeasureMgMin;

    /**
     * @var boolean
     *
     * @ORM\Column(name="good_measure_mg_min_inclusive", type="boolean", nullable=true)
     */
    private $goodMeasureMgMinInclusive;

    /**
     * @var string
     *
     * @ORM\Column(name="good_measure_mg_max", type="decimal", precision=10, scale=6, nullable=true)
     */
    private $goodMeasureMgMax;

    /**
     * @var boolean
     *
     * @ORM\Column(name="good_measure_mg_max_inclusive", type="boolean", nullable=true)
     */
    private $goodMeasureMgMaxInclusive;

    /**
     * @var string
     *
     * @ORM\Column(name="fair_measure_n_min", type="decimal", precision=10, scale=6, nullable=true)
     */
    private $fairMeasureNMin;

    /**
     * @var boolean
     *
     * @ORM\Column(name="fair_measure_n_min_inclusive", type="boolean", nullable=true)
     */
    private $fairMeasureNMinInclusive;

    /**
     * @var string
     *
     * @ORM\Column(name="fair_measure_n_max", type="decimal", precision=10, scale=6, nullable=true)
     */
    private $fairMeasureNMax;

    /**
     * @var boolean
     *
     * @ORM\Column(name="fair_measure_n_max_inclusive", type="boolean", nullable=true)
     */
    private $fairMeasureNMaxInclusive;

    /**
     * @var string
     *
     * @ORM\Column(name="fair_measure_pa_min", type="decimal", precision=10, scale=6, nullable=true)
     */
    private $fairMeasurePaMin;

    /**
     * @var boolean
     *
     * @ORM\Column(name="fair_measure_pa_min_inclusive", type="boolean", nullable=true)
     */
    private $fairMeasurePaMinInclusive;

    /**
     * @var string
     *
     * @ORM\Column(name="fair_measure_pa_max", type="decimal", precision=10, scale=6, nullable=true)
     */
    private $fairMeasurePaMax;

    /**
     * @var boolean
     *
     * @ORM\Column(name="fair_measure_pa_max_inclusive", type="boolean", nullable=true)
     */
    private $fairMeasurePaMaxInclusive;

    /**
     * @var string
     *
     * @ORM\Column(name="fair_measure_mg_min", type="decimal", precision=10, scale=6, nullable=true)
     */
    private $fairMeasureMgMin;

    /**
     * @var boolean
     *
     * @ORM\Column(name="fair_measure_mg_min_inclusive", type="boolean", nullable=true)
     */
    private $fairMeasureMgMinInclusive;

    /**
     * @var string
     *
     * @ORM\Column(name="fair_measure_mg_max", type="decimal", precision=10, scale=6, nullable=true)
     */
    private $fairMeasureMgMax;

    /**
     * @var boolean
     *
     * @ORM\Column(name="fair_measure_mg_max_inclusive", type="boolean", nullable=true)
     */
    private $fairMeasureMgMaxInclusive;


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
     * Set sensorId
     *
     * @param integer $sensorId
     * @return QualityConditions
     */
    public function setSensorId($sensorId)
    {
        $this->sensorId = $sensorId;

        return $this;
    }

    /**
     * Get sensorId
     *
     * @return integer 
     */
    public function getSensorId()
    {
        return $this->sensorId;
    }

    /**
     * Set goodMeasureNMin
     *
     * @param string $goodMeasureNMin
     * @return QualityConditions
     */
    public function setGoodMeasureNMin($goodMeasureNMin)
    {
        $this->goodMeasureNMin = $goodMeasureNMin;

        return $this;
    }

    /**
     * Get goodMeasureNMin
     *
     * @return string 
     */
    public function getGoodMeasureNMin()
    {
        return $this->goodMeasureNMin;
    }

    /**
     * Set goodMeasureNMinInclusive
     *
     * @param boolean $goodMeasureNMinInclusive
     * @return QualityConditions
     */
    public function setGoodMeasureNMinInclusive($goodMeasureNMinInclusive)
    {
        $this->goodMeasureNMinInclusive = $goodMeasureNMinInclusive;

        return $this;
    }

    /**
     * Get goodMeasureNMinInclusive
     *
     * @return boolean 
     */
    public function getGoodMeasureNMinInclusive()
    {
        return $this->goodMeasureNMinInclusive;
    }

    /**
     * Set goodMeasureNMax
     *
     * @param string $goodMeasureNMax
     * @return QualityConditions
     */
    public function setGoodMeasureNMax($goodMeasureNMax)
    {
        $this->goodMeasureNMax = $goodMeasureNMax;

        return $this;
    }

    /**
     * Get goodMeasureNMax
     *
     * @return string 
     */
    public function getGoodMeasureNMax()
    {
        return $this->goodMeasureNMax;
    }

    /**
     * Set goodMeasureNMaxInclusive
     *
     * @param boolean $goodMeasureNMaxInclusive
     * @return QualityConditions
     */
    public function setGoodMeasureNMaxInclusive($goodMeasureNMaxInclusive)
    {
        $this->goodMeasureNMaxInclusive = $goodMeasureNMaxInclusive;

        return $this;
    }

    /**
     * Get goodMeasureNMaxInclusive
     *
     * @return boolean 
     */
    public function getGoodMeasureNMaxInclusive()
    {
        return $this->goodMeasureNMaxInclusive;
    }

    /**
     * Set goodMeasurePaMin
     *
     * @param string $goodMeasurePaMin
     * @return QualityConditions
     */
    public function setGoodMeasurePaMin($goodMeasurePaMin)
    {
        $this->goodMeasurePaMin = $goodMeasurePaMin;

        return $this;
    }

    /**
     * Get goodMeasurePaMin
     *
     * @return string 
     */
    public function getGoodMeasurePaMin()
    {
        return $this->goodMeasurePaMin;
    }

    /**
     * Set goodMeasurePaMinInclusive
     *
     * @param boolean $goodMeasurePaMinInclusive
     * @return QualityConditions
     */
    public function setGoodMeasurePaMinInclusive($goodMeasurePaMinInclusive)
    {
        $this->goodMeasurePaMinInclusive = $goodMeasurePaMinInclusive;

        return $this;
    }

    /**
     * Get goodMeasurePaMinInclusive
     *
     * @return boolean 
     */
    public function getGoodMeasurePaMinInclusive()
    {
        return $this->goodMeasurePaMinInclusive;
    }

    /**
     * Set goodMeasurePaMax
     *
     * @param string $goodMeasurePaMax
     * @return QualityConditions
     */
    public function setGoodMeasurePaMax($goodMeasurePaMax)
    {
        $this->goodMeasurePaMax = $goodMeasurePaMax;

        return $this;
    }

    /**
     * Get goodMeasurePaMax
     *
     * @return string 
     */
    public function getGoodMeasurePaMax()
    {
        return $this->goodMeasurePaMax;
    }

    /**
     * Set goodMeasurePaMaxInclusive
     *
     * @param boolean $goodMeasurePaMaxInclusive
     * @return QualityConditions
     */
    public function setGoodMeasurePaMaxInclusive($goodMeasurePaMaxInclusive)
    {
        $this->goodMeasurePaMaxInclusive = $goodMeasurePaMaxInclusive;

        return $this;
    }

    /**
     * Get goodMeasurePaMaxInclusive
     *
     * @return boolean 
     */
    public function getGoodMeasurePaMaxInclusive()
    {
        return $this->goodMeasurePaMaxInclusive;
    }

    /**
     * Set goodMeasureMgMin
     *
     * @param string $goodMeasureMgMin
     * @return QualityConditions
     */
    public function setGoodMeasureMgMin($goodMeasureMgMin)
    {
        $this->goodMeasureMgMin = $goodMeasureMgMin;

        return $this;
    }

    /**
     * Get goodMeasureMgMin
     *
     * @return string 
     */
    public function getGoodMeasureMgMin()
    {
        return $this->goodMeasureMgMin;
    }

    /**
     * Set goodMeasureMgMinInclusive
     *
     * @param boolean $goodMeasureMgMinInclusive
     * @return QualityConditions
     */
    public function setGoodMeasureMgMinInclusive($goodMeasureMgMinInclusive)
    {
        $this->goodMeasureMgMinInclusive = $goodMeasureMgMinInclusive;

        return $this;
    }

    /**
     * Get goodMeasureMgMinInclusive
     *
     * @return boolean 
     */
    public function getGoodMeasureMgMinInclusive()
    {
        return $this->goodMeasureMgMinInclusive;
    }

    /**
     * Set goodMeasureMgMax
     *
     * @param string $goodMeasureMgMax
     * @return QualityConditions
     */
    public function setGoodMeasureMgMax($goodMeasureMgMax)
    {
        $this->goodMeasureMgMax = $goodMeasureMgMax;

        return $this;
    }

    /**
     * Get goodMeasureMgMax
     *
     * @return string 
     */
    public function getGoodMeasureMgMax()
    {
        return $this->goodMeasureMgMax;
    }

    /**
     * Set goodMeasureMgMaxInclusive
     *
     * @param boolean $goodMeasureMgMaxInclusive
     * @return QualityConditions
     */
    public function setGoodMeasureMgMaxInclusive($goodMeasureMgMaxInclusive)
    {
        $this->goodMeasureMgMaxInclusive = $goodMeasureMgMaxInclusive;

        return $this;
    }

    /**
     * Get goodMeasureMgMaxInclusive
     *
     * @return boolean 
     */
    public function getGoodMeasureMgMaxInclusive()
    {
        return $this->goodMeasureMgMaxInclusive;
    }

    /**
     * Set fairMeasureNMin
     *
     * @param string $fairMeasureNMin
     * @return QualityConditions
     */
    public function setFairMeasureNMin($fairMeasureNMin)
    {
        $this->fairMeasureNMin = $fairMeasureNMin;

        return $this;
    }

    /**
     * Get fairMeasureNMin
     *
     * @return string 
     */
    public function getFairMeasureNMin()
    {
        return $this->fairMeasureNMin;
    }

    /**
     * Set fairMeasureNMinInclusive
     *
     * @param boolean $fairMeasureNMinInclusive
     * @return QualityConditions
     */
    public function setFairMeasureNMinInclusive($fairMeasureNMinInclusive)
    {
        $this->fairMeasureNMinInclusive = $fairMeasureNMinInclusive;

        return $this;
    }

    /**
     * Get fairMeasureNMinInclusive
     *
     * @return boolean 
     */
    public function getFairMeasureNMinInclusive()
    {
        return $this->fairMeasureNMinInclusive;
    }

    /**
     * Set fairMeasureNMax
     *
     * @param string $fairMeasureNMax
     * @return QualityConditions
     */
    public function setFairMeasureNMax($fairMeasureNMax)
    {
        $this->fairMeasureNMax = $fairMeasureNMax;

        return $this;
    }

    /**
     * Get fairMeasureNMax
     *
     * @return string 
     */
    public function getFairMeasureNMax()
    {
        return $this->fairMeasureNMax;
    }

    /**
     * Set fairMeasureNMaxInclusive
     *
     * @param boolean $fairMeasureNMaxInclusive
     * @return QualityConditions
     */
    public function setFairMeasureNMaxInclusive($fairMeasureNMaxInclusive)
    {
        $this->fairMeasureNMaxInclusive = $fairMeasureNMaxInclusive;

        return $this;
    }

    /**
     * Get fairMeasureNMaxInclusive
     *
     * @return boolean 
     */
    public function getFairMeasureNMaxInclusive()
    {
        return $this->fairMeasureNMaxInclusive;
    }

    /**
     * Set fairMeasurePaMin
     *
     * @param string $fairMeasurePaMin
     * @return QualityConditions
     */
    public function setFairMeasurePaMin($fairMeasurePaMin)
    {
        $this->fairMeasurePaMin = $fairMeasurePaMin;

        return $this;
    }

    /**
     * Get fairMeasurePaMin
     *
     * @return string 
     */
    public function getFairMeasurePaMin()
    {
        return $this->fairMeasurePaMin;
    }

    /**
     * Set fairMeasurePaMinInclusive
     *
     * @param boolean $fairMeasurePaMinInclusive
     * @return QualityConditions
     */
    public function setFairMeasurePaMinInclusive($fairMeasurePaMinInclusive)
    {
        $this->fairMeasurePaMinInclusive = $fairMeasurePaMinInclusive;

        return $this;
    }

    /**
     * Get fairMeasurePaMinInclusive
     *
     * @return boolean 
     */
    public function getFairMeasurePaMinInclusive()
    {
        return $this->fairMeasurePaMinInclusive;
    }

    /**
     * Set fairMeasurePaMax
     *
     * @param string $fairMeasurePaMax
     * @return QualityConditions
     */
    public function setFairMeasurePaMax($fairMeasurePaMax)
    {
        $this->fairMeasurePaMax = $fairMeasurePaMax;

        return $this;
    }

    /**
     * Get fairMeasurePaMax
     *
     * @return string 
     */
    public function getFairMeasurePaMax()
    {
        return $this->fairMeasurePaMax;
    }

    /**
     * Set fairMeasurePaMaxInclusive
     *
     * @param boolean $fairMeasurePaMaxInclusive
     * @return QualityConditions
     */
    public function setFairMeasurePaMaxInclusive($fairMeasurePaMaxInclusive)
    {
        $this->fairMeasurePaMaxInclusive = $fairMeasurePaMaxInclusive;

        return $this;
    }

    /**
     * Get fairMeasurePaMaxInclusive
     *
     * @return boolean 
     */
    public function getFairMeasurePaMaxInclusive()
    {
        return $this->fairMeasurePaMaxInclusive;
    }

    /**
     * Set fairMeasureMgMin
     *
     * @param string $fairMeasureMgMin
     * @return QualityConditions
     */
    public function setFairMeasureMgMin($fairMeasureMgMin)
    {
        $this->fairMeasureMgMin = $fairMeasureMgMin;

        return $this;
    }

    /**
     * Get fairMeasureMgMin
     *
     * @return string 
     */
    public function getFairMeasureMgMin()
    {
        return $this->fairMeasureMgMin;
    }

    /**
     * Set fairMeasureMgMinInclusive
     *
     * @param boolean $fairMeasureMgMinInclusive
     * @return QualityConditions
     */
    public function setFairMeasureMgMinInclusive($fairMeasureMgMinInclusive)
    {
        $this->fairMeasureMgMinInclusive = $fairMeasureMgMinInclusive;

        return $this;
    }

    /**
     * Get fairMeasureMgMinInclusive
     *
     * @return boolean 
     */
    public function getFairMeasureMgMinInclusive()
    {
        return $this->fairMeasureMgMinInclusive;
    }

    /**
     * Set fairMeasureMgMax
     *
     * @param string $fairMeasureMgMax
     * @return QualityConditions
     */
    public function setFairMeasureMgMax($fairMeasureMgMax)
    {
        $this->fairMeasureMgMax = $fairMeasureMgMax;

        return $this;
    }

    /**
     * Get fairMeasureMgMax
     *
     * @return string 
     */
    public function getFairMeasureMgMax()
    {
        return $this->fairMeasureMgMax;
    }

    /**
     * Set fairMeasureMgMaxInclusive
     *
     * @param boolean $fairMeasureMgMaxInclusive
     * @return QualityConditions
     */
    public function setFairMeasureMgMaxInclusive($fairMeasureMgMaxInclusive)
    {
        $this->fairMeasureMgMaxInclusive = $fairMeasureMgMaxInclusive;

        return $this;
    }

    /**
     * Get fairMeasureMgMaxInclusive
     *
     * @return boolean 
     */
    public function getFairMeasureMgMaxInclusive()
    {
        return $this->fairMeasureMgMaxInclusive;
    }


    public function jsonSerialize()
    {
        return array(
            'sensorId' => $this->sensorId,
            'goodMeasureNMin' => $this->goodMeasureNMin,
            'goodMeasureNMinInclusive' => $this->goodMeasureNMinInclusive,
            'goodMeasureNMax' => $this->goodMeasureNMax,
            'goodMeasureNMaxInclusive' => $this->goodMeasureNMaxInclusive,
            'goodMeasurePaMin' => $this->goodMeasurePaMin,
            'goodMeasurePaMinInclusive' => $this->goodMeasurePaMinInclusive,
            'goodMeasurePaMax' => $this->goodMeasurePaMax,
            'goodMeasurePaMaxInclusive' => $this->goodMeasurePaMaxInclusive,
            'goodMeasureMgMin' => $this->goodMeasureMgMin,
            'goodMeasureMgMinInclusive' => $this->goodMeasureMgMinInclusive,
            'goodMeasureMgMax' => $this->goodMeasureMgMax,
            'goodMeasureMgMaxInclusive' => $this->goodMeasureMgMaxInclusive,
            'fairMeasureNMin' => $this->fairMeasureNMin,
            'fairMeasureNMinInclusive' => $this->fairMeasureNMinInclusive,
            'fairMeasureNMax' => $this->fairMeasureNMax,
            'fairMeasureNMaxInclusive' => $this->fairMeasureNMaxInclusive,
            'fairMeasurePaMin' => $this->fairMeasurePaMin,
            'fairMeasurePaMinInclusive' => $this->fairMeasurePaMinInclusive,
            'fairMeasurePaMax' => $this->fairMeasurePaMax,
            'fairMeasurePaMaxInclusive' => $this->fairMeasurePaMaxInclusive,
            'fairMeasureMgMin' => $this->fairMeasureMgMin,
            'fairMeasureMgMinInclusive' => $this->fairMeasureMgMinInclusive,
            'fairMeasureMgMax' => $this->fairMeasureMgMax,
            'fairMeasureMgMaxInclusive' => $this->fairMeasureMgMaxInclusive,
        );
    }
}
