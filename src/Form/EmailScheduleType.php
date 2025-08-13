<?php

namespace App\Form;

use App\Entity\EmailSchedule;
use App\Entity\EmailTemplate;
use App\Entity\User;
use App\Form\DataTransformer\SenderToNumberTransformer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints as Assert;

class EmailScheduleType extends AbstractType
{
    /** @var Security */
    private $security;

    /** @var User */
    private $user;

    /** @var ObjectManager */
    private $om;

    /** @var SenderToNumberTransformer */
    private $senderToNumberTransformer;

    public function __construct(Security $security, ObjectManager $om, SenderToNumberTransformer $senderToNumberTransformer)
    {
        $this->security = $security;
        $this->user = $security->getUser();
        $this->om = $om;
        $this->senderToNumberTransformer = $senderToNumberTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var EmailSchedule $emailSchedule */
        $emailSchedule = $builder->getData();
        $event = $emailSchedule->getEvent();

        $templates = new ArrayCollection();

        if ($emailSchedule->getTemplate()) {

            $templates->add($emailSchedule->getTemplate());
        }

        $builder
            ->add('sendAt', DateTimeType::class, [
                'label' => 'Date d\'envoi *',
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'dd/MM/yyyy HH:mm',
                'view_timezone' => 'Europe/Paris',
                'model_timezone' => 'UTC',
                'required' => true,
								'attr' => [
									'autocomplete' => 'off',
									'aria-autocomplete' => 'none',
								],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type d\'email *',
                'choices' => array_flip(EmailTemplate::getTypes())
            ])
            ->add('onlyNew', CheckboxType::class, [
                'label' => 'Uniquement pour les nouveaux contacts',
                'label_attr' => [
                    'class' => 'checkbox-custom'
                ],
                'required' => false,
                'help' => 'Envoi de l\'email uniquement aux contacts n\'ayant jamais reçu ce type d\'email.'
            ])
            ->add('templateOrInput', CheckboxType::class, [
                'label' => 'Utiliser un modèle d\'email',
                'label_attr' => [
                    'class' => 'checkbox-custom'
                ],
                'required' => false,
            ])
            ->add('subject', null, [
                'label' => 'Objet de l\'email *',
            ])
            ->add('content', null, [
                'label' => 'Corps de l\'email *',
                'attr' => [
                    'data-init' => 'wysiwyg',
                ]
            ])
//            ->add('signature', TextareaType::class, [
//                'label' => 'Signature',
//                'attr' => [
//                    'placeholder' => 'Nom / Prénom',
//                ],
//                'required' => false,
//                'help' => 'Utilisez %%EXPEDITEUR%% pour écrire le nom d\'affichage.'
//            ])
        ;

        $this->addTemplate($builder, $templates);

        if (!$emailSchedule->getSender()) {

            $senders = $event->getSenders();

            $choices = ['Tout le monde' => ''];

            foreach ($senders as $sender) {

                $choices[
                    sprintf('#%s %s (%s)', $sender->getId(), $sender->getDisplayName(), $sender->getPublicEmail())
                ] = $sender->getId();
            }

            $builder
                ->add('sender', ChoiceType::class, [
                    'label' => 'Accessible pour',
                    'choices' => $choices,
                    'required' => false,
                ])
            ;

            $builder
                ->get('sender')
                ->addModelTransformer($this->senderToNumberTransformer)
            ;
        }

        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
    }

    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        if (!empty($data['templateOrInput'])) {

            $template = $this->om->getRepository(EmailTemplate::class)->findForSchedule($data);

            if ($template) {

                $this->addTemplate($form, new ArrayCollection([$template], true));

            } else {

                $this->addTemplate($form, new ArrayCollection([]), true);
            }

        } else {

            $constraints = ['constraints' => [
                new Assert\NotBlank()
            ]];

            $config = $form->get('subject')->getConfig();
            $form->add('subject', null, array_merge($config->getOptions(), $constraints));

            $config = $form->get('content')->getConfig();
            $form->add('content', null, array_merge($config->getOptions(), $constraints));

//            $config = $form->get('signature')->getConfig();
//            $form->add('signature', TextType::class, array_merge($config->getOptions(), $constraints));
        }
    }

    private function addTemplate($builder, $templates = null, $required = false)
    {
        $args = [
            'label' => 'Modèle d\'email *',
            'required' => false,
            'class' => EmailTemplate::class,
            'choice_label' => 'type',
            'choices' => $templates
        ];

        if ($required) {

            $args['constraints'] = [
                new Assert\NotBlank()
            ];
        }

        $builder->add('template', EntityType::class, $args);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EmailSchedule::class,
        ]);
    }
}
