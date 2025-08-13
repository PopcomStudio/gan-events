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
use DateInterval;
use Doctrine\Persistence\ObjectManager;
use PhpOffice\PhpSpreadsheet;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/event")
 */
class EventController extends AbstractController
{
    private ObjectManager $om;
    private ?Request $request;
    private EventHelper $eventHelper;
    private ?User $user;

    public function __construct(ObjectManager $om, RequestStack $requestStack, EventHelper $eventHelper, Security $security)
    {
        $this->om = $om;
        $this->request = $requestStack->getCurrentRequest();
        $this->eventHelper = $eventHelper;
        $this->user = $security->getUser();
    }

    /**
     * Event Tab : Résumé
     *
     * @Route("/{id}", name="app_event_resume", requirements={"id": "\d+"})
     * @Entity("event", expr="repository.findResumeTab(id)")
     * @param Event $event
     * @param GuestRepository $guestRepository
     * @return Response
     */
    public function dashboard(Event $event, GuestRepository $guestRepository): Response
    {
        $this->denyAccessUnlessGranted('DASHBOARD', $event);

        $currSender = $this->eventHelper->getCurrentSender();
        $workshopsEvent = $event->getWorkshops()->getValues();

        $renderParams = [
            'event' => $event,
            'sender' => $currSender,
            'senderInfo' => null,
            'workshops' => $workshopsEvent
        ];

        if ( $currSender ) $this->senderDashboard($event, $guestRepository, $renderParams);
        else $this->managerDashboard($event, $guestRepository, $renderParams);

        $renderParams['emailPrevSchedules'] = $this
            ->om
            ->getRepository(EmailSchedule::class)
            ->findPrevSchedules($event, $currSender)
        ;

        $renderParams['emailNextSchedules'] = $this
            ->om
            ->getRepository(EmailSchedule::class)
            ->findNextSchedules($event, $currSender)
        ;

        return $this->render('admin/event/tabs/dashboard.html.twig', $renderParams);
    }

    public function managerDashboard(Event $event, GuestRepository $repository, array &$renderParams): void
    {
        $currentDate = new \DateTime();

        // L'événement est ouvert, on calcule les statistiques
        if ( $event->getClosedAt() === null ) {

            $renderParams['eventInfo']['totalTickets'] = $repository->getTotalTickets($event);
            $renderParams['eventInfo']['totalGuests'] = $repository->getTotalContacts($event);
            $renderParams['eventInfo']['totalReplies'] = $repository->getTotalSupplies($event);
            $renderParams['eventInfo']['totalContactees'] = $repository->getTotalContacted($event);
            $renderParams['eventInfo']['totalParticipated'] = $repository->getTotalParticipated($event);

        }
        elseif ($event->getBeginAt() < $currentDate) {

            $renderParams['eventInfo']['totalParticipated'] = 0;

            foreach($event->getSenders() as $sender){
                $renderParams['eventInfo']['totalParticipated'] += $sender->getStat()['totalParticipated'];
            }
        }

        // L'événement est clôturé, on récupère et additionne les statistiques des expéditeurs stockées en base
        else {
            $renderParams['eventInfo']['totalTickets'] = 0;
            $renderParams['eventInfo']['totalGuests'] = 0;
            $renderParams['eventInfo']['totalReplies'] = 0;
            $renderParams['eventInfo']['totalContactees'] = 0;
            $renderParams['eventInfo']['totalParticipated'] = 0;

            foreach($event->getSenders() as $sender){

                $renderParams['eventInfo']['totalTickets'] += $sender->getStat()['totalTickets'];
                $renderParams['eventInfo']['totalGuests'] += $sender->getStat()['totalGuests'];
                $renderParams['eventInfo']['totalReplies'] += $sender->getStat()['totalReplies'];
                $renderParams['eventInfo']['totalContactees'] += $sender->getStat()['totalContactees'];
                $renderParams['eventInfo']['totalParticipated'] += $sender->getStat()['totalParticipated'];
            }
        }

        // Si l'événement a un nombre d'entrées limitées, on calcule le pourcentage d'entrées consommées
        if ( $event->getTotalTickets() ) {

            $renderParams['eventInfo']['percentTickets'] = round($renderParams['eventInfo']['totalTickets'] * 100 / $event->getTotalTickets());
        }

        //
        if ($renderParams['eventInfo']['totalGuests']) {

            $renderParams['eventInfo']['percentReplies'] = round($renderParams['eventInfo']['totalReplies'] * 100 / $renderParams['eventInfo']['totalGuests']);
        }
    }

