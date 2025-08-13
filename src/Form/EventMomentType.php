<?php

namespace App\Form;

use App\Entity\EventMoment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventMomentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du moment',
                'required' => true,
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de moment',
                'required' => true,
                'choices' => [
                    'Obligatoire' => 'obligatoire',
                    'Facultatif' => 'facultatif'
                ],
                'expanded' => true,
                'multiple' => false
            ])
            ->add('beginAt', DateTimeType::class, [
                'label' => 'Date et heure de début',
                'required' => true,
                'widget' => 'single_text',
                'view_timezone' => 'Europe/Paris',
                'model_timezone' => 'UTC',
            ])
            ->add('finishAt', DateTimeType::class, [
                'label' => 'Date et heure de fin',
                'required' => true,
                'widget' => 'single_text',
                'view_timezone' => 'Europe/Paris',
                'model_timezone' => 'UTC',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu',
                'required' => false,
            ])
            ->add('maxGuests', IntegerType::class, [
                'label' => 'Nombre maximum d\'invités',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EventMoment::class,
        ]);
    }
} 