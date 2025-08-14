<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\EmailSchedule;
use App\Entity\EmailTemplate;
use App\Entity\Event;
use App\Entity\Guest;
use App\Entity\Option;
use App\Entity\Sender;
use App\Entity\TimeSlot;
use App\Entity\User;
use App\Entity\WorkshopTimeSlot;
use App\Form\EventCollectionType;
use App\Form\EventTimeslotsType;
use App\Form\EventType;
use App\Form\GuestType;
use App\Form\VisualEventType;
use App\Repository\EmailScheduleRepository;
use App\Repository\EmailTemplateRepository;
use App\Repository\EventRepository;
use App\Repository\GuestRepository;
use App\Security\AppVoter;
use App\Security\EventVoter;
use App\Service\EventHelper;
use App\Service\FileManagerService;
use DateInterval;
use Doctrine\Persistence\ObjectManager;
use PhpOffice\PhpSpreadsheet;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

/**
 * @Route("/admin/dashboard/event")
 * @Route("/event")
 */
class EventController extends AbstractController
{
    private ObjectManager $om;
    private ?Request $request;
    private EventHelper $eventHelper;
    private FileManagerService $fileManagerService;
    private ?User $user;

    public function __construct(ObjectManager $om, RequestStack $requestStack, EventHelper $eventHelper, FileManagerService $fileManagerService, Security $security)
    {
        $this->om = $om;
        $this->request = $requestStack->getCurrentRequest();
        $this->eventHelper = $eventHelper;
        $this->fileManagerService = $fileManagerService;
        $this->user = $security->getUser();
    }

    /**
     * Event Tab : Résumé
     *
     * @Route("/", name="app_index")
     * @return Response
     */
    public function index(): Response
    {
        $this->denyAccessUnlessGranted(AppVoter::INDEX);
        $repository = $this->om->getRepository(Event::class);

        $events = $repository->findBy([], ['id' => 'DESC']);

        return $this->render('admin/event/index.html.twig', [
            'events' => $events
        ]);
    }

