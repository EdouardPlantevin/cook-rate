<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private UserRepository $userRepository,
    )
    {
    }

    public function generateUniqueFriendCode(): string
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $friendCode = '';

        // Générer un code de 6 caractères aléatoires
        for ($i = 0; $i < 6; $i++) {
            $friendCode .= $characters[rand(0, strlen($characters) - 1)];
        }

        // Vérifier si le code est unique, sinon régénérer
        if ($this->isFriendCodeUnique($friendCode)) {
            return $friendCode;
        }

        return $this->generateUniqueFriendCode();
    }

    private function isFriendCodeUnique(string $friendCode): bool
    {
        $userRepository = $this->manager->getRepository(User::class);
        $user = $userRepository->findOneBy(['friendCode' => $friendCode]);

        return $user === null;
    }

    public function addFriend(User $user, string $friendCode): array
    {
        $friend = $this->userRepository->findOneBy(['friendCode' => $friendCode]);

        if ($friend === $user) {
            return ['success' => false, 'message' => 'Vous n\'êtes pas seul <a class="btn" target="_blank" href="https://www.dofus.com/fr/mmorpg/decouvrir">Cliquez ici</a>', 'type' => 'info'];
        }

        if (!$friend) {
            return ['success' => false, 'message' => 'Ce code n\'existe pas.', 'type' => 'danger'];
        }

        if ($user->getFriends()->contains($friend)) {
            return ['success' => false, 'message' => 'Cet utilisateur est déjà votre ami(e).', 'type' => 'info'];
        }

        try {
            $user->addFriend($friend);
            $friend->addFriend($user);
            $this->manager->flush();
            return ['success' => true, 'message' => 'Nouvel ami ajouté(e) avec succès.', 'type' => 'success'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Une erreur est survenue lors de l\'ajout de l\'ami.', 'type' => 'danger'];
        }
    }
}


