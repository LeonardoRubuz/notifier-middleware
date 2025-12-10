<?php 

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/flexroll", name: "")]
class FlexrollEmailController extends AbstractController{

    #[Route("/notify", name: "flexroll_index", methods: ["GET", "POST"])]
    public function methodName(
        Request $request
    ): Response
    {
        return $this->render('flexroll/index.html.twig',[
            
        ]);
    }
}