<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 4/10/16
 * Time: 4:15 PM
 */

namespace DataConsolidation\CustomNodesBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class JsonFieldMappingSubscriber implements EventSubscriberInterface
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
     * FieldMappingSubscriber constructor.
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
        $fieldMapping = $event->getData();
        $targetSetters = array(
            $fieldMapping->getTargetSetter(),
        );

        // The keys and values should be the same.
        $targetSetters = array_combine($targetSetters, $targetSetters);

        $form = $event->getForm();

        $form
            ->add('sourceGetter', TextType::class, array(
                'attr' => array(
                    'placeholder' => 'Enter the way to access the getter or leave this empty if no mapping is necessary for this field.',
                ),
            ))
            ->add('targetSetter', ChoiceType::class, array(
                'choices' => $targetSetters,
                'choices_as_values' => true,
            ));
    }
}
