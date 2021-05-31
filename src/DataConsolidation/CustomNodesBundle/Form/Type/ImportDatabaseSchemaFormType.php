<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 4/3/16
 * Time: 12:37 AM
 */

namespace DataConsolidation\CustomNodesBundle\Form\Type;


use DataConsolidation\DatabaseConfigurationBundle\Utils\DatabaseConfigurator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImportDatabaseSchemaFormType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        switch ($options['flow_step']) {
            case 1:
                $builder->add('entityManagerName', ChoiceType::class, array(
                    // Use a custom form option for the choices added in the form flow class.
                    'choices' => $options['entityManagerNameChoices'],
                    'choices_as_values' => true,
                ));
                break;
            case 2:
                $builder->add('tableName', ChoiceType::class, array(
                    // Use a custom form option for the choices added in the form flow class.
                    'choices' => $options['tableNameChoices'],
                    'choices_as_values' => true,
                ));
                break;
            case 3:
                $builder->add('nodeConfig', NodeConfigFormType::class, array(
                    'label' => false,
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
        return 'importDatabaseSchema';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        // Register additional valid option names.
        // The values for the options will be passed by the FormFlow.
        $resolver->setDefined(array(
           'entityManagerNameChoices',
           'tableNameChoices',
        ));
    }


}