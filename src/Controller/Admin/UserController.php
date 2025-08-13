<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Security\AppVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    private ?Request $request;
    private EntityManagerInterface $om;

    public function __construct(
        RequestStack $requestStack,
        EntityManagerInterface $om
    )
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->om = $om;
    }

    /**
     * @Route("/users", name="app_user_index", requirements={"id": "\d+"})
     * @param UserRepository $repository
     * @return Response
     */
    public function index(UserRepository $repository): Response
    {
        $this->denyAccessUnlessGranted(AppVoter::MANAGE_USERS);

        $users = $repository->findAll();

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
        ]);
    }

    /**
     * @Route("/profile", name="app_user_profile")
     * @return Response
     */
    public function profile(): Response
    {
        return $this->edit($this->getUser());
    }

    /**
     * @Route("/user/new", name="app_user_create")
     * @return Response
     */
    public function new(): Response
    {
        return $this->edit(new User());
    }

    /**
     * @Route("/user/{id}/edit", name="app_user_edit", requirements={"id": "\d+"})
     * @param User $user
     * @return Response
     */
    public function edit(User $user): Response
    {
        $this->denyAccessUnlessGranted('EDIT_USER', $user);

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($this->request);

        $renderArgs = [
            'form' => $form->createView(),
            'user' => $user,
        ];

        if ($form->isSubmitted()) {

            if ($form->isValid()) {

                $this->om->persist($user);
                $this->om->flush();

                $this->addAlert('success');

                unset($renderArgs['form']);
            }
        }

        return $this->render('admin/user/edit.html.twig', $renderArgs);
    }
}