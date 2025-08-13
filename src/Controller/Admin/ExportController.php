<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\Event;
use App\Entity\Guest;
use App\Entity\Sender;
use App\Entity\User;
use App\Entity\Workshop;
use App\Entity\WorkshopTimeSlot;
use App\Service\EventHelper;
use App\Service\GenPdf;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ObjectManager;
use PhpOffice\PhpSpreadsheet;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/export")
 */
class ExportController extends AbstractController
{
    private ObjectManager $om;
    private ?Request $request;
    private EventHelper $eventHelper;
    private User $user;
    private GenPdf $genPdf;
    private LoggerInterface $logger;

    public function __construct(ObjectManager $om, RequestStack $requestStack, EventHelper $eventHelper, Security $security, GenPdf $genPdf, LoggerInterface $logger)
    {
        $this->om = $om;
        $this->request = $requestStack->getCurrentRequest();
        $this->eventHelper = $eventHelper;
        $this->user = $security->getUser();
        $this->genPdf = $genPdf;
        $this->logger = $logger;
    }

    /**
     * @Route("/event/{id}", name="event_export", requirements={"id": "\d+"})
     * @param Event $event
     */
    public function exportForEvent(Event $event): Response
    {
        $this->denyAccessUnlessGranted('DASHBOARD', $event);

        return $this->export(
            $this->om->getRepository(Guest::class)->findFor($event),
            $event,
            'admin-export-'.$event->getSlug().'.xls'
        );
    }

    /**
     * @Route("/sender/{id}", name="sender_export", requirements={"id": "\d+"})
     * @param Sender $sender
     */
    public function exportForSender(Sender $sender): Response
    {
        if ($sender !== $this->eventHelper->getCurrentSender()) throw $this->createAccessDeniedException();

        return $this->export(
            $this->om->getRepository(Guest::class)->findFor($sender),
            $sender->getEvent(),
            $sender->getDisplayName().'-export-'.$sender->getEvent()->getSlug().'.xls'
        );
    }

