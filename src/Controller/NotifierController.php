<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\MerchantRepository;

 #[Route('/notifier', name: 'app_notifier_')]
final class NotifierController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(
        MerchantRepository $merchantRepository): Response
    {
        $merchants = $merchantRepository->findBy(['deleted' => false]);

        return $this->render('notifier/index.html.twig', [
            'merchants' => $merchants,
        ]);
    }


}
