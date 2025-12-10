<?php

namespace App\Controller\Api;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Routing\Annotation\Route;


#[Route('/api/flexroll')]
final class FlexrollEmailApiController extends AbstractController {

    #[Route('/notify-by-mail', name: 'flexroll_email', methods: ['POST'])]
    public function notifyAllByMail(
        Request $request,
        TransportInterface $mailer,
        LoggerInterface $logger
    ): JsonResponse
    {
        try {

            $data = json_decode($request->getContent(), true);
            
            /* $recipients = [ // ENABEL_K
                "annie.lebughe@enabel.be",
                "francois-xavier.kabala@enabel.be",
                "fifi.esalo@enabel.be",
                "tresor.mutombo@enabel.be",
                "don.bungiena@enabel.be"
            ]; */

            /* $recipients = [ //E_KORLOM
                "rose.musau@enabel.be",
                "pierre.esokowa@enabel.be",
                "victoire.kabambi@enabel.be",
                "gado.moussadododan@enabel.be",
                "pierre.onema@enabel.be"
            ]; */

            /* $recipients = [
                "rubuz.l@infosetgroup.com"
            ];

            $numbers = [
                [
                    'key' => '243857610635',
                    'value' => 'Le bénéficiaire n\'est pas eligible',
                ]
            ]; */
            
            $email_notification_create_paylist = (new TemplatedEmail())
                ->from(new Address('payroll@flexpaie.com', 'FlexRoll'))
                ->to(...$data["emails"])
                ->subject('FlexRoll - Notification exécution')
                ->htmlTemplate('flexroll/failed.html.twig')
                ->context([
                    'merchant_name' => $data["merchantName"],
                    'list_name' => $data["listName"],
                    'execution_date' => $data["executionDate"],
                    'total'=> $data["totalParticipants"],
                    'success' => $data["totalParticipants"] - count($data["failReasons"]),
                    'failed' => count($data["failReasons"]),
                    'numbers' => $data["failReasons"],
                ]);

            $mailer->send($email_notification_create_paylist);

            return new JsonResponse([
                'code' => '0',
                'message' => 'Mail envoyé avec succès',
            ], Response::HTTP_OK);

            
        } catch (\Exception|TransportExceptionInterface $e) {
            $logger->critical($e->getMessage());

            return new JsonResponse([
                'code' => '1',
                'message' => 'Erreur lors de l\'envoi du mail: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}