    public function senderDashboard(Event $event, GuestRepository $repository, array &$renderParams): void
    {
        $currentDate = new \DateTime();

        $sender = $this->eventHelper->getCurrentSender();

        // L'événement est ouvert, on calcule les statistiques
        if ( $event->getClosedAt() === null ) {

            $renderParams['senderInfo'] = [];

            $renderParams['senderInfo']['totalTickets'] = $repository->getTotalTickets($sender);
            $renderParams['senderInfo']['totalGuests'] = $repository->getTotalContacts($sender);
            $renderParams['senderInfo']['totalReplies'] = $repository->getTotalSupplies($sender);
            $renderParams['senderInfo']['totalContactees'] = $repository->getTotalContacted($sender);
            $renderParams['senderInfo']['totalParticipated'] = $repository->getTotalParticipated($sender);
        }
        elseif ($event->getBeginAt() < $currentDate) {

            $renderParams['senderInfo']['totalParticipated'] = $sender->getStat()['totalParticipated'];
        }

        // L'événement est clôturé, on récupère les statistiques stockées en base
        else {

            $renderParams['senderInfo'] = $sender->getStat();
        }

        // Si l'expéditeur a un nombre d'entrées allouées, on calcule le pourcentage d'entrées consommées
        if ( $sender->getAllocatedTickets() ) {

            $renderParams['senderInfo']['percentTickets'] = round($renderParams['senderInfo']['totalTickets'] * 100 / $sender->getAllocatedTickets());
        }

        //
        if ( $renderParams['senderInfo']['totalGuests'] ) {

            $renderParams['senderInfo']['percentReplies'] = round($renderParams['senderInfo']['totalReplies'] * 100 / $renderParams['senderInfo']['totalGuests']);
        }
    }

    /**
     * Event Tab: Senders
     *
     * @Route("/{id}/senders", name="app_event_senders", requirements={"id": "\d+"})
     * @Entity("event", expr="repository.findSendersTab(id)")
     * @param Event $event
     * @return Response
     */
    public function senders(Event $event): Response
    {
        if ( ! $this->isGranted(EventVoter::MANAGE_SENDERS, $event) ) return $this->redirectToRoute('app_event_resume', ['id' => $event]);

        return $this->render('admin/event/tabs/senders.html.twig', [
            'event' => $event,
        ]);
    }

    /**
     * @Route("/{id}/emails/schedules", name="app_event_emails_schedules", requirements={"id": "\d+"})
     * @param Event $event
     * @param EmailScheduleRepository $scheduleRepository
     * @return Response
     */
    public function scheduleEmails(Event $event, EmailScheduleRepository $scheduleRepository): Response
    {
        $this->denyAccessUnlessGranted(EventVoter::MANAGE_EMAIL_SCHEDULES, $event);

        return $this->render('admin/event/tabs/emails.schedules.html.twig', [
            'schedules' => $scheduleRepository->findCurrSchedules($event, $this->eventHelper->getCurrentSender()),
        ]);
    }

    /**
     * @Route("/{id}/emails/templates", name="app_event_emails_templates", requirements={"id": "\d+"})
     * @param Event $event
     * @param EmailTemplateRepository $templateRepository
     * @return Response
     */
    public function templateEmails(Event $event, EmailTemplateRepository $templateRepository): Response
    {
        $this->denyAccessUnlessGranted(EventVoter::MANAGE_EMAIL_TEMPLATES, $event);

        return $this->render('admin/event/tabs/emails.templates.html.twig', [
            'templates' => $templateRepository->findCurrTemplates($event, $this->eventHelper->getCurrentSender()),
        ]);
    }

