<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 3/13/16
 * Time: 5:38 PM
 */

namespace DataConsolidation\DatabaseConfigurationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChangeConnectionPasswordFormType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('password', RepeatedType::class, array(
                'type' => PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'required' => false,
                'first_options' => array(
                    'label' => 'Password',
                    'attr' => array(
                        'placeholder' => 'Enter database password. Leave empty if there is no password',
                    )
                ),
                'second_options' => array(
                    'label' => 'Repeat password',
                    'attr' => array(
                        'placeholder' => 'Repeat the database password',
                    )
                )
            ));
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            // The class that holds the underlying data. This is necessary in case this form will be embedded in the future.
           'data_class' => 'DataConsolidation\DatabaseConfigurationBundle\Entity\DatabaseConnectionConfiguration',
        ));
    }

}
