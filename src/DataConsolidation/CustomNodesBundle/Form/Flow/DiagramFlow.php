<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 4/10/16
 * Time: 7:28 PM
 */

namespace DataConsolidation\CustomNodesBundle\Form\Flow;


use Craue\FormFlowBundle\Form\FormFlow;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class DiagramFlow extends FormFlow
{
    // Use a trait that allows setting a service container.
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected function loadStepsConfig()
    {
        return array(
            array(
                'label' => 'Configure data fields',
                'form_type' => 'DataConsolidation\CustomNodesBundle\Form\Type\NodeConfigFormType',
                'form_options' => array(
                    'target_entity_managers' => array(
                        'default',
                    ),
                    'node_config_fields' => array(
                        // Specify options for the mutable fields (field which the user can add dynamically).
                        'mutable_field_options' => array(
                            // Restrict the available field types.
                            'database_field_types' => array(
                                'integer',
                                'decimal',
                            ),
                            // Do not allow the mutable fields to be primary keys.
                            'node_config_field_options' => array(
                                'primaryKey_options' => array(
                                    'disabled' => true,
                                )
                            )
                        ),
                    ),
                ),
            ),
            array(
                'label' => 'Add data source entities',
                'form_type' => 'DataConsolidation\CustomNodesBundle\Form\Type\DiagramFormType',
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormOptions($step, array $options = array()) {
        $options = parent::getFormOptions($step, $options);

        $formData = $this->getFormData();

        switch ($step) {
            case 2:
                // Fetch all available custom generated doctrine entities.
                $doctrineEntities = array();
                if ($this->container) {
                    $em = $this->container->get('doctrine')->getManager('data_source.db_1');
                    $doctrineEntities = $em->getConfiguration()
                        ->getMetadataDriverImpl()
                        ->getAllClassNames();
                }
                // Update the options for the form flow.
                // The keys and values in the array should be identical to each other.
                $options['doctrineEntities'] = array_combine($doctrineEntities, $doctrineEntities);
                break;
        }

        return $options;
    }

}