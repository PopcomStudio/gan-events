<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\User;
use App\Entity\Workshop;
use App\Entity\EventMoment;
use Arkounay\Bundle\UxCollectionBundle\Form\UxCollectionType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
//        /** @var ?Event $event */
        $event = $builder->getData();
        $parent = $event ? $event->getParent() : null;

        $builder
            ->add('name', null, [
                'label' => 'Nom de l\'événement *',
                'attr' => [
                    'placeholder' => 'Saisir le nom de l\'événement *',
                ],
                'required' => true,
            ])
            ->add('slug', null, [
                'label' => 'Permalien *',
                'attr' => [
                    'placeholder' => 'Automatiquement généré depuis le titre',
                ],
                'required' => true,
                'help' => 'https://gan.events/e/permalien (lettres, chiffres et tirets sont autorisés)',
            ])
            ->add('target', null, [
                'label' => '« Cible »',
                'attr' => [
                    'placeholder' => 'Evénement / Projection / Séminaire',
                ],
                'required' => false,
                'help' => 'Texte affiché dans la flèche jaune.'
            ])
            ->add('totalTickets', NumberType::class, [
                'label' => 'Total de places',
                'attr' => [
                    'step' => '1',
                ],
                'scale' => 0,
                'help' => 'Laisser vide pour gérer le nombre de places via les expéditeurs uniquement.',
                'required' => false
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type d\'événement *',
                'attr' => [
                    'data-init' => 'select2',
                ],
                'choices' => array_flip(Event::getTypes()),
                'required' => true,
                'help' => 'Sélectionner un type d\'événement pour personnaliser les informations et le formulaire d\'inscription.'
            ])
            ->add('moments', UxCollectionType::class, [
                'label' => 'Définir les temps forts',
                'entry_type' => EventMomentType::class,
                'allow_delete' => true,
                'allow_add' => true,
                'allow_drag_and_drop' => false,
                'display_sort_buttons' => false,
                'add_label' => 'Ajouter un moment clé',
                'entry_options' => [
                    'label' => 'Moment clé',
                ],
                'prototype_data' => (new \App\Entity\EventMoment())->setEvent($builder->getData()),
                'min' => 0,
                'by_reference' => false,
                'row_attr' => [
                    'class' => 'arkounay-ux-collection-left'
                ]
            ])
            ->add('beginAt', DateTimeType::class, [
                'label' => 'Début de l\'événement *',
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'dd/MM/yyyy HH:mm',
                'view_timezone' => 'Europe/Paris',
                'model_timezone' => 'UTC',
                'required' => true,
            ])
            ->add('finishAt', DateTimeType::class, [
                'label' => 'Fin de l\'événement',
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'dd/MM/yyyy HH:mm',
                'view_timezone' => 'Europe/Paris',
                'model_timezone' => 'UTC',
                'required' => false,
            ])
            ->add('foundation', CheckboxType::class, [
                'label' => 'En partenariat avec la Fondation',
                'row_attr' => [
                    'class' => 'mb-0',
                ],
                'label_attr' => [
                    'class' => 'checkbox-switch'
                ],
                'required' => false,
                'help' => 'Permet de joindre le logo Gan pour le cinéma.'
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Présentation de l\'événement',
                'attr' => [
                    'data-init' => 'wysiwyg',
                    'placeholder' => 'Présenter votre événement',
                ],
                'required' => false,
            ])
            ->add('autocomplete', TextType::class, [
                'label' => 'Rechercher une adresse',
                'label_attr' => [
                    'class' => 'sr-only',
                ],
                'attr' => [
                    'placeholder' => 'Rechercher une adresse',
                ],
                'mapped' => false,
                'required' => false,
                'help' => 'Utiliser ce champ pour localiser votre adresse sur la carte.'
            ])
            ->add('address', TextareaType::class, [
                'label' => 'Adresse à afficher',
                'attr' => [
                    'placeholder' => 'Saisir l\'adresse complète de votre événement',
                ],
                'required' => false,
                'help' => 'Ce champ correspond à l\'adresse affichée sur l\'événement.'
            ])
            ->add('lat', HiddenType::class)
            ->add('lng', HiddenType::class)
            ->add('visual', ImageType::class, [
                'label' => 'Visuel de l\'événement',
            ])
            ->add('ticketVisual', ImageType::class, [
                'label' => 'Visuel des tickets',
                'help' => 'Si non renseigné, le visuel de l\'événement sera recadré et utilisé par défaut.',
            ])
            ->add('logo', ImageType::class, [
                'label' => 'Logo de l\'événement',
            ])
            ->add('poster', AttachmentType::class, [
                'label' => 'Affiche à télécharger',
                'help' => 'Permettre aux utilisateurs accédant à l\'événement de télécharger une affiche au format jpeg ou pdf.',
            ])
            ->add('managers', EntityType::class, [
                'class' => User::class,
                'label' => 'Gestionnaires de l\'événement',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->orderBy('u.firstName', 'ASC')
                        ->addOrderBy('u.lastName', 'ASC')
                        ->addOrderBy('u.email', 'ASC')
                        ;
                },
                'choice_label' => function (User $user) {
                    return '#'.$user->getId().' '.$user->getDisplayName(). ' ('.$user->getEmail().')';
                },
                'multiple' => true,
                'required' => false,
                'attr' => [
                    'data-placeholder' => 'Sélectionnez des utilisateurs',
                    'data-init' => 'select2',
                ],
                'help' =>
                    'Rechercher des utilisateurs existants. '.
                    'L\'utilisateur pourra modifier toutes les informations de l\'événement.'
            ])
            ->add('viewers', EntityType::class, [
                'class' => User::class,
                'label' => 'Consultants',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->orderBy('u.firstName', 'ASC')
                        ->addOrderBy('u.lastName', 'ASC')
                        ->addOrderBy('u.email', 'ASC')
                        ;
                },
                'choice_label' => function (User $user) {
                    return '#'.$user->getId().' '.$user->getDisplayName(). ' ('.$user->getEmail().')';
                },
                'multiple' => true,
                'required' => false,
                'attr' => [
                    'data-placeholder' => 'Sélectionnez des utilisateurs',
                    'data-init' => 'select2',
                ],
                'help' =>
                    'Rechercher des utilisateurs existants. '.
                    'L\'utilisateur pourra consulter le résumé et les listes de contacts.'
            ])
            ->add('movieTitle', TextType::class, [
                'label' => 'Titre du film',
                'required' => false,
            ])
            ->add('movieGenres', TextType::class, [
                'label' => 'Genre(s)',
                'help' => 'Genres séparés par des virgules.',
                'required' => false,
            ])
            ->add('movieStarredBy', TextType::class, [
                'label' => 'Acteur.trice.s',
                'help' => 'Séparé.e.s par des virgules.',
                'required' => false,
            ])
            ->add('movieDirectedBy', TextType::class, [
                'label' => 'Réalisateur.trice.s',
                'help' => 'Séparé.e.s par des virgules.',
                'required' => false,
            ])
            ->add('movieCountries', TextType::class, [
                'label' => 'Pays',
                'help' => 'Séparés par des virgules.',
                'required' => false,
            ])
            ->add('movieAwards', TextType::class, [
                'label' => 'Récompense(s)',
                'help' => 'Séparées par des virgules.',
                'required' => false,
            ])
            ->add('movieRunningTime', NumberType::class, [
                'label' => 'Durée',
                'help' => 'En minutes.',
                'required' => false,
            ])
            ->add('movieReleasedAt', DateType::class, [
                'label' => 'Date de sortie',
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'dd/MM/yyyy',
                'view_timezone' => 'Europe/Paris',
                'model_timezone' => 'UTC',
                'required' => false,
            ])
            ->add('moviePoster', ImageType::class, [
                'label' => 'Affiche du film',
            ])
            ->add('movieOverview', TextareaType::class, [
                'label' => 'Synopsis',
                'required' => false,
            ])
            ->add('addTicket', CheckboxType::class, [
                'label' => 'Création de ticket d\'accès pour l\'événement',
                'label_attr' => [
                    'class' => 'checkbox-switch'
                ],
                'required' => false,
                'help' => 'Permet de joindre les tickets d\'accès au mail de confirmation d\'inscription.'
            ])
            ->add('workshops', UxCollectionType::class, [
                'label' => 'Définir les ateliers',
                'entry_type' => WorkshopType::class,
                'allow_delete' => true,
                'allow_add' => true,
                'allow_drag_and_drop' => false,
                'display_sort_buttons' => false,
                'add_label' => 'Ajouter un atelier',
                'entry_options' => [
                    'label' => 'Atelier',
                ],
                'prototype_data' => (new Workshop())->setEvent($builder->getData()),
                'min' => 0,
                'by_reference' => false,
            ])
        ;

        if ($parent) {

            $builder->add('switchable', CheckboxType::class, [
                'label' => 'Date permutable',
                'label_attr' => [
                    'class' => 'checkbox-custom',
                ],
                'required' => false,
            ]);
        }

        if ($event && $event->getType() === 'ateliers') {
            $builder->add('timeSlots', UxCollectionType::class, [
                'label' => 'Créneaux',
                'entry_type' => \App\Form\TimeSlotType::class,
                'allow_delete' => true,
                'allow_add' => true,
                'allow_drag_and_drop' => false,
                'display_sort_buttons' => false,
                'add_label' => 'Ajouter un créneau',
                'entry_options' => [
                    'event' => $builder->getData(),
                ],
                'prototype_data' => (new \App\Entity\TimeSlot())->setEvent($builder->getData()),
                'min' => 0,
                'by_reference' => false,
                'attr' => ['data-controller' => 'custom-collection'],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
