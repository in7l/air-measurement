<?php

namespace AirMeasurement\DiagramBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DiagramController extends Controller
{
    public function diagramAction()
    {
        return $this->render('AirMeasurementDiagramBundle:Default:diagram.html.twig');
    }


    public function statusAction() {
    	return $this->render('AirMeasurementDiagramBundle:Default:status.html.twig');
    }


    public function measurementAction() {
    	return new Response('measurement');
    }
}
