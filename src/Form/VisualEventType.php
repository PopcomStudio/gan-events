<?php

namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VisualEventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('emailVisual', ImageType::class, [
                'label' => 'Visuel d\'invitation',
                'help' => 'Egalement utilisé comme visuel par défaut pour tous les emails.',
            ])
            ->add('emailUpVisual', ImageType::class, [
                'label' => 'Visuel de relance',
            ])
            ->add('emailReminderVisual', ImageType::class, [
                'label' => 'Visuel de rappel',
            ])
            ->add('emailThanksVisual', ImageType::class, [
                'label' => 'Visuel de remerciement',
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
