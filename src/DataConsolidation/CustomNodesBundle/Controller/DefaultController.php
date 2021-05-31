<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 10/14/16
 * Time: 1:50 PM
 */

namespace DataConsolidation\CustomNodesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Controller for data source NodeConfig, and for DiagramConfig.
 */
class DefaultController extends Controller
{

    /**
     * Administrative view leading to configuration menus to data source nodes and diagram nodes.
     */
    public function indexAction()
    {
        return $this->render('DataConsolidationCustomNodesBundle:default:index.html.twig');
    }
}