    /**
     * @Route("/{id}/emails/visuals", name="app_event_emails_visuals", requirements={"id": "\d+"})
     * @param Event $event
     * @return Response
     */
    public function visualsEmails(Event $event): Response
    {
        if ( ! $this->isGranted(EventVoter::MANAGE_EMAIL_VISUALS, $event) ) return $this->redirectToRoute('app_event_emails_schedules', ['id' => $event]);

        $form = $this->createForm(VisualEventType::class, $event);
        $form->handleRequest($this->request);

        if ($form->isSubmitted()) {

            if ($form->isValid()) {

                if (!$event->getEmailVisual()->getFile() && !$event->getEmailVisual()->getFileName()) {
                    $this->om->remove($event->getEmailVisual());
                    $event->setEmailVisual(null);
                }
                if (!$event->getEmailUpVisual()->getFile() && !$event->getEmailUpVisual()->getFileName()) {
                    $this->om->remove($event->getEmailUpVisual());
                    $event->setEmailUpVisual(null);
                }
                if (!$event->getEmailReminderVisual()->getFile() && !$event->getEmailReminderVisual()->getFileName()) {
                    $this->om->remove($event->getEmailReminderVisual());
                    $event->setEmailReminderVisual(null);
                }
                if (!$event->getEmailThanksVisual()->getFile() && !$event->getEmailThanksVisual()->getFileName()) {
                    $this->om->remove($event->getEmailThanksVisual());
                    $event->setEmailThanksVisual(null);
                }

                $this->om->persist($event);
                $this->om->flush();

                $this->addAlert(
                    'success',
                    'Visuels mis à jour.'
                );

                return $this->redirect($this->generateUrl('app_event_emails_visuals', ['id' => $event->getId()]).'#visual');
            }

            $this->addAlert('danger');
        }

        return $this->render('admin/event/tabs/emails.visuals.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Event Tab: Contacts
     *
     * @Route("/{id}/contacts", name="app_event_contacts", requirements={"id": "\d+"})
     * @param Event $event
     * @param GuestRepository $guestRepository
     * @return Response
     */
    public function contacts(Event $event, GuestRepository $guestRepository): Response
    {
        if ( ! $this->isGranted(EventVoter::MANAGE_CONTACTS, $event) ) return $this->redirectToRoute('app_event_resume', ['id' => $event->getId()]);

        $guests = $guestRepository->findList($this->eventHelper->getCurrentSender());

        return $this->render('admin/event/tabs/contacts.index.html.twig', [
            'guests' => $guests,
        ]);
    }

    /**
     * Event Tab: Contacts
     *
     * @Route("/{id}/contacts/add", name="app_event_contacts_add", requirements={"id": "\d+"})
     * @param Event $event
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function contactsAdd(Event $event, ValidatorInterface $validator): Response
    {
        if ( ! $this->isGranted(EventVoter::MANAGE_CONTACTS, $event) ) return $this->redirectToRoute('app_event_resume', ['id' => $event->getId()]);

        $form = $this->getContactForm();
        $form = $form->getForm();
        $form->handleRequest($this->request);

        if ($form->isSubmitted()) {

            if ($form->isValid()) {

                $contacts = $form->getNormData()['contacts'];
                $total = count($contacts);

                $countErrors = 0;

                $emails = [];

                /** @var Guest $contact */
                foreach ($contacts as $key => $contact) {

                    $contact
                        ->setEvent($event)
                        ->setSender($this->eventHelper->getCurrentSender())
                    ;

                    if (!in_array($contact->getEmail(), $emails)) {

                        $emails[] = $contact->getEmail();

                        $errors = $validator->validate($contact);

                        if (count($errors)) {

                            $countErrors++;
                            $form->get('contacts')->get($key)->get('email')->addError(new FormError($errors[0]->getMessage()));
                            continue;
                        }

                    } else {

                        $countErrors++;
                        $form->get('contacts')->get($key)->get('email')->addError(new FormError('Cette adresse email est déjà utilisée pour cet événement.'));
                    }

                    // GoTo

                    $this->om->persist($contact);
                }

                if ( ! $countErrors ) {

                    $this->om->flush();

                    if ($total === 0) $msg = 'Aucun contact ajouté.';
                    elseif ($total === 1) $msg = '1 contact ajouté.';
                    else $msg = $total . ' contacts ajoutés.';

                    $this->addAlert(
                        'success',
                        $msg,
                        'fas fa-check-circle'
                    );

                    return $this->redirectToRoute('app_event_contacts', [
                        'id' => $event->getId(),
                    ]);

                } else {

                    $this->addAlert(
                        'danger',
                        'Vérifiez vos informations et réessayez.',
                    );
                }

            } else {

                $this->addAlert(
                    'danger',
                    'Une erreur est survenue. Vérifiez vos informations et réessayez.',
                );
            }
        }

