<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 10/29/16
 * Time: 2:05 AM
 */

namespace DataConsolidation\CustomNodesBundle\Form\Type;


use DataConsolidation\CustomNodesBundle\Utils\DoctrineEntityHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A form for selecting a data source entity manager.
 */
class SelectDataSourceEntityFormType extends AbstractType
{
    protected $doctrineEntityHelper;

    /**
     * NodeConfigFormType constructor needed for dependency injection.
     *
     * @param DoctrineEntityHelper $doctrineEntityHelper
     */
    public function __construct(DoctrineEntityHelper $doctrineEntityHelper)
    {
        // Doctrine entity helper needed for fetching the available data source and diagram names.
        $this->doctrineEntityHelper = $doctrineEntityHelper;
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        // Fetch values provided through the form options.
        $dataSourceEntities = $options['data_source_entity_choices'];

        $builder
            ->add('dataSource', ChoiceType::class, array(
                'choices' => $dataSourceEntities,
                'choices_as_values' => true,
            ));
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_source_entity_choices' => $this->getDataSourceEntities(),
        ));
    }

    /**
     * @return array of available data source entities to be used in the ChoiceType options.
     */
    protected function getDataSourceEntities()
    {
        $fullyQualifiedClassNames = $this->doctrineEntityHelper->getAllDataSourceEntityNames();

        // Display only the last two components in the namespace as a visible option, while the value should be the fully qualified class name.
        $choices = array();
        foreach ($fullyQualifiedClassNames as $fullyQualifiedClassName) {
            // Get the short class name.
            $classNameComponents = explode('\\', $fullyQualifiedClassName);
            $shortClassName = end($classNameComponents);
            $entityManagerName = prev($classNameComponents);
            $shortName = "{$entityManagerName}\\{$shortClassName}";
            $choices[$shortName] = $fullyQualifiedClassName;
        }

        return $choices;
    }
}