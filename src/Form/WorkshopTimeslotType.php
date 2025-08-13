<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\Workshop;
use App\Entity\WorkshopTimeSlot;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkshopTimeslotType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Event $event */
        $event = $options['event'];

        $builder
            ->add('workshop', EntityType::class, [
                'label' => 'Atelier',
                'class' => Workshop::class,
                'choice_label' => function (Workshop $timeSlot) {
                    return $timeSlot->getName();
                },
                'query_builder' => function (EntityRepository $er) use ($event) {
                    return $er
                        ->createQueryBuilder('ws')
                        ->andWhere('ws.event = :event')
                        ->setParameter('event', $event)
                        ->orderBy('ws.name')
                        ;
                },
                'required' => true,
                'multiple' => false,
            ])
            ->add('nbGuests', NumberType::class, [
                'label' => 'Invités max.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WorkshopTimeslot::class,
            'event' => null,
        ]);
    }
}
