<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\Guest;
use App\Entity\Sender;
use App\Entity\TimeSlot;
use App\Entity\Workshop;
use App\Entity\WorkshopTimeSlot;
use App\Entity\EventMoment;
use App\Entity\GuestMomentChoice;
use App\Repository\GuestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class GuestType extends AbstractType
{
    const CONTEXT_ADD = 'admin';
    const CONTEXT_FRONT = 'front';
    const CONTEXT_FORCE_REGISTER = 'register';
    const CONTEXT_EDIT = 'admin-edit';

    private GuestRepository $guestRepository;

    public function __construct(GuestRepository $guestRepository)
    {
        $this->guestRepository = $guestRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Guest $guest */
        $guest = $builder->getData();
        $guestType = $options['type'];

        // Récupérer l'événement soit depuis les options, soit depuis l'invité
        /** @var Event $event */
        $event = $options['event'] ?? ($guest ? $guest->getEvent() : null);

        $this->addPersonalFields($builder, $options['context']);
        $this->addGenderField($builder, $options['context'], $guestType);
        $this->addEmailField($builder, $options['context'], $guestType);
        $this->addPhoneField($builder, $options['context'], $guestType);
        $this->addCompanyFields($builder, $options['context'], $guestType);

				$this->addInspectorFields($builder, $options['context']);
        $this->addPrivacyPolicyField($builder, $options['context'], $guestType);

        $this->addGolfCupFields($builder, $options['context'], $options['event_type'], $guest);
        $this->addWorkshopsFields($builder, $options['context'], $options['event_type'], $guest);

        $this->addSidekicksFields($builder, $options['context'], $options['sidekicks']);
        $this->addProspectsFields($builder, $options['context'], $options['prospects'], $guest);

        if ($guest !== null) {
            $this->addEventTypeFields($builder, $guest->getEvent(), $guest);
        }

        if ($event && $event->getType() === Event::TYPE_STANDARD_PLUS_MOMENTS) {
            $builder->add('momentChoices', EntityType::class, [
                'class' => EventMoment::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'label' => 'Temps forts',
                'required' => false,
                'mapped' => false,
                'data' => $options['momentChoices_data'] ?? null,
                'query_builder' => function (EntityRepository $er) use ($event) {
                    return $er->createQueryBuilder('m')
                        ->where('m.event = :event')
                        ->andWhere('m.type = :type')
                        ->setParameter('event', $event)
                        ->setParameter('type', 'facultatif')
                        ->orderBy('m.beginAt', 'ASC');
                },
            ]);
        }
    }

    private function isGeneralFront($context): bool
    {
        return in_array($context, [self::CONTEXT_FRONT, self::CONTEXT_FORCE_REGISTER]);
    }

    /**
     * Add firstName & lastName
     * @param FormBuilderInterface $builder
     * @param string $context
     */
    private function addPersonalFields(FormBuilderInterface $builder, string $context): void
    {
        $lastNameLabel = 'Nom';
        if ($context === self::CONTEXT_ADD) $lastNameLabel = false;
        elseif ($this->isGeneralFront($context)) $lastNameLabel.= ' *';

        $firstNameLabel = 'Prénom';
        if ($context === self::CONTEXT_ADD) $firstNameLabel = false;
        elseif ($this->isGeneralFront($context)) $firstNameLabel.= ' *';

        $builder
            ->add('lastName', TextType::class, [
                'label' => $lastNameLabel,
                'required' => $context === self::CONTEXT_FRONT,
            ])
            ->add('firstName', TextType::class, [
                'label' => $firstNameLabel,
                'required' => $context === self::CONTEXT_FRONT,
            ])
        ;
    }

    /**
     * Add gender field
     * @param FormBuilderInterface $builder
     * @param string $context
     * @param string $guestType
     */
    private function addGenderField(FormBuilderInterface $builder, string $context, string $guestType): void
    {
        if ($guestType === Guest::TYPE_SIDEKICK) return;

        $builder
            ->add('gender', ChoiceType::class, [
                'label' => false,
                'choices' => [
                    'Madame' => 'Madame',
                    'Monsieur' => 'Monsieur',
                ],
                'label_attr' => [
                    'class' => 'radio-custom radio-inline',
                ],
                'required' => $this->isGeneralFront($context),
                'placeholder' => 'Laisser vide',
                'expanded' => $context !== self::CONTEXT_ADD,
            ])
        ;
    }

    /**
     * Add email field
     * @param FormBuilderInterface $builder
     * @param string $context
     * @param string $guestType
     */
    private function addEmailField(FormBuilderInterface $builder, string $context, string $guestType): void
    {
        if ($guestType === Guest::TYPE_SIDEKICK) return;

        $builder
            ->add('email', EmailType::class, [
                'label' => $context === self::CONTEXT_ADD ? false : 'Email *',
                'required' => true,
                'constraints' => [
                    new NotNull(),
                    new Email(),
                ],
            ])
        ;
    }

    /**
     * Add phone field
     * @param FormBuilderInterface $builder
     * @param string $context
     * @param string $guestType
     */
    private function addPhoneField(FormBuilderInterface $builder, string $context, string $guestType): void
    {
        if ($guestType === Guest::TYPE_PROSPECT) return;

        $phoneLabel = 'Téléphone';

				if ($context === self::CONTEXT_ADD) $phoneLabel = false;
        elseif ($this->isGeneralFront($context)) $phoneLabel.= ' *';

				if ($guestType !== 'agent') {
					$builder
						->add('phone', TextType::class, [
						'label' => $phoneLabel,
						'required' => true,
					]);
				}
    }

    /**
     * Add Company / Siret or
     * @param FormBuilderInterface $builder
     * @param string $context
     * @param string $guestType
     */
    private function addCompanyFields(FormBuilderInterface $builder, string $context, string $guestType): void
    {
        switch ($guestType):

            case Sender::GUEST_TYPE_PRO:
            case Sender::GUEST_TYPE_DEFAULT:
                $builder
                    ->add('company', TextType::class, [
                        'label' => 'Raison sociale'.($this->isGeneralFront($context)&& $guestType === Sender::GUEST_TYPE_PRO ? ' *' : ''),
                        'label_attr' => [
                            'class' => $context === self::CONTEXT_ADD ? 'sr-only' : '',
                        ],
                        'required' => $this->isGeneralFront($context) && $guestType === Sender::GUEST_TYPE_PRO,
                    ])
                ;

                $builder
                    ->add('siret', TextType::class, [
                        'label' => $context === self::CONTEXT_ADD ? false : 'SIRET'.($this->isGeneralFront($context) && $guestType === Sender::GUEST_TYPE_PRO ? ' *' : ''),
                        'required' => $this->isGeneralFront($context) && $guestType === Sender::GUEST_TYPE_PRO,
                        'attr' => [
                            'class' => 'siret',
                        ]
                    ])
                ;
                break;

            case Sender::GUEST_TYPE_AGENT:
                $builder
                    ->add('company', TextType::class, [
                        'label' => 'Agence'.($this->isGeneralFront($context) ? ' *' : ''),
                        'label_attr' => [
                            'class' => $context === self::CONTEXT_ADD ? 'sr-only' : '',
                        ],
                        'required' => $this->isGeneralFront($context),
                    ])
                    ->add('siret', TextType::class, [
                        'label' => $context === self::CONTEXT_ADD ? false : 'ICX *',
                        'required' => $this->isGeneralFront($context),
                        'attr' => [
                            'class' => 'icx'
                        ],
                    ])

                ;
							$builder->remove('siret');
                break;

        endswitch;
    }


	/**
	 * Add Commercial Inspector
	 * @param FormBuilderInterface $builder
	 * @param string $context
	 */
		private function addInspectorFields(FormBuilderInterface $builder, string $context): void
		{
			$inspectorLabel = 'Inspecteur commercial';

			if ($context === self::CONTEXT_ADD) {
				$inspectorLabel = false;
			}

			if ($context === self::CONTEXT_FRONT) return;

			$builder->add('inspecteurCommercial', TextType::class, [
				'label' => $inspectorLabel,
				'required' => false,
			]);

		}

    /**
     * Add privacy policy checkbox (only for external guests)
     * @param FormBuilderInterface $builder
     * @param string $context
     * @param string $guestType
     */
    private function addPrivacyPolicyField(FormBuilderInterface $builder, string $context, string $guestType): void
    {
        if ( self::CONTEXT_FRONT !== $context || in_array($guestType, [Sender::GUEST_TYPE_AGENT, Sender::GUEST_TYPE_INTERNAL])) return;

        $builder->add('privacyPolicy', CheckboxType::class, [
            'label' => 'J\'accepte la __privacyPolicy__',
            'mapped' => false,
            'required' => true,
            'label_attr' => [
                'class' => 'checkbox-custom',
            ],
        ]);
    }

    /**
     * Add Golf Cup Fields
     * @param FormBuilderInterface $builder
     * @param string $context
     * @param string $eventType
     * @param Guest|null $guest
     */
    private function addGolfCupFields(FormBuilderInterface $builder, string $context, string $eventType, ?Guest $guest): void
    {
        if ( $eventType !== Event::TYPE_GOLFCUP || ! $guest ) return;

        $builder
            ->add('golf', ChoiceType::class, [
                'label' => false,
                'label_attr' => [
                    'class' => 'radio-custom',
                ],
                'choices' => [
                    'Je suis golfeur et je souhaite participer à la compétition.' => 1,
                    'Je ne suis pas golfeur et je souhaite participer à l\'initiation.' => 0
                ],
                'placeholder' => 'Laisser vide',
                'mapped' => false,
                'expanded' => true,
                'required' => $this->isGeneralFront($context),
            ])
            ->add('golfLicense', TextType::class, [
                'label' => 'N° de licence *',
                'required' => false,
            ])
            ->add('golfIndex', NumberType::class, [
                'label' => 'Index',
                'scale' => 1,
                'attr' => [
                    'step' => 0.1
                ],
                'required' => false,
            ])
        ;
    }

    private function addWorkshopsFields(FormBuilderInterface $builder, string $context, string $eventType, ?Guest $guest): void
    {
        if ( ! (
            $this->isGeneralFront($context) &&
            $eventType === Event::TYPE_WORKSHOPS &&
            $guest->getEvent()->getWorkshops()->count()
        )) return;

        /** @var Event $event */
        $event = $guest->getEvent();
        $timeSlots = $event->getTimeSlots();

        $timeSlotsForm = $builder->create('workshops', FormType::class, [
            'label' => 'Sélectionnez l\'atelier souhaité pour chaque plage horaire',
            'label_attr' => [ 'class' => 'display-3 separator-top text-dark', ],
            'row_attr' => [ 'class' => 'mt-3', ],
            'mapped' => false,
        ]);

        $dateFormatter = new \IntlDateFormatter(
            'fr_FR',
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL,
            'Europe/Paris',
            null,
            'EEEE HH:mm'
        );

        $hourFormatter = new \IntlDateFormatter(
            'fr_FR',
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL,
            'Europe/Paris',
            null,
            'HH:mm'
        );

        /** @var TimeSlot $timeSlot */
        foreach ($timeSlots as $timeSlot) {

            $choices = [];

            /** @var WorkshopTimeSlot $workshop */
            foreach ($timeSlot->getWorkshops() as $workshop) {

                if ($workshop->getNbGuests() === 0 || $workshop->getGuests(true)->count() < $workshop->getNbGuests()) {

                    $choices[$workshop->getWorkshop()->getName()] = $workshop->getId();
                }
            }

            $choices['Ne pas s\'inscrire'] = 0;

            $label = $dateFormatter->format($timeSlot->getStartAt());

            if ($timeSlot->getStartAt()->format('Y-m-d') !== $timeSlot->getEndAt()->format('Y-m-d')) {

                $label.= ' '.$dateFormatter->format($timeSlot->getEndAt());

            } else {

                $label.= '-'.$hourFormatter->format($timeSlot->getEndAt());
            }

            $timeSlotsForm->add($timeSlot->getId(), ChoiceType::class, [
                'label' => $label,
                'choices' => $choices,
                'multiple' => false,
                'expanded' => true,
                'label_attr' => [
                    'class' => 'radio-custom radio-inline'
                ],
                'invalid_message' => 'Cet atelier est complet',
            ]);
        }

        $builder->add($timeSlotsForm);
    }

    /**
     * Add sidekicks fields
     * @param FormBuilder $builder
     * @param string $context
     * @param int|null $sidekicks
     */
    private function addSidekicksFields(FormBuilderInterface $builder, string $context, ?int $sidekicks): void
    {
        if ($sidekicks > 0 && $this->isGeneralFront($context)) {

            /** @var Guest $guest */
            $guest = $builder->getData();

            $label = $sidekicks === 1
                ? 'Venez accompagné.'
                : 'Venez accompagné de ' . $sidekicks . ' personnes.';

            $maxMessage = $sidekicks === 1
                ? 'Vous ne pouvez venir accompagné que d\'une personne.'
                : 'Vous ne pouvez venir accompagné que de ' . $sidekicks . ' personnes.';

            $builder
                ->add('sidekicks', CollectionType::class, [
                    'label' => 'Envie de partager ce moment ? ' . $label,
                    'entry_type' => GuestType::class,
                    'prototype_data' => (new Guest())
                        ->setParent($guest)
                        ->setSidekick()
                        ->setRegistered()
                        ->setEvent($guest->getEvent())
                        ->setSender($guest->getSender())
                    ,
                    'allow_add' => true,
                    'entry_options' => [
                        'context' => self::CONTEXT_FRONT,
                        'type' => 'sidekick',
                    ],
                    'constraints' => new Count(['max' => $sidekicks, 'maxMessage' => $maxMessage])
                ]);
        }
    }

    /**
     * Add prospects fields
     * @param FormBuilderInterface $builder
     * @param string $context
     * @param int|null $prospects
     * @param Guest $guest
     */
    private function addProspectsFields(FormBuilderInterface $builder, string $context, ?int $prospects, ?Guest $guest): void
    {
        if ($prospects > 0 && $this->isGeneralFront($context)) {

            $label = $prospects === 1
                ? 'Proposez un invité.'
                : 'Proposez jusqu\'à ' . $prospects . ' invités.';
            $maxMessage = $prospects === 1
                ? 'Vous ne pouvez convier qu\'une personne.'
                : 'Vous pouvez convier jusqu\'à ' . $prospects . ' personnes.';

            $builder
                ->add('prospects', CollectionType::class, [
                    'label' => 'Envie de partager ce moment ? ' . $label,
                    'entry_type' => GuestType::class,
                    'prototype_data' => (new Guest())
                        ->setParent($guest)
                        ->setStatus('prospect')
                        ->setEvent($guest->getEvent())
                        ->setSender($guest->getSender())
                    ,
                    'allow_add' => true,
                    'entry_options' => [
                        'context' => self::CONTEXT_FRONT,
                        'type' => $guest->getSender()->getGuestType(),
                    ],
                    'constraints' => new Count(['max' => $prospects, 'maxMessage' => $maxMessage])
                ]);
        }
    }

    private function addEventTypeFields(FormBuilderInterface $builder, Event $event, Guest $guest): void
    {
        if ($event->getType() === Event::TYPE_STANDARD_PLUS_MOMENTS) {
            $builder->add('momentChoices', EntityType::class, [
                'class' => EventMoment::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'label' => 'Temps forts',
                'required' => false,
                'mapped' => false,
                'query_builder' => function (EntityRepository $er) use ($event) {
                    return $er->createQueryBuilder('m')
                        ->where('m.event = :event')
                        ->andWhere('m.type = :type')
                        ->setParameter('event', $event)
                        ->setParameter('type', 'facultatif')
                        ->orderBy('m.beginAt', 'ASC');
                },
            ]);
        }

        if ($event->getType() === Event::TYPE_WORKSHOPS && $event->getWorkshops()->count()) {
            $this->addWorkshopsFields($builder, self::CONTEXT_FRONT, Event::TYPE_WORKSHOPS, $guest);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $repository = $this->guestRepository;

        $resolver->setDefaults([
            'data_class' => Guest::class,
            'event_type' => 'evenement',
            'type' => 'all',
            'context' => self::CONTEXT_ADD,
            'sidekicks' => 0,
            'prospects' => 0,
            'event' => null,
            'momentChoices_data' => null,
            'constraints' => [
                new Callback([
                    'callback' => static function($guest, ExecutionContextInterface $context) use ($repository) {

                        if ($guest->getId() === null && $repository->exists($guest)) {

                            $context
                                ->buildViolation('Cette adresse email est déjà utilisée sur cet événement.')
                                ->atPath('email')
                                ->addViolation()
                            ;
                        }
                    }
                ])
            ]
        ]);
    }
}
