<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\Sender;
use App\Entity\View;
use App\Repository\EventRepository;
use App\Repository\SenderRepository;
use App\Security\EventVoter;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

class EventHelper
{
    private ?Event $event = null;
    private FormFactoryInterface $formFactory;
    private ?Request $request;
    private Security $security;
    private EntityManagerInterface $em;
    private bool $init = false;
    private ?FormInterface $form = null;
    private ?Sender $currentSender = null;
    private ?View $view = null;

    /**
     * @param Security $security
     * @param FormFactoryInterface $formFactory
     * @param RequestStack $requestStack
     * @param EntityManagerInterface $em
     */
    public function __construct(
        Security $security,
        FormFactoryInterface $formFactory,
        RequestStack $requestStack,
        ObjectManager $em
    ) {
        $this->formFactory = $formFactory;
        $this->request = $requestStack->getCurrentRequest();
        $this->security = $security;
        $this->em = $em;
    }

    public function setInitialized(): self
    {
        $this->init = true;

        return $this;
    }

    public function setEvent(?Event $event): self
    {
        if ( $this->init ) return $this;

        $this->event = $event;
        $this->init = true;

        if ( $event && $this->security->getUser() ) {
          
            $form = $this->getOrCreateSwitchSenderForm();

            if ( $form ) {

                $form->handleRequest($this->request);

                if ($form->isSubmitted() && $form->isValid()) {

                    $sender = $form->getData()['sender'];

                    if ($sender === null) {

                        $this->getView()->setSender(null);

                        $this->em->persist($this->getView());
                        $this->em->flush();

                    } else {

                        $criteria = Criteria::create()->andWhere(Criteria::expr()->eq('id', $form->getData()['sender']));

                        $matches = $this->event->getSenders()->matching($criteria);

                        if (count($matches)) {

                            $this->getView()->setSender($matches->first());

                            $this->em->persist($this->getView());
                            $this->em->flush();
                        }
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Utilisé pour le formulaire d'inscrire en front
     * @param Sender $sender
     * @return $this
     */
    public function setCurrentSender(Sender $sender): self
    {
        $this->currentSender = $sender;

        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function getCurrentSender(): ?Sender
    {
        if ( $this->currentSender ) return $this->currentSender;

        if ( ! $this->event ) return null;

        if ( ! $this->event->getSenders()->count() ) return null;

        return $this->getView()->getSender();
    }

    public function getView(): View
    {
        if ( ! $this->view ) $this->view = $this->event->getView($this->security->getUser(), $this->security->isGranted(EventVoter::MANAGE, $this->event));

        return $this->view;
    }

    public function getSwitchSenderForm(): ?FormView
    {
        if ( ! $this->event ) return null;

        $form = $this->getOrCreateSwitchSenderForm();

        return $form ? $form->createView() : null;
    }

    private function getOrCreateSwitchSenderForm(): ?FormInterface
    {
        if ( $this->form ) return $this->form;

        // L'événement doit être défini
        if ( ! $this->event ) return null;

        // L'événement doit avoir au moins 1 expéditeur
        if ( $this->event->getSenders()->count() === 0 ) return null;

        // L'utilisateur doit avoir la capacité de changer d'expéditeur
        // ou il doit avoir plusieurs listes de diffusion à son nom
        if ( ! (
            $this->security->isGranted(EventVoter::SWITCH_USER, $this->event) ||
            $this->event->getSenders()->matching(SenderRepository::createUserCriteria($this->security->getUser()))->count() > 1
        )) return null;

        // Création du formulaire pour switcher entre les listes de diffusion
        $form = $this->formFactory->createNamed(
            'switch_sender',
            FormType::class,
            [
                // Attribuer la valeur de l'expéditeur courant
                'sender' => $this->getCurrentSender() ? $this->getCurrentSender()->getId() : null,
            ]
        );

        // Préparation des choix disponibles dans le formulaire
        $choices = [];

        // L'utilisateur peut switcher sur tous les expéditeurs
        if ( $this->security->isGranted('SWITCH_USER', $this->event) ) {

            $choices['Gestionnaire'] = null;

            foreach ($this->event->getSenders() as $sender) {

                $choices[sprintf('#%s %s', $sender->getId(), $sender->getDisplayName())] = $sender->getId();
            }

        }
        // L'utilisateur peut switcher sur les expéditeurs associés à son compte utilisateur
        else {

            foreach ($this->event->getSenders()->matching(SenderRepository::createUserCriteria($this->security->getUser())) as $sender) {

                $choices[sprintf('#%s %s', $sender->getId(), $sender->getDisplayName())] = $sender->getId();
            }
        }

        $form->add('sender', ChoiceType::class, [
            'label' => false,
            'row_attr' => [ 'class' => 'mb-0' ],
            'label_attr' => [ 'class' => 'small' ],
            'attr' => [  'class' => 'form-select-sm' ],
            'choices' => $choices,
        ]);

        $this->form = $form;

        return $form;
    }

    public function isCollection(): bool
    {
        if ( ! $this->event ) return false;

        return $this->event->isCollection();
    }

    public function hasCollection(): bool
    {
        if ( ! $this->event ) return false;

        return $this->event->isInCollection();
    }

    public function getDashboardColor(): string
    {
        static $color = null;

        if (empty($color)) {

            $color = 'primary';

            if ( ! $this->getCurrentSender() && strpos($this->request->attributes->get('_route'), 'app_event_') === 0) {

                $color = 'dark';
            }
        }

        return $color;
    }

    public function canManageEmail(): bool
    {
        return $this->security->isGranted(EventVoter::MANAGE_EMAIL, $this->event);
    }

    public function canManageEmailSchedules(): bool
    {
        return $this->security->isGranted(EventVoter::MANAGE_EMAIL_SCHEDULES, $this->event);
    }

    public function canManageEmailTemplates(): bool
    {
        return $this->security->isGranted(EventVoter::MANAGE_EMAIL_TEMPLATES, $this->event);
    }

    public function canManageEmailVisuals(): bool
    {
        return $this->security->isGranted(EventVoter::MANAGE_EMAIL_VISUALS, $this->event);
    }

    public function canManageContacts(): bool
    {
        return $this->security->isGranted(EventVoter::MANAGE_CONTACTS, $this->event);
    }

    public function getRelatedEvents(): array
    {
        if ( ! $this->event->isInCollection() ) return [];

        if ( ! $this->event->isSwitchable() ) return [];

        return $this->event
            ->getParent()
            ->getChildren()
            ->matching(EventRepository::createRelatedEventsCriteria($this->event))->toArray()
        ;
    }
}