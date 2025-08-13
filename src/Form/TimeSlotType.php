<?php

namespace App\Form;

use App\Entity\TimeSlot;
use App\Entity\Workshop;
use App\Entity\WorkshopTimeSlot;
use Arkounay\Bundle\UxCollectionBundle\Form\UxCollectionType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TimeSlotType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startAt', null, [
                'label' => 'Début *',
                'label_attr' => [
                    'class' => 'sr-only',
                ],
                'attr' => [
                    'placeholder' => 'Début *',
                    'class' => 'form-control-sm',
                ],
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'dd/MM/yyyy HH:mm',
                'view_timezone' => 'Europe/Paris',
                'model_timezone' => 'UTC',
                'required' => true,
            ])
            ->add('endAt', null, [
                'label' => 'Fin *',
                'label_attr' => [
                    'class' => 'sr-only',
                ],
                'attr' => [
                    'placeholder' => 'Fin *',
                    'class' => 'form-control-sm',
                ],
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'dd/MM/yyyy HH:mm',
                'view_timezone' => 'Europe/Paris',
                'model_timezone' => 'UTC',
                'required' => true,
            ])
            ->add('workshops', UxCollectionType::class, [
                'label' => 'Ateliers',
                'entry_type' => WorkshopTimeslotType::class,
                'prototype_name' => 'workshoptimeslot',
                'entry_options' => [
                    'event' => $options['event'],
                ],
                'prototype_data' => (new WorkshopTimeslot())->setNbGuests(0),
                'add_label' => 'Associer un atelier',
                'row_attr' => [
                    'class' => 'mb-0'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TimeSlot::class,
            'event' => null,
        ]);
    }
}
