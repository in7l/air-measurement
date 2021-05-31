<?php

namespace DataConsolidation\CustomNodesBundle\Form\Type;

use DataConsolidation\CustomNodesBundle\Form\EventListener\FieldMappingSubscriber;
use DataConsolidation\CustomNodesBundle\Form\EventListener\JsonFieldMappingSubscriber;
use DataConsolidation\CustomNodesBundle\Utils\DoctrineEntityHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JsonFieldMappingFormType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Add an event subscriber that will add the relevant form fields.
        // This is necessary because the form fields' properties differ based on the underlying data.
        // For example, some field mappings will allow only certain selectable options.
        $builder->addEventSubscriber(new JsonFieldMappingSubscriber($options));
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            // The class that holds the underlying data. This is necessary in case this form will be embedded in the future.
            'data_class' => 'DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\JsonFieldMapping',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'dataconsolidation_customnodesbundle_jsonfieldmapping';
    }
}
