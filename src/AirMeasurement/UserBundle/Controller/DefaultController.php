<?php

namespace AirMeasurement\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('AirMeasurementUserBundle:Default:index.html.twig', array('name' => $name));
    }
}
