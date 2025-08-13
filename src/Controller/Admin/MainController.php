<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\Event;
use App\Entity\Guest;
use App\Repository\EventRepository;
use App\Service\EventHelper;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\PaginatorInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    private EventRepository $repository;
    private ?Request $request;
    private PaginatorInterface $paginator;
    private EventHelper $eventHelper;

    public function __construct(
        EventRepository $repository,
        RequestStack $requestStack,
        PaginatorInterface $paginator,
        EventHelper $eventHelper
    ) {
        $this->repository = $repository;
        $this->request = $requestStack->getCurrentRequest();
        $this->paginator = $paginator;
        $this->eventHelper = $eventHelper;
    }

    /**
     * @Route("", name="app_index")
     */
    public function index(?QueryBuilder $queryBuilder = null, array $params = []): Response
    {
        // Bloquer l'initialiser de EventHelper
        $this->eventHelper->setInitialized();

        $page = intval($this->request->query->get('page', 1));
        
        if ($page < 1) return $this->redirectToRoute('app_index');

        $queryBuilder = $queryBuilder ?: $this->repository->getDashboardQueryBuilder();
        $paginator = $this->paginator->paginate($queryBuilder, $page, 20);

        $params = array_merge(
            [
                'events' => $paginator,
                'pageTitle' => 'Evénements en cours',
            ],
            $params
        );

        return $this->render('index.html.twig', $params);
    }

    /**
     * @Route("/archive", name="app_archive")
     */
    public function archive(): Response
    {
        $queryBuilder = $this->repository->getArchiveQueryBuilder();

        return $this->index($queryBuilder, [
            'pageTitle' => 'Evénements archivés',
        ]);
    }

    /**
     * @Route("/collection/{id}", name="app_collection", requirements={"id": "\d+"})
     */
    public function collection(Event $event): Response
    {
        if ($event->getType() !== 'collection') return $this->redirectToRoute('app_index');

        $queryBuilder = $this->repository->getCollectionQueryBuilder($event);

        return $this->index($queryBuilder, [
            'pageTitle' => $event->getName(),
        ]);
    }
}