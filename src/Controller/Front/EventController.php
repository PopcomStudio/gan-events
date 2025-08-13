<?php

namespace App\Controller\Front;

use App\Controller\AbstractController;
use App\Entity\Event;
use App\Entity\Guest;
use App\Entity\Option;
use App\Entity\WorkshopTimeSlot;
use App\Entity\GuestMomentChoice;
use App\Entity\EventMoment;
use App\Form\GuestType;
use App\Repository\GuestRepository;
use App\Service\EventHelper;
use App\Service\Mailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use Psr\Log\LoggerInterface;

class EventController extends AbstractController
{
    private ?Request $request;
    private EntityManagerInterface $om;
    private Environment $twig;
    private EventHelper $eventHelper;
    private LoggerInterface $logger;
    private Mailer $mailer;

    public function __construct(
        RequestStack $requestStack, 
        EntityManagerInterface $om, 
        Environment $twig, 
        EventHelper $eventHelper,
        LoggerInterface $logger,
        Mailer $mailer
    ) {
        $this->request = $requestStack->getCurrentRequest();
        $this->om = $om;
        $this->twig = $twig;
        $this->eventHelper = $eventHelper;
        $this->logger = $logger;
        $this->mailer = $mailer;
    }

    /**
     * @param Event $event
     * @return Guest|null
     */
    private function findOneInEventSession(Event $event): ?Guest
    {
        $session = $this->request->getSession();

        if ($session->get('event_'.$event->getId())) {

            /** @var Guest $guest */
            return $this->om->getRepository(Guest::class)->findById(
                $session->get('event_'.$event->getId()),
                $event
            );
        }

        return null;
    }

    private function getEventBySlug($slug): ?Event
    {
        $event = $this->om->getRepository(Event::class)->findOneBy(['slug' => $slug]);

        if (!$event) throw $this->createNotFoundException();

        return $event;
    }

    private function getGuestByUuid(Event $event, string $uuid): ?Guest
    {
        /** @var ?Guest $guest */
        $guest = $this->om->getRepository(Guest::class)->findByUuid($uuid, $event);

        if (!$guest) throw $this->createNotFoundException();

        return $guest;
    }

