<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 4/10/16
 * Time: 4:15 PM
 */

namespace DataConsolidation\CustomNodesBundle\Form\EventListener;

use DataConsolidation\CustomNodesBundle\Utils\DoctrineEntityHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class FieldMappingSubscriber implements EventSubscriberInterface
{
    protected $doctrineEntityHelper;

    /**
     * @var array The form options.
     */
    private $options;

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        // Tells the dispatcher that you want to listen on the form.pre_set_data
        // event and that the preSetData method should be called.
        return array(FormEvents::PRE_SET_DATA => 'preSetData');
    }

    /**
     * FieldMappingSubscriber constructor.
     *
     * @param array $options The form options.
     * @param DoctrineEntityHelper $doctrineEntityHelper
     */
    public function __construct(array $options, DoctrineEntityHelper $doctrineEntityHelper)
    {
        $this->options = $options;
        $this->doctrineEntityHelper = $doctrineEntityHelper;
    }

    public function preSetData(FormEvent $event)
    {
        // Get the underlying form data.
        $fieldMapping = $event->getData();
        $targetGetters = array(
            $fieldMapping->getTargetGetter(),
        );
        $targetSetters = array(
            $fieldMapping->getTargetSetter(),
        );

        // Fetch a list of data source getters.
        // List of properties which should not be included in the results.
        // These are normally "system fields" that the user should not interfere with.
        $propertiesToBeIgnored = array(
            'id',
        );
        // Fetch the available getters for the data source.
        $dataSourceToDiagramMapping = $fieldMapping->getDataSourceToDiagramMapping();
        $dataSourceClassName = $dataSourceToDiagramMapping->getDataSource();
        $dataSourceProperties = $this->doctrineEntityHelper->getReflectionPropertyNames($dataSourceClassName, true, $propertiesToBeIgnored);
        $sourceGetters = $this->doctrineEntityHelper->getReflectionGetters($dataSourceClassName, $dataSourceProperties, true);

        // The keys and values should be the same.
        $targetGetters = array_combine($targetGetters, $targetGetters);
        $targetSetters = array_combine($targetSetters, $targetSetters);
        $sourceGetters = array_combine($sourceGetters, $sourceGetters);

        $form = $event->getForm();

        $form
            ->add('targetGetter', ChoiceType::class, array(
                'choices' => $targetGetters,
                'choices_as_values' => true,
            ))
            ->add('targetSetter', ChoiceType::class, array(
                'choices' => $targetSetters,
                'choices_as_values' => true,
            ))
            ->add('sourceGetter', ChoiceType::class, array(
                'choices' => $sourceGetters,
                'choices_as_values' => true,
                'required' => false,
                'placeholder' => '- None -',
            ));
    }
}
