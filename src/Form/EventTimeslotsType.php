<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\TimeSlot;
use Arkounay\Bundle\UxCollectionBundle\Form\UxCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventTimeslotsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('timeSlots', UxCollectionType::class, [
                'label' => 'Créneaux',
                'entry_type' => TimeSlotType::class,
                'allow_delete' => true,
                'allow_add' => true,
                'allow_drag_and_drop' => false,
                'display_sort_buttons' => false,
                'add_label' => 'Ajouter un créneau',
                'entry_options' => [
                    'event' => $builder->getData(),
                ],
                'prototype_data' => (new TimeSlot())->setEvent($builder->getData()),
                'min' => 0,
                'by_reference' => false,
                'attr' => ['data-controller' => 'custom-collection'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