        return $this->render('admin/event/tabs/contacts.add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Event Tab: Contacts
     *
     * @Route("/{id}/contacts/import", name="app_event_contacts_import", requirements={"id": "\d+"})
     * @param Event $event
     * @return Response
     */
    public function contactsImport(Event $event): Response
    {
        if ( ! $this->isGranted(EventVoter::MANAGE_CONTACTS, $event) ) return $this->redirectToRoute('app_event_resume', ['id' => $event->getId()]);

        $form = $this->createFormBuilder(['thead' => 0, 'firstCol' => 0])
            ->add('file', FileType::class, [
                'label' => 'Choisir un fichier',
                'required' => true,
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '2048k',
                        'mimeTypes' => [
                            'text/csv',
                            'application/vnd.ms-excel', // .xls,
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
                        ],
                        'mimeTypesMessage' => 'Veuillez choisir un fichier compatible.',
                    ])
                ],
                'help' => '2 Mo max. (.xls, .xlsx, .csv)',
            ])
            ->add('thead', NumberType::class, [
                'label'     => 'Nombre de lignes d\'en-tête ?',
                'required'  => true,
                'help' => 'Lignes initiales à supprimer à l\'import.',
            ])
            ->add('firstCol', NumberType::class, [
                'label'     => 'Nombre de colonnes initiales ?',
                'required'  => true,
                'help' => 'Colonnes initiales à supprimer à l\'import.',
            ])
        ;

        $form = $form->getForm();
        $form->handleRequest($this->request);

        if ($form->isSubmitted()) {

            if ($form->isValid()) {

                $firstCol = $form->get('firstCol')->getData();
                $thead = $form->get('thead')->getData();

                /** @var UploadedFile $file */
                $file = $form->get('file')->getData();

                if ($file) {

                    try {
                        $read = PhpSpreadsheet\IOFactory::createReaderForFile($file->getPathname());
                        $spreadsheet = $read->load($file->getPathname());

                        $sheetCount = $spreadsheet->getSheetCount();
                        $data = [];

                        // Parcourir les feuilles
                        for ($i = 0; $i < $sheetCount; $i++) {

                            $sheet = $spreadsheet->getSheet($i);
                            $sheetData = $sheet->toArray(null, true, true, false);

                            for ($j = 0; $j < $thead; $j++) {

                                array_shift($sheetData);
                            }

                            $data = array_merge($data, $sheetData);
                        }

                        if ($data) {

                            $rowTotal = 0;
                            $emptyEmailTotal = 0;
                            $rows = [];
                            $emails = [];
                            $duplicateEmails = [];

                            foreach ($data as $rowKey => $row) {

                                // Supprimer les colonnes initiales
                                for ($i = 0; $i < $firstCol; $i++) {

                                    array_shift($row);
                                }

                                // Compter les cellules vides
                                $counter = 0;
                                foreach ($row as $value) {

                                    if (empty($value) && $value != '0') {

                                        $counter++;
                                    }
                                }

                                // Conserver les lignes où toutes les colonnes ne sont pas vides
                                if ($counter !== count($row)) {

                                    $rows[] = (new Guest())
                                        ->setGender($row[0])
                                        ->setFirstName($row[1])
                                        ->setLastName($row[2])
                                        ->setPhone($row[3])
                                        ->setEmail($row[4])
                                        ->setCompany(isset($row[5]) ? $row[5] : null)
                                        ->setSiret(isset($row[6]) ? $row[6] : null)
																				->setInspecteurCommercial($row[7]);

                                    if (!in_array($row[4], $emails)) {

                                        $emails[] = $row[4];

                                    } else {

                                        $duplicateEmails[] = array_key_last($rows);
                                    }
                                }

                                unset($data[$rowKey]);
                            }

                            $contactForm = $this->getContactForm();
                            $contactForm->get('contacts')->setData($rows);

                            return $this->render('admin/event/tabs/contacts.add.html.twig', [
                                'form' => $contactForm->getForm()->createView(),
                                'rowTotal' => $rowTotal,
                                'emptyEmailTotal' => $emptyEmailTotal,
                                'formName' => 'Vérifier votre import',
                            ]);
                        }

                    } catch (\Exception $e) {

                        $this->addAlert(
                            'danger',
                            'Vérifiez votre fichier : '.
                            'retirez les onglets inutiles, '.
                            'vérifiez l\'ordre des colonnes.'
                        );

                        return $this->redirectToRoute('app_event_contacts_import', ['id' => $event->getId()]);
                    }
                }
            }
        }

        return $this->render('admin/event/tabs/contacts.import.html.twig', [
            'form' => $form->createView(),
            'formButton' => 'Importer',
            'formButtonIcon' => 'fas fa-upload',
        ]);
    }

    private function getContactForm(): FormBuilderInterface
    {
        return $this->createFormBuilder(null)
            ->setAction($this->generateUrl('app_event_contacts_add', ['id' => $this->eventHelper->getEvent()->getId()]))
            ->add('contacts', CollectionType::class, [
                'label' => false,
                'entry_type' => GuestType::class,
                'entry_options' => [
                    'context' => GuestType::CONTEXT_ADD,
                    'event_type' => $this->eventHelper->getEvent()->getType(),
                    'sidekicks' => $this->eventHelper->getCurrentSender()->getSidekicks(),
                    'prospects' => $this->eventHelper->getCurrentSender()->getProspects(),
                    'type' => $this->eventHelper->getCurrentSender()->getGuestType(),
                ],
                'prototype_data' => (new Guest())
                    ->setSender($this->eventHelper->getCurrentSender())
                    ->setEvent($this->eventHelper->getEvent()),
                'allow_add' => true,
            ])
        ;
    }

    /**
     * Create new event form
     *
     * @Route("/new/collection", name="event_new_collection")
     */
    public function newCollection(): Response
    {
        $this->denyAccessUnlessGranted('CREATE_EVENT');

        $event = (new Event())->setType(Event::TYPE_COLLECTION)->setOwner($this->getUser());

        $form = $this->createForm(EventCollectionType::class, $event);
        $form->handleRequest($this->request);

        if ($form->isSubmitted()) {

            if ($form->isValid()) {

                $this->om->persist($event);
                $this->om->flush();

                return $this->redirectToRoute('app_index');
            }
        }

        return $this->render('admin/event/collection/edit.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
            'pageTitle' => 'Nouvelle série d\'événements',
        ]);
    }

    /**
     * Edit collection
     * @Route("/edit/collection/{id}", name="event_edit_collection", requirements={"id": "\d+"})
     */
    public function editCollection(Event $event): Response
    {
        if ( ! $this->isGranted(EventVoter::EDIT, $event) ) return $this->redirectToRoute('app_index',);

        $form = $this->createForm(EventCollectionType::class, $event);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->om->persist($event);
            $this->om->flush();

            return $this->redirectToRoute('app_collection', ['id' => $event->getId()]);
        }

       return $this->render('admin/event/tabs/edit.collection.html.twig', [
           'form' => $form->createView(),
           'event' => $event,
       ]);
    }

    /**
     * Create new event form
     *
     * @Route("/new", name="event_new")
     */
    public function new(): Response
    {
        $this->denyAccessUnlessGranted('CREATE_EVENT');

        $event = (new Event())->setOwner($this->user);

        return $this->edit($event);
    }

    /**
     * Create new event form
     *
     * @Route("/new/{id}", name="event_collection_new", requirements={"id": "\d+"})
     */
    public function newInCollection(?Event $event): Response
    {
        if ($event->getType() !== Event::TYPE_COLLECTION) $this->redirectToRoute('event_new');

        $event = (new Event())->setOwner($this->user)->setParent($event);

        return $this->edit($event);
    }

    /**
     * Edit event form
     *
     * @Route("/{id}/edit", name="app_event_edit", requirements={"id": "\d+"})
     * @param Event $event
     * @return Response
     */
    public function edit(Event $event): Response
    {
        if ( ! $this->isGranted(EventVoter::EDIT, $event) || $this->eventHelper->getCurrentSender() ) return $this->redirectToRoute('app_event_resume', ['id' => $event]);

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($this->request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // On récupère d'abord le type d'événement
                $eventType = $form->get('type')->getData();
                
                // On met à jour le type
                $event->setType($eventType);
                
                // On gère les temps forts uniquement si c'est le bon type et si les méthodes existent
                if ($eventType === Event::TYPE_STANDARD_PLUS_MOMENTS && method_exists($event, 'setMomentBeginAt')) {
                    $event->setMomentBeginAt($form->get('momentBeginAt')->getData());
                    $event->setMomentFinishAt($form->get('momentFinishAt')->getData());
                    $event->setMomentDescription($form->get('momentDescription')->getData());
                    $event->setMomentAddress($form->get('momentAddress')->getData());
                    $event->setMomentMaxGuests($form->get('momentMaxGuests')->getData());
                } else if (method_exists($event, 'setMomentBeginAt')) {
                    // Si ce n'est pas un événement avec temps forts mais que les méthodes existent, on réinitialise les champs
                    $event->setMomentBeginAt(null);
                    $event->setMomentFinishAt(null);
                    $event->setMomentDescription(null);
                    $event->setMomentAddress(null);
                    $event->setMomentMaxGuests(null);
                }

                if (!$event->getVisual()->getFile() && !$event->getVisual()->getFileName()) {
                    $this->om->remove($event->getVisual());
                    $event->setVisual(null);
                }

                if (!$event->getTicketVisual()->getFile() && !$event->getTicketVisual()->getFileName()) {
                    $this->om->remove($event->getTicketVisual());
                    $event->setTicketVisual(null);
                }

                if (!$event->getLogo()->getFile() && !$event->getLogo()->getFileName()) {
                    $this->om->remove($event->getLogo());
                    $event->setLogo(null);
                }

                if (!$event->getPoster()->getFile() && !$event->getPoster()->getFileName()) {
                    $this->om->remove($event->getPoster());
                    $event->setPoster(null);
                }

                if (!$event->getMoviePoster()->getFile() && !$event->getMoviePoster()->getFileName()) {
                    $this->om->remove($event->getMoviePoster());
                    $event->setMoviePoster(null);
                }

                if ($event->getType() === Event::TYPE_WORKSHOPS || $event->getType() === 'ateliers') {
                    foreach ($event->getTimeSlots() as $timeSlot) {
                        foreach ($timeSlot->getWorkshops() as $workshop) {
                            $workshop->setTimeSlot($timeSlot);
                        }
                    }
                }

                $this->om->persist($event);
                $this->om->flush();

                $this->addAlert('success');

                return $this->redirectToRoute('app_event_edit', ['id' => $event->getId()]);
            }
        }

        return $this->render('admin/event/tabs/edit.html.twig', [
            'form' => $form->createView(),
            'form_title' => 'Modifier l\'événement',
            'event' => $event,
        ]);
    }

    /**
     * @Route("/{id}/workshops/timeslots", name="app_event_edit_timeslots", requirements={"id": "\d+"})
     * @param Event $event
     * @return Response
     */
    public function timeslots(Event $event): Response
    {
        if ( ! $this->isGranted(EventVoter::EDIT, $event) ) return $this->redirectToRoute('app_event_resume', ['id' => $event]);

        if ($event->getType() !== 'ateliers') return $this->redirectToRoute('app_event_edit', ['id' => $event->getId()]);

        $form = $this->createForm(EventTimeslotsType::class, $event);
        $form->handleRequest($this->request);

        if ($form->isSubmitted()) {

            if ($form->isValid()) {

                /** @var TimeSlot $timeSlot */
                foreach ($event->getTimeSlots() as $timeSlot) {

                    /** @var WorkshopTimeSlot $workshop */
                    foreach ($timeSlot->getWorkshops() as $workshop) {

                        $workshop->setTimeSlot($timeSlot);
                    }
                }

                $this->om->persist($event);
                $this->om->flush();

                $this->addAlert('success');

                return $this->redirectToRoute('app_event_edit_timeslots', ['id' => $event->getId()]);
            }
        }

        return $this->render('admin/event/tabs/edit.timeslots.html.twig', [
            'form' => $form->createView(),
            'form_title' => 'Définir les créneaux',
        ]);
    }

    /**
     * Event Preview
     *
     * @Route("/{id}/show", name="event_show", requirements={"id": "\d+"})
     * @param Event $event
     * @return Response
     */
    public function show(Event $event): Response
    {
        $this->denyAccessUnlessGranted('DASHBOARD', $event);

        $currSender = $this->eventHelper->getCurrentSender();
        $guest = (new Guest())->setEvent($event)->setSender($currSender);

        $legal = $this->om->getRepository(Option::class)->findLegal();

        $form = $this->createForm(GuestType::class, $guest, [
            'context' => GuestType::CONTEXT_FRONT,
            'event_type' => $event->getType(),
            'sidekicks' => $currSender ? $currSender->getSidekicks() : 0,
            'prospects' => $currSender ? $currSender->getProspects() : 0,
            'type' => $this->eventHelper->getCurrentSender()
                ? $this->eventHelper->getCurrentSender()->getGuestType()
                : Sender::GUEST_TYPE_DEFAULT
            ,
        ]);
        $form->handleRequest($this->request);

        if ($form->isSubmitted()) {

            $this->addAlert('info', 'Seul un invité peut soumettre ce formulaire.');
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

        return $this->render('front/show.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
            'body_class' => 'event',
            'pageTitle' => $event->getName(),
            'addressFirstLine' => $addressFirstLine,
            'address' => $address,
            'preview' => true,
            'legal' => $legal,
        ]);
    }

    /**
     * @Route("/{id}/move", name="event.move", requirements={"id": "\d+"})
     * @param Event $event
     * @param EventRepository $eventRepository
     * @return Response
     */
    public function move(Event $event, EventRepository $eventRepository): Response
    {
        $this->denyAccessUnlessGranted(AppVoter::MANAGE_ALL_EVENTS);

        $collections = $eventRepository->getCollections();
        $collectionsArchived = $eventRepository->getCollectionsArchived();

        return $this->render('admin/event/tabs/move.html.twig', [
            'event' => $event,
            'collections' => $collections,
            'collectionsArchived' => $collectionsArchived
        ]);
    }

    /**
     * @Route("/{id}/move/{collection}", name="event.moveto", requirements={"id": "\d+"})
     * @param Event $event
     * @param int $collection
     * @param EventRepository $eventRepository
     * @return Response
     */
    public function moveTo(Event $event, int $collection, EventRepository $eventRepository): Response
    {
        $this->denyAccessUnlessGranted(AppVoter::MANAGE_ALL_EVENTS);
        if ($collection === 0) {

            // On retire la collection, ça devient un événement hors collection
            $event->setParent(null);

        } else {

            $collection = $eventRepository->find($collection);

            if (!$collection || !$collection->isCollection()) {

                throw $this->createAccessDeniedException(); // Todo : à vérifier ^^

            } else {

                $event->setParent($collection);
            }
        }

        $this->om->persist($event);
        $this->om->flush();

        $this->addAlert('Super !');

        return $this->redirectToRoute('app_event_resume', ['id' => $event->getId()]);
    }

    /**
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
            'backLink' => $this->generateUrl('app_event_resume', [
                'id' => $event->getId(),
            ])
        ]);
    }

    /**
     * @Route("/{id}/archive", name="event.archive", requirements={"id": "\d+"})
     * @param Event $event
     * @return Response
     */
    public function archive(Event $event): Response
    {
        $this->denyAccessUnlessGranted(EventVoter::EDIT, $event);

        $event->toggleArchived();

        $this->om->persist($event);
        $this->om->flush();

        return $event->getArchivedAt() === null
            ? $this->redirectToRoute('app_archive')
            : $this->redirectToRoute('app_index')
        ;
    }

    /**
     * @Route("/{id}/scanner", name="app_event_qrscanner", requirements={"id": "\d+"})
     * @param Event $event
     * @return Response
     */
    public function scanner(Event $event): Response
    {
        $this->denyAccessUnlessGranted(EventVoter::MANAGE_QRCODE, $event);

        return $this->render('admin/event/tabs/scanner.html.twig', [
            'event' => $event,
        ]);
    }

}