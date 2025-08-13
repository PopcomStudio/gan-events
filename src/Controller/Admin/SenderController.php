<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\Event;
use App\Entity\Option;
use App\Entity\Sender;
use App\Entity\User;
use App\Form\SenderType;
use App\Security\EventVoter;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

/**
 * @Route("/sender")
 */
class SenderController extends AbstractController
{
    private ObjectManager$om;
    private ?Request $request;
    private User $user;

    public function __construct(ObjectManager $om, RequestStack $requestStack, Security $security)
    {
        $this->om = $om;
        $this->request = $requestStack->getCurrentRequest();
        $this->user = $security->getUser();
    }

    /**
     * Create new sender
     *
     * @Route("/new/{id}", name="sender.new", requirements={"id": "\d+"})
     * @param Event $event
     * @return Response
     */
    public function new(Event $event): Response
    {
        return $this->edit((new Sender())->setEvent($event));
    }

    /**
     * Edit sender
     *
     * @Route("/{id}", name="sender.edit", requirements={"id": "\d+"})
     * @param Sender $sender
     * @return Response
     */
    public function edit(Sender $sender): Response
    {
        if ( ! $this->isGranted(EventVoter::MANAGE_SENDERS, $sender->getEvent()) ) return $this->redirectToRoute('app_event_resume', ['id' => $sender->getEvent()]);

        $form = $this->createForm(SenderType::class, $sender);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->addAlert(
                'success',
                'Expéditeur enregistré.'
            );

            $this->om->persist($sender);
            $this->om->flush();

            return $this->redirectToRoute('sender.edit', ['id' => $sender->getId()]);
        }

        return $this->render('admin/event/tabs/senders.edit.html.twig', [
            'form' => $form->createView(),
            'event' => $sender->getEvent(),
            'sender' => $sender,
            'legal' => $this->om->getRepository(Option::class)->findEmailLegal(),
        ]);
    }

    /**
     * Delete sender
     *
     * @Route("/{id}/delete", name="sender.delete", requirements={"id": "\d+"})
     * @param Sender $sender
     * @return Response
     */
    public function delete(Sender $sender): Response
    {
        $this->denyAccessUnlessGranted('EDIT', $sender->getEvent());

        $event_id = $sender->getEvent()->getId();

        $this->om->remove($sender);
        $this->om->flush();

        $this->addAlert(
            'success',
            'Expéditeur supprimé.'
        );

        return $this->redirectToRoute('app_event_senders', ['id' => $event_id]);
    }
}