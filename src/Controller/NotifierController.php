<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\MerchantRepository;
use App\Repository\UserRepository;
use App\Repository\EmailRepository;
use Symfony\Component\HttpFoundation\Request;

#[Route('/notifier', name: 'app_notifier_')]
final class NotifierController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(
        MerchantRepository $merchantRepository
    ): Response {

        $merchants = $merchantRepository->findBy(['deleted' => false]);

        return $this->render('notifier/index.html.twig', [
            'merchants' => $merchants,
        ]);
    }

    #[Route('/envoyer', name: 'envoyer', methods: ['GET', 'POST'])]
    public function notifier(
        Request $request,
        MerchantRepository $merchantRepository,
        EmailRepository $emailRepository
    ): Response {
        $submittedData = null;

        if ($request->isMethod('POST')) {

            $data = $request->request->all();

            $merchantId = $data['merchant'] ?? null;
            $executionDate = $data['executionDate'] ?? null;
            $listName = $data['listName'] ?? null;
            $totalParticipants = $data['totalParticipants'] ?? null;
            $phoneNumber = $data['phoneNumber'] ?? [];
            $reasons = $data['reason'] ?? [];

            $failReasons = [];
            foreach ($phoneNumber as $i => $phoneNumber) {
                $failReasons[] = [
                    'phoneNumber' => $phoneNumber,
                    'reason' => $reasons[$i] ?? null,
                ];
            }
            
            
            // récupérer les emails du marchand sélectionné
            $emailsList = [];
            if ($merchantId) {
                $merchant = $merchantRepository->find($merchantId);
                if ($merchant) {
                    $emails = $emailRepository->findBy(['merchant' => $merchant, 'deleted' => false]);
                    foreach ($emails as $e) {
                        $emailsList[] = $e->getValue();
                    }
                }
            }
            
             $merchants = $merchantRepository->findBy(['deleted' => false]);
            // récupérer le shortcode du marchand sélectionné (si présent)
             $merchantcode = isset($merchant) && $merchant ? $merchant->getShortcode() : null;


            $submittedData = [
                'shortcode' => $merchantcode,
                'emails' => $emailsList,
                'listName' => $listName,
                'executionDate' => $executionDate,
                'totalParticipants' => $totalParticipants,
                'failReasons' => $failReasons,
                'emails'=>$emailsList,

            ];
        }

             dd($submittedData);

        return $this->render('notifier/index.html.twig', [
            'merchants' => $merchants,
            'submittedData' => $submittedData,
        ]);
    }
}
