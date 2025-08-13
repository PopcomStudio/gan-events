<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\EmailSchedule;
use App\Entity\Event;
use App\Entity\Guest;
use App\Entity\GuestHistory;
use App\Entity\User;
use App\Entity\WorkshopTimeSlot;
use App\Form\GuestType;
use App\Service\EventHelper;
use App\Service\GenPdf;
use App\Service\Mailer;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use App\Entity\GuestMomentChoice;
use App\Entity\EventMoment;

/**
 * @Route("/guest")
 */
class GuestController extends AbstractController
{
    private ObjectManager $om;
    private ?Request $request;
    private EventHelper $eventHelper;
    private User $user;
    private GenPdf $genPdf;

    public function __construct(ObjectManager $om, RequestStack $requestStack, EventHelper $eventHelper, Security $security, GenPdf $genPdf)
    {
        $this->om = $om;
        $this->request = $requestStack->getCurrentRequest();
        $this->eventHelper = $eventHelper;
        $this->user = $security->getUser();
        $this->genPdf = $genPdf;
    }

    /**
     * @Route("/{id}", name="guest_actions", requirements={"id": "\d+"})
     * @param Guest $guest
     * @return Response
     */
    public function actions(Guest $guest): Response
    {
        return $this->render('admin/guest/actions.modal.html.twig', [
            'guest' => $guest,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="guest_edit", requirements={"id": "\d+"})
     * @param Guest $guest
     * @return Response
     */
    public function guestEdit(Guest $guest): Response
    {
        $this->denyAccessUnlessGranted('CONTACTS', $guest->getEvent());

        $form = $this->createForm(GuestType::class, $guest, [
            'context' => GuestType::CONTEXT_EDIT,
            'type' => $guest->getSender()->getGuestType(),
            'event_type' => $guest->getEvent()->getType(),
        ]);
        $form->handleRequest($this->request);

        $renderArgs = ['form' => $form->createView()];

        if ($form->isSubmitted()) {

            if ($form->isValid()) {
                $data = $this->request->request->get('guest');

                if ($guest->getEvent()->getType() === Event::TYPE_STANDARD_PLUS_MOMENTS) {
                    // Gestion des temps forts
                    $momentChoices = $form->get('momentChoices')->getData();
                    
                    // On vide d'abord la collection
                    foreach ($guest->getMomentChoices() as $choice) {
                        $guest->removeMomentChoice($choice);
                    }
                    
                    // On ajoute les nouveaux choix
                    foreach ($momentChoices as $choice) {
                        $momentChoice = new GuestMomentChoice();
                        $momentChoice->setGuest($guest);
                        $momentChoice->setMoment($choice);
                        $guest->addMomentChoice($momentChoice);
                    }
                    
                    // On ajoute automatiquement les temps forts obligatoires
                    $obligatoryMoments = $this->om->getRepository(EventMoment::class)->findBy([
                        'event' => $guest->getEvent(),
                        'type' => 'obligatoire'
                    ]);

                    foreach ($obligatoryMoments as $moment) {
                        $momentChoice = new GuestMomentChoice();
                        $momentChoice->setGuest($guest);
                        $momentChoice->setMoment($moment);
                        $guest->addMomentChoice($momentChoice);
                    }
                }

                $this->om->persist($guest);
                $this->om->flush();

                $this->addAlert('success');

                unset($renderArgs['form']);

            } else {

                $this->addAlert('danger');
            }
        }

        return $this->render('admin/guest/edit.modal.html.twig', $renderArgs);
    }

    /**
     * @Route("/{id}/register", name="guest_register", requirements={"id": "\d+"})
     * @param Guest $guest
     * @return Response
     */
    public function register(Guest $guest): Response
    {
        $this->denyAccessUnlessGranted('CONTACTS', $guest->getEvent());

        $form = $this->createForm(GuestType::class, $guest, [
            'context' => GuestType::CONTEXT_FORCE_REGISTER,
            'type' => $guest->getSender()->getGuestType(),
            'event_type' => $guest->getEvent()->getType(),
        ]);
        $form->handleRequest($this->request);

        if ($form->isSubmitted()) {

            if ($form->isValid()) {

                // ToDo : Comment éviter de dupliquer tout ce code

                $data = $this->request->request->get('guest');

                // Si c'est un événement golf et que l'invité participe à la compétition,
                // vérifier que le numéro de licence est renseigné.
                if (isset($data['golf'])) {

                    if ($data['golf'] === '1' && empty($data['golfLicense'])) {

                        $form->get('golfLicense')->addError(new FormError('Veuillez renseigner votre numéro de licence.'));
                    }
                }

                if ($guest->getEvent()->getType() === Event::TYPE_WORKSHOPS && is_array($data['workshops'])) {

                    $workshopTimeSlotFindWorkshop = $this->om->getRepository(WorkshopTimeSlot::class)->findByIds($data['workshops']);

                    foreach ($guest->getWorkshops() as $workshop) $guest->removeWorkshop($workshop);
                    foreach ($workshopTimeSlotFindWorkshop as $workshop) $guest->addWorkshop($workshop);
                }

                if ($guest->getSidekicks()->count()) {

                    /** @var Guest $sidekick */
                    foreach ($guest->getSidekicks() as $sidekick) {

                        // ToDo : Envoyer plusieurs tickets

                        $sidekick
                            ->setParent($guest)
                            ->setSidekick()
                            ->setEvent($guest->getEvent())
                            ->setSender($guest->getSender())
                            ->setRegistered()
                        ;

                        $this->om->persist($sidekick);
                    }
                }

                if ($guest->getProspects()->count()) {

                    foreach ($guest->getProspects() as $prospect) {

                        // ToDo : Comment gérer si l'email du prospect est déjà dans la base de l'expéditeur ?

                        $prospect
                            ->setProspect()
                            ->setEvent($guest->getEvent())
                            ->setSender($guest->getSender())
                        ;

                        $this->om->persist($prospect);
                    }
                }

                $this->om->persist($guest->setRegistered());
                $this->om->flush();

                $this->addAlert('success');

                return $this->redirectToRoute('app_event_contacts', [
                    'id' => $guest->getEvent()->getId(),
                ]);
            }

            $this->addAlert('danger');
        }

        return $this->render('admin/event/tabs/contacts.register.html.twig', [
            'form' => $form->createView(),
            'guest' => $guest,
            'formButton' => 'Inscrire',
        ]);
    }

    /**
     * @Route("/{id}/switch", name="guest_switch", requirements={"id": "\d+"})
     * @param Guest $guest
     * @return Response
     */
    public function switch(Guest $guest): Response
    {
        $this->denyAccessUnlessGranted('CONTACTS', $guest->getEvent());

        $events = $guest->getEvent()->getParent()->getOtherEvents($guest->getEvent());

        if (count($events) === 0) {

            $this->addAlert('warning', 'Aucune date disponible.');
            return $this->render('admin/guest/switch.modal.html.twig');
        }

        $choices = [];
        foreach ($events as $event) {

            $label = sprintf('%s, %s', $event->getName(), $event->getBeginAt()->format('d/m/Y'));
            $choices[$label] = $event->getId();
        }

        $form = $this
            ->createFormBuilder([])
            ->add('event', ChoiceType::class, [
                'label' => false,
                'choices' => $choices,
                'expanded' => true,
            ])
            ->getForm();

        $form->handleRequest($this->request);

        $renderArgs = ['form' => $form->createView()];

        if ($form->isSubmitted()) {

            if ($form->isValid()) {

                $eventId = $form->getData()['event'];

                foreach ($events as $newEvent) {

                    if ($newEvent->getId() === $eventId) break;
                }

                $guest->setEvent($newEvent)->setSender($newEvent->getSenders()->first());


                $this->om->persist($guest);
                $this->om->flush();

                $this->addAlert('success');

                unset($renderArgs['form']);

            } else {

                $this->addAlert('danger');
            }
        }

        return $this->render('admin/guest/switch.modal.html.twig', $renderArgs);
    }

    /**
     * @Route("/{id}/status/{status}", name="guest_status", requirements={"id": "\d+", "status": "pending|registered|declined|participated"})
     * @param Guest $guest
     * @param string $status
     * @return Response
     */
    public function changeStatus(Guest $guest, string $status): Response
    {
        if ($guest->getStatus() !== $status) {
            // Si l'invité est désinscrit (status = pending), supprimer ses temps forts
            if ($status === Guest::STATUS_PENDING) {
                // Récupérer tous les choix de moments
                $momentChoices = $guest->getMomentChoices();
                // Supprimer chaque choix
                foreach ($momentChoices as $choice) {
                    $this->om->remove($choice);
                }
                // Vider la collection
                $guest->getMomentChoices()->clear();
                // Flush pour supprimer les enregistrements
                $this->om->flush();
            }

            $guest->setStatus($status);
            $this->om->persist($guest);
            $this->om->flush();
        }

        return $this->redirectToRoute('app_event_contacts', ['id' => $guest->getEvent()->getId()]);
    }

    /**
     * @Route("/{id}/delete",
     *     name="guest_delete",
     *     requirements={"id": "\d+"},
     * )
     * @param Guest $guest
     * @return Response
     */
    public function delete(Guest $guest): Response
    {
        $this->denyAccessUnlessGranted('CONTACTS', $guest->getEvent());

        $this->om->remove($guest);
        $this->om->flush();

        $this->addAlert('success', 'Suppression réussie.');

        return $this->redirectToRoute('app_event_contacts', ['id' => $guest->getEvent()->getId()]);
    }

    /**
     * @Route("/{id}/resend", name="guest_resend", requirements={"id": "\d+"})
     * @param Guest $guest
     * @param Request $request
     * @param Mailer $mailer
     * @return Response
     */
    public function resend(Guest $guest, Request $request, Mailer $mailer): Response
    {
        $event = $guest->getEvent();

        $this->denyAccessUnlessGranted('MANAGE_EMAIL', $event);

        $send = false;

        if (!$this->isGranted('EDIT', $event) && !$this->eventHelper->getCurrentSender()->getAutonomyOnSchedule()) return $this->render('admin/guest/email-resend-nothing.modal.html.twig');

        $emailSchedules = $this->om->getRepository(EmailSchedule::class)
            ->findCurrSchedules($event, $guest->getSender(), true);

        if (!$emailSchedules) return $this->render('admin/guest/email-resend-nothing.modal.html.twig');

        if ($request->request->get('schedule_id')) {

            $schedule = $this->om->getRepository(EmailSchedule::class)->find($request->request->get('schedule_id'));

            if ($schedule) {

                $history = new GuestHistory($guest, $schedule);

                $mailer->sendEventMessage($guest, $schedule);

                $this->om->persist($history);
                $this->om->flush();
                $send = true;
            }
        }

        return $this->render('admin/guest/email-resend.modal.html.twig', [
            'event' => $event,
            'emailSchedules' => $emailSchedules,
            'guest' => $guest,
            'send' => $send,
        ]);
    }

    /**
     * @Route("/{id}/invitation", name="guest_invitation", requirements={"id": "\d+"})
     * @param Guest $guest
     * @return Response
     */
    public function invitation(Guest $guest): Response
    {
        return $this->genPdf->render($this->genPdf->invitation($guest), 'invitation-'.$guest->getId());
    }
}