    /**
     * @Route("/e/{slug}", name="public_event_show")
     * @param Event $event
     * @param GuestRepository $guestRepository
     * @return Response
     */
    public function show(Event $event, GuestRepository $guestRepository): Response
    {
        // Si l'événement est terminé, afficher une page d'avertissement
        if ($event->getBeginAt() < new \DateTime()) return $this->returnEnded($event);

        $guest = $guestRepository->findOneInSession($event);

        if ( ! $guest ) {
            return $this->login($event);
        }

        // Redirige l'utilisateur si il a changé d'événement
        if ($guest->isSwitched()) {

            $dest = $this->om->getRepository(Guest::class)->findOneBy(
                ['parent' => $guest, 'backup' => false]
            );

            $this->request->getSession()->set('event_'.$dest->getEvent()->getId(), $dest->getId());

            return $this->redirectToRoute('public_event_show', ['slug' => $dest->getEvent()->getSlug()]);
        }

        // Gérer le "switch" entre les événements
        if ($this->request->query->get('id')) {

            $other = $this->om->getRepository(Event::class)->find($this->request->query->get('id'));

            if ($other->isSwitchable() && $other->getParent() === $event->getParent() && $other->getSenders()->count()) {

                $guest->setSender($other->getSenders()->first())->setEvent($other);

                $this->om->persist($guest);
                $this->om->flush();

                return $this->redirectToRoute('public_event_access', [
                    'id' => $other->getId(),
                    'uuid' => $guest->getUuid(),
                ]);
            }

            return $this->redirectToRoute('public_event_show', ['slug' => $event->getSlug()]);
        }

        // Si l'événement est terminé
        // Afficher une page d'atterrissage
        if ($event->getBeginAt() < new \DateTime()) {

            return $this->returnEnded($event);
        }

        $this->eventHelper->setCurrentSender($guest->getSender());

        $totalTicket = $this->om->getRepository(Event::class)->totalTickets($event);

        // Si nombre max de tickets sur l'événement, on vérifie le total des tickets
        if ($event->getTotalTickets()) {

            // GoTo : Prévoir selon les accompagnants
            if ($totalTicket >= $event->getTotalTickets()) {

                return $this->returnFull($event);
            }
        }

        // Vérifier le nombre de places de l'expéditeur
        if ($guest->getSender()->getAllocatedTickets()) {

            $totalAllocatedTickets = $guest->getSender()->getAllocatedTickets() + $guest->getSender()->getOverbooking();

            // GoTo : Prévoir selon les accompagnants + surbooking
            if ($this->om->getRepository(Guest::class)->getTotalTickets($guest->getSender()) >= $totalAllocatedTickets) {

                return $this->returnFull($event);
            }
        }

        // Pré-cocher les moments optionnels déjà choisis
        $momentChoices = [];
        foreach ($guest->getMomentChoices() as $choice) {
            if ($choice->getEvent() && $choice->getEvent()->getType() === 'facultatif') {
                $momentChoices[] = $choice->getEvent();
            }
        }

        $form = $this->createForm(GuestType::class, $guest, [
            'context' => GuestType::CONTEXT_FRONT,
            'sidekicks' => $guest->getSender()->getSidekicks(),
            'prospects' => $guest->getSender()->getProspects(),
            'event_type' => $event->getType(),
            'type' => $guest->getSender()->getGuestType(),
            'momentChoices_data' => $momentChoices,
        ]);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                $this->logger->error('Erreur formulaire : ' . $error->getMessage());
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $guest = $form->getData();

            // Si l'invité n'est pas encore inscrit, on le passe à "registered"
            $firstRegistration = false;
            if (!$guest->isRegistered()) {
                $guest->setRegistered();
                $firstRegistration = true;
            }

            // Gérer les choix des moments
            if ($event->getType() === Event::TYPE_STANDARD_PLUS_MOMENTS) {
                $selectedMoments = $form->get('momentChoices')->getData();

                // Supprimer tous les anciens choix (obligatoires et facultatifs)
                foreach ($guest->getMomentChoices() as $choice) {
                    $guest->removeMomentChoice($choice);
                    $this->om->remove($choice);
                }

                // Ajouter les nouveaux choix sélectionnés
                if ($selectedMoments) {
                    foreach ($selectedMoments as $moment) {
                        $choice = new GuestMomentChoice();
                        $choice->setEvent($moment);
                        $choice->setGuest($guest);
                        $guest->addMomentChoice($choice);
                    }
                }

                // Ajouter automatiquement les temps forts obligatoires
                $obligatoryMoments = $this->om->getRepository(EventMoment::class)->findBy([
                    'event' => $event,
                    'type' => 'obligatoire'
                ]);

                foreach ($obligatoryMoments as $moment) {
                    $choice = new GuestMomentChoice();
                    $choice->setEvent($moment);
                    $choice->setGuest($guest);
                    $guest->addMomentChoice($choice);
                }

                $this->logger->info('Choix des moments', [
                    'guest_id' => $guest->getId(),
                    'moment_choices_count' => is_array($selectedMoments) ? count($selectedMoments) : (is_countable($selectedMoments) ? count($selectedMoments) : 0),
                    'moment_choices' => array_map(function($moment) {
                        return [
                            'id' => $moment->getId(),
                            'name' => $moment->getName()
                        ];
                    }, $selectedMoments ?? []),
                ]);
            }

            if ($event->getType() === Event::TYPE_WORKSHOPS) {
                $formData = $form->getData();
                if (isset($formData['workshops'])) {
                    $workshopTimeSlotFindWorkshop = $this->om->getRepository(WorkshopTimeSlot::class)->findByIds($formData['workshops']);
                    foreach ($guest->getWorkshops() as $workshop) {
                        $guest->removeWorkshop($workshop);
                    }
                    foreach ($workshopTimeSlotFindWorkshop as $workshop) {
                        $guest->addWorkshop($workshop);
                    }
                }
            }

            if ($guest->getSidekicks()->count()) {
                /** @var Guest $sidekick */
                foreach ($guest->getSidekicks() as $sidekick) {
                    $sidekick
                        ->setParent($guest)
                        ->setSidekick()
                        ->setEvent($event)
                        ->setSender($guest->getSender())
                        ->setRegistered()
                    ;
                    $this->om->persist($sidekick);
                }
            }

            if ($guest->getProspects()->count()) {
                foreach ($guest->getProspects() as $prospect) {
                    $prospect
                        ->setProspect()
                        ->setEvent($event)
                        ->setSender($guest->getSender())
                    ;
                    $this->om->persist($prospect);
                }
            }

            // Persister l'invité avec tous ses choix
            $this->om->persist($guest);
            $this->om->flush();

            if ($firstRegistration) {
                return $this->redirectToRoute('public_event_success', ['slug' => $event->getSlug()]);
            } else {
                $this->om->flush();
                $this->mailer->sendConfirmationMessage($guest);
                $this->addFlash('success', 'Vos choix de temps forts ont bien été mis à jour. Un email de confirmation vous a été renvoyé.');
                return $this->redirectToRoute('public_event_show', ['slug' => $event->getSlug()]);
            }
        }

