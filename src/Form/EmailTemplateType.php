<?php

namespace App\Form;

use App\Entity\EmailTemplate;
use App\Entity\Sender;
use App\Entity\User;
use App\Form\DataTransformer\SenderToNumberTransformer;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class EmailTemplateType extends AbstractType
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
        /** @var EmailTemplate $emailTemplate */
        $emailTemplate = $builder->getData();
        $event = $emailTemplate->getEvent();

        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type d\'email',
                'choices' => array_flip($emailTemplate::getTypes())
            ])
            ->add('subject', null, [
                'label' => 'Objet de l\'email',
            ])
            ->add('content', null, [
                'label' => 'Corps de l\'email',
                'attr' => [
                    'data-init' => 'wysiwyg',
                ]
            ])
//            ->add('signature', null, [
//                'label' => 'Signature',
//                'attr' => [
//                    'placeholder' => 'Nom / Prénom',
//                ],
//                'required' => false,
//                'help' => 'Utilisez %%EXPEDITEUR%% pour écrire le nom d\'affichage.'
//            ])
        ;

        if (!$emailTemplate->getSender()) {

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
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EmailTemplate::class,
        ]);
    }
}