    /**
     * @param array $guests
     * @param Event $event
     * @param string $filename
     */
    public function export(array $guests, Event $event, $filename = 'export.xls'): Response
    {
        $spreadsheet = new PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $styleArray = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'ffffff'],
            ],
            'fill' => [
                'fillType' => PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '000919']
            ]
        ];

        $numRow = 1;

        $letter = 'A';
        $labels = [
            'ID',
            'Civ.',
            'Nom',
            'Prénom',
            'Email',
            'Tél.',
            'Société',
            'SIRET',
            'Dernière mise à jour',
            'Type',
            'Statut',
            'Evénement destination',
            'Expéditeur',
            'Inspecteur Commercial',
        ];

        foreach ($labels as $label) {
            $sheet->setCellValue($letter.$numRow, $label);
            $sheet->getStyle($letter.$numRow)->applyFromArray($styleArray);
            ++$letter;
        }

        // Ajouter une colonne pour chaque temps fort
        if ($event->getType() === Event::TYPE_STANDARD_PLUS_MOMENTS) {
            foreach ($event->getMoments() as $moment) {
                $sheet->setCellValue($letter.$numRow, $moment->getName());
                $sheet->getStyle($letter.$numRow)->applyFromArray($styleArray);
                ++$letter;
            }
        }

        if ($event->getType() === 'golfcup') {
            $sheet->setCellValue($letter.$numRow, 'Golf Licence');
            $sheet->getStyle($letter.$numRow)->applyFromArray($styleArray);
            ++$letter;

            $sheet->setCellValue($letter.$numRow, 'Golf Index');
            $sheet->getStyle($letter.$numRow)->applyFromArray($styleArray);
            ++$letter;
        } elseif ($event->getType() === 'ateliers') {
            foreach ($event->getWorkshops() as $workshop) {
                $sheet->setCellValue($letter.$numRow, $workshop->getName());
                $sheet->getStyle($letter.$numRow)->applyFromArray($styleArray);
                ++$letter;
            }
        }

        if ($guests) {
            foreach ($guests as $key => $guest) {
                $numRow++;

                foreach ($labels as $label) {
                    switch ($label):
                        case 'Tél.':
                        case 'SIRET':
                            break;
                        case 'Golf Index':
                            $sheet
                                ->getStyle($letter.$numRow)
                                ->getNumberFormat()
                                ->setFormatCode(
                                    PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00
                                )
                            ;
                            break;
                        case 'Dernière mise à jour':
                            $sheet
                                ->getStyle($letter.$numRow)
                                ->getNumberFormat()
                                ->setFormatCode(
                                    PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DATETIME
                                )
                            ;
                            break;
                    endswitch;
                    ++$letter;
                }

                if ($guest->getUpdatedAt()) $guest->getUpdatedAt()->add(new \DateInterval('PT2H'));

                $destinationEventName = null;

                if($guest->isSwitched()){
                    $destinationGuest = $guest->getRoot();
                    if($destinationGuest) $destinationEventName = $destinationGuest->getEvent()->getName();
                }

                $letter = 'A';
                $values = [
                    $guest->getId(),
                    $guest->getGender(),
                    $guest->getLastName(),
                    $guest->getFirstName(),
                    $guest->getEmail(),
                    $guest->getPhone(),
                    $guest->getCompany(),
                    $guest->getSiret(),
                    $guest->getUpdatedAt(),
                    $guest->getDisplayType(),
                    $guest->getDisplayStatus(),
                    $destinationEventName,
                    $guest->getSender()->getDisplayName(),
                    $guest->getInspecteurCommercial(),
                ];

                foreach ($values as $value) {
                    $sheet->setCellValue($letter.$numRow, $value);
                    ++$letter;
                }

                // Ajouter OUI/NON pour chaque temps fort
                if ($event->getType() === Event::TYPE_STANDARD_PLUS_MOMENTS) {
                    $guestMoments = $guest->getMomentChoices()->map(function($choice) {
                        return $choice->getEvent()->getId();
                    })->toArray();

                    foreach ($event->getMoments() as $moment) {
                        // Si l'invité est inscrit et que le temps fort est obligatoire, on met OUI
                        if ($guest->isRegistered() && $moment->getType() === 'obligatoire') {
                            $sheet->setCellValue($letter.$numRow, 'OUI');
                        } else {
                            $sheet->setCellValue($letter.$numRow, in_array($moment->getId(), $guestMoments) ? 'OUI' : 'NON');
                        }
                        ++$letter;
                    }
                }

                if ($event->getType() === 'golfcup') {
                    $sheet->setCellValue($letter.$numRow, $guest->getGolfLicense());
                    ++$letter;

                    $sheet->setCellValue($letter.$numRow, $guest->getGolfIndex());
                    ++$letter;
                } elseif ($event->getType() === 'ateliers') {
                    $workshopCollection = new ArrayCollection();

                    foreach($guest->getWorkshops() as $workshop){
                        $workshopTimeslot = $workshop->getWorkshop();
                        $workshopCollection->add($workshopTimeslot);
                    }

                    foreach ($event->getWorkshops() as $workshop) {
                        if($workshopCollection->contains($workshop)) {
                            $sheet->setCellValue($letter.$numRow,  'OUI');
                        } else{
                            $sheet->setCellValue($letter.$numRow,  'NON');
                        }
                        ++$letter;
                    }
                }
            }
        }

        $lastLetter = $letter;
        $letter = 'A';

        while ($lastLetter != $letter) {
            $sheet->getColumnDimension($letter)->setAutoSize(true);
            $letter++;
        }

        $writer = new PhpSpreadsheet\Writer\Xls($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return new Response(
            $content,
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => 'attachment;filename="'.$filename.'"',
                'Cache-Control' => 'max-age=0'
            ]
        );
    }

    /**
     * @Route("/workshop/{id}/{timeslotId}", name="workshop_export", requirements={"id": "\d+"})
     * @param Workshop $workshop
     */
    public function exportWorkshop(Workshop $workshop, int $timeslotId = 0): void
    {
        $this->denyAccessUnlessGranted('DASHBOARD', $workshop->getEvent());

        $filename = 'export '.$workshop->getName().'.xls';

        $spreadsheet = new PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $styleArray = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'ffffff'],
            ],
            'fill' => [
                'fillType' => PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '000919']
            ]
        ];

        $numRow = 1;

        $letter = 'A';
        $labels = [
            'Date et heure de l\'atelier',
            'Civ.',
            'Nom',
            'Prénom',
            'Email',
            'Tél.', //f

        ];

        foreach ($labels as $label) {
            $sheet->setCellValue($letter . $numRow, $label);
            $sheet->getStyle($letter . $numRow)->applyFromArray($styleArray);
            ++$letter;
        }


        foreach ($workshop->getTimeSlots() as $workshopTimeSlot) {

            if ($timeslotId && $workshopTimeSlot->getTimeslot()->getId() !== $timeslotId) continue;

            $numRow++;
            $letter = 'A';
            foreach ($labels as $label) {
                switch ($label):
                    case 'Tél.':
                        break;
                    case 'Date et heure de l\'atelier':
                        $sheet
                            ->getStyle($letter . $numRow)
                            ->getNumberFormat()
                            ->setFormatCode(
                                PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DATETIME
                            );
                        break;
                endswitch;
                ++$letter;
            }

//            $workshopTimeSlot->getTimeSlot()->getStartAt()->add(new \DateInterval('PT2H'));

            foreach ($workshopTimeSlot->getGuests(true) as $guest){
                $letter = 'A';
                $values = [
                    $workshopTimeSlot->getTimeSlot()->getStartAt(),
                    $guest->getGender(),
                    $guest->getLastName(),
                    $guest->getFirstName(),
                    $guest->getEmail(),
                    $guest->getPhone()
                ];

                foreach ($values as $k => $value) {

                    $sheet->setCellValue($letter.$numRow, $value);

                    if ($k === 0) {

                        $sheet
                            ->getStyle($letter . $numRow)
                            ->getNumberFormat()
                            ->setFormatCode(
                                'dd/mm/yyyy h:mm'
                            );
                    }

                    ++$letter;
                }

                $numRow++;
            }
        }

        $lastLetter = $letter;
        $letter = 'A';

        while ($lastLetter != $letter) {

            $sheet->getColumnDimension($letter)->setAutoSize(true);
            ++$letter;
        }

        // redirect output to client browser
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');

        $writer = new PhpSpreadsheet\Writer\Xls($spreadsheet);
        $writer->save('php://output');
    }

}