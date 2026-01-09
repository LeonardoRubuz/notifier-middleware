<?php

namespace App\Controller;

use App\Entity\Email;
use App\Form\EmailType;
use App\Repository\EmailRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/email', name: 'app_email_')]
final class EmailController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request, EmailRepository $emailRepository): Response
    {
        $filter = $request->query->get('filter', 'all');

        $emails = match ($filter) {
            'enabled' => $emailRepository->findBy(['enabled' => true, 'deleted' => false], ['createdAt' => 'DESC']),
            'disabled' => $emailRepository->findBy(['enabled' => false, 'deleted' => false], ['createdAt' => 'DESC']),
            'all' => $emailRepository->findBy(['deleted' => false], ['enabled' => 'DESC', 'value' => 'ASC']),
            default => $emailRepository->findBy(['deleted' => false], ['enabled' => 'DESC', 'value' => 'ASC']),
        };

        return $this->render('email/index.html.twig', [
            'emails' => $emails,
            'currentFilter' => $filter,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $email = new Email();
        $form = $this->createForm(EmailType::class, $email);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($email);
            $entityManager->flush();

            //Emails
            
             $data = json_decode($request->getContent(), true);
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

            return $this->redirectToRoute('app_email_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('email/new_with_sidebar.html.twig', [
            'email' => $email,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Email $email): Response
    {
        return $this->render('email/show.html.twig', [
            'email' => $email,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Email $email, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EmailType::class, $email);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_email_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('email/edit.html.twig', [
            'email' => $email,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Email $email, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $email->getId(), $request->getPayload()->getString('_token'))) {
            $email->setDeleted(true);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_email_index', [], Response::HTTP_SEE_OTHER);
    }
}
