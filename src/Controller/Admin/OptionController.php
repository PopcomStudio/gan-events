<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\Option;
use App\Form\OptionType;
use App\Repository\OptionRepository;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/settings")
 */
class OptionController extends AbstractController
{
    private ObjectManager $om;
    private ?Request $request;

    public function __construct(ObjectManager $om, RequestStack $requestStack)
    {
        $this->om = $om;
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * @Route("", name="option_index")
     * @param OptionRepository $optionRepository
     * @return Response
     */
    public function index(OptionRepository $optionRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $options = $optionRepository->findAll();

        return $this->render('admin/option/index.html.twig', [
            'options' => $options,
        ]);
    }

    /**
     * @Route("/{name}/edit", name="option_edit")
     * @param $name
     * @return Response
     */
    public function edit($name): Response
    {
        $option = $this->om->getRepository(Option::class)->findByName($name);
        if (!$option) throw $this->createNotFoundException();

        $form = $this->createForm(OptionType::class, $option);
        $form->handleRequest($this->request);

        if ($form->isSubmitted()) {

            if ($form->isValid()) {

                $this->om->persist($option);
                $this->om->flush();
                $this->addAlert('success');
            }
        }

        return $this->render('admin/option/edit.html.twig', [
            'form' => $form->createView(),
            'option' => $option,
        ]);
    }
}
