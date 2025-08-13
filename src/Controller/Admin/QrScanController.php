<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\Event;
use App\Entity\Guest;
use App\Security\EventVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class QrScanController extends AbstractController
{
    private EntityManagerInterface $om;

    public function __construct(EntityManagerInterface $om)
    {
        $this->om = $om;
    }

    /**
     * @Route("/e/access/{id}/{uid}", name="private_event_access", requirements={"id": "\d+"})
     * @param Event $event
     * @param $uid
     * @return JsonResponse
     */
    public function ticketAccess(Event $event, $uid): JsonResponse
    {
        // Todo : Vérifier le bon fonctionnement

        $this->denyAccessUnlessGranted(EventVoter::MANAGE_QRCODE, $event);

        $guest = $this->om->getRepository(Guest::class)->findByUuid($uid, $event);

        switch ($guest->getStatus()):
            case Guest::STATUS_DECLINED:
                return $this->json(['Cet invité a refusé l\'invitation.']);
            case Guest::STATUS_PENDING:
                return $this->json(['Cet invité ne s\'est pas inscrit.']);
            case Guest::STATUS_PARTICIPATED:
                return $this->json(['Cet invité a déjà été scanné.']);
            default:
                $guest->setParticipated();
                $this->om->persist($guest);
                $this->om->flush();

							$totalParticipations = $this->om->getRepository(Guest::class)->getTotalParticipated($event);
                return $this->json(['status ok', 'Participations'=> $totalParticipations]);
        endswitch;
    }
}