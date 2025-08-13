<?php

namespace App\Service;

use App\Entity\EmailSchedule;
use App\Entity\Event;
use App\Entity\Guest;
use App\Entity\Option;
use App\Entity\Sender;
use App\Entity\User;
use DateInterval;
use Doctrine\Persistence\ObjectManager;
use Liip\ImagineBundle\Binary\Loader\FileSystemLoader;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Twig\Environment;
use Vich\UploaderBundle\Storage\StorageInterface;

class Mailer
{
    private MailerInterface $mailer;
    private ParameterBagInterface $param;
    private StorageInterface $vichStorage;
    private FileSystemLoader $imagineLoader;
    private FilterManager$imagineFilterManager;
    private UrlGeneratorInterface$router;
    private ObjectManager $om;
    private Environment $twig;
    private VerifyEmailHelperInterface $verifyEmailHelper;
    private $genPdf;
    private ResetPasswordHelperInterface $resetPasswordHelper;

    public function __construct(
        MailerInterface $mailer,
        ParameterBagInterface $param,
        StorageInterface $storage,
        FileSystemLoader $loader,
        FilterManager $filterManager,
        UrlGeneratorInterface $router,
        ObjectManager $om,
        Environment $twig,
        VerifyEmailHelperInterface $verifyEmailHelper,
        GenPdf $genPdf,
        ResetPasswordHelperInterface $resetPasswordHelper
    )
    {
        $this->mailer = $mailer;
        $this->param = $param;
        $this->vichStorage = $storage;
        $this->imagineLoader = $loader;
        $this->imagineFilterManager = $filterManager;
        $this->router = $router;
        $this->om = $om;
        $this->twig = $twig;
        $this->verifyEmailHelper = $verifyEmailHelper;
        $this->genPdf = $genPdf;
        $this->resetPasswordHelper = $resetPasswordHelper;
    }

    /**
     * @param string|null $name
     * @return Address
     */
    private function from(?string $name = null): Address
    {
        $env = $this->param->get('kernel.environment');
        $name = $name ?: 'Gan Assurances';
        $email = $env === 'dev' ? 'fabien@popcom.me' : 'reply@gan.events';

        return new Address($email, $name);
    }

    public function sendNewSenderMessage(User $user, Event $event)
    {
        $optionLegalMail = $this->om->getRepository(Option::class)->findOneBy(['name' => 'emailLegal']);
        $mentionMail = $optionLegalMail->getData()['value'];

        $verifyEmailSignature = $this->verifyEmailHelper->generateSignature(
            'choose_password',
            $user->getId(),
            $user->getEmail(),
            ['id' => $user->getId()]
        );

        $email = (new TemplatedEmail())
            ->from($this->from())
            ->to($user->getEmail())
            ->subject('Administrez votre événement sur gan.events')
            ->htmlTemplate('emails/new-sender.html.twig')
//            ->textTemplate('emails/new-sender.txt.twig')
            ->context([
                'signature' => $verifyEmailSignature,
                'event' => $event,
                'user' => $user,
                'mentionMail' => $mentionMail
            ])
//            ->attachFromPath($this->param->get('kernel.project_dir').'/public/uploads/guide-utilisateur.pdf')
        ;

        $this->mailer->send($email);
    }

    public function sendNewUserMessage(User $user)
    {
        $verifyEmailSignature = $this->verifyEmailHelper->generateSignature(
            'choose_password',
            $user->getId(),
            $user->getEmail(),
            ['id' => $user->getId()]
        );
        $optionLegalMail = $this->om->getRepository(Option::class)->findOneBy(['name' => 'emailLegal']);
        $mentionMail = $optionLegalMail->getData()['value'];

        $email = (new TemplatedEmail())
            ->from($this->from())
            ->to($user->getEmail())
            ->subject('Votre compte gan.events')
            ->htmlTemplate('emails/new-user.html.twig')
            ->context([
                'signature' => $verifyEmailSignature,
                'user' => $user,
                'mentionMail' => $mentionMail
            ])
//            ->attachFromPath($this->param->get('kernel.project_dir').'/public/uploads/documentation.pdf')
        ;

        $this->mailer->send($email);
    }


