<?php

namespace AirMeasurement\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AirMeasurement\AdminBundle\Entity\QualityConditions;
use AirMeasurement\AdminBundle\Form\Type\QualityConditionsType;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('AirMeasurementAdminBundle:Default:index.html.twig');
    }

    public function conditionsIndexAction()
    {
        $repository = $this->getDoctrine()
            ->getRepository('AirMeasurementAdminBundle:QualityConditions');
        // find *all* conditions
        $conditions = $repository->findAll();

        $condition_data = array();
        foreach ($conditions as $condition) {
            $condition_data[] = array(
                'id' => $condition->getId(),
                'sensor_id' => $condition->getSensorId()
            );
        }
        return $this->render('AirMeasurementAdminBundle:Default:conditions.html.twig', array(
            'conditions' => $condition_data,
        ));
    }

    public function addConditionAction(Request $request)
    {
        // Create a Conditions object.
        $conditions = new QualityConditions();

        $form = $this->createForm(new QualityConditionsType(), $conditions);

        $form->handleRequest($request);

        if ($form->isValid()) {
            // Save the conditions to the database
            $em = $this->getDoctrine()->getManager();
            $em->persist($conditions);
            $em->flush();

            return $this->redirectToRoute('air_measurement_admin_conditions_list');
        }

        return $this->render('AirMeasurementAdminBundle:Default:edit_conditions.html.twig', array(
            'form' => $form->createView(),
        ));
    }


    public function editConditionAction($condition_id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em
            ->getRepository('AirMeasurementAdminBundle:QualityConditions');
        // find *all* conditions
        $conditions = $repository->find($condition_id);

        if (!$conditions) {
            throw $this->createNotFoundException(
                'No conditions found for id '.$condition_id
            );
        }

        $form = $this->createForm(new QualityConditionsType(), $conditions);

        $form->handleRequest($request);

        if ($form->isValid()) {
            // Save the conditions to the database
            $em->persist($conditions);
            $em->flush();

            return $this->redirectToRoute('air_measurement_admin_conditions_list');
        }

        return $this->render('AirMeasurementAdminBundle:Default:edit_conditions.html.twig', array(
            'form' => $form->createView(),
        ));
    }


    public function viewConditionAction($sensor_id)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em
            ->getRepository('AirMeasurementAdminBundle:QualityConditions');
        // find *all* conditions
        $conditions = $repository->findBySensorId($sensor_id);

        if (!$conditions) {
            throw $this->createNotFoundException(
                'No conditions found for sensor id '.$sensor_id
            );
        }

        $response = new Response();
        $response->setContent(json_encode($conditions));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
