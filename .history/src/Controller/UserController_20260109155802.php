<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
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
        SessionInterface $session,
        TransportInterface $mailer,
        EntityManagerInterface $manager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['is_new' => true]);
        $form->handleRequest($request);
 
        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $manager->persist($user);
            $manager->flush();
       
            dd($manager->flush());
            // Email de confirmation avec le mot de passe
            $confirmMail = (new TemplatedEmail())
                ->from(new Address('payroll@flexpaie.com', 'FlexRoll'))
                ->to($user->getUsername())
                ->subject('Confirmation de votre email - FlexRoll')
                ->htmlTemplate('flexroll/confirm_email.html.twig')
                ->context([
                    'nom' => $user->getNom(),
                    'prenom' => $user->getPrenom(),
                    'username' => $user->getUsername(),
                    'password' => $plainPassword, // Mot de passe inclus dans l'email
                ]);

            $mailer->send($confirmMail);

            $this->addFlash('success', 'Un email de confirmation avec vos identifiants a été envoyé à ' . $user->getUsername());

            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/confirm-email/{token}', name: 'confirm_email')]
    public function confirmEmail(
        string $token,
        SessionInterface $session,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        // Récupération des données depuis la session
        $sessionKey = 'pending_user_' . $token;
        $userData = $session->get($sessionKey);

        if (!$userData) {
            $this->addFlash('error', 'Lien de confirmation invalide ou expiré.');
            return $this->redirectToRoute('app_login');
        }

        // Création de User
        $user = new User();
        $user->setNom($userData['nom']);
        $user->setPrenom($userData['prenom']);
        $user->setUsername($userData['username']);
        $user->setRoles($userData['roles']);
        $user->setIsVerified(true); // Email confirmé
        $user->setCreatedAt($userData['created_at']);

        // Hash du mot de passe généré précédemment
        $hashedPassword = $passwordHasher->hashPassword($user, $userData['password']);
        $user->setPassword($hashedPassword);

        // ENREGISTREMENT EN BASE DE DONNÉES
        $em->persist($user);
        $em->flush();

        // Suppression des données temporaires de la session
        $session->remove($sessionKey);

        $this->addFlash('success', 'Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.');

        return $this->redirectToRoute('app_login');
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

    #[Route('/{id}/reset-password', name: 'reset_password', methods: ['GET'])]
    public function resetPassword(
        User $user,
        SessionInterface $session,
        TransportInterface $mailer
    ): Response {
        // Génération du token unique
        $token = bin2hex(random_bytes(32));

        // Génération du nouveau mot de passe
        $newPassword = $this->generatePassword();

        // Stockage temporaire en session
        $session->set('reset_password_' . $token, [
            'user_id' => $user->getId(),
            'password' => $newPassword,
            'created_at' => new \DateTime(),
        ]);

        // Envoi de l'email avec le nouveau mot de passe
        $resetMail = (new TemplatedEmail())
            ->from(new Address('payroll@flexpaie.com', 'FlexRoll'))
            ->to($user->getUsername())
            ->subject('Réinitialisation de votre mot de passe - FlexRoll')
            ->htmlTemplate('flexroll/reset_password.html.twig')
            ->context([
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'token' => $token,
                'password' => $newPassword,
            ]);

        $mailer->send($resetMail);

        $this->addFlash('success', '✅ Un email de réinitialisation a été envoyé à ' . $user->getUsername());

        return $this->redirectToRoute('app_user_index');
    }

    #[Route('/confirm-reset/{token}', name: 'confirm_reset_password')]
    public function confirmResetPassword(
        string $token,
        SessionInterface $session,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository
    ): Response {
        // Récupération des données depuis la session
        $sessionKey = 'reset_password_' . $token;
        $resetData = $session->get($sessionKey);

        if (!$resetData) {
            $this->addFlash('error', 'Lien de réinitialisation invalide ou expiré.');
            return $this->redirectToRoute('app_login');
        }

        // Récupération de l'utilisateur
        $user = $userRepository->find($resetData['user_id']);

        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_login');
        }

        // Hash et mise à jour du mot de passe
        $hashedPassword = $passwordHasher->hashPassword($user, $resetData['password']);
        $user->setPassword($hashedPassword);

        // Enregistrement en BD
        $em->flush();

        // Suppression des données temporaires
        $session->remove($sessionKey);

        $this->addFlash('success', '✅ Votre mot de passe a été réinitialisé avec succès ! Vous pouvez maintenant vous connecter.');

        return $this->redirectToRoute('app_login');
    }

    /**
     * Génère un mot de passe aléatoire sécurisé
     */
    private function generatePassword(int $length = 12): string
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*';

        $allChars = $uppercase . $lowercase . $numbers . $special;

        // Garantir au moins un caractère de chaque type
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];

        // Compléter avec des caractères aléatoires
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        // Mélanger le mot de passe
        return str_shuffle($password);
    }
}