    public function sendResetNewUser(User $user, $resetToken) {
        $verifyEmailSignature = $this->verifyEmailHelper->generateSignature(
            'choose_password',
            $user->getId(),
            $user->getEmail(),
            ['id' => $user->getId()]
        );
        $optionLegalMail = $this->om->getRepository(Option::class)->findOneBy(['name' => 'emailLegal']);
        $mentionMail = $optionLegalMail->getData()['value'];

        $email = (new TemplatedEmail())
            ->from($this->from())
            ->to($user->getEmail())
            ->subject('Votre compte gan.events')
            ->htmlTemplate('emails/new-user.html.twig')
            ->context([
                'resetToken' => $resetToken,
                'signature' => $verifyEmailSignature,
                'user' => $user,
                'mentionMail' => $mentionMail
            ])
//            ->attachFromPath($this->param->get('kernel.project_dir').'/public/uploads/documentation.pdf')
        ;

        $this->mailer->send($email);
    }

public function sendResetPassword(User $user, ResetPasswordToken $resetToken)
    {
        $email = (new TemplatedEmail())
            ->from($this->from())
            ->to($user->getEmail())
            ->subject('Votre demande de réinitialisation de mot de passe')
            ->htmlTemplate('emails/reset-password.html.twig')
            ->context([
                'resetToken' => $resetToken,
            ])
        ;
        $this->mailer->send($email);
    }

