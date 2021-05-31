<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 10/29/16
 * Time: 2:05 AM
 */

namespace DataConsolidation\CustomNodesBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A form for selecting a data source entity manager.
 */
class EnterJsonDataSourceFormType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('sourceUrl', TextType::class, array(
                'attr' => array(
                    'placeholder' => 'Enter the source URL with a http or https prefix.',
                ),
            ))
            ->add('sourceDisplayName', TextType::class, array(
                'attr' => array(
                    'placeholder' => 'Enter an alias for this data source (to be displayed in diagrams).',
                ),
            ));
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            // The class that holds the underlying data. This is necessary in case this form will be embedded in the future.
            'data_class' => 'DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\JsonToDiagramMapping',
        ));
    }
}
