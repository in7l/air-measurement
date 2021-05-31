<?php

namespace DataConsolidation\CustomNodesBundle\Form\Type;

use DataConsolidation\CustomNodesBundle\Form\EventListener\FieldMappingSubscriber;
use DataConsolidation\CustomNodesBundle\Utils\DoctrineEntityHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FieldMappingFormType extends AbstractType
{
    protected $doctrineEntityHelper;

    /**
     * NodeConfigFormType constructor needed for dependency injection.
     *
     * @param DoctrineEntityHelper $doctrineEntityHelper
     */
    public function __construct(DoctrineEntityHelper $doctrineEntityHelper)
    {
        $this->doctrineEntityHelper = $doctrineEntityHelper;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Add an event subscriber that will add the relevant form fields.
        // This is necessary because the form fields' properties differ based on the underlying data.
        // For example, some field mappings will allow only certain selectable options.
        $builder->addEventSubscriber(new FieldMappingSubscriber($options, $this->doctrineEntityHelper));
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            // The class that holds the underlying data. This is necessary in case this form will be embedded in the future.
            'data_class' => 'DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\FieldMapping',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'dataconsolidation_customnodesbundle_fieldmapping';
    }
}
