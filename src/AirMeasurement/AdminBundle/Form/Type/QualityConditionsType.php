<?php

namespace AirMeasurement\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;

class QualityConditionsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('sensorId', 'integer')
            ->add('goodMeasureNMin', 'number', array(
                'required' => false,
                'precision' => 6,
            ))
            ->add('goodMeasureNMinInclusive', 'choice', array(
                    'choice_list' => new ChoiceList(
                        array(true, false),
                        array('>=', '>')
                    )
                )
            )
            ->add('goodMeasureNMax', 'number', array(
                'required' => false,
                'precision' => 6,
            ))
            ->add('goodMeasureNMaxInclusive', 'choice', array(
                    'choice_list' => new ChoiceList(
                        array(true, false),
                        array('<=', '<')
                    )
                )
            )

            ->add('goodMeasurePaMin', 'number', array(
                'required' => false,
                'precision' => 6,
            ))
            ->add('goodMeasurePaMinInclusive', 'choice', array(
                    'choice_list' => new ChoiceList(
                        array(true, false),
                        array('>=', '>')
                    )
                )
            )
            ->add('goodMeasurePaMax', 'number', array(
                'required' => false,
                'precision' => 6,
            ))
            ->add('goodMeasurePaMaxInclusive', 'choice', array(
                    'choice_list' => new ChoiceList(
                        array(true, false),
                        array('<=', '<')
                    )
                )
            )

            ->add('goodMeasureMgMin', 'number', array(
                'required' => false,
                'precision' => 6,
            ))
            ->add('goodMeasureMgMinInclusive', 'choice', array(
                    'choice_list' => new ChoiceList(
                        array(true, false),
                        array('>=', '>')
                    )
                )
            )
            ->add('goodMeasureMgMax', 'number', array(
                'required' => false,
                'precision' => 6,
            ))
            ->add('goodMeasureMgMaxInclusive', 'choice', array(
                    'choice_list' => new ChoiceList(
                        array(true, false),
                        array('<=', '<')
                    )
                )
            )

            ->add('fairMeasureNMin', 'number', array(
                'required' => false,
                'precision' => 6,
            ))
            ->add('fairMeasureNMinInclusive', 'choice', array(
                    'choice_list' => new ChoiceList(
                        array(true, false),
                        array('>=', '>')
                    )
                )
            )
            ->add('fairMeasureNMax', 'number', array(
                'required' => false,
                'precision' => 6,
            ))
            ->add('fairMeasureNMaxInclusive', 'choice', array(
                    'choice_list' => new ChoiceList(
                        array(true, false),
                        array('<=', '<')
                    )
                )
            )

            ->add('fairMeasurePaMin', 'number', array(
                'required' => false,
                'precision' => 6,
            ))
            ->add('fairMeasurePaMinInclusive', 'choice', array(
                    'choice_list' => new ChoiceList(
                        array(true, false),
                        array('>=', '>')
                    )
                )
            )
            ->add('fairMeasurePaMax', 'number', array(
                'required' => false,
                'precision' => 6,
            ))
            ->add('fairMeasurePaMaxInclusive', 'choice', array(
                    'choice_list' => new ChoiceList(
                        array(true, false),
                        array('<=', '<')
                    )
                )
            )

            ->add('fairMeasureMgMin', 'number', array(
                'required' => false,
                'precision' => 6,
            ))
            ->add('fairMeasureMgMinInclusive', 'choice', array(
                    'choice_list' => new ChoiceList(
                        array(true, false),
                        array('>=', '>')
                    )
                )
            )
            ->add('fairMeasureMgMax', 'number', array(
                'required' => false,
                'precision' => 6,
            ))
            ->add('fairMeasureMgMaxInclusive', 'choice', array(
                    'choice_list' => new ChoiceList(
                        array(true, false),
                        array('<=', '<')
                    )
                )
            )

            ->add('save', 'submit', array('label' => 'Save conditions'));
    }

    public function getName()
    {
        return 'quality_conditions';
    }
}