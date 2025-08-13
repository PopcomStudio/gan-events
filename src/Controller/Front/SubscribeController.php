<?php

namespace App\Controller\Front;

use App\Controller\AbstractController;
use App\Entity\Optout;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;

class SubscribeController extends AbstractController
{
    private ?Request $request;
    private EntityManagerInterface $om;

    public function __construct(RequestStack $requestStack, EntityManagerInterface $om)
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->om = $om;
    }

    /**
     * @Route("/unsubscribe", name="app_unsubscribe")
     */
    public function index(): Response
    {
        $form = $this->createFormBuilder()
            ->add('email', EmailType::class, [
                'label' => 'Saisissez l\'adresse email à désinscrire *',
                'required' => true,
                'constraints' => [
                    new Assert\Email()
                ]
            ]);

        $form = $form->getForm();
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {

            $optout = new Optout($form->getData()['email']);

            try {

                $this->om->persist($optout);
                $this->om->flush();

                $this->addAlert('success', 'Vous est maintenant désinscrit.');

                return $this->redirectToRoute('app_unsubscribe');

            } catch(UniqueConstraintViolationException $e) {

                $form->get('email')->addError(new FormError('Vous êtes déjà désinscrit.'));
            }
        }

        return $this->render('front/unsubscribe.html.twig', [
            'body_class' => 'login',
            'form' => $form->createView()
        ]);
    }
}