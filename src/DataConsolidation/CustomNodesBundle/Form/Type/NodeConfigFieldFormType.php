<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 3/18/16
 * Time: 6:53 PM
 */

namespace DataConsolidation\CustomNodesBundle\Form\Type;

use DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\NodeConfigField;
use DataConsolidation\CustomNodesBundle\Form\EventListener\NodeConfigFieldSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NodeConfigFieldFormType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Add an event subscriber that will add the relevant form fields.
        // This is necessary because the form fields' properties differ based on the underlying data.
        // For example, some fields are mutable, and others are immutable (meaning most of the form fields should be disabled).
        $builder->addEventSubscriber(new NodeConfigFieldSubscriber($options));
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            // The class that holds the underlying data. This is necessary in case this form will be embedded in the future.
            'data_class' => 'DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\NodeConfigField',
            // Custom options.
            'database_field_types' => $this->getDatabaseFieldTypes(),
            'name_options' => array(),
            'type_options' => array(),
            'node_config_field_options' => array(),
        ));

        $resolver->setDefined(array(
            // Options that are applicable only to mutable fields.
            // These can overwrite all of the custom options with defaults defined above.
            'mutable_field_options',
        ));
    }

    /**
     * Fetches the available database field types.
     *
     * @return string[] The available database field types.
     */
    protected function getDatabaseFieldTypes()
    {
        // Select all available database field types.
        $databaseTypes = NodeConfigField::getValidFieldTypes();
        return $databaseTypes;
    }

}