    public function sendEventMessage(Guest $guest, EmailSchedule $schedule)
    {
        $src = $schedule->getTemplate() ?: $schedule;

        $event = $guest->getEvent();
        $sender = $guest->getSender();

        // Mentions légales
        if ($sender->getLegalNoticeSender()) {

            $mentionMail = $sender->getLegalNoticeSender();

        } else {

            $nameOptionLegalMail = 'emailLegal';
            if ($sender->getGuestType() === Sender::GUEST_TYPE_AGENT) $nameOptionLegalMail = 'agentEmailLegal';
            elseif ($sender->getGuestType() === Sender::GUEST_TYPE_INTERNAL) $nameOptionLegalMail = 'internalEmailLegal';

            $optionLegalMail = $this->om->getRepository(Option::class)->findOneBy(['name' => $nameOptionLegalMail]);
            $mentionMail = $optionLegalMail->getData()['value'];
        }

        // Lien de désinscription
        if ( in_array($sender->getGuestType(), [Sender::GUEST_TYPE_INTERNAL, Sender::GUEST_TYPE_AGENT])) {

            $unsubscribeUrl = null;

        } else {

            $unsubscribeUrl = $this->router->generate('app_unsubscribe', [],UrlGeneratorInterface::ABSOLUTE_URL);
        }

        $email = (new TemplatedEmail())
            ->from($this->from($sender->getDisplayName()))
            ->to($guest->getEmail())
            ->subject($src->getSubject())
            ->htmlTemplate('emails/template.html.twig')
//            ->textTemplate('emails/template.txt.twig')
        ;

        $publicEmail = $sender->getPublicEmail();
        if ($publicEmail) $email->replyTo(new Address($sender->getPublicEmail(), $sender->getDisplayName()));

        $context = [
            'mentionMail' => $mentionMail,
            'unsubscribe_url' => $unsubscribeUrl,
            'body' => html_entity_decode($src->getContent()),
            'signature' => $src->getDisplaySignature($guest),
            'hello' => $guest->getDisplayName(),
            'h1' => $event->getName(),
        ];

        // Buttons

        if ($schedule->getType() === 'invitation' || $schedule->getType() === 'up') {

            $context['subscribe_url'] = $guest->getUuid() === 'test'
                ? $this->router->generate(
                    'app_index',
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
                : $this->router->generate(
                    'public_event_access',
                    [
                        'id' => $event->getId(),
                        'uuid' => $guest->getUuid(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            ;

            $context['decline_url'] = $guest->getUuid() === 'test'
                ? $this->router->generate(
                    'app_index',
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
                : $this->router->generate(
                    'public_event_decline',
                    [
                        'id' => $event->getId(),
                        'uuid' => $guest->getUuid(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            ;
        }

        // Header visual
        $visual = $guest->getEvent()->getEmailVisual($schedule);

        if ($visual) {

            $visualUri = $this->vichStorage->resolveUri($visual);
            $visual = $this->imagineFilterManager->applyFilter($this->imagineLoader->find($visualUri), 'email_header');

            $email->embed($visual->getContent(), 'header-visual', $visual->getMimeType());

            $context['header'] = 'cid:header-visual';
        }

        // Prepare & Send email

        $email->context($context);
        $this->mailer->send($email);
    }

    public function sendConfirmationMessage(Guest $guest)
    {
        $event = $guest->getEvent();
        $sender = $guest->getSender();

        // Mentions légales
        if ($sender->getLegalNoticeSender()) {

            $mentionMail = $sender->getLegalNoticeSender();

        } else {

            $nameOptionLegalMail = 'emailLegal';
            if ($sender->getGuestType() === Sender::GUEST_TYPE_AGENT) $nameOptionLegalMail = 'agentEmailLegal';
            elseif ($sender->getGuestType() === Sender::GUEST_TYPE_INTERNAL) $nameOptionLegalMail = 'internalEmailLegal';

            $optionLegalMail = $this->om->getRepository(Option::class)->findOneBy(['name' => $nameOptionLegalMail]);
            $mentionMail = $optionLegalMail->getData()['value'];
        }

        if ($event->getAddTicket() == true) {

            $ticket = $this->genPdf->ticketQrcode($guest)->output();

            $email = (new TemplatedEmail())
                ->from($this->from($sender->getDisplayName()))
                ->to($guest->getEmail())
                ->subject('Confirmation d\'inscription')
                ->attach($ticket, 'ticket-gan-events.pdf', 'application/pdf')
                ->htmlTemplate('emails/template.html.twig')
//            ->textTemplate('emails/template.txt.twig')
            ;

            $publicEmail = $sender->getPublicEmail();
            if ($publicEmail) $email->replyTo(new Address($sender->getPublicEmail(), $sender->getDisplayName()));

            foreach ($guest->getSidekicks() as $i => $sidekick) {

                $email->attach(
                    $this->genPdf->ticketQrcode($sidekick)->output(),
                    'ticket-gan-events-'.($i+1).'.pdf',
                    'application/pdf'
                );
            }

        } else {

            $email = (new TemplatedEmail())
                ->from($this->from($sender->getDisplayName()))
                ->to($guest->getEmail())
                ->subject('Confirmation d\'inscription')
                ->htmlTemplate('emails/template.html.twig')
//            ->textTemplate('emails/template.txt.twig')
            ;

            $publicEmail = $sender->getPublicEmail();
            if ($publicEmail) $email->replyTo(new Address($sender->getPublicEmail(), $sender->getDisplayName()));
        }

        $body = $this->twig->render('emails/message-confirmation.html.twig', [
            'workshops' => $guest->getWorkshops(),
            'event' => $event,
            'address' => $event->getAddress() ? ' @ '.str_replace("\n", ', ', $event->getAddress()) : null,
            'guest' => $guest,
        ]);

        $context = [
            'body' => $body,
            'signature' => 'Au plaisir de vous retrouver, '.PHP_EOL.$sender->getDisplayName(),
            'hello' => $guest->getDisplayName(),
            'h1' => 'Confirmation d\'inscription',
            'mentionMail' => $mentionMail,
        ];

        // Header visual

        $visual = $guest->getEvent()->getEmailVisual('confirmation');

        if ($visual) {

            $visualUri = $this->vichStorage->resolveUri($visual);
            $visual = $this->imagineFilterManager->applyFilter($this->imagineLoader->find($visualUri), 'email_header');

            $email->embed($visual->getContent(), 'header-visual', $visual->getMimeType());

            $context['header'] = 'cid:header-visual';
        }

        // Prepare & Send email
        $email->context($context);
        $this->mailer->send($email);
    }

    public function sendRappelDeleteEvent(Event $event, $days)
    {
        $sendTo = $event->getOwner()->getEmail();

        $subject = $days > 0
            ? 'J-'.$days.' avant la "suppression" de votre événement'
            : 'Evénement "supprimé"'
        ;

        // Envoi du mail de rappel ou d'archivage de l'event
        $email = (new TemplatedEmail())
            ->from($this->from())
            ->to($sendTo)
            ->subject($subject." : ".$event->getName())
            ->htmlTemplate('emails/delete-event.html.twig')
            ->context([
                'days' => $days,
                'event' => $event
            ])
        ;

        $this->mailer->send($email);
    }
}