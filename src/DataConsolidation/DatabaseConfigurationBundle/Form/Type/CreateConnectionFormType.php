<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 3/13/16
 * Time: 5:38 PM
 */

namespace DataConsolidation\DatabaseConfigurationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateConnectionFormType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('driver', ChoiceType::class, array(
                'choices' => array(
                    'pdo_mysql' => 'pdo_mysql',
                    'pdo_pgsql' => 'pdo_pgsql',
                ),
                'choices_as_values' => true,
            ))
            ->add('host', TextType::class, array(
                'attr' => array(
                    'placeholder' => 'Enter database host',
                ),
            ))
            ->add('port', IntegerType::class, array(
                'required' => false,
                'attr' => array(
                    'placeholder' => 'Enter database port. Leave empty to use the default port',
                ),
            ))
            ->add('dbName', TextType::class, array(
                'label' => 'Database name',
                'attr' => array(
                    'placeholder' => 'Enter database name',
                ),
            ))
            ->add('user', TextType::class, array(
                'attr' => array(
                    'placeholder' => 'Enter database user',
                )
            ))
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
