<?php

namespace App\Controller\Front;

use App\Controller\AbstractController;
use App\Entity\User;
use App\Form\ChoosePasswordFormType;
use App\Form\RegisterType;
use App\Form\ResetPasswordRequestFormType;
use App\Service\Mailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class SecurityController extends AbstractController
{
    use ResetPasswordControllerTrait;

    private Mailer $mailer;
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $passwordHasher;
    private TranslatorInterface $translator;
    private ?Request $request;
    private ResetPasswordHelperInterface $resetPasswordHelper;

    public function __construct(
        RequestStack $requestStack,
        Mailer $mailer,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        ResetPasswordHelperInterface $resetPasswordHelper,
        TranslatorInterface $translator
    )
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->mailer = $mailer;
        $this->em = $em;
        $this->passwordHasher = $passwordHasher;
        $this->translator = $translator;
        $this->resetPasswordHelper = $resetPasswordHelper;
    }

    /**
     * @Route("/", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
         if ($this->getUser()) return $this->redirectToRoute('app_index');

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        if ($error) $this->addAlert(
            'error',
            $this->translator->trans($error->getMessageKey(), [], 'security')
        );

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
        ]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * Display & process form to request a password reset.
     *
     * @Route("/forgot-password", name="app_forgot_password")
     */
    public function request(): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {

            return $this->processSendingPasswordResetEmail($form->get('email')->getData());
        }

        return $this->render('security/forgot-password.html.twig', [
            'requestForm' => $form->createView(),
            'body_class' => 'login',
        ]);
    }

    /**
     * Validates and process the reset URL that the user clicked in their email.
     *
     * @Route("/reset/{token}", name="app_reset_password")
     */
    public function reset(string $token = null): Response
    {
        if ( $token ) {

            // We store the token in session and remove it from the URL, to avoid the URL being
            // loaded in a browser and potentially leaking the token to 3rd party JavaScript.
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_reset_password');
        }

        $token = $this->getTokenFromSession();
        if ( null === $token ) {

            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }

        try {

            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);

        } catch (ResetPasswordExceptionInterface $e) {

            $this->addFlash('reset_password_error', sprintf(
                '%s - %s',
                $this->translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE, [], 'ResetPasswordBundle'),
                $this->translator->trans($e->getReason(), [], 'ResetPasswordBundle')
            ));

            return $this->redirectToRoute('app_forgot_password');
        }

        // The token is valid; allow the user to change their password.
        $form = $this->createForm(ChoosePasswordFormType::class, $user);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {

            // A password reset token should be used only once, remove it.
            $this->resetPasswordHelper->removeResetRequest($token);

            $this->em->persist($user);
            $this->em->flush();

            // The session is cleaned up after the password has been changed.
            $this->cleanSessionAfterReset();

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset-password.html.twig', [
            'resetForm' => $form->createView(),
            'body_class' => 'login',
        ]);
    }

    private function processSendingPasswordResetEmail(string $emailFormData): RedirectResponse
    {
        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $emailFormData,
        ]);

        // Do not reveal whether a user account was found or not.
        if ( ! $user ) {

            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();

        } else {

            try {

                $resetToken = $this->resetPasswordHelper->generateResetToken($user);
                $this->mailer->sendResetPassword($user, $resetToken);

                // Store the token object in session for retrieval in check-email route.
                $this->setTokenObjectInSession($resetToken);

            } catch (ResetPasswordExceptionInterface $e) {
                // If you want to tell the user why a reset email was not sent, uncomment
                // the lines below and change the redirect to 'app_forgot_password'.
                // Caution: This may reveal if a user is registered or not.
                //
                // $this->addFlash('reset_password_error', sprintf(
                //     '%s - %s',
                //     $translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_HANDLE, [], 'ResetPasswordBundle'),
                //     $translator->trans($e->getReason(), [], 'ResetPasswordBundle')
                // ));

                $this->addAlert('danger', $this->translator->trans($e->getReason(), [], 'ResetPasswordBundle'));

                return $this->redirectToRoute('app_forgot_password');
            }
        }

        $alert =
            'Si un compte correspond à votre adresse e-mail, ' .
            'un lien de réinitialisation de mot de passe vous a été adressé par e-mail. ' .
            'Ce lien expirera dans %s. ' .
            'Si vous ne recevez pas cet email, veuillez vérifier vos spams ou réessayer.'
        ;

        $alert = sprintf(
            $alert,
            $this->translator->trans(
                $resetToken->getExpirationMessageKey(),
                $resetToken->getExpirationMessageData(),
                'ResetPasswordBundle'
            )
        );

        $this->addAlert('success', $alert);

        return $this->redirectToRoute('app_forgot_password');
    }

    /**
     * @Route("/register/{key}", name="app_register_sender")
     * @param string $key
     * @return Response
     */
    public function registerSender(string $key): Response
    {
        $user = null;
        $session = $this->request->getSession();
        $renderArgs = [
            'heading' => 'Inscrivez-vous',
            'button' => 'Suivant',
            'body_class' => 'login',
        ];

        // A la première ouverture, demander à l'utilisateur de confirmer son adresse email
        // Stocker son ID dans une session
        if ( ! $session->get($key) ) {

            $form = $this->createFormBuilder()
                ->add('email', EmailType::class, [
                    'label' => 'Saisissez votre adresse e-mail *',
                    'attr' => [
                        'placeholder' => 'pierre.quentin@gan.fr'
                    ],
                    'help' =>
                        'L\'adresse doit correspondre à celle indiquer dans l\'email. ' .
                        'Vous pourrez la remplacer à l\'étape suivante.',
                    'required' => true,
                ]);

            $form = $form->getForm();
            $form->handleRequest($this->request);

            if ($form->isSubmitted()) {

                if ($form->isValid() && User::verifyRegisterKey($key, $form->getData()['email'])) {

                    $user = $this->em->getRepository(User::class)->findOneBy([
                        'email' => $form->getData()['email']
                    ]);

                    $session->set($key, $user->getId());

                } else {

                    $this->addAlert(
                        'danger',
                        'Vérifiez l\'adresse saisie.',
                        'fas fa-exclamation-circle'
                    );
                }
            }

        }
        // Si l'utilisateur est déjà en session mais que le formulaire précédent ne vient pas d'être exécuter
        // Récupérer l'utilisateur en base
        elseif ( ! $user ) {

            $user = $this->em->getRepository(User::class)->find($session->get($key));
        }

        // L'utilisateur est vérifier, afficher le formulaire pour clôturer son inscription
        if ( $user ) {

            $form = $this->createForm(RegisterType::class, $user);
            $form->handleRequest($this->request);

            $renderArgs['button'] = 'S\'inscrire';

            if ($form->isSubmitted()) {

                if ($form->isValid()) {

                    // GoTo : Si l'adresse email est différente, renvoyer un mail de vérification

                    $user->setAgreedTermsAt(new \DateTime());

                    $this->addAlert(
                        'success',
                        'Votre inscription est terminée. Connectez-vous.',
                        'fas fa-check-circle'
                    );

                    $this->em->persist($user);
                    $this->em->flush();

                    $session->remove($key);

                    return $this->redirectToRoute('app_login');
                }
            }
        }

        $renderArgs['form'] = $form->createView();

        return $this->render('security/register.html.twig', $renderArgs);
    }

    /**
     * @Route("/choose-password", name="choose_password")
     */
    public function choosePassword(Request $request, VerifyEmailHelperInterface $verifyEmailHelper)
    {
        $id = $request->get('id'); // retrieve the user id from the url
        $error = false;
        $form = null;

        // Verify the user id exists and is not null
        if (null === $id) {
            $error = true;

            $this->addAlert('error', 'Ce lien ne fonctionne plus.');
        }

        $user = $this->em->getRepository(User::class)->find($id);

        // Ensure the user exists in persistence
        if (null === $user) {
            $error = true;
            $this->addAlert('error', 'Ce lien ne fonctionne plus.');
        }

        // Do not get the User's Id or Email Address from the Request object
        try {

            $verifyEmailHelper->validateEmailConfirmation($request->getUri(), $user->getId(), $user->getEmail());

        } catch (VerifyEmailExceptionInterface $e) {

            $error = true;
//            $this->addAlert('error', 'Ce lien ne fonctionne plus');
            $this->addAlert('error', 'Ce lien ne fonctionne plus.'.'<br> <a href="' . $this->generateUrl('app_reset_mail') . '">Renvoyer un mail d\'accès</a>');
        }

        if (!$error) {

            $form = $this->createForm(ChoosePasswordFormType::class, $user);
            $form->handleRequest($request);

            if ($form->isSubmitted()) {

                if ($form->isValid()) {

                    $this->em->persist($user);
                    $this->em->flush();

                    $this->addAlert('success', 'Votre mot de passe a été modifié. Connectez-vous sur la plateforme.');

                    return $this->redirectToRoute('app_index');
                }
            }

            $form = $form->createView();
        }

        return $this->render('security/register.html.twig', [
            'heading' => 'Définissez votre mot de passe',
            'button' => 'Définir',
            'form' => $form,
            'body_class' => 'login',
        ]);
    }

    /**
     * @Route("/resend-access-email", name="app_reset_mail")
     *
     */
    public function resendAccessEmail(Request $request, ResetPasswordHelperInterface $resetPasswordHelper, Mailer $mailer)
    {


        $referer = $request->headers->get('referer');
        $queryString = parse_url($referer, PHP_URL_QUERY);
        parse_str($queryString, $queryParameters);
        $id = $queryParameters['id'];

        $userRepository = $this->em->getRepository(User::class);
        $user = $userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        } else {

            $resetToken = $resetPasswordHelper->generateResetToken($user);
//            $user = $resetPasswordHelper->validateTokenAndFetchUser($newToken);
            $mailer->sendResetNewUser($user, $resetToken);
            $this->addFlash('success', 'Un nouveau lien d\'authentification a été envoyé à votre adresse email.');

        }

        return $this->render('security/resend-mail-new-user.html.twig');

    }
}