        $address = $event->getAddress();
        $addressBreakLinePos = strpos($address, "\n");

        if ($addressBreakLinePos) {

            $addressFirstLine = substr($address, 0, $addressBreakLinePos);
            $address = substr($address, $addressBreakLinePos+1);

        } else {

            $addressFirstLine = $address;
            $address = '';
        }

        $legal = $this->om->getRepository(Option::class)->findLegal();

        return $this->render('front/show.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
            'body_class' => 'event',
            'guest' => $guest,
            'pageTitle' => $event->getName(),
            'addressFirstLine' => $addressFirstLine,
            'address' => $address,
            'legal' => $legal,
        ]);
    }

    public function login(Event $event): Response
    {
        $legal = $this->om->getRepository(Option::class)->findLegal();

        $form = $this->createFormBuilder()
            ->add('email', EmailType::class, [
                'label' => 'Votre adresse e-mail *',
                'help' => 'Utilisez l\'adresse e-mail avec laquelle vous avez été invité à l\'événement.',
                'required' => true,
            ]);

        $form = $form->getForm();
        $form->handleRequest($this->request);

        if ($form->isSubmitted()) {

            if ($form->isValid()) {

                // Find email address
                /** @var Guest $guest */
                $guest = $this->om->getRepository(Guest::class)->findByEmail(
                    $form->getData()['email'],
                    $event
                );

                if ($guest) {

                    $this->request->getSession()->set('event_'.$event->getId(), $guest->getId());

                    return $this->redirectToRoute('public_event_show', ['slug' => $event->getSlug()]);

                } else {

                    $form->get('email')->addError(new FormError('Vérifiez votre adresse e-mail.'));
                }
            }
        }

        return $this->render('front/access.html.twig', [
            'form' => $form->createView(),
            'body_class' => 'login',
            'legal' => $legal,
        ]);
    }

    /**
     * @Route("/e/{slug}/success", name="public_event_success")
     * @param string $slug
     * @param GuestRepository $guestRepository
     * @return Response
     */
    public function success(string $slug, GuestRepository $guestRepository): Response
    {
        $event = $this->getEventBySlug($slug);
        $guest = $guestRepository->findOneInSession($event);

        if ( ! $guest ) throw $this->createNotFoundException();

        return $this->returnSuccess($event);
    }

    /**
     * @Route("/e/{id}/{uuid}", name="public_event_access", requirements={"id": "\d+"})
     * @param Event $event
     * @param string $uuid
     * @return Response
     */
    public function access(Event $event, string $uuid): Response
    {
        // Si l'événement est terminé,
        // Rediriger vers l'événement
        if ($event->getBeginAt() < new \DateTime()) {

            return $this->redirectToRoute('public_event_show', [
                'slug' => $event->getSlug(),
            ]);
        }

        $guest = $this->getGuestByUuid($event, $uuid);

        $session = $this->request->getSession();
        $session->set('event_'.$event->getId(), $guest->getId());

        return $this->redirectToRoute('public_event_show', ['slug' => $event->getSlug()]);
    }

    /**
     * @Route("/e/{id}/{uuid}/decline", name="public_event_decline", requirements={"id": "\d+"})
     * @param Event $event
     * @param string $uuid
     * @return Response
     */
    public function decline(Event $event, string $uuid): Response
    {
        // Affiche que l'événement est terminé si la date de début est passée
        if ($event->getBeginAt() < new \DateTime()) {

            return $this->returnEnded($event);
        }

        // Récupérer l'invité
        $guest = $this->getGuestByUuid($event, $uuid);

				if ($guest->isSwitched()) {

					$currentGuest = $guest->getCurrentGuest();

					if ($currentGuest) {
						$guest = $currentGuest;
						$event = $currentGuest->getEvent();
					}
				}

        // Si l'invité a déjà décliné, afficher la page "décliné"
        if ( $guest->isDeclined() ) return $this->returnDecline($event);

        $sessionName = 'event_'.$event->getId();

        $this->request->getSession()->set($sessionName, $guest->getId());

        // Sinon, on affiche un formulaire
        $form = $this->createFormBuilder([])->add('confirm', SubmitType::class, [
            'label' => 'Décliner',
        ]);

        $form = $form->getForm();
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->request->getSession()->remove($sessionName);

            // Supprimer les temps forts de l'invité
            $momentChoices = $guest->getMomentChoices();
            foreach ($momentChoices as $choice) {
                $this->om->remove($choice);
            }
            $guest->getMomentChoices()->clear();

            $guest->setDeclined();

            $this->om->persist($guest);
            $this->om->flush();

            return $this->returnDecline($event);
        }

        return $this->render('front/decline.html.twig', [
            'form' => $form->createView(),
            'legal' => $this->om->getRepository(Option::class)->findLegal(),
            'event' => $event,
        ]);
    }

    private function returnDecline(Event $event): Response
    {
        $this->clearSession($event);

        return $this->render('front/message.html.twig', [
            'message' => 'Vous êtes maintenant désinscrit de notre événement.',
            'body_class' => 'login'
        ]);
    }

    private function returnSuccess(Event $event): Response
    {
        $this->clearSession($event);

        $message = $this->twig->createTemplate('Vous êtes maintenant inscrit à notre événement. Rendez-vous '.
            '{{ date|format_date(pattern="eeee d MMMM y, à H:mm", locale="fr") }}.');

        return $this->render('front/message.html.twig', [
            'message' => $this->twig->render($message, ['date' => $event->getBeginAt()]),
            'body_class' => 'login'
        ]);
    }

    private function returnFull(Event $event): Response
    {
        $this->clearSession($event);

        return $this->render('front/message.html.twig', [
            'pageTitle' => 'L\'événement est complet',
            'body_class' => 'login',
            'message' => 'Malheureusement, il ne reste plus de places disponibles pour cet événement.',
        ]);
    }

    private function returnEnded(Event $event): Response
    {
        $this->clearSession($event);

        return $this->render('front/message.html.twig', [
            'pageTitle' => 'L\'événement est passé',
            'body_class' => 'login',
            'message' => 'Les inscriptions sont maintenant fermées.',
        ]);
    }

    private function clearSession(Event $event)
    {
        $session = $this->request->getSession();
        $session->remove('event_'.$event->getId());
    }
}