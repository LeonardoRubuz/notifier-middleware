<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;



#[Route('/user', name: 'app_user_')]
#[IsGranted('ROLE_ADMIN', message: 'Cette option est accessible uniquement par un administrateur.')]
final class UserController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $filter = $request->query->get('filter', 'all');

        $users = match ($filter) {
            'admin' => $userRepository->findByRole('ROLE_ADMIN'),
            'user' => $userRepository->findByRole('ROLE_USER'),
            'all' => $userRepository->findBy([], ['createdAt' => 'DESC']),
            default => $userRepository->findBy([], ['createdAt' => 'DESC']),
        };

        return $this->render('user/index.html.twig', [
            'users' => $users,
            'currentFilter' => $filter,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
public function new(
    Request $request,
    EntityManagerInterface $entityManager,
    TransportInterface $mailer
): Response
{
    $user = new User();
    $form = $this->createForm(UserType::class, $user, ['is_new' => true]);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        // 1️⃣ L'utilisateur n'est PAS encore vérifié
        $user->setIsVerified(false);

        // 2️⃣ Génération du token email
        $token = bin2hex(random_bytes(32));
        $user->setEmailConfirmationToken($token);

        // 3️⃣ Sauvegarde sans mot de passe
        $entityManager->persist($user);
        $entityManager->flush();

        // 4️⃣ Email de confirmation
        $confirmMail = (new TemplatedEmail())
            ->from(new Address('payroll@flexpaie.com', 'FlexRoll'))
            ->to($user->getUsername())
            ->subject('Confirmation de votre email')
            ->htmlTemplate('flexroll/confirm_email.html.twig')
            ->context([
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'token' => $token,
            ]);

        $mailer->send($confirmMail);

        $this->addFlash('success', 'Un email de confirmation a été envoyé.');

        return $this->redirectToRoute('app_user_index');
    }

    return $this->render('user/new.html.twig', [
        'user' => $user,
        'form' => $form,
    ]);
}









    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createForm(UserType::class, $user, ['is_new' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash du mot de passe seulement si un nouveau mot de passe est fourni
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }

            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur modifié avec succès.');

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
