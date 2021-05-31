<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 3/18/16
 * Time: 7:12 PM
 */

namespace DataConsolidation\CustomNodesBundle\Form\Type;

use DataConsolidation\DatabaseConfigurationBundle\Utils\DatabaseConfigurator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NodeConfigFormType extends AbstractType
{
    protected $databaseConfigurator;

    /**
     * NodeConfigFormType constructor needed for dependency injection.
     *
     * @param DatabaseConfigurator $databaseConfigurator
     */
    public function __construct(DatabaseConfigurator $databaseConfigurator)
    {
        // Database configurator needed for fetching the available entity managers.
        $this->databaseConfigurator = $databaseConfigurator;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entityManagerNames = $options['target_entity_managers'];
        // The keys and values should be the same.
        $entityManagerChoices = array_combine($entityManagerNames, $entityManagerNames);

        // For each field apply options passed to the $options array with a higher priority than the ones defined here.
        $builder
            ->add('name', TextType::class, array_merge_recursive(array(
                'attr' => array(
                    'placeholder' => 'Enter node name',
                )
            ), $options['name_options']))
            ->add('tableName', TextType::class, array_merge_recursive(array(
                'required' => false,
                'label' => 'Database table name',
                'attr' => array(
                    'placeholder' => 'Enter a table name only in case it needs to differ from the node name',
                )
            ), $options['tableName_options']))
            ->add('targetEntityManagers', CollectionType::class, array_merge_recursive(array(
                'entry_type' => ChoiceType::class,
                'entry_options'  => array(
                    'choices' => $entityManagerChoices,
                    'choices_as_values' => true,
                ),
                'allow_add' => true,
                'allow_delete' => true,
                'label' => false,
                // "if you're using the CollectionType field where your underlying collection data is an object (like with Doctrine's ArrayCollection), then by_reference must be set to false if you need the adder and remover (e.g. addAuthor() and removeAuthor()) to be called."
                'by_reference' => true,
            ), $options['targetEntityManagers_options']))
            ->add('fields', CollectionType::class, array_merge_recursive(array(
                'entry_type' => NodeConfigFieldFormType::class,
                'entry_options' => $options['node_config_fields'],
                'allow_add' => true,
                'allow_delete' => true,
                'label' => false,
                // "if you're using the CollectionType field where your underlying collection data is an object (like with Doctrine's ArrayCollection), then by_reference must be set to false if you need the adder and remover (e.g. addAuthor() and removeAuthor()) to be called."
                'by_reference' => false,
            ), $options['fields_options']));
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            // The class that holds the underlying data. This is necessary in case this form will be embedded in the future.
            'data_class' => 'DataConsolidation\CustomNodesBundle\Entity\DefaultEntityManager\Base\NodeConfig',
            // Custom options.
            'node_config_fields' => array(),
            'target_entity_managers' => $this->getTargetEntityManagerNames(),
            'name_options' => array(),
            'tableName_options' => array(),
            'targetEntityManagers_options' => array(),
            'fields_options' => array(),
        ));
    }

    /**
     * Fetches the available target entity manager names.
     *
     * @return string[] The available entity manager names.
     */
    protected function getTargetEntityManagerNames()
    {
        // Get all custom entity managers that exist in the database configuration.
        $entityManagerNames = $this->databaseConfigurator->getEntityManagerNames();
        // Also prepend the default entity manager to that list.
        array_unshift($entityManagerNames, 'default');

        return $entityManagerNames;
    }

}