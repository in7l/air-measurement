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

class SelectTargetDiagramFormType extends AbstractType
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
        $diagrams = $options['diagram_choices'];

        $builder
            ->add('diagram', ChoiceType::class, array(
                'choices' => $diagrams,
                'choices_as_values' => true,
            ));
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'diagram_choices' => $this->getDiagrams(),
        ));
    }

    /**
     * @return array of available Diagrams to be used in the ChoiceType options.
     */
    protected function getDiagrams()
    {
        $fullyQualifiedClassNames = $this->doctrineEntityHelper->getAllDiagramEntityNames();

        // Display only the simple name of the diagrams, whereas the actual choice values would be the fully classified class names.
        $choices = array();
        foreach ($fullyQualifiedClassNames as $fullyQualifiedClassName) {
            // Get the short class name.
            $classNameComponents = explode('\\', $fullyQualifiedClassName);
            $shortClassName = end($classNameComponents);
            $choices[$shortClassName] = $fullyQualifiedClassName;
        }

        return $choices;
    }
}