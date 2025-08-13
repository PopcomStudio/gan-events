<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\EmailSchedule;
use App\Entity\EmailTemplate;
use App\Entity\Event;
use App\Entity\Guest;
use App\Entity\Sender;
use App\Entity\User;
use App\Form\EmailScheduleType;
use App\Form\EmailTemplateType;
use App\Service\EventHelper;
use App\Service\Mailer;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Route("/email")
 */
class EmailController extends AbstractController
{
    private ObjectManager$om;
    private ?Request $request;
    private EventHelper $eventHelper;
    private User $user;

    public function __construct(ObjectManager $om, RequestStack $requestStack, EventHelper $eventHelper, Security $security)
    {
        $this->om = $om;
        $this->request = $requestStack->getCurrentRequest();
        $this->eventHelper = $eventHelper;
        $this->user = $security->getUser();
    }

    /**
     * Create new email schedule with modal
     *
     * @Route("/schedule/new/{id}", name="schedule_new", requirements={"id": "\d+"})
     * @param Event $event
     * @return Response
     */
    public function emailScheduleNew(Event $event): Response
    {
        // ToDo : Pourquoi il faut repréciser l'événement ? A quel moment eventHelper est vidé ?
        $this->eventHelper->setEvent($event);

        $emailSchedule = (new EmailSchedule())
            ->setSubject($event->getName())
            ->setEvent($event)
            ->setTemplateOrInput(true)
        ;

        $emailSchedule->setSender($this->eventHelper->getCurrentSender());

        return $this->emailScheduleEdit($emailSchedule);

    }

    /**
     * Edit email schedule with modal
     *
     * @Route("/schedule/{id}", name="schedule_edit", requirements={"id": "\d+"})
     * @param EmailSchedule $emailSchedule
     * @return Response
     */
    public function emailScheduleEdit(EmailSchedule $emailSchedule): Response
    {
        // ToDo : Pourquoi il faut repréciser l'événement ? A quel moment eventHelper est vidé ?
        $this->eventHelper->setEvent($emailSchedule->getEvent());

        $this->denyAccessUnlessGranted('MANAGE_EMAIL', $emailSchedule->getEvent());

        $form = $this->createForm(EmailScheduleType::class, $emailSchedule, []);
        $form->handleRequest($this->request);

        $renderArgs = [
            'form' => $form->createView(),
            'event' => $emailSchedule->getEvent(),
            'sender' => $emailSchedule->getSender(),
        ];

        if ($form->isSubmitted()) {

            if ($form->isValid()) {

                $this->om->persist($emailSchedule);
                $this->om->flush();

                $this->addAlert('success');

                unset($renderArgs['form']);

            } else {

                $this->addAlert('danger');
            }
        }

        return $this->render('admin/email/email-schedule.edit.modal.html.twig', $renderArgs);
    }

    /**
     * Get email templates into json
     *
     * @Route("/template/json/{id}", name="json_email_template", requirements={"id": "\d+"})
     * @param Event $event
     * @return Response
     */
    public function emailTemplateJson(Event $event): Response
    {
        $data = $this->request->query->all();

        if (!isset($data['search'])) {

            $data['search'] = '';
        }

        $data['event'] = $event->getId();

        $results = $this->om->getRepository(EmailTemplate::class)->findJson($data);

        return $this->json(["results" => $results]);
    }

    /**
     * Create new email template with modal
     *
     * @Route("/template/new/{id}", name="email_new", requirements={"id": "\d+"})
     * @param Event $event
     * @return Response
     */
    public function emailTemplateNew(Event $event): Response
    {
        // ToDo : Pourquoi il faut repréciser l'événement ? A quel moment eventHelper est vidé ?
        $this->eventHelper->setEvent($event);

        $emailTemplate = (new EmailTemplate())
            ->setSubject($event->getName())
            ->setEvent($event)
        ;

        $emailTemplate->setSender($this->eventHelper->getCurrentSender());

        return $this->emailTemplateEdit($emailTemplate);
    }

    /**
     * Edit email template with modal
     *
     * @Route("/template/{id}", name="email_edit", requirements={"id": "\d+"})
     * @param EmailTemplate $emailTemplate
     * @return Response
     */
    public function emailTemplateEdit(EmailTemplate $emailTemplate): Response
    {
        // ToDo : Pourquoi il faut repréciser l'événement ? A quel moment eventHelper est vidé ?
        $this->eventHelper->setEvent($emailTemplate->getEvent());

        $this->denyAccessUnlessGranted('MANAGE_EMAIL', $emailTemplate->getEvent());

        $form = $this->createForm(EmailTemplateType::class, $emailTemplate);
        $form->handleRequest($this->request);

        $renderArgs = ['form' => $form->createView()];

        if ($form->isSubmitted()) {

            if ($form->isValid()) {

                $this->om->persist($emailTemplate);
                $this->om->flush();

                $this->addAlert('success');

                unset($renderArgs['form']);

            } else {

                $this->addAlert('danger');
            }
        }

        return $this->render('admin/email/email-template.edit.modal.html.twig', $renderArgs);
    }

    /**
     * Send preview email with modal
     *
     * @Route("/{type}/preview/{id}", name="send", requirements={"id": "\d+", "type": "schedule|template"})
     * @param string $type
     * @param string $id
     * @param Mailer $mailer
     * @return Response
     */
    public function send(string $type, string $id, Mailer $mailer): Response
    {
        if ($type === 'schedule') {

            /** @var EmailSchedule|null $email */
            $email = $this->om->getRepository(EmailSchedule::class)->find($id);

        } else {

            /** @var EmailTemplate|null $email */
            $email = $this->om->getRepository(EmailTemplate::class)->find($id);
            if ($email) $email = (new EmailSchedule())
                ->setTemplate($email)
                ->setType($email->getType())
                ->setEvent($email->getEvent())
                ->setSender($email->getSender() ?: (new Sender())->setUser($this->getUser()))
            ;
        }

        if (!$email) throw $this->createNotFoundException();

        // Todo : Pourquoi je dois encore préciser ça !?
        $this->eventHelper->setEvent($email->getEvent());

        if ($email->getSender() === null) {
            $currSender = $this->eventHelper->getCurrentSender();
            if ($currSender) {
                $email->setSender($currSender);
            } else {
                $email->setSender((new Sender())->setEvent($email->getEvent())->setUser($this->getUser()));
            }
        }

        $this->denyAccessUnlessGranted('MANAGE_EMAIL', $email->getEvent());

        $form = $this->createFormBuilder(['email' => $this->user->getEmail()])
            ->add('email', EmailType::class, [
                'label' => 'Envoyer à *',
                'required' => true,
                'constraints' => [
                    new Assert\Email(),
                ],
            ]);

        $form = $form->getForm();
        $form->handleRequest($this->request);

        $renderArgs = [
            'type' => $type === 'schedule' ? 'planning' : 'template',
            'form' => $form->createView(),
        ];

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();
            $sender = $email->getSender() ?: $email->getEvent()->getCurrSender($this->getUser());

            $guest = (new Guest())
                ->setEmail($data['email'])
                ->setEvent($email->getEvent())
                ->setSender($sender)
                ->setUuid('test')
            ;

            $mailer->sendEventMessage($guest, $email);

            unset($renderArgs['form']);

            $this->addAlert('success', 'Email test envoyé.');
        }

        return $this->render('admin/email/send-preview.modal.html.twig', $renderArgs);
    }
}