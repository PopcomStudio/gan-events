<?php

namespace App\Form;

use App\Entity\Sender;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SenderType extends AbstractType
{
    private ObjectManager $om;
    private UserPasswordHasherInterface $passwordEncoder;

    /**
     * SenderType constructor.
     * @param ObjectManager $om
     * @param UserPasswordHasherInterface $passwordEncoder
     */
    public function __construct(
        ObjectManager $om,
        UserPasswordHasherInterface $passwordEncoder
    ) {
        $this->om = $om;
        $this->passwordEncoder = $passwordEncoder;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->event = $options['event'];

        $builder
            ->add('name', null, [
                'label' => 'Nom à afficher',
                'attr' => [
                    'placeholder' => 'Personnaliser le nom à afficher',
                ],
                'required' => false,
            ])
            ->add('email', null, [
                'label' => 'Email de réponse',
                'attr' => [
                    'placeholder' => 'Personnaliser l\'email de réponse',
                ],
                'required' => false,
            ])
            ->add('plural', CheckboxType::class, [
                'label' => 'Cet expéditeur représente plusieurs personnes',
                'required' => false,
                'label_attr' => [
                    'class' => 'checkbox-switch',
                ]
            ])
            ->add('guestType', ChoiceType::class, [
                'label' => 'Type d\'invités *',
                'choices' => array_flip(Sender::getGuestTypes()),
                'required' => true,
                'help' => 'Modifie les champs du formulaire d\'inscription à afficher (SIRET, ICX…) ainsi que les mentions légales au sein des emails.',
            ])
            ->add('allocatedTickets', NumberType::class, [
                'label' => 'Nb. places allouées',
                'scale' => 0,
                'attr' => [
                    'step' => '1',
                    'min' => '0',
                ],
                'required' => false,
                'help' => 'Laissez vide pour un nombre de places illimité (dans la limite des places de l\'événement).',
            ])
            ->add('sidekicks', NumberType::class, [
                'label' => 'Nb. d\'accompagnants par invité',
                'scale' => 0,
                'attr' => [
                    'step' => '1',
                    'min' => '0',
                ],
                'required' => false,
                'help' => 'Combien de personnes chaque invité peut inscrire ?',
            ])
            ->add('prospects', NumberType::class, [
                'label' => 'Nb. de prospects',
                'scale' => 0,
                'attr' => [
                    'step' => '1',
                    'min' => '0',
                ],
                'required' => false,
                'help' => 'Combien de personnes chaque invité peut proposer ?',
            ])
            ->add('overbooking', NumberType::class, [
                'label' => 'Surbooking',
                'scale' => 0,
                'attr' => [
                    'step' => '1',
                    'min' => '0',
                ],
                'required' => false,
                'help' => 'Dépasser le nb. de places allouées (dans la limite des places de l\'événement).',
            ])
        ;

        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'label' => 'Attribuer à un utilisateur / agent',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->orderBy('u.firstName', 'ASC')
                        ->addOrderBy('u.lastName', 'ASC')
                        ->addOrderBy('u.email', 'ASC')
                    ;
                },
                'choice_label' => function (User $user) {
                    return $user->getFirstName().' '.$user->getLastName(). ' ('.$user->getUsername().')';
                },
                'required' => false,
                'attr' => [
                    'data-placeholder' => 'Sélectionnez un utilisateur / un agent',
                    'data-init' => 'select2',
                ],
                'help' =>
                    'Recherchez un utilisateur existant '.
                    'ou créez un nouvel utilisateur en saisissant son adresse e-mail. '.
                    'L\'utilisateur pourra notamment gérer ses contacts.'
            ])
            ->add('autonomyOnEmails', CheckboxType::class, [
                'label' => 'Autonomie sur le contenu des emails',
                'label_attr' => [
                    'class' => 'checkbox-switch',
                ],
                'required' => false,
            ])
            ->add('autonomyOnSchedule', CheckboxType::class, [
                'label' => 'Autonomie sur le planning des envois',
                'label_attr' => [
                    'class' => 'checkbox-switch',
                ],
                'required' => false,
            ])
            ->add('legalNoticeSender', TextareaType::class, [
                'label' => 'Mentions légales des emails',
                'attr' => [
                    'data-init' => 'wysiwyg',
                ],
                'help' => 'Saissez ce champ pour remplacer les mentions légales dans les emails. Laissez vide pour utiliser les mentions légales par défaut.',
                'required' => false,
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
    }

    /** PreSubmit Event
     *
     * @param FormEvent $ev
     */
    public function preSubmit(FormEvent $ev)
    {
        $data = $ev->getData();

        if (!is_int($data['user']) && filter_var($data['user'], FILTER_VALIDATE_EMAIL)) {

            $user = new User($ev->getForm()->getData()->getEvent());
            $user
                ->setEmail($data['user'])
                ->setPassword($this->passwordEncoder->hashPassword($user, $user::generatePassword()))
            ;

            $this->om->persist($user);
            $this->om->flush();

            $data['user'] = $user->getId();

            $ev->setData($data);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Sender::class,
            'event' => null
        ]);
    }
}
