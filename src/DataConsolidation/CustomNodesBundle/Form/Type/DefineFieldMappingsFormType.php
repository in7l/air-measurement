<?php

namespace DataConsolidation\CustomNodesBundle\Form\Type;

use DataConsolidation\CustomNodesBundle\Utils\DoctrineEntityHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DefineFieldMappingsFormType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('fieldMappings', CollectionType::class, array(
                'entry_type' => FieldMappingFormType::class,
                'options' => array(
                    // Hide the index-numbering for the embedded form.
                    'label' => false,
                ),
            ));;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            // The class that holds the underlying data. This is necessary in case this form will be embedded in the future.
            'data_class' => 'DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\DataSourceToDiagramMapping',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'dataconsolidation_customnodesbundle_datasourcetodiagrammapping';
    }
}
