<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\Workshop;
use Arkounay\Bundle\UxCollectionBundle\Form\UxCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventWorkshopsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('workshops', UxCollectionType::class, [
                'label' => false,
                'entry_type' => WorkshopType::class,
                'allow_delete' => true,
                'allow_add' => true,
                'allow_drag_and_drop' => true,
                'display_sort_buttons' => true,
                'add_label' => 'Ajouter un atelier',
                'entry_options' => [
                    'label' => 'Atelier',
                ],
                'prototype_data' => (new Workshop())->setEvent($builder->getData()),
                'min' => 0,
                'by_reference' => false,
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
