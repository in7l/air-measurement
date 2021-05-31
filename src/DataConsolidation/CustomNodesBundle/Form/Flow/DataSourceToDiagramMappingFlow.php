<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 10/29/16
 * Time: 1:42 AM
 */

namespace DataConsolidation\CustomNodesBundle\Form\Flow;


use Craue\FormFlowBundle\Form\FormFlow;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class DataSourceToDiagramMappingFlow extends FormFlow
{
    // Use a trait that allows setting a service container.
    use ContainerAwareTrait;

    /**
     * @inheritdoc
     */
    protected function loadStepsConfig() {
        return array(
            1 => array(
                'label' => 'Select target diagram',
                'form_type' => 'DataConsolidation\CustomNodesBundle\Form\Type\SelectTargetDiagramFormType',
            ),
            2 => array(
                'label' => 'Select data source entity',
                'form_type' => 'DataConsolidation\CustomNodesBundle\Form\Type\SelectDataSourceEntityFormType',
            ),
            3 => array(
                'label' => 'Define field mappings',
                'form_type' => 'DataConsolidation\CustomNodesBundle\Form\Type\DefineFieldMappingsFormType',
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormOptions($step, array $options = array()) {
        $options = parent::getFormOptions($step, $options);

        $dataSourceToDiagramMapping = $this->getFormData();

        switch ($step) {
            case 3:
                // This step is for defining the field mappings.
                // Add empty field mappings so the form lets the user select additional mappings that may be of relevance.
                $doctrineEntityHelper = $this->container->get('data_consolidation.custom_nodes.doctrine_entity_helper');
                $doctrineEntityHelper->addEmptyFieldMappingsToDataSourceToDiagramMapping($dataSourceToDiagramMapping);
        }

        return $options;
    }
}