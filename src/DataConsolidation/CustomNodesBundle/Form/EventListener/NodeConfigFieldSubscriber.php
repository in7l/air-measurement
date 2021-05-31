<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 4/10/16
 * Time: 4:15 PM
 */

namespace DataConsolidation\CustomNodesBundle\Form\EventListener;


use DataConsolidation\CustomNodesBundle\Form\Type\NodeConfigOptionsFormType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class NodeConfigFieldSubscriber implements EventSubscriberInterface
{
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
     * NodeConfigFieldSubscriber constructor.
     *
     * @param array $options The form options.
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function preSetData(FormEvent $event)
    {
        // Get the underlying form data.
        $nodeConfigField = $event->getData();
        // Determine if the field is mutable.
        $mutable = TRUE;
        if ($nodeConfigField && !$nodeConfigField->isMutable()) {
            $mutable = FALSE;
        }

        // Obtain a copy of the form options.
        $options = $this->options;

        if (!$mutable) {
            // An immutable field should be rendered.
            // Check if specific form options were passed for immutable fields.
            $immutableFieldFormOptions = $this->getImmutableFieldFormOptions();

            // Merge the immutable options to the main options array.
            // The immutable field options will be used with a higher priority.
            $options = array_merge($options, $immutableFieldFormOptions);
        }
        else {
            // A mutable field should be rendered.
            // Merge possible options that were specified only for mutable fields.
            if (isset($options['mutable_field_options'])) {
                $mutableFieldFormOptions = $options['mutable_field_options'];
                // Remove the mutable field options.
                unset($options['mutable_field_options']);

                // Merge the mutable options to the main options array.
                // The mutable field options will be used with a higher priority.
                $options = array_merge($options, $mutableFieldFormOptions);
            }
        }

        $databaseFieldTypes = $options['database_field_types'];
        // The field types should be used both as keys and as values in the form choices.
        $databaseFieldTypeChoices = array_combine($databaseFieldTypes, $databaseFieldTypes);

        $form = $event->getForm();

        // For each field apply options passed to the $options array with a higher priority than the ones defined here.
        $form
            // Add a hidden mutable field which indicates to the front-end JS if the node config field is mutable.
            // Having this field is also crucial if all other fields are disaled (or if all other fields are empty).
            // Without this field, an immutable node config field could get automatically removed.
            // The field is not mapped to the underlying class so that users cannot modify this value by editing the form.
            ->add('mutable', HiddenType::class, array(
                'data' => $mutable,
                'mapped' => false,
                'attr' => array(
                    'class' => 'mutable',
                )
            ))
            ->add('name', TextType::class, array_merge_recursive(array(
                'attr' => array(
                    'placeholder' => 'Enter field name',
                ),
            ), $options['name_options']))
            ->add('type', ChoiceType::class, array_merge_recursive(array(
                'choices' => $databaseFieldTypeChoices,
                'choices_as_values' => true,
            ), $options['type_options']))
            ->add('options', NodeConfigOptionsFormType::class, array_merge_recursive(array(
                'label' => false,
            ), $options['node_config_field_options']));
    }

    /**
     * Fetches an array of options that can be used in a NodeConfigFieldFormType form
     * for a NodeConfigField that is immutable.
     *
     * @return array The form options for a form of an immutable field.
     */
    public function getImmutableFieldFormOptions()
    {
        $immutableFieldFormOptions = array(
            'name_options' => array(
                'disabled' => true,
            ),
            'type_options' => array(
                'disabled' => true,
            ),
            'node_config_field_options' => array(
                // Not disabling the whole field options because the visibleInContentList checkbox should be selectable.
                'primaryKey_options' => array(
                    'disabled' => true,
                ),
                'strategy_options' => array(
                    'disabled' => true,
                ),
                'nullable_options' => array(
                    'disabled' => true,
                ),
                'unique_options' => array(
                    'disabled' => true,
                ),
                'columnName_options' => array(
                    'disabled' => true,
                ),
                'precision_options' => array(
                    'disabled' => true,
                ),
                'scale_options' => array(
                    'disabled' => true,
                ),
                'length_options' => array(
                    'disabled' => true,
                ),
            ),
        );

        return $immutableFieldFormOptions;
    }
}
