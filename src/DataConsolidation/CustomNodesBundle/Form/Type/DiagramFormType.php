<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 4/10/16
 * Time: 8:05 PM
 */

namespace DataConsolidation\CustomNodesBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DiagramFormType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        switch ($options['flow_step']) {
            case 2:
                $builder->add('dataSourceEntities', ChoiceType::class, array(
                    'label' => false,
                    'mapped' => false,
                    'choices' => $options['doctrineEntities'],
                    'choices_as_values' => true,
                ));
                break;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        // Not sure if this is needed but it is included in the form flow example documentation.
        return 'diagram';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        // Register additional valid option names.
        // The values for the options will be passed by the FormFlow.
        $resolver->setDefined(array(
            'doctrineEntities',
        ));
    }

}