    /**
     * Event Tab : Événement
     *
     * @Route("/create", name="event.create")
     * @Route("/create/{type}", name="event.create.typed", requirements={"type": "evenement|projection|ateliers|collection|golfcup|standard_plus_moments"})
     * @Route("/{id}/edit", name="event.edit", requirements={"id": "\d+"})
     * @param Event|null $event
     * @return Response
     */
    public function event(Event $event = null, string $type = null): Response
    {
        if ($event) {
            $this->denyAccessUnlessGranted(EventVoter::VIEW, $event);
        } else {
            $this->denyAccessUnlessGranted(AppVoter::CREATE);
        }

        $eventRepository = $this->om->getRepository(Event::class);
        $isNew = $event === null;

        if ($isNew) {
            $event = new Event();
            $event->setUser($this->user);
            if ($type) {
                $event->setType($type);
            }
        }

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($this->request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {

                if (!$event->getSlug()) {
                    // Génère un slug unique
                    $baseSlug = $this->eventHelper->slugify($event->getName());
                    $slug = $baseSlug;
                    $counter = 1;

                    while ($eventRepository->findOneBy(['slug' => $slug])) {
                        $slug = $baseSlug . '-' . $counter;
                        $counter++;
                    }

                    $event->setSlug($slug);
                }

                $this->om->persist($event);
                $this->om->flush();

                if ($isNew) {
                    $this->addAlert('success', 'Evénement créé.');
                    return $this->redirectToRoute('event.edit', ['id' => $event->getId()]);
                } else {
                    $this->addAlert('success', 'Evénement modifié.');
                }
            }
        }

        $isEventClosed = $event->getDateEnd() && $event->getDateEnd() < new \DateTime();

        return $this->render('admin/event/event.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
            'isEventClosed' => $isEventClosed
        ]);
    }

    /**
     * Event Tab : Créneau horaire d'atelier
     * @Route("/{id}/timeslots", name="event.timeslots", requirements={"id": "\d+"})
     * @param Event $event
     * @return Response
     */
    public function timeslots(Event $event): Response
    {
        $this->denyAccessUnlessGranted(EventVoter::VIEW, $event);

        $form = $this->createForm(EventTimeslotsType::class, $event);
        $form->handleRequest($this->request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {

                // Supprime les créneaux qui ne sont plus dans la nouvelle liste
                $timeslotRepository = $this->om->getRepository(TimeSlot::class);
                $existingTimeslots = $timeslotRepository->findBy(['event' => $event]);

                foreach ($existingTimeslots as $existingTimeslot) {
                    $found = false;
                    foreach ($event->getTimeSlots() as $newTimeslot) {
                        if ($existingTimeslot->getId() === $newTimeslot->getId()) {
                            $found = true;
                            break;
                        }
                    }

                    if (!$found) {
                        $this->om->remove($existingTimeslot);
                    }
                }

                // Associe les nouveaux créneaux à l'événement
                foreach ($event->getTimeSlots() as $timeslot) {
                    $timeslot->setEvent($event);
                    $this->om->persist($timeslot);
                }

                $this->om->persist($event);
                $this->om->flush();

                $this->addAlert('success', 'Créneaux modifiés.');
            }
        }

        return $this->render('admin/event/timeslots.html.twig', [
            'event' => $event,
            'form' => $form->createView()
        ]);
    }

    /**
     * Event Tab : Visuels de l'événement
     *
     * @Route("/{id}/visuals", name="event.visuals", requirements={"id": "\d+"})
     * @param Event $event
     * @return Response
     */
    public function visuals(Event $event): Response
    {
        $this->denyAccessUnlessGranted(EventVoter::VIEW, $event);

        $form = $this->createForm(VisualEventType::class, $event);
        $form->handleRequest($this->request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {

                // Supprimer l'ancien fichier si un nouveau est uploadé
                if ($event->getEmailVisual() && $event->getEmailVisual()->getFile()) {
                    $this->om->remove($event->getEmailVisual());
                    $event->setEmailVisual(null);
                }

                if ($event->getEmailUpVisual() && $event->getEmailUpVisual()->getFile()) {
                    $this->om->remove($event->getEmailUpVisual());
                    $event->setEmailUpVisual(null);
                }

                if ($event->getEmailReminderVisual() && $event->getEmailReminderVisual()->getFile()) {
                    $this->om->remove($event->getEmailReminderVisual());
                    $event->setEmailReminderVisual(null);
                }

                if ($event->getEmailThanksVisual() && $event->getEmailThanksVisual()->getFile()) {
                    $this->om->remove($event->getEmailThanksVisual());
                    $event->setEmailThanksVisual(null);
                }

                $this->om->persist($event);
                $this->om->flush();

                $this->addAlert('success', 'Visuels modifiés.');
            }
        }

        return $this->render('admin/event/visuals.html.twig', [
            'event' => $event,
            'form' => $form->createView()
        ]);
    }

    /**
     * Event Tab : Invités
     *
     * @Route("/{id}/guests", name="event.guests", requirements={"id": "\d+"})
     * @param Event $event
     * @return Response
     */
    public function guests(Event $event): Response
    {
        $this->denyAccessUnlessGranted(EventVoter::VIEW, $event);

        $guests = $this->om->getRepository(Guest::class)->findBy(['event' => $event], ['id' => 'DESC']);

        return $this->render('admin/event/guests.html.twig', [
            'event' => $event,
            'guests' => $guests
        ]);
    }

    /**
     * Event Tab : Import d'invité
     *
     * @Route("/{id}/guests/import", name="event.guests.import", requirements={"id": "\d+"})
     * @param Event $event
     * @return Response
     */
    public function guestsImport(Event $event): Response
    {
        $this->denyAccessUnlessGranted(EventVoter::EDIT, $event);

        $importForm = $this->createFormBuilder()
            ->add('file', FileType::class, [
                'label' => 'Fichier Excel',
                'required' => true,
                'attr' => ['accept' => '.xlsx,.xls'],
                'help' => 'Fichiers Excel uniquement (.xlsx, .xls)'
            ])
            ->add('skip_rows', IntegerType::class, [
                'label' => 'Lignes à ignorer',
                'data' => 0,
                'required' => false,
                'attr' => ['min' => 0],
                'help' => 'Lignes initiales à supprimer à l\'import.',
            ])
            ->add('skip_cols', IntegerType::class, [
                'label' => 'Colonnes à ignorer',
                'data' => 0,
                'required' => false,
                'attr' => ['min' => 0],
                'help' => 'Colonnes initiales à supprimer à l\'import.',
            ])
            ->add('import', SubmitType::class, [
                'label' => 'Importer',
                'attr' => ['class' => 'btn-primary']
            ])
            ->getForm();

        $importForm->handleRequest($this->request);

        if ($importForm->isSubmitted() && $importForm->isValid()) {
            $file = $importForm->get('file')->getData();
            $skipRows = $importForm->get('skip_rows')->getData() ?: 0;
            $skipCols = $importForm->get('skip_cols')->getData() ?: 0;

            if ($file instanceof UploadedFile) {
                try {
                    $spreadsheet = PhpSpreadsheet\IOFactory::load($file->getPathname());
                    $worksheet = $spreadsheet->getActiveSheet();
                    $rows = $worksheet->toArray();

                    $importedCount = 0;
                    $errors = [];

                    // En-têtes (après avoir sauté les lignes et colonnes spécifiées)
                    $headers = [];
                    if (count($rows) > $skipRows) {
                        $headerRow = $rows[$skipRows];
                        for ($i = $skipCols; $i < count($headerRow); $i++) {
                            $headers[] = $headerRow[$i];
                        }
                    }

                    // Données
                    for ($rowIndex = $skipRows + 1; $rowIndex < count($rows); $rowIndex++) {
                        $row = $rows[$rowIndex];

                        // Ignore empty rows
                        if (empty(array_filter($row))) {
                            continue;
                        }

                        try {
                            // Supprimer les colonnes initiales
                            $dataRow = [];
                            for ($i = $skipCols; $i < count($row); $i++) {
                                $dataRow[] = $row[$i] ?? '';
                            }

                            $guest = new Guest();
                            $guest->setEvent($event);

                            // Mapping des colonnes (ajuster selon votre structure)
                            if (isset($dataRow[0])) $guest->setFirstname($dataRow[0]);
                            if (isset($dataRow[1])) $guest->setLastname($dataRow[1]);
                            if (isset($dataRow[2])) $guest->setEmail($dataRow[2]);
                            if (isset($dataRow[3])) $guest->setPhone($dataRow[3]);
                            if (isset($dataRow[4])) $guest->setCompany($dataRow[4]);
                            if (isset($dataRow[5])) $guest->setFunction($dataRow[5]);

                            // Validation de base
                            if (empty($guest->getFirstname()) && empty($guest->getLastname())) {
                                $errors[] = "Ligne {$rowIndex}: Nom ou prénom requis";
                                continue;
                            }

                            $this->om->persist($guest);
                            $importedCount++;

                        } catch (\Exception $e) {
                            $errors[] = "Ligne {$rowIndex}: " . $e->getMessage();
                        }
                    }

                    $this->om->flush();

                    $this->addAlert('success', "$importedCount invité(s) importé(s).");

                    if (!empty($errors)) {
                        foreach (array_slice($errors, 0, 10) as $error) { // Limite à 10 erreurs
                            $this->addAlert('warning', $error);
                        }
                        if (count($errors) > 10) {
                            $this->addAlert('info', '... et ' . (count($errors) - 10) . ' autres erreurs.');
                        }
                    }

                    return $this->redirectToRoute('event.guests', ['id' => $event->getId()]);

                } catch (\Exception $e) {
                    $this->addAlert('error', 'Erreur lors de l\'import: ' . $e->getMessage());
                }
            }
        }

        return $this->render('admin/event/guests_import.html.twig', [
            'event' => $event,
            'importForm' => $importForm->createView()
        ]);
    }

    /**
     * Event Tab : Expéditeurs
     *
     * @Route("/{id}/senders", name="event.senders", requirements={"id": "\d+"})
     * @param Event $event
     * @return Response
     */
    public function senders(Event $event): Response
    {
        $this->denyAccessUnlessGranted(EventVoter::VIEW, $event);

        $senders = $this->om->getRepository(Sender::class)->findBy(['event' => $event], ['id' => 'ASC']);

        return $this->render('admin/event/senders.html.twig', [
            'event' => $event,
            'senders' => $senders
        ]);
    }

    /**
     * Event Tab : Templates d'email
     *
     * @Route("/{id}/emails", name="event.emails", requirements={"id": "\d+"})
     * @param Event $event
     * @return Response
     */
    public function emails(Event $event): Response
    {
        $this->denyAccessUnlessGranted(EventVoter::VIEW, $event);

        $emailTemplates = $this->om->getRepository(EmailTemplate::class)->findBy(['event' => $event], ['id' => 'ASC']);

        return $this->render('admin/event/emails.html.twig', [
            'event' => $event,
            'emailTemplates' => $emailTemplates
        ]);
    }

    /**
     * Event Tab : Planifications d'email
     *
     * @Route("/{id}/schedules", name="event.schedules", requirements={"id": "\d+"})
     * @param Event $event
     * @return Response
     */
    public function schedules(Event $event): Response
    {
        $this->denyAccessUnlessGranted(EventVoter::VIEW, $event);

        $emailSchedules = $this->om->getRepository(EmailSchedule::class)->findBy(['event' => $event], ['scheduledAt' => 'ASC']);

        return $this->render('admin/event/schedules.html.twig', [
            'event' => $event,
            'emailSchedules' => $emailSchedules
        ]);
    }

    /**
     * Duplication d'événement
     * @Route("/{id}/duplicate", name="event.duplicate", requirements={"id": "\d+"})
     * @param Event $originalEvent
     * @return Response
     */
    public function duplicate(Event $originalEvent): Response
    {
        $this->denyAccessUnlessGranted(EventVoter::VIEW, $originalEvent);
        $this->denyAccessUnlessGranted(AppVoter::CREATE);

        $form = $this->createFormBuilder()
            ->add('duplicate', SubmitType::class, [
                'label' => 'Dupliquer',
                'attr' => ['class' => 'btn-primary']
            ])
            ->getForm();

        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Clone l'événement
            $newEvent = clone $originalEvent;
            $newEvent->setName($originalEvent->getName() . ' (Copie)');
            $newEvent->setSlug(null); // Will be generated automatically
            $newEvent->setUser($this->user);

            // Reset dates to null or future
            $newEvent->setDateStart(null);
            $newEvent->setDateEnd(null);
            $newEvent->setDateLimit(null);

            // Clear collections and relations
            $newEvent->getGuests()->clear();
            $newEvent->getSenders()->clear();
            $newEvent->getEmailTemplates()->clear();
            $newEvent->getEmailSchedules()->clear();
            $newEvent->getTimeSlots()->clear();
            $newEvent->getWorkshops()->clear();
            $newEvent->getViews()->clear();
            $newEvent->getManagers()->clear();
            $newEvent->getViewers()->clear();

            // Clear files/attachments
            $newEvent->setVisual(null);
            $newEvent->setTicketVisual(null);
            $newEvent->setLogo(null);
            $newEvent->setPoster(null);
            $newEvent->setMoviePoster(null);
            $newEvent->setEmailVisual(null);
            $newEvent->setEmailUpVisual(null);
            $newEvent->setEmailReminderVisual(null);
            $newEvent->setEmailThanksVisual(null);

            $this->om->persist($newEvent);
            $this->om->flush();

            $this->addAlert('success', 'Événement dupliqué avec succès.');
            return $this->redirectToRoute('event.edit', ['id' => $newEvent->getId()]);
        }

        return $this->render('admin/confirm.html.twig', [
            'formConfirm' => $form->createView(),
            'formTitle' => 'Dupliquer l\'événement ?',
            'item' => $originalEvent
        ]);
    }

    /**
     * Archive/Désarchiver un événement
     * @Route("/{id}/archive", name="event.archive", requirements={"id": "\d+"})
     */
    public function archive(Event $event): Response
    {
        $this->denyAccessUnlessGranted(EventVoter::EDIT, $event);

        $form = $this->createFormBuilder()
            ->add('confirm', SubmitType::class, [
                'label' => $event->isArchived() ? 'Désarchiver' : 'Archiver',
                'attr' => ['class' => 'btn-warning']
            ])
            ->getForm();

        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event->setArchived(!$event->isArchived());
            $this->om->flush();

            $this->addAlert('success', $event->isArchived() ? 'Événement archivé.' : 'Événement désarchivé.');
            return $this->redirectToRoute('app_index');
        }

        return $this->render('admin/confirm.html.twig', [
            'formConfirm' => $form->createView(),
            'formTitle' => ($event->isArchived() ? 'Désarchiver' : 'Archiver') . ' l\'événement ?',
            'item' => $event
        ]);
    }

    /**
     * Collection d'événement
     * @Route("/{id}/collection", name="event.collection", requirements={"id": "\d+"})
     * @param Event $event
     * @return Response
     */
    public function collection(Event $event): Response
    {
        $this->denyAccessUnlessGranted(EventVoter::VIEW, $event);

        if ($event->getType() !== 'collection') {
            throw $this->createNotFoundException('Cet événement n\'est pas de type collection.');
        }

        $form = $this->createForm(EventCollectionType::class, $event);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Supprimer l'ancien fichier si un nouveau est uploadé
            if ($event->getVisual() && $event->getVisual()->getFile()) {
                $this->om->remove($event->getVisual());
                $event->setVisual(null);
            }

            if ($event->getTicketVisual() && $event->getTicketVisual()->getFile()) {
                $this->om->remove($event->getTicketVisual());
                $event->setTicketVisual(null);
            }

            if ($event->getLogo() && $event->getLogo()->getFile()) {
                $this->om->remove($event->getLogo());
                $event->setLogo(null);
            }

            if ($event->getPoster() && $event->getPoster()->getFile()) {
                $this->om->remove($event->getPoster());
                $event->setPoster(null);
            }

            if ($event->getMoviePoster() && $event->getMoviePoster()->getFile()) {
                $this->om->remove($event->getMoviePoster());
                $event->setMoviePoster(null);
            }

            $this->om->persist($event);
            $this->om->flush();

            $this->addAlert('success', 'Collection modifiée.');
        }

        return $this->render('admin/event/collection.html.twig', [
            'event' => $event,
            'form' => $form->createView()
        ]);
    }

    /**
     * Suppression d'événement
     * @Route("/{id}/delete", name="event.delete", requirements={"id": "\d+"})
     * @param Event $event
     * @return Response
     */
    public function delete(Event $event): Response
    {
        $this->denyAccessUnlessGranted(EventVoter::EDIT, $event);

        $form = $this
            ->createFormBuilder()
            ->add('confirm', SubmitType::class, [
                'label' => 'Oui',
                'attr' => [
                    'class' => 'btn-danger'
                ]
            ]);

        $form = $form->getForm();
        $form->handleRequest($this->request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $connection = $this->om->getConnection();
                
                try {
                    $connection->beginTransaction();
                    
                    // Supprimer les plannings d'emails en premier car ils dépendent des expéditeurs
                    $connection->executeStatement('DELETE FROM email_schedule WHERE event_id = ?', [$event->getId()]);
                    
                    // Supprimer les templates d'emails
                    $connection->executeStatement('DELETE FROM email_template WHERE event_id = ?', [$event->getId()]);
                    
                    // Supprimer les invités
                    $connection->executeStatement('DELETE FROM guest WHERE event_id = ?', [$event->getId()]);
                    
                    // Supprimer les expéditeurs
                    $connection->executeStatement('DELETE FROM sender WHERE event_id = ?', [$event->getId()]);
                    
                    // Supprimer les créneaux horaires
                    $connection->executeStatement('DELETE FROM time_slot WHERE event_id = ?', [$event->getId()]);
                    
                    // Supprimer les ateliers
                    $connection->executeStatement('DELETE FROM workshop WHERE event_id = ?', [$event->getId()]);
                    
                    // Supprimer les vues
                    $connection->executeStatement('DELETE FROM view WHERE event_id = ?', [$event->getId()]);
                    
                    // Supprimer les relations ManyToMany
                    $connection->executeStatement('DELETE FROM event_manager WHERE event_id = ?', [$event->getId()]);
                    $connection->executeStatement('DELETE FROM event_viewer WHERE event_id = ?', [$event->getId()]);
                    
                    // Supprimer tous les dossiers d'attachments associés à l'événement
                    $this->fileManagerService->deleteEventAttachments($event->getId());
                    
                    // Supprimer l'événement
                    $connection->executeStatement('DELETE FROM event WHERE id = ?', [$event->getId()]);
                    
                    $connection->commit();
                    
                    $this->addAlert('success', 'Evénement supprimé.');
                    
                    return $this->redirectToRoute('app_index');
                    
                } catch (\Exception $e) {
                    $connection->rollBack();
                    throw $e;
                }
            }
        }

        return $this->render('admin/confirm.html.twig', [
            'formConfirm' => $form->createView(),
            'formTitle' => 'Confirmer la suppression de l\'événement ? Cette action est irréversible.',
            'item' => $event
        ]);
    }

    /**
     * Export de données
     * @Route("/{id}/export", name="event.export", requirements={"id": "\d+"})
     * @param Event $event
     * @return Response
     */
    public function export(Event $event): Response
    {
        $this->denyAccessUnlessGranted(EventVoter::VIEW, $event);

        $guests = $this->om->getRepository(Guest::class)->findBy(['event' => $event]);

        // Créer le fichier Excel
        $spreadsheet = new PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // En-têtes
        $headers = ['Prénom', 'Nom', 'Email', 'Téléphone', 'Entreprise', 'Fonction', 'Statut', 'Date d\'inscription'];
        $sheet->fromArray($headers, null, 'A1');

        // Données
        $row = 2;
        foreach ($guests as $guest) {
            $data = [
                $guest->getFirstname(),
                $guest->getLastname(),
                $guest->getEmail(),
                $guest->getPhone(),
                $guest->getCompany(),
                $guest->getFunction(),
                $guest->getStatus(),
                $guest->getCreatedAt() ? $guest->getCreatedAt()->format('d/m/Y H:i') : ''
            ];
            $sheet->fromArray($data, null, 'A' . $row);
            $row++;
        }

        // Configurer la réponse
        $filename = 'invites_' . $event->getSlug() . '_' . date('Y-m-d') . '.xlsx';

        $writer = new PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $response = new Response();

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        $response->setContent($content);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}