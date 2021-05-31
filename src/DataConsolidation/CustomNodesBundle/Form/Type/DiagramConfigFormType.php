<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 10/14/16
 * Time: 5:56 PM
 */

namespace DataConsolidation\CustomNodesBundle\Form\Type;
use DataConsolidation\DatabaseConfigurationBundle\Utils\DatabaseConfigurator;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This class exists so modify the DiagramConfig form in the ways that it should differ from data source node config form.
 */
class DiagramConfigFormType extends NodeConfigFormType
{
    /**
     * @inheritdoc
     */
    public function __construct(DatabaseConfigurator $databaseConfigurator)
    {
        parent::__construct($databaseConfigurator);
    }

    /**
     * @inheritdoc
     */
    protected function getTargetEntityManagerNames()
    {
        // Diagram entities are only meant to work with the default entity manager.
        $entityManagerNames = array(
            'default',
        );

        return $entityManagerNames;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            // The class that holds the underlying data. This is necessary in case this form will be embedded in the future.
            'data_class' => 'DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\DiagramConfig',
            // Custom options.
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
            'target_entity_managers' => $this->getTargetEntityManagerNames(),
            'name_options' => array(),
            'tableName_options' => array(),
            'targetEntityManagers_options' => array(),
            'fields_options' => array(),
        ));
    }
}