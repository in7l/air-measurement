<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 3/21/16
 * Time: 1:03 PM
 */

namespace DataConsolidation\CustomNodesBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NodeConfigOptionsFormType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // For each field apply options passed to the $options array with a higher priority than the ones defined here.
        $builder
            ->add('primaryKey', CheckboxType::class, array_merge_recursive(array(
                'required' => false,
            ), $options['primaryKey_options']))
            ->add('strategy', ChoiceType::class, array_merge_recursive(array(
                'label' => 'Generated value strategy',
                'choices' => array(
                    'NONE' => null,
                    'AUTO' => 'AUTO',
                    'SEQUENCE' => 'SEQUENCE',
                    'IDENTITY' => 'IDENTITY',
                    'UUID' => 'UUID',
                ),
                'choices_as_values' => true,
                'attr' => array(
                    'class' => 'input-primaryKey-enabled',
                ),
            ), $options['strategy_options']))
            ->add('nullable', CheckboxType::class, array_merge_recursive(array(
                'required' => false,
                'label' => 'Allow NULL',
            ), $options['nullable_options']))
            ->add('unique', CheckboxType::class, array_merge_recursive(array(
                'required' => false,
            ), $options['unique_options']))
            ->add('visibleInContentList', CheckboxType::class, array_merge_recursive(array(
                'required' => false,
            ), $options['visibleInContentList_options']))
            ->add('columnName', TextType::class, array_merge_recursive(array(
                'required' => false,
                'attr' => array(
                    'placeholder' => 'Enter a column name only in case it needs to differ from the field name',
                ),
            ), $options['columnName_options']))
            ->add('precision', IntegerType::class, array_merge_recursive(array(
                'required' => false,
                'attr' => array(
                    'placeholder' => 'Enter the maximum number of digits that are stored for the decimal values',
                    'class' => 'decimal-enabled', // Should become enabled for decimal values and disabled for other values.
                ),
            ), $options['precision_options']))
            ->add('scale', IntegerType::class, array_merge_recursive(array(
                'required' => false,
                'attr' => array(
                    'placeholder' => 'Enter the number of digits to the right of the decimal point. Must not be greater than precision',
                    'class' => 'decimal-enabled', // Should become enabled for decimal values and disabled for other values.
                ),
            ), $options['scale_options']))
            ->add('length', IntegerType::class, array_merge_recursive(array(
                'required' => false,
                'attr' => array(
                    'placeholder' => 'Enter the maximum length of the string values in the database',
                    'class' => 'string-enabled', // Should become enabled for string values and disabled for other values.
                ),
            ), $options['length_options']));
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            // The class that holds the underlying data. This is necessary in case this form will be embedded in the future.
            'data_class' => 'DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\NodeConfigOptions',
            // Custom options.
            'primaryKey_options' => array(),
            'strategy_options' => array(),
            'nullable_options' => array(),
            'unique_options' => array(),
            'visibleInContentList_options' => array(),
            'columnName_options' => array(),
            'precision_options' => array(),
            'scale_options' => array(),
            'length_options' => array(),
        ));
    }

}