<?php

namespace App\Service;

use App\Entity\Guest;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Dompdf\Dompdf;
use Dompdf\Options;
use Liip\ImagineBundle\Binary\Loader\FileSystemLoader;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Vich\UploaderBundle\Storage\StorageInterface;

class GenPdf
{
    /** @var Environment */
    private $twig;

    /** @var ParameterBagInterface */
    private $param;

    /** @var StorageInterface */
    private $vichStorage;

    /** @Var FileSystemLoader */
    private $imagineLoader;

    /** @Var FilterManager */
    private $imagineFilterManager;

    /** @Var UrlGeneratorInterface */
    private $router;

    public function __construct(
        Environment $twig,
        ParameterBagInterface $param,
        StorageInterface $storage,
        FileSystemLoader $loader,
        FilterManager $filterManager,
        UrlGeneratorInterface $router
    )
    {
        $this->twig = $twig;
        $this->param = $param;
        $this->vichStorage = $storage;
        $this->imagineLoader = $loader;
        $this->imagineFilterManager = $filterManager;
        $this->router = $router;
    }

    private function new(): Dompdf
    {
        // Configure Dompdf according to your needs
        $options = new Options();
        $options->setFontDir($this->param->get('kernel.project_dir') . '/assets/fonts-pdf/');
        $options->setFontCache($options->getFontDir());
        $options->setIsPhpEnabled(true);

        return new Dompdf($options);
    }

    private function args(): array
    {
        return [
            'color' => '#231f20',
            'primary' => '#001b4c',
            'secondary' => '#ffd631',
            'light' => '#e6e7e8',
        ];
    }

    public function invitation(Guest $guest): ?Dompdf
    {
        $dompdf = $this->new();

        $dompdf->setPaper([0, 0, 595.28, 280.63]);

        $visual = $guest->getEvent()->getVisual();
        $visualUri = $this->vichStorage->resolveUri($visual);

        $visual = $this->imagineFilterManager->applyFilter($this->imagineLoader->find($visualUri), 'event_ticket');
        $qrcode = new QRCode(new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'margin' => 0,
            'addQuietzone' => false
        ]));

        $event_url = $this->router->generate('public_event_show', [
            'slug' => $guest->getEvent()->getSlug()
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        $access_url = $this->router->generate('public_event_access', [
            'id' => $guest->getEvent()->getId(),
            'uuid' => $guest->getUuid(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $dompdf->loadHtml($this->twig->render('pdf/invitation.html.twig', array_merge($this->args(), [
            'guest' => $guest,
            'event' => $guest->getEvent(),
            'sender' => $guest->getSender(),
            'visual' => 'data:'.$visual->getMimeType().';base64,'.base64_encode($visual->getContent()),
            'qrcode' => $qrcode->render($access_url),
            'event_url' => $event_url,
            'event_uri' => preg_replace('@^https?://@', '', $event_url),
        ])));

        // Render the HTML as PDF
        $dompdf->render();

        return $dompdf;
    }

    public function ticketQrcode(Guest $guest): ?Dompdf
    {
        $dompdf = $this->new();
        $dompdf->setPaper([0, 0, 595.28, 280.63]);


        $visual = $guest->getEvent()->getVisual();
        if($visual) {
            $visualUri = $this->vichStorage->resolveUri($visual);
            $visual = $this->imagineFilterManager->applyFilter($this->imagineLoader->find($visualUri), 'event_ticket');
        }
        $access_url = $this->router->generate('private_event_access', [
            'id' => $guest->getEvent()->getId(),
            'uid' => $guest->getId(),

        ], UrlGeneratorInterface::ABSOLUTE_URL);


        $qrcode = new QRCode(new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'margin' => 0,
            'addQuietzone' => false,
        ]));

if($visual){
    $dompdf->loadHtml($this->twig->render('pdf/ticket-qrcode.html.twig', array_merge($this->args(), [
        'guest' => $guest,
        'event' => $guest->getEvent(),
        'visual' => 'data:'.$visual->getMimeType().';base64,'.base64_encode($visual->getContent()),
        'qrcode' => $qrcode->render($access_url),

    ])));
}else{
    $dompdf->loadHtml($this->twig->render('pdf/ticket-qrcode.html.twig', array_merge($this->args(), [
        'guest' => $guest,
        'event' => $guest->getEvent(),
        'qrcode' => $qrcode->render($access_url),

    ])));
}


        $dompdf->render();

        return $dompdf;

    }

    public function render(Dompdf $dompdf, $filename = null): Response
    {
        $filename = $filename ?: 'doc.pdf';

        if (!preg_match('@\.pdf$@', $filename)) {

            $filename.= '.pdf';
        }

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}