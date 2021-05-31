<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 5/9/17
 * Time: 8:35 PM
 */

namespace DataConsolidation\CustomNodesBundle\Utils;


use DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\DiagramConfig;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class RandomDataGenerator
{
    // Use a trait that allows setting a service container.
    use ContainerAwareTrait;

    public function generateSineData($fullyQualifiedClassName, $start, $limit, $intervalInSeconds, $measurementTimeSetter, $measurementTimeGetter, $measurementTimeField, $valueSetter, $valuesDeviation, $intervalDeviation, $min, $max) {
        $doctrineEntityHelper = $this->container->get('data_consolidation.custom_nodes.doctrine_entity_helper');
        $em = $doctrineEntityHelper->getEntityManager($fullyQualifiedClassName);

        if ($start === NULL) {
            // Attempt to get the last available datetime.
            // Sort by measurement time in descending order so that the latest data is fetched.
            $sortCriteria = array(
                $measurementTimeField => 'DESC',
            );

            // Get the content.
            $repository = $em->getRepository($fullyQualifiedClassName);
            $content = $repository->findBy(array(), $sortCriteria, 1, 0);
            $latestEntry = reset($content);
            if (!empty($latestEntry)) {
                $start = $latestEntry->$measurementTimeGetter();
                $start = clone $start;
                // Make the start time INTERVAL seconds later.
                $intervalSpec = 'PT' . $intervalInSeconds . 'S';
                $dateInterval = new \DateInterval($intervalSpec);
                $start->add($dateInterval);
            }
            else {
                // No existing entries found.
                // Make the start time 2 * intervalInSeconds earlier than the current time.
                $startTimestamp = time() - 2 * $intervalInSeconds;
                $start = new \DateTime(date('c', $startTimestamp));
                $start->setTimezone(new \DateTimeZone('Europe/Helsinki'));
            }
        }

        $generatedEntriesCount = 0;
        for ($i = 0; $i < $limit; $i++) {
            $measurementTime = clone $start;
            // Add the necessary amount of seconds to the measurement time.
            $seconds = $i * $intervalInSeconds + intval(round((mt_rand() / mt_getrandmax()) * $intervalDeviation));
            $intervalSpec = 'PT' . $seconds . 'S';
            $dateInterval = new \DateInterval($intervalSpec);
            $measurementTime->add($dateInterval);

            if (time() < $measurementTime->getTimestamp()) {
                // Do not generate values in the future.
                break;
            }

            $object = new $fullyQualifiedClassName();
            $object->{$measurementTimeSetter}($measurementTime);

            $value = $this->getTemperatureForTime($measurementTime, $min, $max) + (mt_rand() / mt_getrandmax()) * $valuesDeviation;
            $object->{$valueSetter}($value);

            $em->persist($object);

            $generatedEntriesCount++;
        }

        $em->flush();

        return $generatedEntriesCount;
    }

    public function generateRandomData($fullyQualifiedClassName, \DateTime $start, $limit, $intervalInSeconds, $intervalDeviation, $min, $max, $measurementTimeSetter, $valueSetter) {
        $doctrineEntityHelper = $this->container->get('data_consolidation.custom_nodes.doctrine_entity_helper');
        $em = $doctrineEntityHelper->getEntityManager($fullyQualifiedClassName);

        for ($i = 0; $i < $limit; $i++) {
            $measurementTime = clone $start;
            // Add the necessary amount of seconds to the measurement time.
            $seconds = $i * $intervalInSeconds + intval(round((mt_rand() / mt_getrandmax()) * $intervalDeviation));
            $intervalSpec = 'PT' . $seconds . 'S';
            $dateInterval = new \DateInterval($intervalSpec);
            $measurementTime->add($dateInterval);

            if (time() < $measurementTime->getTimestamp()) {
                // Do not generate values in the future.
                break;
            }

            $object = new $fullyQualifiedClassName();
            $object->{$measurementTimeSetter}($measurementTime);

            $value = (mt_rand() / mt_getrandmax()) * ($max - $min) + $min;
            $object->{$valueSetter}($value);

            $em->persist($object);
        }

        $em->flush();
    }

    public function getTemperatureForTime(\DateTime $time, $minT, $maxT) {
        $timestamp = $time->getTimestamp();
        $hours = intval(date('G', $timestamp));
        $minutes = intval(date('i', $timestamp), 10);
        $seconds = intval(date('s', $timestamp), 10);
        $sineStartHour = 6;

        $secondsSinceStartHour = ((($hours + 24) - $sineStartHour) % 24) * 3600 + $minutes * 60 + $seconds;
        $radians = (M_PI / (12 * 60 * 60)) * $secondsSinceStartHour;
        $temperature = ((sin($radians) + 1) / 2) * ($maxT - $minT) + $minT;

        return $temperature;
    }
}