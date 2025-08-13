<?php

namespace App\Form;

use App\Entity\GuestMomentChoice;
use App\Entity\EventMoment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class GuestMomentChoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('moment', EntityType::class, [
                'class' => EventMoment::class,
                'choice_label' => 'name',
                'label' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => GuestMomentChoice::class,
        ]);
    }
} 